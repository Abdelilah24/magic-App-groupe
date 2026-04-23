@extends('admin.layouts.app')
@section('title', 'Réservations')
@section('page-title', 'Réservations')

@section('content')

{{-- Filtres par statut --}}
<div class="flex flex-wrap gap-2 mb-5"> <a href="{{ route('admin.reservations.index', array_filter(request()->except('status', 'page'))) }}"
       class="px-3 py-1.5 rounded-lg text-sm font-medium border transition-colors {{ !request('status') ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300' }}"> Tous <span class="ml-1 text-xs opacity-70">({{ array_sum($statusCounts->toArray()) }})</span> </a> @foreach([
        'pending'              => ['label' => 'En attente',       'color' => 'yellow'],
        'modification_pending' => ['label' => 'Modifications',    'color' => 'purple'],
        'waiting_payment'      => ['label' => 'Att. paiement',   'color' => 'orange'],
        'partially_paid'       => ['label' => 'Part. payées',    'color' => 'blue'],
        'confirmed'            => ['label' => 'Confirmées',      'color' => 'green'],
        'refused'              => ['label' => 'Refusées',        'color' => 'red'],
        'cancelled'            => ['label' => 'Annulées',        'color' => 'gray'],
    ] as $s => $meta)
    @php $count = $statusCounts[$s] ?? 0; @endphp
    <a href="{{ route('admin.reservations.index', array_filter(array_merge(request()->except('status', 'page'), ['status' => $s]))) }}"
       class="px-3 py-1.5 rounded-lg text-sm font-medium border transition-colors {{ request('status') === $s ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300' }}"> {{ $meta['label'] }}
        @if($count)
        <span class="ml-1 text-xs opacity-70">({{ $count }})</span> @endif
    </a> @endforeach
