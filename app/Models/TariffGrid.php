<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TariffGrid extends Model
{
    use LogsActivity;

    protected string $activitySection = 'Grilles tarifaires';
    protected $fillable = [
        'hotel_id', 'name', 'code', 'is_base',
        'base_grid_id', 'operator', 'operator_value',
        'rounding', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_base'        => 'boolean',
        'is_active'      => 'boolean',
        'operator_value' => 'float',
        'sort_order'     => 'integer',
    ];

    /* ── Relations ─────────────────────────────────────────── */

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function baseGrid(): BelongsTo
    {
        return $this->belongsTo(TariffGrid::class, 'base_grid_id');
    }

    public function derivedGrids(): HasMany
    {
        return $this->hasMany(TariffGrid::class, 'base_grid_id');
    }

    /* ── Helpers ────────────────────────────────────────────── */

    /**
     * Calcule le prix final à partir du prix de base NRF.
     * $allGrids doit contenir toutes les grilles de l'hôtel (indexées par id).
     */
    public function calculatePrice(float $basePrice, array $allGrids): float
    {
        if ($this->is_base) {
            return $this->applyRounding($basePrice);
        }

        if (! $this->base_grid_id || ! isset($allGrids[$this->base_grid_id])) {
            return $this->applyRounding($basePrice);
        }

        $parentGrid  = $allGrids[$this->base_grid_id];
        $parentPrice = $parentGrid->calculatePrice($basePrice, $allGrids);

        $result = match ($this->operator) {
            'divide'           => $parentPrice / max($this->operator_value, 0.0001),
            'multiply'         => $parentPrice * $this->operator_value,
            'subtract_percent' => $parentPrice * (1 - $this->operator_value / 100),
            default            => $parentPrice,
        };

        return $this->applyRounding($result);
    }

    private function applyRounding(float $value): float
    {
        return match ($this->rounding) {
            'round' => round($value, 2),
            'ceil'  => ceil($value * 100) / 100,
            'floor' => floor($value * 100) / 100,
            default => round($value, 2),
        };
    }

    /**
     * Représentation lisible de la formule, ex: "NRF ÷ 1.1"
     */
    public function formulaLabel(): string
    {
        if ($this->is_base) return 'Base (saisie manuelle)';
        if (! $this->baseGrid) return '—';

        $base = $this->baseGrid->code;
        return match ($this->operator) {
            'divide'           => "{$base} ÷ {$this->operator_value}",
            'multiply'         => "{$base} × {$this->operator_value}",
            'subtract_percent' => "{$base} − {$this->operator_value}%",
            default            => "{$base} (formule inconnue)",
        };
    }
}
