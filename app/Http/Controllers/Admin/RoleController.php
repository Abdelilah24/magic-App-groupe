<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    // ─── Liste ────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $this->authorizeSuperAdmin();

        $roles = Role::orderBy('sort_order')->orderBy('label')->get()->map(function (Role $r) {
            $r->user_count       = $r->userCount();
            $r->permission_count = $r->permissionCount();
            return $r;
        });

        return view('admin.roles.index', compact('roles'));
    }

    // ─── Créer ────────────────────────────────────────────────────────────────

    public function create(): View
    {
        $this->authorizeSuperAdmin();

        $permissionsGrouped = Permission::allGrouped();

        return view('admin.roles.create', compact('permissionsGrouped'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $data = $request->validate([
            'label'         => 'required|string|max:100',
            'name'          => 'nullable|string|max:50|alpha_dash',
            'description'   => 'nullable|string|max:500',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        // Générer le slug si non fourni
        $slug = !empty($data['name'])
            ? Str::slug($data['name'], '_')
            : Str::slug($data['label'], '_');

        // Vérifier unicité
        if (Role::where('name', $slug)->exists()) {
            return back()->withErrors(['name' => 'Ce nom de rôle est déjà utilisé.'])->withInput();
        }

        $role = Role::create([
            'name'        => $slug,
            'label'       => $data['label'],
            'description' => $data['description'] ?? null,
            'is_system'   => false,
            'sort_order'  => Role::max('sort_order') + 1,
        ]);

        // Assigner les permissions
        $this->syncPermissions($slug, $data['permissions'] ?? []);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Rôle « {$role->label} » créé avec succès.");
    }

    // ─── Éditer ───────────────────────────────────────────────────────────────

    public function edit(string $roleName): View
    {
        $this->authorizeSuperAdmin();

        $role = Role::where('name', $roleName)->firstOrFail();
        $permissionsGrouped = Permission::allGrouped();

        $granted = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.role', $roleName)
            ->pluck('permissions.name')
            ->flip()
            ->toArray();

        return view('admin.roles.edit', compact('role', 'permissionsGrouped', 'granted'));
    }

    public function update(Request $request, string $roleName): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $role = Role::where('name', $roleName)->firstOrFail();

        $data = $request->validate([
            'label'         => 'required|string|max:100',
            'description'   => 'nullable|string|max:500',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        // Les rôles super_admin ne peuvent pas avoir leurs permissions modifiées
        if ($roleName !== User::ROLE_SUPER_ADMIN) {
            $role->update([
                'label'       => $data['label'],
                'description' => $data['description'] ?? null,
            ]);

            $this->syncPermissions($roleName, $data['permissions'] ?? []);
        } else {
            // Juste le libellé / description pour super_admin
            $role->update([
                'label'       => $data['label'],
                'description' => $data['description'] ?? null,
            ]);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Rôle « {$role->label} » mis à jour.");
    }

    // ─── Supprimer ────────────────────────────────────────────────────────────

    public function destroy(string $roleName): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $role = Role::where('name', $roleName)->firstOrFail();

        if ($role->is_system) {
            return back()->with('error', 'Les rôles système ne peuvent pas être supprimés.');
        }

        if ($role->userCount() > 0) {
            return back()->with('error', "Impossible de supprimer ce rôle : {$role->userCount()} utilisateur(s) y sont assignés.");
        }

        $role->deleteWithPermissions();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Rôle « {$role->label} » supprimé.");
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function syncPermissions(string $roleName, array $permNames): void
    {
        $permIds = Permission::whereIn('name', $permNames)->pluck('id');

        DB::transaction(function () use ($roleName, $permIds) {
            DB::table('role_permissions')->where('role', $roleName)->delete();
            foreach ($permIds as $id) {
                DB::table('role_permissions')->insert([
                    'role'          => $roleName,
                    'permission_id' => $id,
                ]);
            }
        });

        Permission::clearCache($roleName);
    }

    private function authorizeSuperAdmin(): void
    {
        if (!auth()->user()?->isSuperAdmin()) {
            abort(403, 'Accès réservé au super-administrateur.');
        }
    }
}
