@extends('admin.layouts.app')
@section('title', 'Statuts agences')
@section('page-title', 'Statuts & Tarification agences')
@section('page-subtitle', 'Définissez les remises applicables selon le type de client')

@section('header-actions')
    <a href="{{ route('admin.agency-statuses.create') }}"
       class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
        + Nouveau statut
    </a>
@endsection

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 mb-6 text-sm">
    ✓ {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 mb-6 text-sm">
    {{ session('error') }}
</div>
@endif

<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-5 py-3 text-left font-medium text-gray-500">#</th>
                <th class="px-5 py-3 text-left font-medium text-gray-500">Nom</th>
                <th class="px-5 py-3 text-left font-medium text-gray-500">Remise</th>
                <th class="px-5 py-3 text-left font-medium text-gray-500">Formule</th>
                <th class="px-5 py-3 text-left font-medium text-gray-500">Agences</th>
                <th class="px-5 py-3 text-left font-medium text-gray-500">Statut</th>
                <th class="px-5 py-3 text-left font-medium text-gray-500">Défaut</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($statuses as $s)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3 text-gray-400">{{ $s->sort_order }}</td>
                <td class="px-5 py-3 font-semibold text-gray-900">{{ $s->name }}</td>
                <td class="px-5 py-3">
                    @if($s->discount_percent > 0)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                            −{{ number_format($s->discount_percent, 0) }} %
                        </span>
                    @else
                        <span class="text-gray-400 text-xs">Aucune</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-gray-600">
                    @if($s->discount_percent > 0)
                        Tarif de base × {{ number_format(1 - $s->discount_percent/100, 2) }}
                    @else
                        Tarif de base (100 %)
                    @endif
                </td>
                <td class="px-5 py-3 text-gray-700">{{ $s->agencies_count ?? $s->agencies()->count() }}</td>
                <td class="px-5 py-3">
                    @if($s->is_active)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">Actif</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-500">Inactif</span>
                    @endif
                </td>
                <td class="px-5 py-3">
                    @if($s->is_default)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-700">★ Défaut</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-right">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('admin.agency-statuses.edit', $s) }}"
                           class="text-amber-600 hover:text-amber-800 text-xs font-medium">Modifier</a>
                        <form action="{{ route('admin.agency-statuses.destroy', $s) }}" method="POST"
                              onsubmit="return confirm('Supprimer ce statut ?')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-700 text-xs">Supprimer</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-5 py-8 text-center text-gray-400 text-sm">
                    Aucun statut configuré. <a href="{{ route('admin.agency-statuses.create') }}" class="text-amber-600 hover:underline">Créer le premier →</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800">
    <strong>ℹ Comment ça marche :</strong> Chaque agence se voit attribuer un statut lors de son inscription.
    Le tarif final = Tarif de base calendrier − remise du statut.
    Ex : tarif 1 000 MAD × statut Agence de voyages (−10 %) = <strong>900 MAD</strong>.
</div>
@endsection
