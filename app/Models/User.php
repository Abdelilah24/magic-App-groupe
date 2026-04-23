<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
    ];

    // ─── Constantes de rôle système ──────────────────────────────────────────

    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN       = 'admin';
    const ROLE_STAFF       = 'staff';

    /**
     * Libellés lisibles des rôles système (fallback si roles table non disponible).
     */
    public static array $roleLabels = [
        self::ROLE_SUPER_ADMIN => 'Super Administrateur',
        self::ROLE_ADMIN       => 'Administrateur',
        self::ROLE_STAFF       => 'Staff',
    ];

    // ─── Helpers de rôle ─────────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN]);
    }

    /**
     * Retourne vrai si l'utilisateur peut accéder au panel admin.
     * Inclut les rôles système ET les rôles personnalisés de la table roles.
     */
    public function isStaff(): bool
    {
        if (in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_STAFF])) {
            return true;
        }
        // Rôles personnalisés créés via le panel : ont accès au panel admin
        return !empty($this->role) && Role::where('name', $this->role)->exists();
    }

    /**
     * Libellé lisible du rôle (cherche d'abord en DB, puis fallback statique).
     */
    public function getRoleLabelAttribute(): string
    {
        if (isset(static::$roleLabels[$this->role])) {
            return static::$roleLabels[$this->role];
        }
        // Rôle dynamique : chercher le libellé en base
        $role = Role::where('name', $this->role)->first();
        return $role?->label ?? ucfirst(str_replace('_', ' ', $this->role ?? ''));
    }

    // ─── Gestion des permissions ──────────────────────────────────────────────

    /**
     * Vérifie si l'utilisateur possède une permission.
     * Le super_admin possède toujours toutes les permissions.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // forRole() retourne déjà une Collection de noms (strings)
        return Permission::forRole($this->role)->contains($permission);
    }

    /**
     * Vérifie si l'utilisateur possède au moins l'une des permissions données.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $p) {
            if ($this->hasPermission($p)) {
                return true;
            }
        }
        return false;
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function createdSecureLinks()
    {
        return $this->hasMany(SecureLink::class, 'created_by');
    }

    public function handledReservations()
    {
        return $this->hasMany(Reservation::class, 'handled_by');
    }
}
