<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\PdfTemplate;
use App\Models\Reservation;
use App\Services\ProformaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationQuoteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Reservation $reservation,
    ) {}

    public function envelope(): Envelope
    {
        $subject = 'Réponse à votre demande de réservation – ' . $this->reservation->hotel->name;

        $tpl = EmailTemplate::getByKey('reservation_quote');
        if ($tpl) {
            $subject = $tpl->renderSubject(['hotel_nom' => $this->reservation->hotel->name]);
        }

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $tpl = EmailTemplate::getByKey('reservation_quote');

        $data = $this->buildTemplateData();

        if ($tpl) {
            return new Content(
                view: 'emails.db-template',
                with: ['renderedBody' => $tpl->renderBody($data)],
            );
        }

        // Fallback Blade
        return new Content(
            view: 'emails.reservation.quote',
            with: $data,
        );
    }

    // ─── Pièce jointe : PDF proforma ─────────────────────────────────────────

    public function attachments(): array
    {
        $reservation = $this->reservation;

        $tpl = PdfTemplate::getByKey('proforma');

        if ($tpl) {
            $data = app(ProformaService::class)->buildData($reservation);
            $html = $tpl->renderBody($data);
            $pdf  = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        } else {
            $reservation->loadMissing([
                'hotel', 'rooms.roomType', 'rooms.occupancyConfig',
                'supplements.supplement', 'paymentSchedules', 'agency',
            ]);
            $pdf = Pdf::loadView('pdf.proforma', compact('reservation'))
                ->setPaper('a4', 'portrait');
        }

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                'proforma-' . $reservation->reference . '.pdf'
            )->withMime('application/pdf'),
        ];
    }

    // ─── Builder de données ───────────────────────────────────────────────────

    private function buildTemplateData(): array
    {
        $reservation = $this->reservation;
        $reservation->loadMissing(['hotel', 'rooms.roomType']);

        $hotel   = $reservation->hotel;
        $rooms   = $reservation->rooms;
        $sejours = $reservation->sejours;

        // ── Civilité (best effort sur contact_name) ──────────────────────────
        $civList  = ['M.', 'Mme', 'M.', 'Mme.', 'Dr.', 'Pr.'];
        $civility = 'M./Mme';
        foreach ($civList as $c) {
            if (str_starts_with(trim((string) $reservation->contact_name), $c)) {
                $civility = $c;
                break;
            }
        }
        $clientNom = trim(preg_replace('/^(M\.|Mme\.?|Dr\.|Pr\.)\s*/i', '', (string) $reservation->contact_name));

        // ── Dates ─────────────────────────────────────────────────────────────
        $checkIn  = $reservation->check_in;
        $checkOut = $reservation->check_out;
        $nights   = (int) $reservation->nights;

        if ($sejours->count() > 1) {
            // multi-séjour : du premier check_in au dernier check_out
            $allCheckIns  = $sejours->pluck('check_in');
            $allCheckOuts = $sejours->pluck('check_out');
            $checkIn  = $allCheckIns->min();
            $checkOut = $allCheckOuts->max();
        }

        $locale  = app()->getLocale();
        $months  = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];
        $inFmt  = $checkIn->format('d') . ' ';
        $outFmt = $checkOut->format('d') . ' ' . ($months[$checkOut->month] ?? $checkOut->format('F')) . ' ' . $checkOut->format('Y');
        // même mois → "Du 24 au 26 Avril 2026"
        if ($checkIn->month === $checkOut->month && $checkIn->year === $checkOut->year) {
            $inFmt .= 'au ';
        } else {
            $inFmt .= ($months[$checkIn->month] ?? $checkIn->format('F')) . ' ' . $checkIn->format('Y') . ' au ';
        }
        $dates = 'Du ' . $inFmt . $outFmt . ', soit ' . $nights . ' nuit' . ($nights > 1 ? 's' : '');

        // ── Nombre de chambres (résumé) ───────────────────────────────────────
        $chambresParts = [];
        foreach ($rooms->groupBy('room_type_id') as $rtId => $group) {
            $totalQty  = $group->sum(fn($r) => max(1, $r->quantity ?? 1));
            $typeName  = $group->first()->roomType?->name ?? 'chambre';
            $chambresParts[] = $totalQty . ' × ' . $typeName;
        }
        $nombreChambres = implode(', ', $chambresParts);

        // ── Nombre de personnes ───────────────────────────────────────────────
        $totalAdults   = $rooms->sum(fn($r) => ($r->adults   ?? 0) * max(1, $r->quantity ?? 1));
        $totalChildren = $rooms->sum(fn($r) => ($r->children ?? 0) * max(1, $r->quantity ?? 1));
        $totalBabies   = $rooms->sum(fn($r) => ($r->babies   ?? 0) * max(1, $r->quantity ?? 1));
        $personsParts  = [];
        if ($totalAdults)   $personsParts[] = $totalAdults   . ' adulte'  . ($totalAdults   > 1 ? 's' : '');
        if ($totalChildren) $personsParts[] = $totalChildren . ' enfant'  . ($totalChildren > 1 ? 's' : '');
        if ($totalBabies)   $personsParts[] = $totalBabies   . ' bébé'    . ($totalBabies   > 1 ? 's' : '');
        $nombrePersonnes = implode(', ', $personsParts) ?: ((int) $reservation->total_persons . ' personne(s)');

        // ── Régime ────────────────────────────────────────────────────────────
        $regime = $hotel->meal_plan_label ?? 'All Inclusive';

        // ── Détail chambres HTML ──────────────────────────────────────────────
        $chambresDetail = $this->buildChambresDetailHtml($rooms, $nights, $sejours);

        // ── Taxe de séjour ────────────────────────────────────────────────────
        $taxeRate    = (float) ($hotel->taxe_sejour ?? 0);
        $taxeSejour  = number_format($taxeRate, 2, ',', ' ');

        // ── Date limite paiement (50% — configurable via magic.quote_deposit_days) ─
        $depositDays = (int) config('magic.quote_deposit_days', 7);
        $dateLimite  = now()->addDays($depositDays)->format('d/m/Y');

        // ── Commercial (depuis config ou Admin user) ──────────────────────────
        $commercialNom   = config('magic.commercial_nom',   'L\'équipe commerciale');
        $commercialTitre = config('magic.commercial_titre', 'Direction des Ventes & Marketing');
        $commercialTel   = config('magic.commercial_tel',   $hotel->phone ?? '');
        $siteWeb         = config('magic.site_web',         'www.magichotels.ma');

        // ── Échéancier ────────────────────────────────────────────────────────
        $reservation->loadMissing('paymentSchedules');
        $schedules      = $reservation->paymentSchedules->sortBy('installment_number');
        $scheduleDetail = $schedules->isNotEmpty()
            ? $this->buildScheduleDetailHtml($schedules, $reservation)
            : '';

        // Date limite du premier acompte : première échéance si elle existe, sinon J+deposit_days
        if ($schedules->isNotEmpty()) {
            $dateLimite = $schedules->first()->due_date->format('d/m/Y')
                . ($schedules->first()->due_time
                    ? ' avant ' . \Carbon\Carbon::parse($schedules->first()->due_time)->format('H:i')
                    : '');
        }

        return [
            'client_civilite'      => $civility,
            'client_nom'           => $clientNom ?: $reservation->contact_name,
            'hotel_nom'            => $hotel->name,
            'dates'                => $dates,
            'nombre_chambres'      => $nombreChambres,
            'nombre_personnes'     => $nombrePersonnes,
            'regime'               => $regime,
            'chambres_detail'      => $chambresDetail,
            'taxe_sejour'          => $taxeSejour,
            'date_limite_paiement' => $dateLimite,
            'schedule_detail'      => $scheduleDetail,
            'commercial_nom'       => $commercialNom,
            'commercial_titre'     => $commercialTitre,
            'commercial_tel'       => $commercialTel,
            'site_web'             => $siteWeb,
        ];
    }

    /**
     * Génère le bloc HTML de l'échéancier pour le template devis.
     */
    private function buildScheduleDetailHtml($schedules, Reservation $reservation): string
    {
        $totalPrice = round(
            ($reservation->total_price ?? 0) + ($reservation->taxe_total ?? 0)
        );

        $rows = '';
        foreach ($schedules as $sch) {
            $label   = $sch->label ?: ('Échéance #' . $sch->installment_number);
            $dateStr = $sch->due_date->format('d/m/Y');
            if ($sch->due_time) {
                $dateStr .= ' avant ' . \Carbon\Carbon::parse($sch->due_time)->format('H:i');
            }
            $amountFmt = number_format((float) $sch->amount, 2, ',', ' ') . ' MAD';
            $pctLabel  = ($totalPrice > 0)
                ? ' <span style="color:#9ca3af; font-size:12px;">(' . round($sch->amount / $totalPrice * 100) . '%)</span>'
                : '';

            $rows .= '
  <tr style="border-bottom:1px solid #fde68a;">
    <td style="padding:10px 16px; color:#78350f; font-weight:600;">' . e($label) . '</td>
    <td style="padding:10px 16px; color:#374151; font-size:14px;">📅 ' . $dateStr . '</td>
    <td style="padding:10px 16px; font-weight:700; color:#b45309; white-space:nowrap;">' . $amountFmt . $pctLabel . '</td>
  </tr>';
        }

        return '
<table width="100%" cellpadding="0" cellspacing="0"
  style="border-collapse:collapse; background:#fffbeb; border:1px solid #fde68a; border-radius:8px; font-size:14px; margin:0 0 16px; overflow:hidden;">
  <thead>
    <tr style="background:#fef3c7;">
      <th style="padding:8px 16px; text-align:left; color:#92400e; font-size:12px; text-transform:uppercase; letter-spacing:0.05em;">Libellé</th>
      <th style="padding:8px 16px; text-align:left; color:#92400e; font-size:12px; text-transform:uppercase; letter-spacing:0.05em;">Date limite</th>
      <th style="padding:8px 16px; text-align:left; color:#92400e; font-size:12px; text-transform:uppercase; letter-spacing:0.05em;">Montant</th>
    </tr>
  </thead>
  <tbody>' . $rows . '
  </tbody>
</table>';
    }

    /**
     * Génère le bloc HTML du détail des chambres pour le template devis.
     */
    private function buildChambresDetailHtml($rooms, int $nights, $sejours): string
    {
        if ($rooms->isEmpty()) return '';

        $lines = [];
        foreach ($rooms as $room) {
            $qty      = max(1, (int) ($room->quantity ?? 1));
            $typeName = $room->roomType?->name ?? 'Chambre';
            $label    = $room->occupancy_config_label ?? $room->occupancyConfig?->label ?? null;

            $rNights = ($room->check_in && $room->check_out)
                ? max(1, (int) $room->check_in->diffInDays($room->check_out))
                : $nights;

            $pricePerNight = $room->price_per_night ? round((float) $room->price_per_night) : null;

            $desc = $label ? "{$typeName} ({$label})" : $typeName;

            if ($pricePerNight !== null) {
                $lines[] = '<li style="margin-bottom:6px;">'
                    . $qty . ' × <strong>' . e($desc) . '</strong>'
                    . ' — <strong>' . number_format($pricePerNight, 2, ',', ' ') . ' MAD/nuit</strong>'
                    . ($room->roomType?->hotel?->meal_plan_label ? ', ' . e($room->roomType->hotel->meal_plan_label) : '')
                    . ', hors taxes de séjour.'
                    . '</li>';
            } else {
                $lines[] = '<li style="margin-bottom:6px;">'
                    . $qty . ' × <strong>' . e($desc) . '</strong>'
                    . '</li>';
            }
        }

        return '<ul style="margin:0 0 12px; padding-left:20px; font-size:14px; color:#374151;">'
            . implode("\n", $lines)
            . '</ul>';
    }
}
