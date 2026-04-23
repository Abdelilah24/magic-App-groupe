<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id', 'room_type_id', 'occupancy_config_id',
        'date_from', 'date_to',
        'price_per_night', 'currency', 'label', 'is_active',
    ];

    protected $casts = [
        'date_from'       => 'date',
        'date_to'         => 'date',
        'price_per_night' => 'float',
        'is_active'       => 'boolean',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function occupancyConfig()
    {
        return $this->belongsTo(RoomOccupancyConfig::class, 'occupancy_config_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDate($query, string $date)
    {
        return $query->where('date_from', '<=', $date)
                     ->where('date_to', '>=', $date);
    }

    public function scopeOverlapping($query, string $from, string $to)
    {
        return $query->where('date_from', '<=', $to)
                     ->where('date_to', '>=', $from);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getPeriodLabelAttribute(): string
    {
        return $this->date_from->format('d/m/Y') . ' → ' . $this->date_to->format('d/m/Y');
    }
}
