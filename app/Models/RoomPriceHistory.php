<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomPriceHistory extends Model
{
    protected $table = 'room_price_history';

    protected $fillable = [
        'hotel_id',
        'occupancy_config_id',
        'room_type_id',
        'date_from',
        'date_to',
        'label',
        'old_price',
        'new_price',
        'delta',
        'changed_by_id',
        'changed_by_name',
    ];

    protected $casts = [
        'date_from'  => 'date',
        'date_to'    => 'date',
        'old_price'  => 'decimal:2',
        'new_price'  => 'decimal:2',
        'delta'      => 'decimal:2',
    ];

    public function occupancyConfig()
    {
        return $this->belongsTo(RoomOccupancyConfig::class, 'occupancy_config_id');
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }
}
