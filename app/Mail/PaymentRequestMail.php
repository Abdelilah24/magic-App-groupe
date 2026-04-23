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

class PaymentRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Reservation $reservation) {}

    public function envelope(): Envelope
    {
        $subject = "✅ Réservation acceptée — Lien de paiement #{$this->reservation->reference}";

        $tpl = EmailTemplate::getByKey('payment_request');
        if ($tpl) {
            $subject = $tpl->renderSubject(['reference' => $this->reservation->reference]);
        }

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $tpl = EmailTemplate::getByKey('payment_request');

        $reservation = $this->reservation;
        $reservation->loadMissing('hotel');

        $data = [
            'contact_name'  => $reservation->contact_name,
            'reference'     => $reservation->reference,
            'hotel_name'    => $reservation->hotel?->name ?? '—',
            'check_in'      => $reservation->check_in?->format('d/m/Y') ?? '—',
            'check_out'     => $reservation->check_out?->format('d/m/Y') ?? '—',
            'total'         => number_format((float) $reservation->total_price, 2, ',', ' '),
            'payment_url'   => $reservation->hasValidPaymentToken()
                ? route('client.payment', $reservation->payment_token)
                : '#',
            'expires_at'    => $reservation->payment_token_expires_at?->format('d/m/Y') ?? '—',
            'contact_email' => config('magic.contact_email', 'reservations@magichotels.ma'),
        ];

        if ($tpl) {
            return new Content(
                view: 'emails.db-template',
                with: ['renderedBody' => $tpl->renderBody($data)],
            );
        }

        return new Content(view: 'emails.client.payment-request');
    }
}
