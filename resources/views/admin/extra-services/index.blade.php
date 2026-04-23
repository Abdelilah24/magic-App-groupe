@extends('admin.layouts.app')
@section('title', 'Services Extras')
@section('page-title', 'Services Extras')
@section('page-subtitle', 'Catalogue des services facturables ajoutables aux réservations')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
        <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Formulaire ajout --}}
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <h2 class="text-sm font-bold text-gray-900">Nouveau service extra</h2>
        </div>
        <form action="{{ route('admin.extra-services.store') }}" method="POST" class="px-6 py-5">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Nom <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           placeholder="Ex : Transport aéroport"
                           class="w-full border @error('name') border-red-400 bg-red-50 @else border-gray-200 @enderror rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                    @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Prix unitaire (MAD) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" value="{{ old('price', '0') }}" min="0" step="0.01" required
                           class="w-full border @error('price') border-red-400 bg-red-50 @else border-gray-200 @enderror rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                    @error('price')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-end gap-3">
                    <label class="flex items-center gap-2 text-sm text-gray-700 mb-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked
                               class="w-4 h-4 rounded text-amber-500 focus:ring-amber-400">
                        Actif
                    </label>
                    <button type="submit"
                            class="ml-auto inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Ajouter
                    </button>
                </div>
            </div>
            <div class="mt-3">
                <label class="block text-xs font-medium text-gray-700 mb-1.5">Description</label>
                <textarea name="description" rows="2" placeholder="Description optionnelle…"
                          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 resize-none">{{ old('description') }}</textarea>
            </div>
        </form>
    </div>

    {{-- Liste --}}
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-bold text-gray-900">Catalogue — {{ $extras->count() }} service{{ $extras->count() > 1 ? 's' : '' }}</h2>
        </div>

        @if($extras->isEmpty())
        <div class="px-6 py-12 text-center text-gray-400 text-sm">Aucun service extra pour le moment.</div>
        @else
        <div class="divide-y divide-gray-100">
            @foreach($extras as $extra)
            <div class="px-6 py-4 flex items-start gap-4" x-data="{ editing: false }">
                <div class="flex-1 min-w-0">

                    {{-- Affichage --}}
                    <div x-show="!editing">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-sm font-semibold text-gray-900">{{ $extra->name }}</p>
                            @if($extra->is_active)
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Actif</span>
                            @else
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">Inactif</span>
                            @endif
                            <span class="text-sm font-bold text-amber-600 ml-auto">{{ number_format($extra->price, 2, ',', ' ') }} MAD</span>
                        </div>
                        @if($extra->description)
                        <p class="text-xs text-gray-500 mt-0.5">{{ $extra->description }}</p>
                        @endif
                    </div>

                    {{-- Formulaire d'édition --}}
                    <form x-show="editing" action="{{ route('admin.extra-services.update', $extra) }}" method="POST" class="space-y-3">
                        @csrf @method('PUT')
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <input type="text" name="name" value="{{ $extra->name }}" required
                                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                            <input type="number" name="price" value="{{ $extra->price }}" min="0" step="0.01" required
                                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="is_active" value="1" {{ $extra->is_active ? 'checked' : '' }}
                                       class="w-4 h-4 rounded text-amber-500">
                                Actif
                            </label>
                        </div>
                        <textarea name="description" rows="2"
                                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 resize-none">{{ $extra->description }}</textarea>
                        <div class="flex gap-2">
                            <button type="submit" class="text-xs font-semibold bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-lg transition-colors">Enregistrer</button>
                            <button type="button" @click="editing = false" class="text-xs text-gray-500 hover:text-gray-700 px-3 py-1.5 rounded-lg border border-gray-200">Annuler</button>
                        </div>
                    </form>
                </div>

                <div class="flex items-center gap-2 shrink-0 mt-0.5" x-show="!editing">
                    <button type="button" @click="editing = true"
                            class="text-xs text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 px-2.5 py-1.5 rounded-lg transition-colors font-medium">
                        Modifier
                    </button>
                    <form action="{{ route('admin.extra-services.destroy', $extra) }}" method="POST"
                          onsubmit="return confirm('Supprimer « {{ $extra->name }} » ?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 px-2.5 py-1.5 rounded-lg transition-colors font-medium">
                            Supprimer
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

</div>
@endsection
