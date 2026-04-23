<?php

namespace App\Mail;

use App\Models\Agency;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgencyRegistrationReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Agency $agency) {}

    public function envelope(): Envelope
    {
        $subject = 'Demande de partenariat reçue — Magic Hotels';

        $tpl = EmailTemplate::getByKey('agency_registration_received');
        if ($tpl) {
            $subject = $tpl->renderSubject(['agency_name' => $this->agency->name]);
        }

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $tpl = EmailTemplate::getByKey('agency_registration_received');

        $data = [
            'contact_name'  => $this->agency->contact_name ?? $this->agency->name,
            'agency_name'   => $this->agency->name,
            'contact_email' => config('magic.contact_email', 'reservations@magichotels.ma'),
        ];

        if ($tpl) {
            return new Content(
                view: 'emails.db-template',
                with: ['renderedBody' => $tpl->renderBody($data)],
            );
        }

        return new Content(view: 'emails.agency.registration-received');
    }
}