</div> {{-- Panneau de recherche & filtres --}}
<div class="bg-white border border-gray-200 rounded-xl p-4 mb-5" x-data="{ open: {{ $hasFilters ? 'true' : 'false' }} }"> <form method="GET" action="{{ route('admin.reservations.index') }}" id="filter-form"> @if(request('status')) <input type="hidden" name="status" value="{{ request('status') }}"> @endif
        @if(request('sort'))   <input type="hidden" name="sort"   value="{{ request('sort') }}"> @endif
        @if(request('dir'))    <input type="hidden" name="dir"    value="{{ request('dir') }}"> @endif

        {{-- Barre de recherche principale --}}
        <div class="flex gap-2"> <div class="relative flex-1"> <span class="absolute inset-y-0 left-3 flex items-center text-gray-400"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/> </svg> </span> <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Référence, agence, email, contact, téléphone..."
                       class="w-full border border-gray-200 rounded-lg pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"> </div> <button type="submit"
                    class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"> Rechercher
            </button> <button type="button" @click="open = !open"
                    class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm border transition-colors"
                    :class="open ? 'bg-slate-100 border-slate-300 text-slate-700' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 4a1 1 0 0 1 1-1h16a1 1 0 0 1 .7 1.7L13 13.4V19a1 1 0 0 1-1.4.9l-4-2A1 1 0 0 1 7 17v-3.6L3.3 4.7A1 1 0 0 1 3 4z"/> </svg> Filtres
                @if($hasFilters)
                <span class="inline-flex items-center justify-center w-4 h-4 text-xs font-bold bg-amber-500 text-white rounded-full">!</span> @endif
            </button> @if($hasFilters || request('search'))
            <a href="{{ route('admin.reservations.index', array_filter(['status' => request('status')])) }}"
               class="flex items-center gap-1 px-3 py-2 rounded-lg text-sm border border-gray-200 text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/> </svg> Réinitialiser
            </a> @endif
        </div> {{-- Filtres avancés --}}
        <div x-show="open" x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-2 md:grid-cols-4 gap-3"> {{-- Hôtel --}}
            <div> <label class="block text-xs font-medium text-gray-500 mb-1">Hôtel</label> <select name="hotel_id"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white"> <option value="">Tous les hôtels</option> @foreach($hotels as $hotel)
                    <option value="{{ $hotel->id }}" {{ request('hotel_id') == $hotel->id ? 'selected' : '' }}> {{ $hotel->name }}
                    </option> @endforeach
                </select> </div> {{-- Code tarifaire --}}
            <div> <label class="block text-xs font-medium text-gray-500 mb-1">Tarif</label> <select name="tariff_code"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white"> <option value="">Tous les tarifs</option> @foreach($tariffCodes as $code)
                    <option value="{{ $code }}" {{ request('tariff_code') === $code ? 'selected' : '' }}> {{ $code }}
                    </option> @endforeach
                </select> </div> {{-- Date d'arrivée de --}}
            <div> <label class="block text-xs font-medium text-gray-500 mb-1">Arrivée  du</label> <input type="date" name="check_in_from" value="{{ request('check_in_from') }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"> </div> {{-- Date d'arrivée jusqu'à --}}
            <div> <label class="block text-xs font-medium text-gray-500 mb-1">Arrivée  au</label> <input type="date" name="check_in_to" value="{{ request('check_in_to') }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"> </div> {{-- Créée du --}}
            <div> <label class="block text-xs font-medium text-gray-500 mb-1">Créée  du</label> <input type="date" name="created_from" value="{{ request('created_from') }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"> </div> {{-- Créée jusqu'à --}}
            <div> <label class="block text-xs font-medium text-gray-500 mb-1">Créée  au</label> <input type="date" name="created_to" value="{{ request('created_to') }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"> </div> {{-- Bouton appliquer --}}
            <div class="md:col-span-2 flex items-end"> <button type="submit"
                        class="w-full bg-slate-800 hover:bg-slate-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"> Appliquer les filtres
                </button> </div> </div> </form>
</div> {{-- Résumé des filtres actifs + infos résultats --}}
<div class="flex items-center justify-between mb-3 text-sm text-gray-500"> <span> {{ $reservations->total() }} réservation{{ $reservations->total() > 1 ? 's' : '' }}
        @if(request('search')) pour <strong class="text-gray-700">« {{ request('search') }} »</strong> @endif
    </span> {{-- Tri --}}
    <div class="flex items-center gap-2"> <span class="text-xs text-gray-400">Trier par :</span> @foreach(['created_at' => 'Date création', 'check_in' => 'Arrivée', 'total_price' => 'Total', 'reference' => 'Référence'] as $col => $label)
        @php
            $isActive = request('sort', 'created_at') === $col;
            $newDir = ($isActive && request('dir', 'desc') === 'desc') ? 'asc' : 'desc';
        @endphp
        <a href="{{ route('admin.reservations.index', array_merge(request()->except('sort','dir','page'), ['sort' => $col, 'dir' => $newDir])) }}"
           class="text-xs px-2 py-1 rounded border transition-colors {{ $isActive ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-gray-500 border-gray-200 hover:border-gray-300' }}"> {{ $label }}
            @if($isActive)
                {{ request('dir','desc') === 'desc' ? '' : '' }}
            @endif
        </a> @endforeach
    </div>
</div>

{{-- Wrapper AJAX --}}
<div id="res-table-wrapper">
    @include('admin.reservations.partials.table')
</div>

@endsection

@push('scripts')
<script>
(function () {
    const wrapper    = document.getElementById('res-table-wrapper');
    const filterForm = document.getElementById('filter-form');

    function setLoading(on) {
        wrapper.style.opacity = on ? '0.4' : '1';
        wrapper.style.pointerEvents = on ? 'none' : '';
    }

    async function loadTable(url) {
        setLoading(true);
        try {
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            wrapper.innerHTML = await res.text();
            history.pushState(null, '', url);
            bindEvents();
        } catch (e) {
            console.error('Res AJAX error:', e);
        } finally {
            setLoading(false);
        }
    }

    function bindEvents() {
        // Liens de tri
        wrapper.querySelectorAll('a.res-sort').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                loadTable(a.href);
            });
        });
        // Liens de pagination
        wrapper.querySelectorAll('.res-pagination a[href]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                loadTable(a.href);
            });
        });
    }

    // Soumission du formulaire de filtre/recherche
    if (filterForm) {
        filterForm.addEventListener('submit', e => {
            e.preventDefault();
            const params = new URLSearchParams(new FormData(filterForm));
            loadTable('{{ route("admin.reservations.index") }}?' + params.toString());
        });
    }

    // Filtres de statut (liens en haut de page)
    document.querySelectorAll('a[href*="status="], a[href="{{ route("admin.reservations.index") }}"]').forEach(a => {
        if (!a.closest('#res-table-wrapper')) {
            a.addEventListener('click', e => {
                e.preventDefault();
                loadTable(a.href);
            });
        }
    });

    bindEvents();
})();
</script>
@endpush
