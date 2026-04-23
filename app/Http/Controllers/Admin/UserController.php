<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    // ─── Liste ────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $this->authorize();

        $query = User::orderBy('name')
            ->select('id', 'name', 'email', 'role', 'is_active', 'created_at');

        // Filtre par rôle (utilisé depuis la page liste des rôles)
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        $users = $query->paginate(25)->withQueryString();

        $filterRole = $request->filled('role')
            ? \App\Models\Role::where('name', $request->input('role'))->first()
            : null;

        return view('admin.users.index', compact('users', 'filterRole'));
    }

    // ─── Créer ────────────────────────────────────────────────────────────────

    public function create(): View
    {
        $this->authorize();

        $roles = $this->availableRoles();

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize();

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'role'     => ['required', 'string', Rule::in($this->allowedRoleNames())],
            'is_active'=> 'boolean',
        ]);

        // Seul le super_admin peut créer un autre super_admin
        if ($data['role'] === User::ROLE_SUPER_ADMIN && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'role'      => $data['role'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Utilisateur « {$user->name} » créé avec succès.");
    }

    // ─── Éditer ───────────────────────────────────────────────────────────────

    public function edit(User $user): View
    {
        $this->authorize();
        $this->guardSuperAdminEdit($user);

        $roles = $this->availableRoles();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize();
        $this->guardSuperAdminEdit($user);

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', Password::min(8)->letters()->numbers()],
            'role'     => ['required', 'string', Rule::in($this->allowedRoleNames())],
            'is_active'=> 'boolean',
        ]);

        // Seul le super_admin peut attribuer le rôle super_admin
        if ($data['role'] === User::ROLE_SUPER_ADMIN && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        // Empêcher de se retirer soi-même le rôle super_admin
        if ($user->id === auth()->id() && $user->isSuperAdmin() && $data['role'] !== User::ROLE_SUPER_ADMIN) {
            return back()->with('error', 'Vous ne pouvez pas retirer votre propre rôle super-administrateur.');
        }

        $updateData = [
            'name'      => $data['name'],
            'email'     => $data['email'],
            'role'      => $data['role'],
            'is_active' => $request->boolean('is_active', true),
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Utilisateur « {$user->name} » mis à jour.");
    }

    // ─── Supprimer ────────────────────────────────────────────────────────────

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize();

        // Impossible de se supprimer soi-même
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        // Seul un super_admin peut supprimer un autre super_admin
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Utilisateur « {$name} » supprimé.");
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Rôles disponibles dans le sélecteur.
     * Le super_admin peut assigner tous les rôles.
     * Les admins ne peuvent pas assigner super_admin.
     */
    private function availableRoles()
    {
        if (auth()->user()->isSuperAdmin()) {
            return Role::orderBy('sort_order')->orderBy('label')->get();
        }

        return Role::where('name', '!=', User::ROLE_SUPER_ADMIN)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }

    private function allowedRoleNames(): array
    {
        return $this->availableRoles()->pluck('name')->toArray();
    }

    private function authorize(): void
    {
        $user = auth()->user();
        if (!$user?->isSuperAdmin() && !$user?->hasPermission('users.manage')) {
            abort(403, 'Accès non autorisé.');
        }
    }

    /**
     * Empêche un non-super_admin de modifier un super_admin.
     */
    private function guardSuperAdminEdit(User $target): void
    {
        if ($target->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Seul un super-administrateur peut modifier un autre super-administrateur.');
        }
    }
}
