<?php

namespace App\Services;

use App\Mail\AgencyApprovedMail;
use App\Mail\AgencyRegistrationReceivedMail;
use App\Mail\AgencyRejectedMail;
use App\Mail\ClientReservationReceivedMail;
use App\Mail\InvitationMail;
use App\Mail\ModificationAcceptedMail;
use App\Mail\ModificationRefusedMail;
use App\Mail\PaymentConfirmedMail;
use App\Mail\PaymentRequestMail;
use App\Mail\ProformaInvoiceMail;
use App\Mail\ReservationQuoteMail;
use App\Mail\ReservationRefusedMail;
use App\Models\Agency;
use App\Models\AppSetting;
use App\Models\Reservation;
use App\Models\SecureLink;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    // ─── Emails client ────────────────────────────────────────────────────────

    public function sendInvitation(SecureLink $link): void
    {
        $this->sendSafely(function () use ($link) {
            Mail::to($link->agency_email)
                ->sendNow(new InvitationMail($link));
        }, "Invitation [{$link->agency_email}]");
    }

    public function sendClientReservationReceived(Reservation $reservation): void
    {
        $this->sendSafely(function () use ($reservation) {
            Mail::to($reservation->email)
                ->sendNow(new ClientReservationReceivedMail($reservation));
        }, "Confirmation réception [{$reservation->reference}]");
    }

    public function sendQuote(Reservation $reservation): void
    {
        $this->sendSafely(function () use ($reservation) {
            Mail::to($reservation->email)
                ->sendNow(new ReservationQuoteMail($reservation));
        }, "Devis [{$reservation->reference}]");
    }

    public function sendProformaInvoice(Reservation $reservation): void
    {
        $this->sendSafely(function () use ($reservation) {
            Mail::to($reservation->email)
                ->sendNow(new ProformaInvoiceMail($reservation));
        }, "Proforma [{$reservation->reference}]");
    }

    public function sendPaymentRequest(Reservation $reservation): void
    {
        $this->sendSafely(function () use ($reservation) {
            Mail::to($reservation->email)
                ->sendNow(new PaymentRequestMail($reservation));
        }, "Demande paiement [{$reservation->reference}]");
    }

    public function sendRefusal(Reservation $reservation): void
    {
        $this->sendSafely(function () use ($reservation) {
            Mail::to($reservation->email)
                ->sendNow(new ReservationRefusedMail($reservation));
        }, "Refus [{$reservation->reference}]");
    }

    public function sendPaymentConfirmation(Reservation $reservation): void
    {
        $this->sendSafely(function () use ($reservation) {
            Mail::to($reservation->email)
                ->sendNow(new PaymentConfirmedMail($reservation));
        }, "Confirmation paiement [{$reservation->reference}]");
    }

    public function sendModificationAccepted(Reservation $reservation): void
    {
        $this->sendSafely(function () use ($reservation) {
            Mail::to($reservation->email)
                ->sendNow(new ModificationAcceptedMail($reservation));
        }, "Modif acceptée [{$reservation->reference}]");
    }

    public function sendModificationRefused(Reservation $reservation, string $reason): void
    {
        $this->sendSafely(function () use ($reservation, $reason) {
            Mail::to($reservation->email)
                ->sendNow(new ModificationRefusedMail($reservation, $reason));
        }, "Modif refusée [{$reservation->reference}]");
    }

    // ─── Notifications admin ─────────────────────────────────────────────────

    public function notifyAdminNewReservation(Reservation $reservation): void
    {
        $adminEmail = $this->getAdminEmail();
        if (! $adminEmail) return;

        $this->sendSafely(function () use ($reservation, $adminEmail) {
            Mail::to($adminEmail)->sendNow(
                new \App\Mail\AdminNewReservationMail($reservation)
            );
        }, "Notif admin nouvelle réservation [{$reservation->reference}]");
    }

    public function notifyAdminModification(Reservation $reservation): void
    {
        $adminEmail = $this->getAdminEmail();
        if (! $adminEmail) return;

        $this->sendSafely(function () use ($reservation, $adminEmail) {
            Mail::to($adminEmail)->sendNow(
                new \App\Mail\AdminModificationRequestMail($reservation)
            );
        }, "Notif admin modification [{$reservation->reference}]");
    }

    public function notifyAdminCancellation(Reservation $reservation): void
    {
        $adminEmail = $this->getAdminEmail();
        if (! $adminEmail) return;

        $this->sendSafely(function () use ($reservation, $adminEmail) {
            Mail::to($adminEmail)->sendNow(
                new \App\Mail\AdminCancellationMail($reservation)
            );
        }, "Notif admin annulation [{$reservation->reference}]");
    }

    // ─── Emails agences ──────────────────────────────────────────────────────

    public function sendAgencyRegistrationConfirmed(Agency $agency): void
    {
        $this->sendSafely(function () use ($agency) {
            Mail::to($agency->email)->sendNow(new AgencyRegistrationReceivedMail($agency));
        }, "Confirmation inscription agence [{$agency->email}]");
    }

    public function sendAgencyApproved(Agency $agency, ?string $plainPassword = null, ?SecureLink $secureLink = null): void
    {
        $this->sendSafely(function () use ($agency, $plainPassword, $secureLink) {
            Mail::to($agency->email)->sendNow(new AgencyApprovedMail($agency, $plainPassword, $secureLink));
        }, "Approbation agence [{$agency->email}]");
    }

    public function sendAgencyRejected(Agency $agency, string $reason): void
    {
        $this->sendSafely(function () use ($agency, $reason) {
            Mail::to($agency->email)->sendNow(new AgencyRejectedMail($agency, $reason));
        }, "Rejet agence [{$agency->email}]");
    }

    public function notifyAdminNewAgency(Agency $agency): void
    {
        $adminEmail = $this->getAdminEmail();
        if (! $adminEmail) return;
        $this->sendSafely(function () use ($agency, $adminEmail) {
            Mail::to($adminEmail)->sendNow(new \App\Mail\AdminNewAgencyMail($agency));
        }, "Notif admin nouvelle agence [{$agency->name}]");
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Retourne l'email admin : priorité au paramètre DB (AppSetting),
     * sinon fallback sur config/magic.php (env MAGIC_ADMIN_EMAIL).
     */
    private function getAdminEmail(): ?string
    {
        return AppSetting::get(AppSetting::KEY_ADMIN_EMAIL)
            ?: config('magic.admin_notification_email');
    }

    private function sendSafely(callable $fn, string $context): void
    {
        try {
            $fn();
            Log::info("Email envoyé : {$context}");
        } catch (\Throwable $e) {
            Log::error("Erreur envoi email [{$context}] : " . $e->getMessage());
        }
    }
}
