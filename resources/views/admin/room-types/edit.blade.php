@extends('admin.layouts.app')
@section('title', 'Modifier ' . $roomType->name)
@section('page-title', 'Modifier : ' . $roomType->name)

@section('content')
<div class="max-w-xl">
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <form action="{{ route('admin.room-types.update', $roomType) }}" method="POST">
            @csrf @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hôtel *</label>
                    <select name="hotel_id" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        @foreach($hotels as $h)
                        <option value="{{ $h->id }}" {{ old('hotel_id', $roomType->hotel_id) == $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                    <input type="text" name="name" required value="{{ old('name', $roomType->name) }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                </div>
                {{-- Capacité globale --}}
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Personnes min *</label>
                        <input type="number" name="min_persons" required min="1"
                               value="{{ old('min_persons', $roomType->min_persons ?? 1) }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        <p class="text-xs text-gray-400 mt-0.5">Minimum total</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Personnes max *</label>
                        <input type="number" name="max_persons" required min="1"
                               value="{{ old('max_persons', $roomType->max_persons ?? $roomType->capacity) }}"
                               id="editMaxPersons"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        <p class="text-xs text-gray-400 mt-0.5">Maximum total</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock total *</label>
                        <input type="number" name="total_rooms" required min="0" value="{{ old('total_rooms', $roomType->total_rooms) }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        <p class="text-xs text-gray-400 mt-0.5">Nb de chambres</p>
                    </div>
                </div>
                {{-- Capacité adultes / enfants --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max adultes</label>
                        <input type="number" name="max_adults" min="0"
                               value="{{ old('max_adults', $roomType->max_adults) }}"
                               placeholder="Laisser vide = pas de limite"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        <p class="text-xs text-gray-400 mt-0.5">Adultes max par chambre</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max enfants</label>
                        <input type="number" name="max_children" min="0"
                               value="{{ old('max_children', $roomType->max_children) }}"
                               placeholder="Laisser vide = pas de limite"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        <p class="text-xs text-gray-400 mt-0.5">Enfants max par chambre</p>
                    </div>
                </div>
                {{-- Lit bébé --}}
                <label class="flex items-center gap-2 cursor-pointer p-3 bg-blue-50 border border-blue-100 rounded-lg">
                    <input type="checkbox" name="baby_bed_available" value="1"
                        {{ old('baby_bed_available', $roomType->baby_bed_available) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-amber-500">
                    <span class="text-sm text-gray-700 font-medium">🍼 Lit bébé supplémentaire disponible</span>
                    <span class="text-xs text-gray-400 ml-1">(le client pourra en faire la demande)</span>
                </label>
                <input type="hidden" name="capacity" id="editCapacityHidden"
                       value="{{ old('capacity', $roomType->max_persons ?? $roomType->capacity) }}">
                <script>
                    document.getElementById('editMaxPersons').addEventListener('input', function() {
                        document.getElementById('editCapacityHidden').value = this.value;
                    });
                </script>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="2"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">{{ old('description', $roomType->description) }}</textarea>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $roomType->is_active) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-amber-500">
                    <span class="text-sm text-gray-700">Actif</span>
                </label>
            </div>
            <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100">
                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-6 py-2 rounded-lg text-sm">Enregistrer</button>
                <a href="{{ route('admin.room-types.index') }}" class="text-gray-500 text-sm px-4 py-2">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
