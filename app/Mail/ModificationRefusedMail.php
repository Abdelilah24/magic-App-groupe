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

class ModificationRefusedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Reservation $reservation,
        public readonly string $reason
    ) {}

    public function envelope(): Envelope
    {
        $subject = "Modification non retenue #{$this->reservation->reference}";

        $tpl = EmailTemplate::getByKey('modification_refused');
        if ($tpl) {
            $subject = $tpl->renderSubject(['reference' => $this->reservation->reference]);
        }

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $tpl = EmailTemplate::getByKey('modification_refused');

        $data = [
            'contact_name'  => $this->reservation->contact_name,
            'reference'     => $this->reservation->reference,
            'reason'        => $this->reason,
            'contact_email' => config('magic.contact_email', 'reservations@magichotels.ma'),
        ];

        if ($tpl) {
            return new Content(
                view: 'emails.db-template',
                with: ['renderedBody' => $tpl->renderBody($data)],
            );
        }

        return new Content(view: 'emails.client.modification-refused');
    }
}
