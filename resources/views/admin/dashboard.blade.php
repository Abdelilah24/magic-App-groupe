@extends('admin.layouts.app')
@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')
@section('page-subtitle', 'Vue d\'ensemble des réservations Magic Hotels')

@section('header-actions')
@endsection

@section('content')
@php
    $periodLabel = \Carbon\Carbon::parse($statsFrom)->locale('fr')->isoFormat('D MMM YYYY')
                 . ' – '
                 . \Carbon\Carbon::parse($statsTo)->locale('fr')->isoFormat('D MMM YYYY');

    $isDefault = $statsFrom === now()->subDays(29)->toDateString() && $statsTo === now()->toDateString();
    $nbDays    = (int) \Carbon\Carbon::parse($statsFrom)->diffInDays(\Carbon\Carbon::parse($statsTo)) + 1;
@endphp

{{-- ══════════════════════════════════════════════════════════
     DATE RANGE PICKER
══════════════════════════════════════════════════════════ --}}
<div
    x-data="{
        from: '{{ $statsFrom }}',
        to:   '{{ $statsTo }}',
        setPreset(days) {
            const today = new Date();
            const pad   = n => String(n).padStart(2,'0');
            const fmt   = d => d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate());

            if (days === 'month') {
                const s = new Date(today.getFullYear(), today.getMonth(), 1);
                this.from = fmt(s);
                this.to   = fmt(today);
            } else if (days === 'year') {
                const s = new Date(today.getFullYear(), 0, 1);
                this.from = fmt(s);
                this.to   = fmt(today);
            } else {
                const s = new Date(today);
                s.setDate(s.getDate() - (days - 1));
                this.from = fmt(s);
                this.to   = fmt(today);
            }
            this.$nextTick(() => document.getElementById('stats-range-form').submit());
        },
        isPreset(days) {
            const today = new Date();
            const pad   = n => String(n).padStart(2,'0');
            const fmt   = d => d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate());

            if (days === 'month') {
                return this.from === fmt(new Date(today.getFullYear(), today.getMonth(), 1)) && this.to === fmt(today);
            } else if (days === 'year') {
                return this.from === fmt(new Date(today.getFullYear(), 0, 1)) && this.to === fmt(today);
            } else {
                const s = new Date(today);
                s.setDate(s.getDate() - (days - 1));
                return this.from === fmt(s) && this.to === fmt(today);
            }
        }
    }"
    class="bg-white border border-gray-200 rounded-2xl px-5 py-4 mb-6 shadow-sm"
>
    <form id="stats-range-form" method="GET" action="{{ route('admin.dashboard') }}">
        {{-- Conserver les filtres du tableau --}}
        @foreach(['search','status','hotel_id','tariff_code','check_in_from','check_in_to','created_from','created_to'] as $p)
            @if(request($p))
                <input type="hidden" name="{{ $p }}" value="{{ request($p) }}">
            @endif
        @endforeach

        <input type="hidden" name="stats_from" x-model="from">
        <input type="hidden" name="stats_to"   x-model="to">

        <div class="flex flex-wrap items-center gap-3">
            {{-- Icône + Label --}}
            <div class="flex items-center gap-2 mr-1">
                <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-sm font-semibold text-gray-700 whitespace-nowrap">Période des stats</span>
            </div>

            {{-- Raccourcis --}}
            <div class="flex flex-wrap items-center gap-1.5">
                @foreach([7 => '7 jours', 30 => '30 jours', 90 => '90 jours', 'month' => 'Ce mois', 'year' => 'Cette année'] as $val => $label)
                <button type="button"
                        @click="setPreset('{{ $val }}')"
                        :class="isPreset('{{ $val }}')
                            ? 'bg-amber-500 text-white border-amber-500 shadow-sm'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-amber-300 hover:text-amber-700'"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all">
                    {{ $label }}
                </button>
                @endforeach
            </div>

            {{-- Séparateur --}}
            <div class="hidden md:block w-px h-6 bg-gray-200 mx-1"></div>

            {{-- Saisie manuelle --}}
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-1.5">
                    <label class="text-xs font-medium text-gray-500 whitespace-nowrap">Du</label>
                    <input type="date" x-model="from"
                           class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white w-36">
                </div>
                <span class="text-gray-300 font-light text-sm">—</span>
                <div class="flex items-center gap-1.5">
                    <label class="text-xs font-medium text-gray-500 whitespace-nowrap">Au</label>
                    <input type="date" x-model="to"
                           class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white w-36">
                </div>
                <button type="submit"
                        class="inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Appliquer
                </button>
            </div>

            {{-- Badge période active --}}
            <div class="ml-auto flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold px-3 py-1.5 rounded-full whitespace-nowrap">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    {{ $periodLabel }}
                    <span class="text-amber-500 font-normal">({{ $nbDays }}j)</span>
                </span>
                @if(! $isDefault)
                <a href="{{ route('admin.dashboard') }}" class="text-xs text-gray-400 hover:text-gray-600 transition-colors underline whitespace-nowrap">
                    Réinitialiser
                </a>
                @endif
            </div>
        </div>
    </form>
