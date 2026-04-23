<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtraService extends Model
{
    protected $fillable = ['name', 'description', 'price', 'is_active'];

    protected $casts = [
        'price'     => 'float',
        'is_active' => 'boolean',
    ];

    public function reservationExtras()
    {
        return $this->hasMany(ReservationExtra::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
