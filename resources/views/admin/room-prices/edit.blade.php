@extends('admin.layouts.app')
@section('title', 'Modifier tarif')
@section('page-title', 'Modifier le tarif')

@section('content')
<div class="max-w-xl">
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <form action="{{ route('admin.room-prices.update', $roomPrice) }}" method="POST">
            @csrf @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hôtel</label>
                    <select name="hotel_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        @foreach($hotels as $h)
                        <option value="{{ $h->id }}" {{ $roomPrice->hotel_id == $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type de chambre</label>
                    <select name="room_type_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        @foreach($roomTypes as $rt)
                        <option value="{{ $rt->id }}" {{ $roomPrice->room_type_id == $rt->id ? 'selected' : '' }}>{{ $rt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date début</label>
                        <input type="date" name="date_from" value="{{ old('date_from', $roomPrice->date_from->format('Y-m-d')) }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date fin</label>
                        <input type="date" name="date_to" value="{{ old('date_to', $roomPrice->date_to->format('Y-m-d')) }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prix/nuit (MAD)</label>
                        <input type="number" name="price_per_night" min="0" step="0.01"
                            value="{{ old('price_per_night', $roomPrice->price_per_night) }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Libellé</label>
                        <input type="text" name="label" value="{{ old('label', $roomPrice->label) }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ $roomPrice->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-amber-500">
                    <span class="text-sm text-gray-700">Actif</span>
                </label>
            </div>
            <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100">
                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-6 py-2 rounded-lg text-sm">Enregistrer</button>
                <a href="{{ route('admin.room-prices.index') }}" class="text-gray-500 text-sm px-4 py-2">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
