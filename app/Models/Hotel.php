<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Hotel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'address', 'city', 'country',
        'phone', 'email', 'description', 'image', 'logo', 'stars', 'is_active',
        // Coordonnées bancaires (RIB)
        'bank_name', 'bank_rib', 'bank_iban', 'bank_swift',
        // Promos long séjour
        'promo_long_stay_enabled',
        'promo_tier1_nights', 'promo_tier1_rate',
        'promo_tier2_nights', 'promo_tier2_rate',
        // Tarification relative
        'pricing_base_room_type_id',
        'room_type_price_offsets',
        // Taxe de séjour
        'taxe_sejour',
        // Régime de pension
        'meal_plan',
    ];

    protected $casts = [
        'is_active'                  => 'boolean',
        'stars'                      => 'integer',
        'promo_long_stay_enabled'    => 'boolean',
        'promo_tier1_nights'         => 'integer',
        'promo_tier1_rate'           => 'float',
        'promo_tier2_nights'         => 'integer',
        'promo_tier2_rate'           => 'float',
        'pricing_base_room_type_id'  => 'integer',
        'room_type_price_offsets'    => 'array',
        'taxe_sejour'                => 'float',
    ];

    // ─── Boot ──────────────────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $hotel) {
            if (empty($hotel->slug)) {
                $hotel->slug = Str::slug($hotel->name);
            }
        });
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function roomTypes()
    {
        return $this->hasMany(RoomType::class);
    }

    public function activeRoomTypes()
    {
        return $this->hasMany(RoomType::class)->where('is_active', true);
    }

    public function roomPrices()
    {
        return $this->hasMany(RoomPrice::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function secureLinks()
    {
        return $this->hasMany(SecureLink::class);
    }

    public function supplements()
    {
        return $this->hasMany(Supplement::class)->orderBy('date_from');
    }

    public function activeSupplements()
    {
        return $this->hasMany(Supplement::class)->where('is_active', true)->orderBy('date_from');
    }

    public function pricingBaseRoomType()
    {
        return $this->belongsTo(RoomType::class, 'pricing_base_room_type_id');
    }

    // ─── Helpers tarification relative ───────────────────────────────────────

    /**
     * Retourne le facteur multiplicateur pour un type de chambre donné.
     * Le type de base retourne 1.0. Les autres retournent (1 + offset/100).
     * Si aucune matrice n'est configurée ou que le type est inconnu, retourne 1.0.
     */
    public function relativePriceFactorForRoomTypeId(int $roomTypeId): float
    {
        if (! $this->pricing_base_room_type_id) return 1.0;
        if ($roomTypeId === $this->pricing_base_room_type_id) return 1.0;

        $offsets = $this->room_type_price_offsets ?? [];
        $offset  = $offsets[$roomTypeId] ?? 0;   // offset en %

        return 1.0 + ($offset / 100.0);
    }

    // ─── Helpers promos ───────────────────────────────────────────────────────

    /**
     * Retourne le taux de remise promo selon le nombre de nuits.
     * 0.0 si promo désactivée ou seuil non atteint.
     */
    public function getPromoRate(int $nights): float
    {
        if (! $this->promo_long_stay_enabled || $nights <= 0) return 0.0;

        // Tier 2 (palier haut) — uniquement si configuré en base (nights > 0)
        if ((int) $this->promo_tier2_nights > 0 && $nights >= (int) $this->promo_tier2_nights) {
            return (float) $this->promo_tier2_rate;
        }

        // Tier 1 (palier bas) — uniquement si configuré en base (nights > 0)
        if ((int) $this->promo_tier1_nights > 0 && $nights >= (int) $this->promo_tier1_nights) {
            return (float) $this->promo_tier1_rate;
        }

        return 0.0;
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getStarsLabelAttribute(): string
    {
        return str_repeat('⭐', $this->stars);
    }

    public function getMealPlanLabelAttribute(): string
    {
        return match($this->meal_plan) {
            'all_inclusive'     => 'All Inclusive',
            'bed_and_breakfast' => 'Bed & Breakfast',
            'half_board'        => 'Demi-Pension',
            'full_board'        => 'Pension Complète',
            default             => '',
        };
    }
}
