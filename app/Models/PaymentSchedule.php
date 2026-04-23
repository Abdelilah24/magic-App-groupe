<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSchedule extends Model
{
    protected $fillable = [
        'reservation_id', 'installment_number', 'label',
        'due_date', 'due_time', 'amount', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount'   => 'float',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Le paiement soumis par le client (ou enregistré) pour cette échéance.
     */
    public function payment()
    {
        return $this->hasOne(Payment::class, 'payment_schedule_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    /**
     * Statut calculé : tient compte de la date d'échéance même si le DB dit 'pending'.
     */
    public function getComputedStatusAttribute(): string
    {
        if ($this->status === 'paid') return 'paid';
        if ($this->due_date->isPast()) return 'overdue';
        return 'pending';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->computed_status) {
            'paid'    => 'Payé',
            'overdue' => 'En retard',
            default   => 'En attente',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->computed_status) {
            'paid'    => 'green',
            'overdue' => 'red',
            default   => 'yellow',
        };
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /** L'échéance est payée (DB ou via paiement validé). */
    public function isPaid(): bool
    {
        return $this->status === 'paid'
            || ($this->payment && $this->payment->status === 'completed');
    }

    /** Le client a soumis une preuve en attente de validation. */
    public function hasPendingProof(): bool
    {
        return $this->payment && $this->payment->status === 'pending';
    }
}
