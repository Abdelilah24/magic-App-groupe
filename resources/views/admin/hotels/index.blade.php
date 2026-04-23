@extends('admin.layouts.app')
@section('title', 'Hôtels')
@section('page-title', 'Hôtels')

@section('header-actions')
    <a href="{{ route('admin.hotels.create') }}" class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium px-4 py-2 rounded-lg">+ Ajouter un hôtel</a>
@endsection

@section('content')
<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
    <table class="min-w-full divide-y divide-gray-100">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ville</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Étoiles</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Chambres</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Réservations</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                <th></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            @forelse($hotels as $hotel)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-900">{{ $hotel->name }}</td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ $hotel->city }}</td>
                <td class="px-4 py-3 text-sm">{{ $hotel->stars_label }}</td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ $hotel->room_types_count }}</td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ $hotel->reservations_count }}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $hotel->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $hotel->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                </td>
                <td class="px-4 py-3 flex gap-2">
                    <a href="{{ route('admin.hotels.show', $hotel) }}" class="text-amber-600 hover:underline text-sm">Voir</a>
                    <a href="{{ route('admin.hotels.edit', $hotel) }}" class="text-blue-600 hover:underline text-sm">Éditer</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="py-12 text-center text-gray-400">Aucun hôtel.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t">{{ $hotels->links() }}</div>
</div>
@endsection
