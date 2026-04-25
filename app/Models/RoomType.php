<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RoomType extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected string $activitySection = 'Types de chambres';

    protected $fillable = [
        'hotel_id', 'name', 'slug', 'capacity', 'description',
        'image', 'is_active', 'total_rooms', 'min_persons', 'max_persons',
        'max_adults', 'max_children', 'baby_bed_available',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'capacity'           => 'integer',
        'total_rooms'        => 'integer',
        'min_persons'        => 'integer',
        'max_persons'        => 'integer',
        'max_adults'         => 'integer',
        'max_children'       => 'integer',
        'baby_bed_available' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $rt) {
            if (empty($rt->slug)) {
                $rt->slug = Str::slug($rt->name);
            }
        });
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function prices()
    {
        return $this->hasMany(RoomPrice::class);
    }

    public function reservationRooms()
    {
        return $this->hasMany(ReservationRoom::class);
    }

    public function occupancyConfigs()
    {
        return $this->hasMany(RoomOccupancyConfig::class)->orderBy('sort_order');
    }

    public function activeOccupancyConfigs()
    {
        return $this->hasMany(RoomOccupancyConfig::class)
                    ->where('is_active', true)
                    ->orderBy('sort_order');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Retourne le prix par nuit pour une date donnée.
     * Si une config d'occupation est fournie, priorise le tarif spécifique à cette config.
     */
    public function getPriceForDate(\Carbon\Carbon $date, ?int $occupancyConfigId = null): ?float
    {
        $dateStr = $date->toDateString();

        // 1. Cherche un tarif spécifique à la config d'occupation
        if ($occupancyConfigId) {
            $price = $this->prices()
                ->where('occupancy_config_id', $occupancyConfigId)
                ->where('date_from', '<=', $dateStr)
                ->where('date_to', '>=', $dateStr)
                ->where('is_active', true)
                ->orderByDesc('date_from')
                ->first();
            if ($price) return $price->price_per_night;
        }

        // 2. Fallback : tarif général (sans config)
        $price = $this->prices()
            ->whereNull('occupancy_config_id')
            ->where('date_from', '<=', $dateStr)
            ->where('date_to', '>=', $dateStr)
            ->where('is_active', true)
            ->orderByDesc('date_from')
            ->first();

        return $price?->price_per_night;
    }

    /**
     * Trouve la config d'occupation qui correspond à une occupation donnée.
     * Retourne null si aucune config définie ou si aucune ne correspond.
     */
    public function findMatchingOccupancyConfig(int $adults, int $children, int $babies = 0): ?RoomOccupancyConfig
    {
        return $this->activeOccupancyConfigs()
            ->get()
            ->first(fn ($cfg) => $cfg->matches($adults, $children, $babies));
    }
}
