@extends('admin.layouts.app')
@section('title', 'Nouveau type de chambre')
@section('page-title', 'Ajouter un type de chambre')

@section('content')
<div class="max-w-xl">
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <form action="{{ route('admin.room-types.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hôtel *</label>
                    <select name="hotel_id" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        <option value="">— Choisir —</option>
                        @foreach($hotels as $h)
                        <option value="{{ $h->id }}" {{ old('hotel_id', request('hotel_id')) == $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
                        @endforeach
                    </select>
                    @error('hotel_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom du type *</label>
                    <input type="text" name="name" required value="{{ old('name') }}" placeholder="Ex : Chambre Double, Suite Junior..."
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                </div>
                {{-- Capacité globale --}}
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Personnes min *</label>
                        <input type="number" name="min_persons" required min="1" value="{{ old('min_persons', 1) }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        <p class="text-xs text-gray-400 mt-0.5">Minimum total</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Personnes max *</label>
                        <input type="number" name="max_persons" id="max_persons" required min="1" value="{{ old('max_persons', 2) }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        <p class="text-xs text-gray-400 mt-0.5">Maximum total</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nb total chambres *</label>
                        <input type="number" name="total_rooms" required min="0" value="{{ old('total_rooms', 0) }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        <p class="text-xs text-gray-400 mt-0.5">Stock disponible</p>
                    </div>
                </div>
                {{-- Capacité adultes / enfants --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max adultes</label>
                        <input type="number" name="max_adults" min="0" value="{{ old('max_adults') }}"
                            placeholder="Laisser vide = pas de limite"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        <p class="text-xs text-gray-400 mt-0.5">Adultes max par chambre</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max enfants</label>
                        <input type="number" name="max_children" min="0" value="{{ old('max_children') }}"
                            placeholder="Laisser vide = pas de limite"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        <p class="text-xs text-gray-400 mt-0.5">Enfants max par chambre</p>
                    </div>
                </div>
                {{-- Lit bébé --}}
                <label class="flex items-center gap-2 cursor-pointer p-3 bg-blue-50 border border-blue-100 rounded-lg">
                    <input type="checkbox" name="baby_bed_available" value="1" {{ old('baby_bed_available') ? 'checked' : '' }}
                        class="rounded border-gray-300 text-amber-500">
                    <span class="text-sm text-gray-700 font-medium">🍼 Lit bébé supplémentaire disponible</span>
                    <span class="text-xs text-gray-400 ml-1">(le client pourra en faire la demande)</span>
                </label>
                {{-- Champ capacity caché — synchronisé avec max_persons via JS --}}
                <input type="hidden" name="capacity" id="capacityHidden" value="{{ old('capacity', 2) }}">
                <script>
                    document.querySelector('[name="max_persons"]').addEventListener('input', function() {
                        document.getElementById('capacityHidden').value = this.value;
                    });
                </script>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="2"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"></textarea>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-amber-500">
                    <span class="text-sm text-gray-700">Actif</span>
                </label>
            </div>
            <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100">
                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-6 py-2 rounded-lg text-sm">Créer</button>
                <a href="{{ route('admin.room-types.index') }}" class="text-gray-500 text-sm px-4 py-2">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
