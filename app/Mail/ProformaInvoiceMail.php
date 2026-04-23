<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\PdfTemplate;
use App\Models\Reservation;
use App\Services\ProformaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProformaInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Reservation $reservation,
    ) {}

    public function envelope(): Envelope
    {
        $tpl  = EmailTemplate::getByKey('proforma_invoice');
        $data = $this->buildData();

        return new Envelope(
            subject: $tpl
                ? $tpl->renderSubject($data)
                : 'Facture Proforma – ' . $this->reservation->reference . ' – ' . $this->reservation->hotel->name,
        );
    }

    public function content(): Content
    {
        $tpl  = EmailTemplate::getByKey('proforma_invoice');
        $data = $this->buildData();

        if ($tpl) {
            return new Content(
                view: 'emails.db-template',
                with: ['renderedBody' => $tpl->renderBody($data)],
            );
        }

        // Fallback : Blade view classique
        return new Content(
            view: 'emails.proforma',
            with: [
                'reservation' => $this->reservation,
                'hotel'       => $this->reservation->hotel,
            ],
        );
    }

    private function buildData(): array
    {
        $reservation = $this->reservation;
        $hotel       = $reservation->hotel;
        $nights      = (int) $reservation->nights;

        return [
            'contact_name'      => $reservation->contact_name,
            'reference'         => $reservation->reference,
            'hotel_name'        => $hotel->name,
            'check_in'          => $reservation->check_in
                                        ? $reservation->check_in->format('d/m/Y') : '',
            'check_out'         => $reservation->check_out
                                        ? $reservation->check_out->format('d/m/Y') : '',
            'nights'            => $nights . ' nuit' . ($nights > 1 ? 's' : ''),
            'total'             => number_format((float) $reservation->total_price, 2, ',', ' ') . ' MAD',
            'payment_deadline'  => $reservation->payment_deadline
                                        ? '<p style="color:#dc2626;font-size:13px;">&#9888;&#65039; <strong>Date limite de paiement&nbsp;:</strong> ' . $reservation->payment_deadline->format('d/m/Y') . '</p>'
                                        : '',
            'hotel_phone'       => $hotel->phone ?? '',
            'hotel_email'       => $hotel->email ?? '',
            'hotel_address'     => trim(implode(', ', array_filter([
                                        $hotel->address ?? '',
                                        $hotel->city    ?? '',
                                        $hotel->country ?? '',
                                    ]))),
        ];
    }

    public function attachments(): array
    {
        $reservation = $this->reservation;

        $tpl = PdfTemplate::getByKey('proforma');

        if ($tpl) {
            $data = app(ProformaService::class)->buildData($reservation);
            $html = $tpl->renderBody($data);
            $pdf  = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        } else {
            // Fallback : vue Blade classique
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
}
