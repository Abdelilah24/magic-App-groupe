@extends('admin.layouts.app')
@section('title', 'Utilisateurs')
@section('page-title', 'Utilisateurs')
@section('page-subtitle', 'Gérez les comptes du panel d\'administration')

@section('header-actions')
    <a href="{{ route('admin.users.create') }}"
       class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nouvel utilisateur
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

@if($filterRole)
<div class="mb-4 bg-blue-50 border border-blue-200 text-blue-800 rounded-xl px-4 py-3 text-sm flex items-center justify-between">
    <span>Filtre actif : rôle <strong>{{ $filterRole->label }}</strong></span>
    <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:text-blue-900 text-xs font-medium">Effacer le filtre</a>
</div>
@endif

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="text-left px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-widest">Utilisateur</th>
                <th class="text-left px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-widest hidden md:table-cell">Email</th>
                <th class="text-center px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-widest">Rôle</th>
                <th class="text-center px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-widest">Statut</th>
                <th class="text-right px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-widest">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($users as $user)
            @php
                $roleColors = match($user->role) {
                    'super_admin' => 'bg-amber-100 text-amber-700 border-amber-200',
                    'admin'       => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                    'staff'       => 'bg-teal-100 text-teal-700 border-teal-200',
                    default       => 'bg-purple-100 text-purple-700 border-purple-200',
                };
                $isMe = $user->id === auth()->id();
            @endphp
            <tr class="hover:bg-gray-50 transition-colors {{ !$user->is_active ? 'opacity-60' : '' }}">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-white text-xs font-bold shrink-0">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 leading-tight">
                                {{ $user->name }}
                                @if($isMe)
                                <span class="ml-1 text-[10px] text-gray-400">(vous)</span>
                                @endif
                            </p>
                            <p class="text-xs text-gray-400 md:hidden">{{ $user->email }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 hidden md:table-cell">
                    <span class="text-gray-600 text-xs">{{ $user->email }}</span>
                </td>
                <td class="px-4 py-4 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $roleColors }}">
                        {{ $user->role_label }}
                    </span>
                </td>
                <td class="px-4 py-4 text-center">
                    @if($user->is_active)
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Actif
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span> Inactif
                    </span>
                    @endif
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center justify-end gap-2" x-data="{ confirmDelete: false }">
                        <a href="{{ route('admin.users.edit', $user) }}"
                           class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 hover:text-gray-900 px-3 py-1.5 rounded-lg border border-gray-200 hover:border-gray-300 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Éditer
                        </a>
                        @if(!$isMe)
                        <button type="button" @click="confirmDelete = true"
                                class="inline-flex items-center gap-1.5 text-xs font-medium text-red-600 hover:text-red-800 px-3 py-1.5 rounded-lg border border-red-100 hover:border-red-300 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Supprimer
                        </button>
                        {{-- Modale confirmation --}}
                        <div x-show="confirmDelete" x-cloak x-transition
                             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
                            <div @click.outside="confirmDelete = false"
                                 class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm mx-4">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Supprimer l'utilisateur</p>
                                        <p class="text-xs text-gray-500">{{ $user->name }}</p>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 mb-5">Cette action est irréversible. L'utilisateur ne pourra plus se connecter.</p>
                                <div class="flex gap-2 justify-end">
                                    <button type="button" @click="confirmDelete = false"
                                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                                        Annuler
                                    </button>
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition">
                                            Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">
                    Aucun utilisateur trouvé.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($users->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $users->links() }}
    </div>
    @endif
</div>

@endsection
