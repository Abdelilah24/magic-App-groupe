<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomOccupancyConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_type_id',
        'code', 'label',
        'min_adults', 'max_adults',
        'min_children', 'max_children',
        'min_babies', 'max_babies',
        'sort_order', 'is_active',
        'coefficient',
    ];

    protected $casts = [
        'min_adults'   => 'integer',
        'max_adults'   => 'integer',
        'min_children' => 'integer',
        'max_children' => 'integer',
        'min_babies'   => 'integer',
        'max_babies'   => 'integer',
        'sort_order'   => 'integer',
        'is_active'    => 'boolean',
        'coefficient'  => 'float',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function prices()
    {
        return $this->hasMany(RoomPrice::class, 'occupancy_config_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Vérifie si une combinaison adultes/enfants/bébés correspond à cette config.
     */
    public function matches(int $adults, int $children, int $babies = 0): bool
    {
        return $adults   >= $this->min_adults   && $adults   <= $this->max_adults
            && $children >= $this->min_children && $children <= $this->max_children
            && $babies   >= $this->min_babies   && $babies   <= $this->max_babies;
    }

    /**
     * Description lisible de l'occupation.
     * Ex: "2-3 adultes · 0-2 enfants"
     */
    public function getOccupancyDescriptionAttribute(): string
    {
        $parts = [];

        if ($this->min_adults === $this->max_adults) {
            $parts[] = $this->min_adults . ' adulte' . ($this->min_adults > 1 ? 's' : '');
        } else {
            $parts[] = $this->min_adults . '–' . $this->max_adults . ' adultes';
        }

        if ($this->max_children > 0) {
            if ($this->min_children === $this->max_children) {
                $parts[] = $this->min_children . ' enfant' . ($this->min_children > 1 ? 's' : '');
            } else {
                $parts[] = $this->min_children . '–' . $this->max_children . ' enfant' . ($this->max_children > 1 ? 's' : '');
            }
        }

        if ($this->max_babies > 0) {
            $parts[] = '0–' . $this->max_babies . ' bébé' . ($this->max_babies > 1 ? 's' : '');
        }

        return implode(' · ', $parts);
    }
}
