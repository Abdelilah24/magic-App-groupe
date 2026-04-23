@extends('admin.layouts.app')
@section('title', 'Éditer — '.$role->label)
@section('page-title', 'Éditer le rôle')
@section('page-subtitle', $role->label . ($role->is_system ? ' — rôle système' : ''))

@section('content')

<form action="{{ route('admin.roles.update', $role->name) }}" method="POST">
@csrf @method('PUT')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Colonne gauche : infos du rôle --}}
    <div class="lg:col-span-1 space-y-4">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
            <h2 class="text-sm font-bold text-gray-700 mb-4">Informations du rôle</h2>

            {{-- Badge système --}}
            @if($role->is_system)
            <div class="mb-4 flex items-center gap-2 bg-gray-50 border border-gray-100 rounded-xl px-3 py-2.5 text-xs text-gray-500">
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                Rôle système — l'identifiant ne peut pas être modifié.
            </div>
            @endif

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">
                        Identifiant technique
                    </label>
                    <input type="text" value="{{ $role->name }}" disabled
                           class="w-full rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-mono text-gray-400">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">
                        Nom affiché <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="label" value="{{ old('label', $role->label) }}"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none transition
                                  @error('label') border-red-300 @enderror">
                    @error('label')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Description</label>
                    <textarea name="description" rows="3"
                              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none transition resize-none">{{ old('description', $role->description) }}</textarea>
                </div>

                {{-- Stats --}}
                <div class="grid grid-cols-2 gap-3 pt-2">
                    <div class="bg-blue-50 rounded-xl px-4 py-3 text-center">
                        <p class="text-lg font-bold text-blue-700">{{ $role->userCount() }}</p>
                        <p class="text-xs text-blue-500">utilisateur(s)</p>
                    </div>
                    <div class="bg-green-50 rounded-xl px-4 py-3 text-center">
                        @if($role->name === \App\Models\User::ROLE_SUPER_ADMIN)
                            <p class="text-lg font-bold text-amber-600">∞</p>
                            <p class="text-xs text-amber-500">permissions</p>
                        @else
                            <p class="text-lg font-bold text-green-700">{{ $role->permissionCount() }}</p>
                            <p class="text-xs text-green-500">permission(s)</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-100 flex gap-2">
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Enregistrer
                </button>
                <a href="{{ route('admin.roles.index') }}"
                   class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                    Retour
                </a>
            </div>
        </div>
    </div>

    {{-- Colonne droite : matrice de permissions --}}
    <div class="lg:col-span-2">
        @if($role->name === \App\Models\User::ROLE_SUPER_ADMIN)
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 flex items-start gap-3">
            <div class="w-9 h-9 rounded-full bg-amber-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-amber-900">Accès total permanent</p>
                <p class="text-xs text-amber-700 mt-0.5">Le Super Administrateur possède toutes les permissions de manière permanente. Elles ne peuvent pas être restreintes.</p>
            </div>
        </div>
        @else
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-700">Permissions</h2>
                <div class="flex gap-2">
                    <button type="button" onclick="toggleAllPerms(true)"
                            class="text-xs text-gray-500 hover:text-gray-700 px-2 py-1 rounded hover:bg-gray-100 transition">
                        Tout cocher
                    </button>
                    <button type="button" onclick="toggleAllPerms(false)"
                            class="text-xs text-gray-500 hover:text-gray-700 px-2 py-1 rounded hover:bg-gray-100 transition">
                        Tout décocher
                    </button>
                </div>
            </div>

            <div class="divide-y divide-gray-50">
                @foreach($permissionsGrouped as $group => $permissions)
                <div class="px-6 py-4">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">{{ $group }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach($permissions as $perm)
                        @php $isGranted = isset($granted[$perm->name]); @endphp
                        <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-colors
                                      {{ $isGranted ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-100' }}
                                      hover:border-gray-300"
                               x-data>
                            <input type="checkbox"
                                   name="permissions[]"
                                   value="{{ $perm->name }}"
                                   {{ $isGranted ? 'checked' : '' }}
                                   class="mt-0.5 rounded border-gray-300 text-green-600 focus:ring-green-500 shrink-0 perm-cb"
                                   onchange="updateLabel(this)">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-800 leading-tight">{{ $perm->label }}</p>
                                @if($perm->description)
                                <p class="text-xs text-gray-400 mt-0.5">{{ $perm->description }}</p>
                                @endif
                                <p class="text-[10px] font-mono text-gray-300 mt-0.5">{{ $perm->name }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Enregistrer les permissions
                </button>
            </div>
        </div>
        @endif
    </div>

</div>
</form>

<script>
function toggleAllPerms(state) {
    document.querySelectorAll('.perm-cb').forEach(cb => {
        cb.checked = state;
        updateLabel(cb);
    });
}
function updateLabel(cb) {
    const label = cb.closest('label');
    if (!label) return;
    if (cb.checked) {
        label.classList.remove('bg-gray-50', 'border-gray-100');
        label.classList.add('bg-green-50', 'border-green-200');
    } else {
        label.classList.remove('bg-green-50', 'border-green-200');
        label.classList.add('bg-gray-50', 'border-gray-100');
    }
}
</script>

@endsection
