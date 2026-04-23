<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestRegistration extends Model
{
    protected $fillable = [
        'reservation_id',
        'guest_index',
        'guest_type',
        'civilite',
        'nom',
        'prenom',
        'date_naissance',
        'lieu_naissance',
        'pays_naissance',
        'nationalite',
        'type_document',
        'numero_document',
        'date_expiration_document',
        'pays_emission_document',
        'adresse',
        'ville',
        'code_postal',
        'pays_residence',
        'profession',
        'numero_entree_maroc',
    ];

    protected $casts = [
        'date_naissance'           => 'date',
        'date_expiration_document' => 'date',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /** Nom complet affiché */
    public function getFullNameAttribute(): string
    {
        return trim(($this->civilite ? $this->civilite . ' ' : '') . $this->prenom . ' ' . $this->nom);
    }

    /** Vrai si la fiche est complète (champs obligatoires remplis) */
    public function isComplete(): bool
    {
        return filled($this->nom)
            && filled($this->prenom)
            && filled($this->date_naissance)
            && filled($this->nationalite)
            && filled($this->numero_document);
    }
}
