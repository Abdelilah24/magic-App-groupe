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

class AdminNewReservationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Reservation $reservation) {}

    public function envelope(): Envelope
    {
        $tpl  = EmailTemplate::getByKey('admin_new_reservation');
        $data = $this->buildData();

        return new Envelope(
            subject: $tpl ? $tpl->renderSubject($data)
                          : "🔔 Nouvelle demande #{$this->reservation->reference} — {$this->reservation->agency_name}",
        );
    }

    public function content(): Content
    {
        $tpl  = EmailTemplate::getByKey('admin_new_reservation');
        $data = $this->buildData();

        if ($tpl) {
            return new Content(
                view: 'emails.db-template',
                with: ['renderedBody' => $tpl->renderBody($data)],
            );
        }

        return new Content(view: 'emails.admin.new-reservation');
    }

    private function buildData(): array
    {
        return [
            'reference'     => $this->reservation->reference,
            'agency_name'   => $this->reservation->agency_name,
            'contact_name'  => $this->reservation->contact_name,
            'contact_email' => $this->reservation->email,
            'hotel_name'    => $this->reservation->hotel?->name ?? '—',
            'check_in'      => $this->reservation->check_in?->format('d/m/Y') ?? '—',
            'check_out'     => $this->reservation->check_out?->format('d/m/Y') ?? '—',
            'total_persons' => $this->reservation->total_persons ?? '—',
            'admin_url'     => route('admin.reservations.show', $this->reservation),
        ];
    }
}
