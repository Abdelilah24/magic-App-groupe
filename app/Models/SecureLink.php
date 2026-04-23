<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SecureLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'token', 'agency_name', 'agency_email', 'contact_name', 'contact_phone',
        'hotel_id', 'agency_id', 'created_by', 'expires_at', 'used_at', 'max_uses',
        'uses_count', 'is_active', 'notes',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
        'is_active'  => 'boolean',
        'max_uses'   => 'integer',
        'uses_count' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $link) {
            if (empty($link->token)) {
                $link->token = Str::random(64);
            }
        });
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function getUrlAttribute(): string
    {
        return ''; // Liens sécurisés désactivés
    }

    public function getStatusLabelAttribute(): string
    {
        if (! $this->is_active) return 'Désactivé';
        if ($this->expires_at && $this->expires_at->isPast()) return 'Expiré';
        if ($this->uses_count >= $this->max_uses) return 'Utilisé';
        return 'Actif';
    }
}
