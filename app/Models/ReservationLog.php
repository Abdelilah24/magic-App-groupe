<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationLog extends Model
{
    protected $fillable = [
        'reservation_id',
        'event_type',
        'summary',
        'reason',
        'old_data',
        'new_data',
        'actor_type',
        'actor_id',
        'actor_name',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Icône et couleur selon le type d'événement.
     */
    public function getEventStyleAttribute(): array
    {
        return match($this->event_type) {
            'created'                => ['icon' => '🆕', 'color' => 'blue',   'label' => 'Création'],
            'status_changed'         => ['icon' => '🔄', 'color' => 'gray',   'label' => 'Changement de statut'],
            'modification_requested' => ['icon' => '✏️',  'color' => 'purple', 'label' => 'Modification demandée'],
            'modification_accepted'  => ['icon' => '✅', 'color' => 'green',  'label' => 'Modification acceptée'],
            'modification_refused'   => ['icon' => '❌', 'color' => 'red',    'label' => 'Modification refusée'],
            'payment_added'          => ['icon' => '💳', 'color' => 'amber',  'label' => 'Paiement soumis'],
            'payment_validated'      => ['icon' => '✅', 'color' => 'green',  'label' => 'Paiement validé'],
            'payment_refused'        => ['icon' => '❌', 'color' => 'red',    'label' => 'Paiement refusé'],
            'devis_sent'             => ['icon' => '📧', 'color' => 'blue',   'label' => 'Devis envoyé'],
            'price_recalculated'     => ['icon' => '💰', 'color' => 'amber',  'label' => 'Réservation modifiée'],
            'schedule_created'       => ['icon' => '📅', 'color' => 'blue',   'label' => 'Échéance ajoutée'],
            'schedule_updated'       => ['icon' => '📅', 'color' => 'gray',   'label' => 'Échéance modifiée'],
            'schedule_deleted'       => ['icon' => '🗑️',  'color' => 'red',    'label' => 'Échéance supprimée'],
            'cancelled'              => ['icon' => '🚫', 'color' => 'red',    'label' => 'Annulation'],
            default                  => ['icon' => '📝', 'color' => 'gray',   'label' => ucfirst(str_replace('_', ' ', $this->event_type))],
        };
    }

    public function getActorLabelAttribute(): string
    {
        if ($this->actor_name) return $this->actor_name;
        return match($this->actor_type) {
            'admin'  => 'Admin',
            'agency' => 'Agence',
            default  => 'Système',
        };
    }

    /**
     * Helper statique pour créer un log facilement.
     */
    public static function record(
        Reservation $reservation,
        string      $eventType,
        string      $summary,
        array       $oldData   = [],
        array       $newData   = [],
        ?string     $reason    = null,
        string      $actorType = 'system',
        ?int        $actorId   = null,
        ?string     $actorName = null,
    ): self {
        return static::create([
            'reservation_id' => $reservation->id,
            'event_type'     => $eventType,
            'summary'        => $summary,
            'reason'         => $reason,
            'old_data'       => empty($oldData) ? null : $oldData,
            'new_data'       => empty($newData) ? null : $newData,
            'actor_type'     => $actorType,
            'actor_id'       => $actorId,
            'actor_name'     => $actorName,
        ]);
    }
}
