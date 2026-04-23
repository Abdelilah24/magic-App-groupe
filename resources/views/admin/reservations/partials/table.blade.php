@php
    $resSortUrl = function(string $col) use ($sort, $dir): string {
        $newDir = ($sort === $col && $dir === 'desc') ? 'asc' : 'desc';
        return route('admin.reservations.index', array_merge(request()->except('sort','dir','page'), [
            'sort' => $col,
            'dir'  => $newDir,
        ]));
    };
    $sortIcon = function(string $col) use ($sort, $dir): string {
        if ($sort !== $col) return '<svg class="w-3 h-3 text-gray-300 inline-block ml-0.5" viewBox="0 0 10 14" fill="currentColor"><path d="M5 0l3 4H2L5 0zM5 14l-3-4h6l-3 4z"/></svg>';
        $up = $dir === 'asc'  ? 'text-amber-500' : 'text-gray-300';
        $dn = $dir === 'desc' ? 'text-amber-500' : 'text-gray-300';
        return '<svg class="w-3 h-3 inline-block ml-0.5" viewBox="0 0 10 14" fill="currentColor"><path class="'.$up.'" d="M5 0l3 4H2L5 0z"/><path class="'.$dn.'" d="M5 14l-3-4h6l-3 4z"/></svg>';
    };
@endphp

<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                        <a href="{{ $resSortUrl('reference') }}" class="res-sort flex items-center gap-1 hover:text-gray-700 whitespace-nowrap">
                            Réf. {!! $sortIcon('reference') !!}
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Agence</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hôtel</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                        <a href="{{ $resSortUrl('check_in') }}" class="res-sort flex items-center gap-1 hover:text-gray-700 whitespace-nowrap">
                            1er Check-in {!! $sortIcon('check_in') !!}
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dernier Check-out</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Chb.</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarif</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                        <a href="{{ $resSortUrl('total_price') }}" class="res-sort flex items-center gap-1 hover:text-gray-700 whitespace-nowrap">
                            Total {!! $sortIcon('total_price') !!}
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                        <a href="{{ $resSortUrl('created_at') }}" class="res-sort flex items-center gap-1 hover:text-gray-700 whitespace-nowrap">
                            Créée le {!! $sortIcon('created_at') !!}
                        </a>
                    </th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($reservations as $res)
                @php $unread = ! $res->is_read; @endphp
                <tr class="{{ $unread ? 'font-bold' : '' }} hover:bg-amber-50/40 transition-colors">

                    {{-- Référence --}}
                    <td class="px-4 py-3 text-sm font-mono whitespace-nowrap text-gray-900">
                        {{ $res->reference }}
                        @if($unread)
                        <span class="ml-1.5 inline-block w-2 h-2 rounded-full bg-amber-400 align-middle" title="Non consulté"></span>
                        @endif
                    </td>

                    {{-- Agence --}}
                    <td class="px-4 py-3">
                        <p class="text-sm text-gray-900">{{ $res->agency_name }}</p>
                        <p class="text-xs text-gray-400 font-normal">{{ $res->email }}</p>
                    </td>

                    <td class="px-4 py-3 text-sm text-gray-700">{{ $res->hotel->name ?? '' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">{{ $res->check_in->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">{{ $res->check_out->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $res->rooms->sum('quantity') }}</td>

                    {{-- Tarif --}}
                    <td class="px-4 py-3">
                        @if($res->tariff_code)
                        <span class="text-xs font-semibold px-1.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700">{{ $res->tariff_code }}</span>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>

                    {{-- Total --}}
                    <td class="px-4 py-3 text-sm text-gray-900">
                        @php
                            $resTaxe = (float)($res->taxe_total ?? 0);
                            if ($resTaxe == 0 && $res->rooms->isNotEmpty()) {
                                $taxeR = (float)(optional($res->hotel)->taxe_sejour ?? 19.80);
                                $stayGroups = $res->rooms->groupBy(
                                    fn($r) => ($r->check_in?->toDateString()  ?? $res->check_in->toDateString())
                                            . '_'
                                            . ($r->check_out?->toDateString() ?? $res->check_out->toDateString())
                                );
                                foreach ($stayGroups as $sRooms) {
                                    $sFirst  = $sRooms->first();
                                    $sNights = (int)(($sFirst->check_in ?? $res->check_in)->diffInDays($sFirst->check_out ?? $res->check_out));
                                    $sAdults = (int)$sRooms->sum(fn($r) => ($r->adults ?? 0) * max(1, $r->quantity ?? 1));
                                    if ($sAdults > 0 && $sNights > 0) {
                                        $resTaxe += round($sAdults * $sNights * $taxeR, 2);
                                    }
                                }
                            }
                            $resGrand = ($res->total_price ?? 0) + $resTaxe;
                        @endphp
                        {{ $resGrand > 0 ? number_format($resGrand, 2, ',', ' ') . ' MAD' : '' }}
                    </td>

                    {{-- Statut --}}
                    <td class="px-4 py-3">
                        @include('admin.partials.status-badge', ['status' => $res->status, 'label' => $res->status_label])
                    </td>

                    {{-- Date création --}}
                    <td class="px-4 py-3 text-xs text-gray-400 whitespace-nowrap">
                        {{ $res->created_at->format('d/m/Y H:i') }}
                    </td>

                    {{-- Bouton Voir --}}
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.reservations.show', $res) }}"
                           class="inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded transition-colors
                                  {{ $unread
                                      ? 'text-white bg-amber-500 hover:bg-amber-600'
                                      : 'text-amber-600 hover:text-amber-700 bg-amber-50 hover:bg-amber-100' }}">
                            Voir →
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="px-6 py-16 text-center">
                        <div class="text-gray-400 text-sm">
                            @if($hasFilters || request('search'))
                            Aucune réservation ne correspond aux filtres.
                            <a href="{{ route('admin.reservations.index', array_filter(['status' => request('status')])) }}"
                               class="ml-2 text-amber-600 underline">Réinitialiser</a>
                            @else
                            Aucune réservation pour le moment.
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($reservations->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 res-pagination">
        {{ $reservations->links() }}
    </div>
    @endif
</div>
