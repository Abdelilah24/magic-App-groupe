<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AgencyStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'discount_percent',
        'description', 'is_default', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'discount_percent' => 'float',
        'is_default'       => 'boolean',
        'is_active'        => 'boolean',
        'sort_order'       => 'integer',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function agencies()
    {
        return $this->hasMany(Agency::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Retourne le facteur multiplicateur pour le calcul du prix.
     * Ex : 10% de remise → 0.90
     */
    public function getPriceMultiplierAttribute(): float
    {
        return 1 - ($this->discount_percent / 100);
    }

    /**
     * Retourne le label de remise formaté.
     * Ex : "−10 %" ou "Tarif normal"
     */
    public function getDiscountLabelAttribute(): string
    {
        if ($this->discount_percent <= 0) {
            return 'Tarif normal';
        }
        return '−' . number_format($this->discount_percent, 0) . ' %';
    }

    /**
     * Crée le slug depuis le nom.
     */
    public static function makeSlug(string $name): string
    {
        return \Illuminate\Support\Str::slug($name);
    }
}
