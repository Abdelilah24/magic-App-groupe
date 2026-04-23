@extends('admin.layouts.app')
@section('title', 'Historique des tarifs')
@section('page-title', 'Historique des tarifs')
@section('page-subtitle', 'Toutes les modifications de prix enregistrées')

@section('header-actions')
    <a href="{{ route('admin.room-prices.table', ['hotel_id' => $hotelId]) }}"
       class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg flex items-center gap-1.5">
        ← Retour au tableau tarifaire
    </a>
@endsection

@section('content')
<div class="space-y-4">

    {{-- Sélecteur hôtel --}}
    <div class="flex flex-wrap items-center gap-3">
        <form method="GET" action="{{ route('admin.room-prices.history') }}" class="flex items-center gap-2">
            <select name="hotel_id" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-amber-400 focus:outline-none">
                @foreach($hotels as $h)
                    <option value="{{ $h->id }}" {{ $hotelId == $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
                @endforeach
            </select>
        </form>
        <span class="text-xs text-gray-400">{{ $history->total() }} modification(s) au total</span>
    </div>

    {{-- Table historique --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 uppercase tracking-wide text-[10px]">
                        <th class="px-4 py-3 text-left">Date modification</th>
                        <th class="px-4 py-3 text-left">Chambre / Config</th>
                        <th class="px-4 py-3 text-left">Code config</th>
                        <th class="px-4 py-3 text-left">Période tarifaire</th>
                        <th class="px-4 py-3 text-left">Libellé</th>
                        <th class="px-4 py-3 text-right">Ancien tarif</th>
                        <th class="px-4 py-3 text-right">Nouveau tarif</th>
                        <th class="px-4 py-3 text-right">Variation</th>
                        <th class="px-4 py-3 text-right">%</th>
                        <th class="px-4 py-3 text-left">Modifié par</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($history as $h)
                    <tr class="hover:bg-amber-50/30 transition-colors">
                        <td class="px-4 py-2.5 text-gray-400 whitespace-nowrap">
                            {{ $h->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-2.5">
                            <div class="font-semibold text-gray-800">{{ $h->occupancyConfig?->roomType?->name ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-2.5">
                            <span class="font-mono text-[10px] font-bold bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded">
                                {{ $h->occupancyConfig?->code ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($h->date_from)->format('d/m/Y') }}
                            → {{ \Carbon\Carbon::parse($h->date_to)->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-2.5 text-gray-400 text-[11px]">
                            {{ $h->label ?: '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-right text-gray-400 whitespace-nowrap">
                            @if($h->old_price !== null)
                                {{ number_format($h->old_price, 2, ',', ' ') }} MAD
                            @else
                                <span class="text-green-500 font-semibold">Nouveau</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-right font-semibold text-gray-800 whitespace-nowrap">
                            {{ number_format($h->new_price, 2, ',', ' ') }} MAD
                        </td>
                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                            @if($h->delta !== null)
                                <span class="font-semibold {{ $h->delta > 0 ? 'text-red-500' : ($h->delta < 0 ? 'text-green-600' : 'text-gray-400') }}">
                                    {{ $h->delta > 0 ? '+' : '' }}{{ number_format($h->delta, 2, ',', ' ') }} MAD
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                            @if($h->delta !== null && $h->old_price > 0)
                                @php $pct = round($h->delta / $h->old_price * 100, 1); @endphp
                                <span class="text-[11px] {{ $pct > 0 ? 'text-red-400' : ($pct < 0 ? 'text-green-500' : 'text-gray-400') }}">
                                    {{ $pct > 0 ? '+' : '' }}{{ $pct }}%
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-gray-500">
                            {{ $h->changed_by_name ?? 'Système' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-gray-400 text-sm">
                            Aucune modification enregistrée pour cet hôtel.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($history->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $history->appends(['hotel_id' => $hotelId])->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
