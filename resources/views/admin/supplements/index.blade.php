@extends('admin.layouts.app')
@section('title', 'Suppléments')
@section('page-title', 'Suppléments')
@section('page-subtitle', 'Dîners de gala, événements et extras par hôtel')

@section('header-actions')
<a href="{{ route('admin.supplements.create') }}"
   class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium px-4 py-2 rounded-lg">
    + Nouveau supplément
</a>
@endsection

@section('content')
<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">

    {{-- Filtres --}}
    <div class="p-4 border-b border-gray-100 flex flex-wrap gap-3 items-center">
        <form method="GET" class="flex gap-2">
            <select name="hotel_id" onchange="this.form.submit()"
                class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                <option value="">Tous les hôtels</option>
                @foreach($hotels as $hotel)
                <option value="{{ $hotel->id }}" {{ request('hotel_id') == $hotel->id ? 'selected' : '' }}>{{ $hotel->name }}</option>
                @endforeach
            </select>
            <select name="status" onchange="this.form.submit()"
                class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                <option value="">Tous</option>
                <option value="mandatory" {{ request('status') === 'mandatory' ? 'selected' : '' }}>Obligatoires</option>
                <option value="optional"  {{ request('status') === 'optional'  ? 'selected' : '' }}>Optionnels</option>
            </select>
        </form>
    </div>

    @if($supplements->isEmpty())
    <div class="p-12 text-center text-gray-400">
        <p class="text-4xl mb-3">🎭</p>
        <p class="font-medium">Aucun supplément</p>
        <p class="text-sm mt-1">Créez des suppléments comme les dîners de gala, soirées thématiques, etc.</p>
    </div>
    @else
    <table class="min-w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 bg-gray-50">
                <th class="px-4 py-3 text-left font-medium text-gray-500">Supplément</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Hôtel</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Période</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Statut</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Prix adulte</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Prix enfant</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Prix bébé</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($supplements as $sup)
            <tr class="{{ ! $sup->is_active ? 'opacity-50' : '' }}">
                <td class="px-4 py-3">
                    <p class="font-medium text-gray-900">{{ $sup->title }}</p>
                    @if($sup->description)
                    <p class="text-xs text-gray-400 truncate max-w-xs">{{ $sup->description }}</p>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $sup->hotel->name }}</td>
                <td class="px-4 py-3 text-gray-700 font-medium">
                    @if($sup->date_from)
                        {{ $sup->date_from->format('d/m/Y') }}
                        @if($sup->date_to && $sup->date_from->ne($sup->date_to))
                            <span class="text-gray-400">→</span> {{ $sup->date_to->format('d/m/Y') }}
                        @endif
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $sup->status === 'mandatory' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                        {{ $sup->status === 'mandatory' ? '🔴 Obligatoire' : '🔵 Optionnel' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-700">{{ number_format($sup->price_adult, 2, ',', ' ') }} MAD</td>
                <td class="px-4 py-3 text-gray-700">{{ number_format($sup->price_child, 2, ',', ' ') }} MAD</td>
                <td class="px-4 py-3 text-gray-700">{{ number_format($sup->price_baby,  2, ',', ' ') }} MAD</td>
                <td class="px-4 py-3">
                    <div class="flex gap-2 justify-end">
                        <a href="{{ route('admin.supplements.edit', $sup) }}"
                           class="text-xs text-amber-600 hover:text-amber-800 font-medium">Modifier</a>
                        <form action="{{ route('admin.supplements.destroy', $sup) }}" method="POST"
                              onsubmit="return confirm('Supprimer ce supplément ?')">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-500 hover:text-red-700">Supprimer</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-gray-100">{{ $supplements->links() }}</div>
    @endif
</div>
@endsection
