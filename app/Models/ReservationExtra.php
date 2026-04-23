<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationExtra extends Model
{
    protected $fillable = [
        'reservation_id', 'extra_service_id',
        'name', 'description', 'unit_price', 'quantity', 'total_price', 'notes',
    ];

    protected $casts = [
        'unit_price'  => 'float',
        'total_price' => 'float',
        'quantity'    => 'integer',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function extraService()
    {
        return $this->belongsTo(ExtraService::class);
    }
}
