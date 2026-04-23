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

class AdminModificationRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Reservation $reservation) {}

    public function envelope(): Envelope
    {
        $tpl  = EmailTemplate::getByKey('admin_modification_request');
        $data = $this->buildData();

        return new Envelope(
            subject: $tpl ? $tpl->renderSubject($data)
                          : "🔄 Modification demandée #{$this->reservation->reference}",
        );
    }

    public function content(): Content
    {
        $tpl  = EmailTemplate::getByKey('admin_modification_request');
        $data = $this->buildData();

        if ($tpl) {
            return new Content(
                view: 'emails.db-template',
                with: ['renderedBody' => $tpl->renderBody($data)],
            );
        }

        return new Content(view: 'emails.admin.modification-request');
    }

    private function buildData(): array
    {
        return [
            'reference'   => $this->reservation->reference,
            'agency_name' => $this->reservation->agency_name,
            'admin_url'   => route('admin.reservations.show', $this->reservation),
        ];
    }
}
