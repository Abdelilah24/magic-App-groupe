<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefusalReason extends Model
{
    protected $fillable = ['label', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }

    /** Retourne true si ce motif est le motif "Autre" (réservé pour saisie libre). */
    public function isOther(): bool
    {
        return strtolower(trim($this->label)) === 'autre';
    }
}
