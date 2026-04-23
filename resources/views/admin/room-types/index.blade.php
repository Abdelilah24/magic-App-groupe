@extends('admin.layouts.app')
@section('title', 'Types de chambres')
@section('page-title', 'Types de chambres')

@section('header-actions')
    <a href="{{ route('admin.room-types.create') }}" class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium px-4 py-2 rounded-lg">+ Ajouter</a>
@endsection

@section('content')
{{-- Filtre hôtel --}}
<form method="GET" class="mb-4 flex gap-3">
    <select name="hotel_id" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
        <option value="">Tous les hôtels</option>
        @foreach($hotels as $h)
        <option value="{{ $h->id }}" {{ $hotelId == $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
        @endforeach
    </select>
    <button class="bg-gray-100 hover:bg-gray-200 text-sm px-4 py-2 rounded-lg">Filtrer</button>
</form>

<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
    <table class="min-w-full divide-y divide-gray-100">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hôtel</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Capacité</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                <th></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            @forelse($roomTypes as $rt)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-900">{{ $rt->name }}</td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ $rt->hotel->name }}</td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ $rt->capacity }} pers.</td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ $rt->total_rooms }}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $rt->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $rt->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                </td>
                <td class="px-4 py-3 flex gap-2">
                    <a href="{{ route('admin.room-types.edit', $rt) }}" class="text-blue-600 hover:underline text-sm">Éditer</a>
                    <a href="{{ route('admin.room-prices.index', ['hotel_id' => $rt->hotel_id]) }}" class="text-amber-600 hover:underline text-sm">Tarifs</a>
                    <a href="{{ route('admin.occupancy-configs.index', ['hotel_id' => $rt->hotel_id]) }}" class="text-purple-600 hover:underline text-sm">Occupations</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="py-12 text-center text-gray-400">Aucun type de chambre.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t">{{ $roomTypes->links() }}</div>
</div>
@endsection
