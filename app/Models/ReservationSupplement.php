<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationSupplement extends Model
{
    protected $fillable = [
        'reservation_id', 'supplement_id',
        'adults_count', 'children_count', 'babies_count',
        'unit_price_adult', 'unit_price_child', 'unit_price_baby',
        'total_price', 'is_mandatory',
    ];

    protected $casts = [
        'adults_count'    => 'integer',
        'children_count'  => 'integer',
        'babies_count'    => 'integer',
        'unit_price_adult'=> 'float',
        'unit_price_child'=> 'float',
        'unit_price_baby' => 'float',
        'total_price'     => 'float',
        'is_mandatory'    => 'boolean',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function supplement()
    {
        return $this->belongsTo(Supplement::class);
    }
}
