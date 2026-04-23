<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\SecureLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly SecureLink $link) {}

    public function envelope(): Envelope
    {
        $subject = '✦ Votre accès portail réservation — Magic Hotels';

        $tpl = EmailTemplate::getByKey('invitation');
        if ($tpl) {
            $subject = $tpl->renderSubject(['agency_name' => $this->link->agency_name]);
        }

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $tpl = EmailTemplate::getByKey('invitation');

        $data = [
            'agency_name'   => $this->link->agency_name,
            'hotel_name'    => $this->link->hotel?->name ?? '—',
            'portal_url'    => $this->link->url,
            'expires_at'    => $this->link->expires_at?->format('d/m/Y') ?? '—',
            'contact_email' => config('magic.contact_email', 'reservations@magichotels.ma'),
        ];

        if ($tpl) {
            return new Content(
                view: 'emails.db-template',
                with: ['renderedBody' => $tpl->renderBody($data)],
            );
        }

        return new Content(view: 'emails.invitation');
    }
}
