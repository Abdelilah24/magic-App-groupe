<?php

namespace App\Mail;

use App\Models\Agency;
use App\Models\EmailTemplate;
use App\Models\SecureLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgencyApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Agency      $agency,
        public readonly ?string     $plainPassword = null,
        public readonly ?SecureLink $secureLink    = null,
    ) {}

    public function envelope(): Envelope
    {
        $tpl  = EmailTemplate::getByKey('agency_approved');
        $data = $this->buildData();

        return new Envelope(
            subject: $tpl ? $tpl->renderSubject($data)
                          : '✅ Votre agence a été approuvée — Magic Hotels',
        );
    }

    public function content(): Content
    {
        $tpl  = EmailTemplate::getByKey('agency_approved');
        $data = $this->buildData();

        if ($tpl) {
            return new Content(
                view: 'emails.db-template',
                with: ['renderedBody' => $tpl->renderBody($data)],
            );
        }

        // Fallback : Blade view classique
        return new Content(view: 'emails.agency.approved');
    }

    private function buildData(): array
    {
        return [
            'contact_name'  => $this->agency->contact_name ?? $this->agency->name,
            'agency_name'   => $this->agency->name,
            'email'         => $this->agency->email,
            'password'      => $this->plainPassword ?? '(mot de passe existant)',
            'login_url'     => route('agency.login'),
            'portal_url'    => route('agency.portal.dashboard'),
            'tariff_status' => $this->agency->agencyStatus?->name ?? '—',
            'approval_date' => now()->format('d/m/Y'),
            'contact_email' => config('magic.contact_email', 'reservations@magichotels.ma'),
        ];
    }
}
