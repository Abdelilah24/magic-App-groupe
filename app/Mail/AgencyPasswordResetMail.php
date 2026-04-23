<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgencyPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $resetUrl,
        public readonly string $agencyName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Réinitialisation de votre mot de passe – Magic Hotels',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.agency-password-reset',
            with: [
                'resetUrl'   => $this->resetUrl,
                'agencyName' => $this->agencyName,
            ],
        );
    }
}
