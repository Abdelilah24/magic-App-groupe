<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id', 'from_status', 'to_status',
        'comment', 'metadata', 'actor_type', 'actor_id', 'actor_name',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getActorLabelAttribute(): string
    {
        if ($this->actor_name) return $this->actor_name;
        return match($this->actor_type) {
            'system' => 'Système',
            'client' => 'Client',
            default  => 'Admin',
        };
    }

    public function getFromStatusLabelAttribute(): string
    {
        return $this->formatStatus($this->from_status);
    }

    public function getToStatusLabelAttribute(): string
    {
        return $this->formatStatus($this->to_status);
    }

    private function formatStatus(?string $status): string
    {
        return match($status) {
            'draft'                => 'Brouillon',
            'pending'              => 'En attente',
            'accepted'             => 'Acceptée',
            'refused'              => 'Refusée',
            'waiting_payment'      => 'En attente paiement',
            'paid'                 => 'Payée',
            'confirmed'            => 'Confirmée',
            'modification_pending' => 'Modif. en attente',
            'cancelled'            => 'Annulée',
            null                   => '—',
            default                => ucfirst($status),
        };
    }
}
