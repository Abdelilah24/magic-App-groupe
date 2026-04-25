<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Agency extends Authenticatable
{
    use HasFactory, SoftDeletes, Notifiable, LogsActivity;

    protected string $activitySection = 'Agences';

    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'name', 'email', 'phone', 'contact_name',
        'address', 'city', 'country', 'website', 'notes',
        'status', 'admin_notes', 'approved_at', 'approved_by',
        'access_token', 'password', 'agency_status_id',
        'licence_number', 'licence_file',
        'pending_changes',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'approved_at'       => 'datetime',
        'password'          => 'hashed',
        'pending_changes'   => 'array',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function agencyStatus()
    {
        return $this->belongsTo(AgencyStatus::class);
    }

    /**
     * Retourne le pourcentage de remise applicable à cette agence.
     */
    public function getDiscountPercentAttribute(): float
    {
        return $this->agencyStatus?->discount_percent ?? 0.0;
    }

    public function secureLinks()
    {
        return $this->hasMany(SecureLink::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopePending($query)  { return $query->where('status', self::STATUS_PENDING); }
    public function scopeApproved($query) { return $query->where('status', self::STATUS_APPROVED); }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING  => 'En attente',
            self::STATUS_APPROVED => 'Approuvée',
            self::STATUS_REJECTED => 'Rejetée',
            default               => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING  => 'yellow',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
            default               => 'gray',
        };
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getPortalUrlAttribute(): ?string
    {
        return route('agency.portal.dashboard');
    }

    public function generateAccessToken(): string
    {
        $token = \Illuminate\Support\Str::random(64);
        $this->update(['access_token' => $token]);
        return $token;
    }

    /**
     * Génère et retourne un mot de passe temporaire en clair (puis le hash en base).
     */
    public function generatePassword(): string
    {
        $plain = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(3))
               . rand(100, 999)
               . \Illuminate\Support\Str::random(3);
        $this->update(['password' => $plain]); // cast 'hashed' s'occupe du hash
        return $plain;
    }
}
