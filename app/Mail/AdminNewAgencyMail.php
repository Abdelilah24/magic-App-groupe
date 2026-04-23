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

class AdminNewAgencyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Agency $agency) {}

    public function envelope(): Envelope
    {
        $tpl  = EmailTemplate::getByKey('admin_new_agency');
        $data = $this->buildData();

        return new Envelope(
            subject: $tpl ? $tpl->renderSubject($data)
                          : 'Nouvelle demande de partenariat agence — ' . $this->agency->name,
        );
    }

    public function content(): Content
    {
        $tpl  = EmailTemplate::getByKey('admin_new_agency');
        $data = $this->buildData();

        if ($tpl) {
            return new Content(
                view: 'emails.db-template',
                with: ['renderedBody' => $tpl->renderBody($data)],
            );
        }

        return new Content(view: 'emails.admin.new-agency');
    }

    private function buildData(): array
    {
        $message = $this->agency->message
            ? '<p><strong>Message :</strong> ' . e($this->agency->message) . '</p>'
            : '';

        return [
            'agency_name'   => $this->agency->name,
            'contact_name'  => $this->agency->contact_name,
            'contact_email' => $this->agency->email,
            'phone'         => $this->agency->phone ?? '—',
            'city'          => $this->agency->city ?? '—',
            'country'       => $this->agency->country ?? '—',
            'message'       => $message,
            'admin_url'     => url('/admin/agencies/' . $this->agency->id),
        ];
    }
}
