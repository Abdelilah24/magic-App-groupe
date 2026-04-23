<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ClientReservationReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Reservation $reservation) {}

    public function envelope(): Envelope
    {
        $subject = "Demande reçue #{$this->reservation->reference} — Magic Hotels";

        $tpl = EmailTemplate::getByKey('client_reservation_received');
        if ($tpl) {
            $subject = $tpl->renderSubject(['reference' => $this->reservation->reference]);
        }

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $tpl = EmailTemplate::getByKey('client_reservation_received');

        $reservation = $this->reservation;
        $reservation->loadMissing(['hotel', 'rooms.roomType', 'supplements.supplement']);

        $sejours     = $reservation->sejours;
        $taxeRate    = (float) ($reservation->hotel?->taxe_sejour ?? 19.80);
        $multiSejour = $sejours->count() > 1;

        // Séjours label
        $totalNights  = $sejours->sum('nights');
        $sejoursLabel = $multiSejour
            ? $sejours->count() . ' séjours · ' . $totalNights . ' nuits au total'
            : ($totalNights . ' nuit' . ($totalNights > 1 ? 's' : ''));

        $data = [
            'contact_name'     => $reservation->contact_name,
            'reference'        => $reservation->reference,
            'hotel_name'       => $reservation->hotel?->name ?? '—',
            'total_persons'    => $reservation->total_persons,
            'sejours_label'    => $sejoursLabel,
            'sejours_detail'   => $this->buildSejoursDetailHtml($sejours, $taxeRate, $multiSejour),
            'financial_recap'  => $this->buildFinancialRecapHtml($reservation, $sejours, $taxeRate),
            'special_requests' => $this->buildSpecialRequestsHtml($reservation->special_requests),
            'contact_email'    => config('magic.contact_email', 'reservations@magichotels.ma'),
        ];

        if ($tpl) {
            return new Content(
                view: 'emails.db-template',
                with: ['renderedBody' => $tpl->renderBody($data)],
            );
        }

        return new Content(view: 'emails.client.reservation-received');
    }

    // ─── Builders ─────────────────────────────────────────────────────────────

    private function buildSejoursDetailHtml(Collection $sejours, float $taxeRate, bool $multiSejour): string
    {
        $html = '';

        foreach ($sejours as $i => $sejour) {
            $nights       = (int) $sejour['nights'];
            $rooms        = $sejour['rooms'];
            $checkIn      = $sejour['check_in']?->format('d/m/Y') ?? '—';
            $checkOut     = $sejour['check_out']?->format('d/m/Y') ?? '—';
            $sejourAdults = $rooms->sum(fn ($r) => (int) ($r->adults ?? 0) * max(1, (int) ($r->quantity ?? 1)));
            $sejourTaxe   = round($sejourAdults * $nights * $taxeRate);

            $label = $multiSejour
                ? 'Séjour ' . ($i + 1) . ' — ' . $checkIn . ' → ' . $checkOut . ' (' . $nights . ' nuit' . ($nights > 1 ? 's' : '') . ')'
                : $checkIn . ' → ' . $checkOut . ' (' . $nights . ' nuit' . ($nights > 1 ? 's' : '') . ')';

            $html .= '<h3 style="font-size:14px; color:#92400e; margin:20px 0 8px;">' . $label . '</h3>';
            $html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-size:13px; margin-bottom:8px;">';
            $html .= '<thead><tr style="background:#fef3c7; color:#92400e;">';
            $html .= '<th style="text-align:left; padding:6px 8px; border:1px solid #fde68a;">Chambre / Occupation</th>';
            $html .= '<th style="text-align:center; padding:6px 8px; border:1px solid #fde68a;">Personnes/ch.</th>';
            $html .= '<th style="text-align:right; padding:6px 8px; border:1px solid #fde68a;">Montant</th>';
            $html .= '</thead><tbody>';

            foreach ($rooms as $room) {
                $typeName  = $room->occupancy_config_label ?? $room->roomType?->name ?? 'Chambre';
                $qty       = max(1, (int) ($room->quantity ?? 1));
                $adults    = (int) ($room->adults ?? 0);
                $children  = (int) ($room->children ?? 0);
                $babies    = (int) ($room->babies ?? 0);
                $amount    = $room->total_price ? number_format((float) $room->total_price, 0, ',', ' ') . ' MAD' : '—';

                $personnes = $adults . ' ad.';
                if ($children) $personnes .= ' · ' . $children . ' enf.';
                if ($babies)   $personnes .= ' · ' . $babies . ' bébé';

                $html .= '<tr>';
                $html .= '<td style="padding:6px 8px; border:1px solid #f3f4f6; color:#374151;">';
                $html .= '<strong>' . $qty . '</strong> × ' . $typeName;
                $html .= ' <span style="color:#9ca3af; font-size:11px;">× ' . $nights . ' nuit' . ($nights > 1 ? 's' : '') . '</span>';
                $html .= '</td>';
                $html .= '<td style="padding:6px 8px; border:1px solid #f3f4f6; text-align:center; color:#6b7280; font-size:12px;">' . $personnes . '</td>';
                $html .= '<td style="padding:6px 8px; border:1px solid #f3f4f6; text-align:right; font-weight:600; color:#374151;">' . $amount . '</td>';
                $html .= '</tr>';
            }

            if ($sejourTaxe > 0) {
                $html .= '<tr style="background:#eff6ff;">';
                $html .= '<td colspan="2" style="padding:5px 8px; border:1px solid #dbeafe; color:#1d4ed8; font-size:12px;">';
                $html .= 'Taxe de séjour (' . $sejourAdults . ' adulte(s) × ' . $nights . ' nuit(s) × ' . number_format($taxeRate, 2, ',', ' ') . ' DHS)';
                $html .= '</td>';
                $html .= '<td style="padding:5px 8px; border:1px solid #dbeafe; text-align:right; font-weight:600; color:#1d4ed8;">';
                $html .= number_format($sejourTaxe, 0, ',', ' ') . ' MAD';
                $html .= '</td></tr>';
            }

            if ($multiSejour) {
                $sejourRoomsTotal = $rooms->sum('total_price');
                $html .= '<tr style="background:#fef9ee;">';
                $html .= '<td colspan="2" style="padding:6px 8px; border:1px solid #fde68a; font-weight:700; color:#92400e;">Sous-total séjour ' . ($i + 1) . '</td>';
                $html .= '<td style="padding:6px 8px; border:1px solid #fde68a; text-align:right; font-weight:700; color:#92400e;">';
                $html .= number_format($sejourRoomsTotal + $sejourTaxe, 0, ',', ' ') . ' MAD';
                $html .= '</td></tr>';
            }

            $html .= '</tbody></table>';
        }

        return $html;
    }

    private function buildFinancialRecapHtml(Reservation $reservation, Collection $sejours, float $taxeRate): string
    {
        if (! $reservation->total_price) {
            return '';
        }

        $roomsTotal = $reservation->rooms->sum('total_price');

        $taxeTotal = 0;
        foreach ($sejours as $sejour) {
            $nights      = (int) $sejour['nights'];
            $adults      = $sejour['rooms']->sum(fn ($r) => (int) ($r->adults ?? 0) * max(1, (int) ($r->quantity ?? 1)));
            $taxeTotal  += round($adults * $nights * $taxeRate);
        }

        $html  = '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-size:13px; margin-top:12px;">';

        $html .= '<tr><td style="padding:5px 8px; color:#374151;">Hébergement (chambres)</td>';
        $html .= '<td style="padding:5px 8px; text-align:right; color:#374151;">' . number_format((float) $roomsTotal, 0, ',', ' ') . ' MAD</td></tr>';

        if ($taxeTotal > 0) {
            $html .= '<tr><td style="padding:5px 8px; color:#1d4ed8;">Taxe de séjour</td>';
            $html .= '<td style="padding:5px 8px; text-align:right; color:#1d4ed8;">' . number_format($taxeTotal, 0, ',', ' ') . ' MAD</td></tr>';
        }

        if ($reservation->group_discount_amount > 0) {
            $html .= '<tr><td style="padding:5px 8px; color:#059669;">Remise groupe (1 pers. gratuite / 20 payants)</td>';
            $html .= '<td style="padding:5px 8px; text-align:right; color:#059669;">− ' . number_format((float) $reservation->group_discount_amount, 0, ',', ' ') . ' MAD</td></tr>';
        }

        foreach ($reservation->supplements as $rs) {
            $color = $rs->is_mandatory ? '#d97706' : '#7c3aed';
            $type  = $rs->is_mandatory ? 'obligatoire' : 'optionnel';
            $html .= '<tr><td style="padding:5px 8px; color:' . $color . ';">' . $rs->supplement->title . ' (' . $type . ')</td>';
            $html .= '<td style="padding:5px 8px; text-align:right; color:' . $color . ';">' . number_format((float) $rs->total_price, 0, ',', ' ') . ' MAD</td></tr>';
        }

        $html .= '<tr style="background:#fef3c7; border-top:2px solid #fbbf24;">';
        $html .= '<td style="padding:8px; font-weight:700; font-size:14px; color:#92400e;">TOTAL ESTIMÉ</td>';
        $html .= '<td style="padding:8px; text-align:right; font-weight:700; font-size:14px; color:#d97706;">';
        $html .= number_format((float) $reservation->total_price, 2, ',', ' ') . ' MAD';
        $html .= '</td></tr></table>';
        $html .= '<p style="font-size:11px; color:#9ca3af; margin-top:4px;">* Prix indicatif, confirmé après validation par notre équipe.</p>';

        return $html;
    }

    private function buildSpecialRequestsHtml(?string $requests): string
    {
        if (! $requests) {
            return '';
        }

        return '<div style="margin-top:16px; padding:10px 12px; background:#f9fafb; border-radius:6px; border-left:3px solid #d1d5db;">'
            . '<p style="font-size:12px; color:#6b7280; margin:0 0 4px;">Demandes spéciales :</p>'
            . '<p style="font-size:13px; color:#374151; margin:0;">' . e($requests) . '</p>'
            . '</div>';
    }
}
