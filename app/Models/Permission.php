<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class Permission extends Model
{
    protected $fillable = ['name', 'label', 'group', 'description', 'sort_order'];

    // ── Relations ────────────────────────────────────────────────────────

    /**
     * Rôles qui possèdent cette permission (via la table pivot role_permissions).
     */
    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        // Pseudo-relation via la table pivot : pas de modèle Role dédié,
        // on utilise la colonne "role" comme clé étrangère non-standard.
        // Pour la liste des rôles ayant une permission, on passe par la table directement.
        return $this->belongsToMany(
            self::class,     // placeholder – non utilisé
            'role_permissions',
            'permission_id',
            'permission_id'
        );
    }

    // ── Helpers statiques ────────────────────────────────────────────────

    /**
     * Retourne les noms de permissions d'un rôle donné (depuis le cache).
     * On cache uniquement des strings (jamais des objets Eloquent) pour éviter
     * les erreurs __PHP_Incomplete_Class lors de la désérialisation.
     *
     * @return SupportCollection<int, string>
     */
    public static function forRole(string $role): SupportCollection
    {
        $names = cache()->remember(
            "permissions.role.{$role}",
            now()->addMinutes(10),
            fn () => static::query()
                ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                ->where('role_permissions.role', $role)
                ->orderBy('sort_order')
                ->pluck('permissions.name')
                ->all()   // tableau PHP simple — sérialisable sans risque
        );

        return collect($names);
    }

    /**
     * Invalide le cache des permissions pour un rôle.
     */
    public static function clearCache(string $role): void
    {
        cache()->forget("permissions.role.{$role}");
    }

    /**
     * Retourne toutes les permissions groupées par catégorie.
     *
     * @return Collection<string, Collection<int, Permission>>
     */
    public static function allGrouped(): \Illuminate\Support\Collection
    {
        return static::query()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group');
    }
}
