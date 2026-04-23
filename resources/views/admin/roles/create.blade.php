@extends('admin.layouts.app')
@section('title', 'Nouveau rôle')
@section('page-title', 'Nouveau rôle')
@section('page-subtitle', 'Créez un rôle personnalisé avec ses permissions')

@section('content')

<form action="{{ route('admin.roles.store') }}" method="POST">
@csrf

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Colonne gauche : infos du rôle --}}
    <div class="lg:col-span-1 space-y-4">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
            <h2 class="text-sm font-bold text-gray-700 mb-4">Informations du rôle</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">
                        Nom affiché <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="label" value="{{ old('label') }}"
                           placeholder="Ex: Comptable, Commercial…"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none transition
                                  @error('label') border-red-300 @enderror">
                    @error('label')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">
                        Identifiant technique
                        <span class="text-gray-400 font-normal">(optionnel — auto-généré sinon)</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           placeholder="Ex: comptable, commercial_senior…"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm font-mono focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none transition
                                  @error('name') border-red-300 @enderror">
                    <p class="text-[10px] text-gray-400 mt-1">Lettres minuscules, chiffres et underscores uniquement.</p>
                    @error('name')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Description</label>
                    <textarea name="description" rows="3"
                              placeholder="Décrivez le périmètre d'action de ce rôle…"
                              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none transition resize-none">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-100 flex gap-2">
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Créer le rôle
                </button>
                <a href="{{ route('admin.roles.index') }}"
                   class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                    Annuler
                </a>
            </div>
        </div>
    </div>

    {{-- Colonne droite : matrice de permissions --}}
    <div class="lg:col-span-2">
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
                        <label class="flex items-start gap-3 p-3 rounded-xl border bg-gray-50 border-gray-100 hover:border-gray-300 cursor-pointer transition-colors group"
                               x-data>
                            <input type="checkbox"
                                   name="permissions[]"
                                   value="{{ $perm->name }}"
                                   {{ old('permissions') && in_array($perm->name, old('permissions', [])) ? 'checked' : '' }}
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
        </div>
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
// Initialiser les couleurs au chargement
document.querySelectorAll('.perm-cb:checked').forEach(cb => updateLabel(cb));
</script>

@endsection
