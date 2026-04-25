<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class ExtraService extends Model
{
    use LogsActivity;

    protected string $activitySection = 'Services supplémentaires';
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
