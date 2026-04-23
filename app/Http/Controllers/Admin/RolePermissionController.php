<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolePermissionController extends Controller
{
    /**
     * Affiche la matrice rôles × permissions.
     * Accessible uniquement au super_admin.
     */
    public function index()
    {
        $this->authorizeSuperAdmin();

        $permissionsGrouped = Permission::allGrouped();

        // Charger les permissions accordées à chaque rôle configurable
        $roles = [User::ROLE_ADMIN, User::ROLE_STAFF];

        $granted = [];
        foreach ($roles as $role) {
            $granted[$role] = DB::table('role_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                ->where('role_permissions.role', $role)
                ->pluck('permissions.name')
                ->flip() // name => index, pour O(1) lookup en blade
                ->toArray();
        }

        return view('admin.roles.index', compact('permissionsGrouped', 'roles', 'granted'));
    }

    /**
     * Sauvegarde les permissions d'un rôle donné.
     */
    public function update(Request $request, string $role)
    {
        $this->authorizeSuperAdmin();

        // Seuls admin et staff sont configurables (super_admin est immuable)
        if (! in_array($role, [User::ROLE_ADMIN, User::ROLE_STAFF])) {
            abort(422, 'Rôle non configurable.');
        }

        $data = $request->validate([
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $permNames = $data['permissions'] ?? [];

        // Résoudre les IDs
        $permIds = Permission::whereIn('name', $permNames)->pluck('id');

        DB::transaction(function () use ($role, $permIds) {
            DB::table('role_permissions')->where('role', $role)->delete();

            foreach ($permIds as $id) {
                DB::table('role_permissions')->insert([
                    'role'          => $role,
                    'permission_id' => $id,
                ]);
            }
        });

        // Invalider le cache
        Permission::clearCache($role);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Permissions du rôle « ' . User::$roleLabels[$role] . ' » mises à jour.');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function authorizeSuperAdmin(): void
    {
        if (! auth()->user()?->isSuperAdmin()) {
            abort(403, 'Accès réservé au super-administrateur.');
        }
    }
}
