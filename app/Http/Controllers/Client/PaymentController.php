<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\Reservation;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Page de paiement accessible via token.
     */
    public function show(string $paymentToken)
    {
        $reservation = Reservation::where('payment_token', $paymentToken)
            ->with(['hotel', 'rooms.roomType', 'paymentSchedules.payment'])
            ->firstOrFail();

        if (! $reservation->hasValidPaymentToken()) {
            return view('client.payment.expired', compact('reservation'));
        }

        // Date limite dépassée → devis grisé, paiement impossible
        if ($reservation->isPaymentDeadlineExpired()) {
            return view('client.payment.deadline-expired', compact('reservation'));
        }

        if (! in_array($reservation->status, [
            Reservation::STATUS_WAITING_PAYMENT,
            Reservation::STATUS_ACCEPTED,
            Reservation::STATUS_PARTIALLY_PAID,
        ])) {
            return view('client.payment.not-available', compact('reservation'));
        }

        $bankInfo  = config('magic.bank_details');
        $schedules = $reservation->paymentSchedules;

        return view('client.payment.show', compact('reservation', 'bankInfo', 'schedules'));
    }

    /**
     * Soumettre la preuve de paiement pour une échéance précise.
     */
    public function submitScheduleProof(Request $request, string $paymentToken, PaymentSchedule $schedule)
    {
        $reservation = Reservation::where('payment_token', $paymentToken)
            ->with('paymentSchedules.payment')
            ->firstOrFail();

        abort_if(! $reservation->hasValidPaymentToken(), 403);
        abort_if($reservation->isPaymentDeadlineExpired(), 403, 'Délai de paiement dépassé.');
        abort_if($schedule->reservation_id !== $reservation->id, 403);
        abort_if($schedule->isPaid(), 422, 'Cette échéance est déjà payée.');
        abort_if($schedule->hasPendingProof(), 422, 'Une preuve est déjà en cours de validation pour cette échéance.');

        $data = $request->validate([
            'proof'     => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'method'    => 'required|in:bank_transfer,cash,card,check,other',
            'reference' => 'nullable|string|max:100',
        ]);

        $proofPath = $request->file('proof')->store('payment-proofs', 'public');

        Payment::create([
            'reservation_id'      => $reservation->id,
            'payment_schedule_id' => $schedule->id,
            'amount'              => $schedule->amount,
            'currency'            => 'MAD',
            'method'              => $data['method'],
            'status'              => 'pending',
            'reference'           => $data['reference'] ?? null,
            'notes'               => "Preuve échéance #{$schedule->installment_number} soumise par client.",
            'proof_path'          => $proofPath,
            'submitted_by_client' => true,
            'submitted_at'        => now(),
        ]);

        // Passer en partially_paid si waiting_payment
        if ($reservation->status === Reservation::STATUS_WAITING_PAYMENT) {
            $reservation->update(['status' => Reservation::STATUS_PARTIALLY_PAID]);
        }

        return back()->with('success', "Preuve envoyée pour l'échéance #{$schedule->installment_number}. Validation sous 24h.");
    }
}
