@extends('admin.layouts.app')
@section('title', 'Nouvel utilisateur')
@section('page-title', 'Nouvel utilisateur')
@section('page-subtitle', 'Créez un nouveau compte d\'accès au panel')

@section('content')

<div class="max-w-2xl">
<form action="{{ route('admin.users.store') }}" method="POST">
@csrf

@if($errors->any())
<div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm">
    <p class="font-medium mb-1">Veuillez corriger les erreurs suivantes :</p>
    <ul class="list-disc list-inside space-y-0.5 text-xs">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 space-y-5">

    {{-- Nom --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Nom complet <span class="text-red-500">*</span>
        </label>
        <input type="text" name="name" value="{{ old('name') }}" autofocus
               placeholder="Jean Dupont"
               class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none transition
                      @error('name') border-red-300 @enderror">
        @error('name')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Email --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Adresse email <span class="text-red-500">*</span>
        </label>
        <input type="email" name="email" value="{{ old('email') }}"
               placeholder="jean.dupont@example.com"
               class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none transition
                      @error('email') border-red-300 @enderror">
        @error('email')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Mot de passe --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Mot de passe <span class="text-red-500">*</span>
        </label>
        <input type="password" name="password"
               placeholder="Minimum 8 caractères, lettres et chiffres"
               class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none transition
                      @error('password') border-red-300 @enderror">
        @error('password')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Rôle --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Rôle <span class="text-red-500">*</span>
        </label>
        <select name="role"
                class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none transition
                       @error('role') border-red-300 @enderror">
            <option value="">— Sélectionnez un rôle —</option>
            @foreach($roles as $role)
            <option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>
                {{ $role->label }}
                @if($role->is_system) (système) @endif
            </option>
            @endforeach
        </select>
        @error('role')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
        <p class="text-xs text-gray-400 mt-1">
            Le rôle détermine les permissions de l'utilisateur dans le panel.
        </p>
    </div>

    {{-- Statut --}}
    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-100">
        <div>
            <p class="text-sm font-medium text-gray-700">Compte actif</p>
            <p class="text-xs text-gray-400 mt-0.5">Un compte inactif ne peut pas se connecter.</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1"
                   {{ old('is_active', '1') === '1' ? 'checked' : '' }}
                   class="sr-only peer">
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
        </label>
    </div>

</div>

<div class="mt-4 flex gap-3">
    <button type="submit"
            class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-6 py-2.5 rounded-xl transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        Créer l'utilisateur
    </button>
    <a href="{{ route('admin.users.index') }}"
       class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
        Annuler
    </a>
</div>

</form>
</div>

@endsection
