@extends('admin.layouts.app')
@section('title', 'Rôles & Permissions')
@section('page-title', 'Rôles & Permissions')
@section('page-subtitle', 'Gérez les rôles et leurs accès')

@section('header-actions')
    <a href="{{ route('admin.roles.create') }}"
       class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nouveau rôle
    </a>
@endsection

@section('content')

@if(session('success'))
<div class="mb-6 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
    <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
    <svg class="w-4 h-4 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
    {{ session('error') }}
</div>
@endif

<div class="mb-6 bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 flex items-start gap-3">
    <div class="w-9 h-9 rounded-full bg-amber-400 flex items-center justify-center shrink-0">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
    </div>
    <div>
        <p class="text-sm font-semibold text-amber-900">Seul le Super Administrateur peut gérer les rôles</p>
        <p class="text-xs text-amber-700 mt-0.5">
            Les rôles système (Super Administrateur, Administrateur, Staff) ne peuvent pas être supprimés.
            Vous pouvez créer des rôles personnalisés et définir leurs permissions.
        </p>
    </div>
</div>

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="text-left px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-widest">Rôle</th>
                <th class="text-left px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-widest hidden sm:table-cell">Description</th>
                <th class="text-center px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-widest">Utilisateurs</th>
                <th class="text-center px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-widest">Permissions</th>
                <th class="text-right px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-widest">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($roles as $role)
            @php
                $isSuper = $role->name === \App\Models\User::ROLE_SUPER_ADMIN;
                $colors = match($role->name) {
                    'super_admin' => 'bg-amber-100 text-amber-700 border-amber-200',
                    'admin'       => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                    'staff'       => 'bg-teal-100 text-teal-700 border-teal-200',
                    default       => 'bg-purple-100 text-purple-700 border-purple-200',
                };
            @endphp
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $colors }}">
                            {{ $role->label }}
                        </span>
                        @if($role->is_system)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 text-gray-500 rounded text-[10px] font-medium">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                            Système
                        </span>
                        @endif
                    </div>
                    <p class="text-[10px] text-gray-400 mt-0.5 font-mono">{{ $role->name }}</p>
                </td>
                <td class="px-6 py-4 hidden sm:table-cell">
                    <p class="text-gray-500 text-xs leading-relaxed">{{ $role->description ?: '—' }}</p>
                </td>
                <td class="px-4 py-4 text-center">
                    <a href="{{ route('admin.users.index', ['role' => $role->name]) }}"
                       class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ $role->user_count > 0 ? 'bg-blue-50 text-blue-700 hover:bg-blue-100' : 'bg-gray-50 text-gray-400' }} text-xs font-bold transition">
                        {{ $role->user_count }}
                    </a>
                </td>
                <td class="px-4 py-4 text-center">
                    @if($isSuper)
                        <span class="text-xs text-amber-600 font-semibold">Toutes</span>
                    @else
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ $role->permission_count > 0 ? 'bg-green-50 text-green-700' : 'bg-gray-50 text-gray-400' }} text-xs font-bold">
                            {{ $role->permission_count }}
                        </span>
                    @endif
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center justify-end gap-2" x-data="{ confirmDelete: false }">
                        <a href="{{ route('admin.roles.edit', $role->name) }}"
                           class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 hover:text-gray-900 px-3 py-1.5 rounded-lg border border-gray-200 hover:border-gray-300 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Éditer
                        </a>
                        @if($role->canBeDeleted())
                        <button type="button" @click="confirmDelete = true"
                                class="inline-flex items-center gap-1.5 text-xs font-medium text-red-600 hover:text-red-800 px-3 py-1.5 rounded-lg border border-red-100 hover:border-red-300 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Supprimer
                        </button>
                        {{-- Modale suppression --}}
                        <div x-show="confirmDelete" x-cloak x-transition
                             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
                            <div @click.outside="confirmDelete = false"
                                 class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm mx-4">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Supprimer le rôle</p>
                                        <p class="text-xs text-gray-500">« {{ $role->label }} »</p>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 mb-5">Cette action est irréversible. Toutes les permissions associées à ce rôle seront également supprimées.</p>
                                <div class="flex gap-2 justify-end">
                                    <button type="button" @click="confirmDelete = false"
                                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                                        Annuler
                                    </button>
                                    <form action="{{ route('admin.roles.destroy', $role->name) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition">
                                            Supprimer définitivement
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @else
                        <span class="text-[10px] text-gray-300 px-2">{{ $role->is_system ? 'Système' : $role->user_count.' user(s)' }}</span>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection
