<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id', 'room_type_id', 'occupancy_config_id', 'occupancy_config_label',
        'quantity',
        'adults', 'children', 'babies', 'baby_bed',
        'check_in', 'check_out',
        'price_per_night', 'total_price', 'price_detail',
        'price_override', 'original_price_per_night', 'original_total_price',
    ];

    protected $casts = [
        'quantity'                 => 'integer',
        'adults'                   => 'integer',
        'children'                 => 'integer',
        'babies'                   => 'integer',
        'baby_bed'                 => 'boolean',
        'check_in'                 => 'date',
        'check_out'                => 'date',
        'price_per_night'          => 'float',
        'total_price'              => 'float',
        'price_detail'             => 'array',
        'price_override'           => 'boolean',
        'original_price_per_night' => 'float',
        'original_total_price'     => 'float',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function occupancyConfig()
    {
        return $this->belongsTo(RoomOccupancyConfig::class, 'occupancy_config_id');
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total_price, 2, ',', ' ') . ' ' . ($this->reservation->currency ?? 'MAD');
    }
}
