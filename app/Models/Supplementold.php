<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'hotel_id', 'title', 'description', 'date_from', 'date_to',
        'status', 'price_adult', 'price_child', 'price_baby', 'is_active',
    ];

    protected $casts = [
        'date_from'   => 'date',
        'date_to'     => 'date',
        'price_adult' => 'float',
        'price_child' => 'float',
        'price_baby'  => 'float',
        'is_active'   => 'boolean',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function reservationSupplements()
    {
        return $this->hasMany(ReservationSupplement::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMandatory($query)
    {
        return $query->where('status', 'mandatory');
    }

    public function scopeForHotel($query, int $hotelId)
    {
        return $query->where('hotel_id', $hotelId);
    }

    /**
     * Suppléments dont la période (date_from → date_to) chevauche la période de séjour.
     * Un supplément est concerné si : date_from <= check_out ET date_to >= check_in
     */
    public function scopeOverlapping($query, string $checkIn, string $checkOut)
    {
        return $query->where('date_from', '<=', $checkOut)
                     ->where('date_to',   '>=', $checkIn);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isMandatory(): bool
    {
        return $this->status === 'mandatory';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === 'mandatory' ? 'Obligatoire' : 'Optionnel';
    }

    /**
     * Calcule le total pour un nombre de personnes donné.
     */
    public function calculateTotal(int $adults, int $children = 0, int $babies = 0): float
    {
        return round(
            ($this->price_adult * $adults)
          + ($this->price_child * $children)
          + ($this->price_baby  * $babies),
            2
        );
    }
}
