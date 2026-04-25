<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class Role extends Model
{
    use LogsActivity;

    protected string $activitySection = 'Rôles & Permissions';
    protected $fillable = ['name', 'label', 'description', 'is_system', 'sort_order'];

    protected $casts = ['is_system' => 'boolean'];

    // ─── Scopes & queries ────────────────────────────────────────────────────

    public static function allSorted(): Collection
    {
        return static::orderBy('sort_order')->orderBy('label')->get();
    }

    /**
     * Rôles assignables par un super_admin (tous sauf super_admin lui-même si désiré).
     * Filtre optionnel : exclure le rôle super_admin de la liste de sélection utilisateur.
     */
    public static function selectable(): Collection
    {
        return static::where('name', '!=', User::ROLE_SUPER_ADMIN)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }

    // ─── Compteurs ───────────────────────────────────────────────────────────

    public function userCount(): int
    {
        return User::where('role', $this->name)->count();
    }

    public function permissionCount(): int
    {
        return DB::table('role_permissions')->where('role', $this->name)->count();
    }

    // ─── Gestion ─────────────────────────────────────────────────────────────

    /**
     * Un rôle peut être supprimé s'il n'est pas système et n'a aucun utilisateur.
     */
    public function canBeDeleted(): bool
    {
        return !$this->is_system && $this->userCount() === 0;
    }

    /**
     * Supprime le rôle ainsi que toutes ses permissions associées.
     */
    public function deleteWithPermissions(): void
    {
        DB::table('role_permissions')->where('role', $this->name)->delete();
        Permission::clearCache($this->name);
        $this->delete();
    }
}
