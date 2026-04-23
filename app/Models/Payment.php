<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id', 'payment_schedule_id',
        'amount', 'currency', 'method',
        'status', 'reference', 'notes', 'proof_path',
        'recorded_by', 'paid_at',
        'submitted_by_client', 'submitted_at',
    ];

    protected $casts = [
        'amount'              => 'float',
        'paid_at'             => 'datetime',
        'submitted_at'        => 'datetime',
        'submitted_by_client' => 'boolean',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function schedule()
    {
        return $this->belongsTo(PaymentSchedule::class, 'payment_schedule_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getMethodLabelAttribute(): string
    {
        return match($this->method) {
            'bank_transfer' => 'Virement bancaire',
            'cash'          => 'Espèces',
            'card'          => 'Carte bancaire',
            'check'         => 'Chèque',
            default         => 'Autre',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'   => 'En attente',
            'completed' => 'Complété',
            'failed'    => 'Échoué',
            'refunded'  => 'Remboursé',
            default     => ucfirst($this->status),
        };
    }
}