</div>

{{-- ══════════════════════════════════════════════════════════
     SECTION 1 : Actions requises (filtrées par période)
══════════════════════════════════════════════════════════ --}}
<div class="mb-2">
    <div class="flex items-center justify-between mb-3">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Actions requises</p>
        <span class="text-xs text-gray-400">réservations créées · {{ $periodLabel }}</span>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        <a href="{{ route('admin.reservations.index', ['status' => 'pending', 'created_from' => $statsFrom, 'created_to' => $statsTo]) }}"
           class="group bg-white border border-yellow-200 rounded-2xl overflow-hidden flex hover:shadow-md transition-shadow">
            <div class="w-1.5 bg-yellow-400 shrink-0"></div>
            <div class="px-4 py-4 flex-1">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-bold text-yellow-600 uppercase tracking-wide">En attente</p>
                    <div class="w-8 h-8 rounded-lg bg-yellow-50 flex items-center justify-center group-hover:bg-yellow-100 transition-colors">
                        <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold text-yellow-700">{{ $stats['pending'] }}</p>
                <p class="text-xs text-gray-400 mt-1">à traiter</p>
            </div>
        </a>

        <a href="{{ route('admin.reservations.index', ['status' => 'modification_pending', 'created_from' => $statsFrom, 'created_to' => $statsTo]) }}"
           class="group bg-white border border-purple-200 rounded-2xl overflow-hidden flex hover:shadow-md transition-shadow">
            <div class="w-1.5 bg-purple-500 shrink-0"></div>
            <div class="px-4 py-4 flex-1">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-bold text-purple-600 uppercase tracking-wide">Modifications</p>
                    <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center group-hover:bg-purple-100 transition-colors">
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.772-8.772z"/></svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold text-purple-700">{{ $stats['modification'] }}</p>
                <p class="text-xs text-gray-400 mt-1">demandes de modif.</p>
            </div>
        </a>

        <a href="{{ route('admin.reservations.index', ['status' => 'waiting_payment', 'created_from' => $statsFrom, 'created_to' => $statsTo]) }}"
           class="group bg-white border border-orange-200 rounded-2xl overflow-hidden flex hover:shadow-md transition-shadow">
            <div class="w-1.5 bg-orange-500 shrink-0"></div>
            <div class="px-4 py-4 flex-1">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-bold text-orange-600 uppercase tracking-wide">Att. paiement</p>
                    <div class="w-8 h-8 rounded-lg bg-orange-50 flex items-center justify-center group-hover:bg-orange-100 transition-colors">
                        <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold text-orange-700">{{ $stats['waiting_payment'] }}</p>
                <p class="text-xs text-gray-400 mt-1">en attente de règlement</p>
            </div>
        </a>

        <a href="{{ route('admin.reservations.index', ['status' => 'confirmed', 'created_from' => $statsFrom, 'created_to' => $statsTo]) }}"
           class="group bg-white border border-emerald-200 rounded-2xl overflow-hidden flex hover:shadow-md transition-shadow">
            <div class="w-1.5 bg-emerald-500 shrink-0"></div>
            <div class="px-4 py-4 flex-1">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-wide">Confirmées</p>
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center group-hover:bg-emerald-100 transition-colors">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold text-emerald-700">{{ $stats['confirmed'] }}</p>
                <p class="text-xs text-gray-400 mt-1">payées et confirmées</p>
            </div>
        </a>

    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     SECTION 2 : Chiffre d'affaires (filtré par période)
══════════════════════════════════════════════════════════ --}}
<div class="mb-2 mt-6">
    <div class="flex items-center justify-between mb-3">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Chiffre d'affaires</p>
        <span class="text-xs text-gray-400">réservations créées · {{ $periodLabel }}</span>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <div class="bg-gradient-to-br from-emerald-600 to-emerald-700 rounded-2xl p-5 text-white">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-bold text-emerald-200 uppercase tracking-wide">CA Confirmé</p>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-extrabold">{{ number_format($stats['revenue_confirmed'], 2, ',', ' ') }}</p>
            <p class="text-xs text-emerald-200 mt-1">MAD · hébergement + taxe</p>
        </div>

        <div class="bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl p-5 text-white">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-bold text-amber-100 uppercase tracking-wide">CA En cours</p>
                <div class="w-8 h-8 rounded-lg bg-amber-400/50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-extrabold">{{ number_format($stats['revenue_in_progress'], 2, ',', ' ') }}</p>
            <p class="text-xs text-amber-100 mt-1">MAD · att. paiement + part. payées</p>
        </div>

        <div class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-2xl p-5 text-white">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-bold text-slate-300 uppercase tracking-wide">CA Pipeline total</p>
                <div class="w-8 h-8 rounded-lg bg-slate-600/50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
            <p class="text-2xl font-extrabold">{{ number_format($stats['revenue_total'], 2, ',', ' ') }}</p>
            <p class="text-xs text-slate-400 mt-1">MAD · toutes réservations actives</p>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     SECTIONS 3+4 : Donut (gauche) + Activité opérationnelle (droite)
══════════════════════════════════════════════════════════ --}}
@php
    $statusConfig = [
        'pending'              => ['label' => 'En attente',      'hex' => '#facc15'],
        'modification_pending' => ['label' => 'Modifications',   'hex' => '#a855f7'],
        'accepted'             => ['label' => 'Acceptées',       'hex' => '#3b82f6'],
        'waiting_payment'      => ['label' => 'Att. paiement',   'hex' => '#f97316'],
        'partially_paid'       => ['label' => 'Part. payées',    'hex' => '#818cf8'],
        'confirmed'            => ['label' => 'Confirmées',      'hex' => '#10b981'],
        'refused'              => ['label' => 'Refusées',        'hex' => '#f87171'],
        'cancelled'            => ['label' => 'Annulées',        'hex' => '#9ca3af'],
    ];
    $byStatusTotal = $stats['by_status']->sum();
    $radius        = 58;
    $circumference = 2 * M_PI * $radius;
@endphp
<div class="mb-8 mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- ── GAUCHE : Répartition par statut (donut SVG) ───────────────── --}}
    <div class="bg-white border border-gray-200 rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-gray-900">Répartition par statut</h3>
            <span class="text-xs text-gray-400">{{ $periodLabel }}</span>
        </div>

        @if($byStatusTotal > 0)
        <div class="flex items-center gap-6">

            {{-- Donut SVG --}}
            <div class="shrink-0">
                <svg width="160" height="160" viewBox="0 0 160 160">
                    {{-- Fond gris --}}
                    <circle cx="80" cy="80" r="{{ $radius }}" fill="none"
                            stroke="#f3f4f6" stroke-width="30" />
                    {{-- Segments --}}
                    @php $cumOffset = 0; @endphp
                    @foreach($statusConfig as $key => $cfg)
                    @php $cnt = $stats['by_status'][$key] ?? 0; @endphp
                    @if($cnt > 0)
                    @php
                        $segLen     = ($cnt / $byStatusTotal) * $circumference;
                        $dashOffset = $circumference - $cumOffset;
                        $cumOffset += $segLen;
                    @endphp
                    <circle cx="80" cy="80" r="{{ $radius }}" fill="none"
                            stroke="{{ $cfg['hex'] }}"
                            stroke-width="30"
                            stroke-dasharray="{{ round($segLen, 4) }} {{ round($circumference, 4) }}"
                            stroke-dashoffset="{{ round($dashOffset, 4) }}"
                            transform="rotate(-90 80 80)"
                            stroke-linecap="butt" />
                    @endif
                    @endforeach
                    {{-- Texte central --}}
                    <text x="80" y="74" text-anchor="middle"
                          font-size="24" font-weight="bold" fill="#111827"
                          font-family="-apple-system,sans-serif">{{ $byStatusTotal }}</text>
                    <text x="80" y="90" text-anchor="middle"
                          font-size="10" fill="#9ca3af"
                          font-family="-apple-system,sans-serif">réservations</text>
                </svg>
            </div>

            {{-- Légende --}}
            <div class="flex-1 space-y-2.5">
                @foreach($statusConfig as $key => $cfg)
                @php $cnt = $stats['by_status'][$key] ?? 0; @endphp
                @if($cnt > 0)
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full shrink-0"
                         style="background-color:{{ $cfg['hex'] }}"></div>
                    <span class="text-xs text-gray-600 flex-1 truncate">{{ $cfg['label'] }}</span>
                    <span class="text-xs font-bold text-gray-900">{{ $cnt }}</span>
                    <span class="text-[11px] text-gray-400 w-8 text-right">
                        {{ round($cnt / $byStatusTotal * 100) }}%
                    </span>
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @else
        <p class="text-sm text-gray-400 text-center py-8">Aucune donnée sur la période</p>
        @endif
    </div>

    {{-- ── DROITE : Activité opérationnelle ──────────────────────────── --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Activité opérationnelle</p>
            <span class="text-xs text-gray-400">temps réel</span>
        </div>
        <div class="grid grid-cols-2 gap-4">

            <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden flex">
                <div class="w-1.5 bg-gray-400 shrink-0"></div>
                <div class="px-4 py-4 flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Total période</p>
                        <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900">{{ $stats['total'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">réservations créées</p>
                </div>
            </div>

            <a href="{{ route('admin.reservations.agenda', ['date' => now()->toDateString()]) }}"
               class="group bg-white border border-blue-200 rounded-2xl overflow-hidden flex hover:shadow-md transition-shadow">
                <div class="w-1.5 bg-blue-500 shrink-0"></div>
                <div class="px-4 py-4 flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-bold text-blue-600 uppercase tracking-wide">Arrivées</p>
                        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-extrabold text-blue-700">{{ $stats['arrivals_today'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">aujourd'hui</p>
                </div>
            </a>

            <a href="{{ route('admin.reservations.agenda-depart', ['date' => now()->toDateString()]) }}"
               class="group bg-white border border-indigo-200 rounded-2xl overflow-hidden flex hover:shadow-md transition-shadow">
                <div class="w-1.5 bg-indigo-500 shrink-0"></div>
                <div class="px-4 py-4 flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-bold text-indigo-600 uppercase tracking-wide">Départs</p>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center group-hover:bg-indigo-100 transition-colors">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" transform="scale(-1,1) translate(-24,0)"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-extrabold text-indigo-700">{{ $stats['departures_today'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">aujourd'hui</p>
                </div>
            </a>

            <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden flex">
                <div class="w-1.5 bg-red-400 shrink-0"></div>
                <div class="px-4 py-4 flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-bold text-red-500 uppercase tracking-wide">Annulées</p>
                        <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-extrabold text-red-500">{{ $stats['cancelled'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">annulées / refusées</p>
                </div>
            </div>

        </div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════
     SECTION 5 : Tableau des demandes (filtres indépendants)
══════════════════════════════════════════════════════════ --}}
<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">

    {{-- En-tête + barre de recherche --}}
    <div class="px-5 py-4 border-b border-gray-100" x-data="{ open: {{ $hasFilters ? 'true' : 'false' }} }">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900">Demandes de réservation</h2>
                    <p class="text-xs text-gray-400">{{ $recent->total() }} résultat{{ $recent->total() > 1 ? 's' : '' }}</p>
                </div>
            </div>
            <a href="{{ route('admin.reservations.index') }}"
               class="text-sm font-medium text-amber-600 hover:text-amber-700 flex items-center gap-1">
                Voir tout
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <form method="GET" action="{{ route('admin.dashboard') }}" id="dash-filter-form">
            {{-- Conserver la période des stats --}}
            <input type="hidden" name="stats_from" value="{{ $statsFrom }}">
            <input type="hidden" name="stats_to"   value="{{ $statsTo }}">

            {{-- Barre de recherche --}}
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Référence, agence, email, contact..."
                           class="w-full border border-gray-200 rounded-lg pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
                <button type="submit"
                        class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    Rechercher
                </button>
                <button type="button" @click="open = !open"
                        class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm border transition-colors"
                        :class="open ? 'bg-slate-100 border-slate-300 text-slate-700' : 'bg-white border-gray-200 text-gray-500 hover:border-gray-300'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 0 1 1-1h16a1 1 0 0 1 .7 1.7L13 13.4V19a1 1 0 0 1-1.4.9l-4-2A1 1 0 0 1 7 17v-3.6L3.3 4.7A1 1 0 0 1 3 4z"/>
                    </svg>
                    Filtres
                    @if($hasFilters)
                    <span class="inline-flex items-center justify-center w-4 h-4 text-xs font-bold bg-amber-500 text-white rounded-full">!</span>
                    @endif
                </button>
                @if($hasFilters || request('search'))
                <a href="{{ route('admin.dashboard', ['stats_from' => $statsFrom, 'stats_to' => $statsTo]) }}"
                   class="flex items-center gap-1 px-3 py-2 rounded-lg text-sm border border-gray-200 text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Réinitialiser
                </a>
                @endif
            </div>

            {{-- Filtres avancés --}}
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="mt-3 pt-3 border-t border-gray-100 grid grid-cols-2 md:grid-cols-4 gap-3">

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Statut</label>
                    <select name="status" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        <option value="">Tous</option>
                        @foreach(['pending' => 'En attente', 'modification_pending' => 'Modifications', 'waiting_payment' => 'Att. paiement', 'partially_paid' => 'Part. payées', 'confirmed' => 'Confirmées', 'refused' => 'Refusées', 'cancelled' => 'Annulées'] as $s => $l)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Hôtel</label>
                    <select name="hotel_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        <option value="">Tous les hôtels</option>
                        @foreach($hotels as $hotel)
                        <option value="{{ $hotel->id }}" {{ request('hotel_id') == $hotel->id ? 'selected' : '' }}>{{ $hotel->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Tarif</label>
                    <select name="tariff_code" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        <option value="">Tous les tarifs</option>
                        @foreach($tariffCodes as $code)
                        <option value="{{ $code }}" {{ request('tariff_code') === $code ? 'selected' : '' }}>{{ $code }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Arrivée du</label>
                    <input type="date" name="check_in_from" value="{{ request('check_in_from') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Arrivée au</label>
                    <input type="date" name="check_in_to" value="{{ request('check_in_to') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Créée du</label>
                    <input type="date" name="created_from" value="{{ request('created_from') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Créée au</label>
                    <input type="date" name="created_to" value="{{ request('created_to') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>

                <div class="flex items-end">
                    <button type="submit"
                            class="w-full bg-slate-800 hover:bg-slate-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Appliquer
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Wrapper AJAX du tableau --}}
    <div id="dash-table-wrapper" class="relative">
        @include('admin.partials.dashboard-table')
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    const wrapper = document.getElementById('dash-table-wrapper');
    const filterForm = document.getElementById('dash-filter-form');

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
            console.error('Dash AJAX error:', e);
        } finally {
            setLoading(false);
        }
    }

    function bindEvents() {
        // Liens de tri
        wrapper.querySelectorAll('a.dash-sort').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                loadTable(a.href);
            });
        });
        // Liens de pagination
        wrapper.querySelectorAll('.dash-pagination a[href]').forEach(a => {
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
            loadTable('{{ route("admin.dashboard") }}?' + params.toString());
        });
    }

    bindEvents();
})();
</script>
@endpush
