@extends('admin.layouts.app')

@section('title', 'Liste des événements')
@section('page-title', 'Jours fériés & Vacances')

@section('page-subtitle')
    Liste complète — {{ number_format($totalCount) }} événements en base
@endsection

@section('header-actions')
    <button @click="$dispatch('open-add-event')" x-data
            class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold text-sm px-4 py-2 rounded-lg transition-colors shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nouvel événement
    </button>
    <a href="{{ route('admin.calendar.index') }}"
       class="inline-flex items-center gap-2 border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 font-semibold text-sm px-4 py-2 rounded-lg transition-colors shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        Vue calendrier
    </a>
@endsection

@section('content')

<div
    @open-add-event.window="openAdd = true"
    x-data="{
        openAdd:    false,
        openEdit:   false,
        openDelete: false,

        editId:          null,
        editName:        '',
        editCountry:     'MA',
        editType:        'holiday',
        editStartDate:   '',
        editEndDate:     '',
        editZone:        '',

        deleteId:   null,
        deleteName: '',

        get showZone() {
            return this.editType === 'school_vacation' && this.editCountry === 'FR';
        },

        startEdit(id, name, country, type, startDate, endDate, zone) {
            this.editId        = id;
            this.editName      = name;
            this.editCountry   = country;
            this.editType      = type;
            this.editStartDate = startDate;
            this.editEndDate   = endDate;
            this.editZone      = zone ?? '';
            this.openEdit      = true;
        },

        startDelete(id, name) {
            this.deleteId   = id;
            this.deleteName = name;
            this.openDelete = true;
        }
    }"
>

{{-- ═══════════════════════════════════════════════════════
     BARRE DE FILTRES
══════════════════════════════════════════════════════════ --}}
<form method="GET" action="{{ route('admin.calendar.list') }}" id="filter-form"
      x-data="{
          countries: {{ json_encode(request()->get('countries', [])) }},
          hasFilter: {{ (request()->hasAny(['search','countries','type','year','source','zone']) ? 'true' : 'false') }}
      }">

    {{-- Conserver le tri actif --}}
    @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
    @if(request('dir'))<input type="hidden" name="dir"  value="{{ request('dir') }}">@endif

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 mb-5">

        {{-- Ligne 1 : Recherche + Pays + Type + Année --}}
        <div class="flex flex-wrap gap-3 items-end mb-3">

            {{-- Recherche --}}
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Recherche</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Nom de l'événement…"
                           class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent">
                </div>
            </div>

            {{-- Pays --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Pays</label>
                <div class="flex gap-1.5">
                    @foreach(['MA' => ['🇲🇦','amber'], 'FR' => ['🇫🇷','blue'], 'GB' => ['🇬🇧','orange']] as $code => [$flag, $color])
                    <label class="cursor-pointer">
                        <input type="checkbox" name="countries[]" value="{{ $code }}"
                               x-model="countries"
                               class="sr-only peer">
                        <span class="peer-checked:ring-2 peer-checked:ring-{{ $color }}-400 peer-checked:bg-{{ $color }}-50
                                     inline-flex items-center gap-1.5 px-3 py-2 border border-gray-200 rounded-xl text-sm font-semibold
                                     text-gray-600 hover:border-{{ $color }}-300 transition-all cursor-pointer select-none">
                            {{ $flag }} {{ $code }}
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Type --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Type</label>
                <select name="type"
                        class="border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                    <option value="">Tous les types</option>
                    <option value="holiday"          {{ request('type') === 'holiday'          ? 'selected' : '' }}>Jours fériés</option>
                    <option value="school_vacation"  {{ request('type') === 'school_vacation'  ? 'selected' : '' }}>Vacances scolaires</option>
                </select>
            </div>

            {{-- Année --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Année</label>
                <select name="year"
                        class="border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                    <option value="">Toutes</option>
                    @foreach($availableYears as $yr)
                    <option value="{{ $yr }}" {{ request('year') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>

        </div>

        {{-- Ligne 2 : Source + Zone + Boutons --}}
        <div class="flex flex-wrap gap-3 items-end">

            {{-- Source --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Source</label>
                <select name="source"
                        class="border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                    <option value="">Toutes</option>
                    <option value="api"    {{ request('source') === 'api'    ? 'selected' : '' }}>API (auto)</option>
                    <option value="manual" {{ request('source') === 'manual' ? 'selected' : '' }}>Manuel</option>
                </select>
            </div>

            {{-- Zone --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Zone scolaire</label>
                <select name="zone"
                        class="border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                    <option value="">Toutes les zones</option>
                    <option value="Zone A" {{ request('zone') === 'Zone A' ? 'selected' : '' }}>Zone A</option>
                    <option value="Zone B" {{ request('zone') === 'Zone B' ? 'selected' : '' }}>Zone B</option>
                    <option value="Zone C" {{ request('zone') === 'Zone C' ? 'selected' : '' }}>Zone C</option>
                </select>
            </div>

            {{-- Actions --}}
            <div class="flex gap-2 ml-auto">
                <a href="{{ route('admin.calendar.list') }}"
                   x-show="hasFilter"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Réinitialiser
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold text-sm px-4 py-2 rounded-xl transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                    </svg>
                    Filtrer
                </button>
            </div>
        </div>
    </div>
</form>

{{-- ═══════════════════════════════════════════════════════
     RÉSULTATS
══════════════════════════════════════════════════════════ --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

    {{-- En-tête table --}}
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
            <span class="text-sm font-semibold text-gray-900">
                {{ $events->total() }} résultat{{ $events->total() > 1 ? 's' : '' }}
            </span>
            @if($events->total() !== $totalCount)
            <span class="text-xs text-gray-400 ml-2">sur {{ number_format($totalCount) }} au total</span>
            @endif
        </div>
        <span class="text-xs text-gray-400">Page {{ $events->currentPage() }} / {{ $events->lastPage() }}</span>
    </div>

    @if($events->isEmpty())
    <div class="px-6 py-16 text-center">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <p class="text-sm text-gray-500 font-medium">Aucun événement trouvé</p>
        <p class="text-xs text-gray-400 mt-1">Modifiez vos filtres ou <a href="{{ route('admin.calendar.list') }}" class="text-amber-600 hover:underline">réinitialisez la recherche</a>.</p>
    </div>
    @else

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    @php
                        $sortLink = fn($col, $label) =>
                            '<a href="' . route('admin.calendar.list', array_merge(request()->except(['sort','dir','page']), [
                                'sort' => $col,
                                'dir'  => (request('sort') === $col && request('dir') === 'asc') ? 'desc' : 'asc',
                            ])) . '" class="group inline-flex items-center gap-1 text-xs font-semibold text-gray-500 uppercase tracking-wider hover:text-gray-800">'
                            . $label
                            . '<svg class="w-3 h-3 text-gray-300 group-hover:text-gray-500 ' . (request('sort') === $col ? 'text-amber-500' : '') . '" fill="currentColor" viewBox="0 0 24 24"><path d="M7 10l5-5 5 5H7zm0 4h10l-5 5-5-5z"/></svg>'
                            . '</a>';
                    @endphp
                    <th class="px-5 py-3 text-left">{!! $sortLink('country', 'Pays') !!}</th>
                    <th class="px-5 py-3 text-left">{!! $sortLink('type', 'Type') !!}</th>
                    <th class="px-5 py-3 text-left">{!! $sortLink('name', 'Nom') !!}</th>
                    <th class="px-5 py-3 text-left hidden md:table-cell">Zone</th>
                    <th class="px-5 py-3 text-left">{!! $sortLink('start_date', 'Début') !!}</th>
                    <th class="px-5 py-3 text-left hidden sm:table-cell">Fin</th>
                    <th class="px-5 py-3 text-center hidden lg:table-cell">Durée</th>
                    <th class="px-5 py-3 text-center hidden lg:table-cell">Source</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($events as $event)
                @php
                    $duration = $event->start_date->diffInDays($event->end_date) + 1;
                    $countryMeta = match($event->country) {
                        'MA' => ['flag' => '🇲🇦', 'bg' => 'bg-amber-100',  'text' => 'text-amber-800'],
                        'FR' => ['flag' => '🇫🇷', 'bg' => 'bg-blue-100',   'text' => 'text-blue-800'],
                        'GB' => ['flag' => '🇬🇧', 'bg' => 'bg-orange-100', 'text' => 'text-orange-800'],
                        default => ['flag' => '🌐', 'bg' => 'bg-gray-100',  'text' => 'text-gray-800'],
                    };
                    $typeMeta = $event->type === 'holiday'
                        ? ['label' => 'Jour férié',         'bg' => 'bg-red-50',    'text' => 'text-red-700',    'dot' => 'bg-red-400']
                        : ['label' => 'Vacances scolaires', 'bg' => 'bg-indigo-50', 'text' => 'text-indigo-700', 'dot' => 'bg-indigo-400'];
                @endphp
                <tr class="hover:bg-gray-50/60 transition-colors">

                    {{-- Pays --}}
                    <td class="px-5 py-3.5">
                        <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-xs font-bold {{ $countryMeta['bg'] }} {{ $countryMeta['text'] }}">
                            {{ $countryMeta['flag'] }} {{ $event->country }}
                        </span>
                    </td>

                    {{-- Type --}}
                    <td class="px-5 py-3.5">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $typeMeta['bg'] }} {{ $typeMeta['text'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $typeMeta['dot'] }}"></span>
                            {{ $typeMeta['label'] }}
                        </span>
                    </td>

                    {{-- Nom --}}
                    <td class="px-5 py-3.5">
                        <span class="font-medium text-gray-900">{{ $event->name }}</span>
                    </td>

                    {{-- Zone --}}
                    <td class="px-5 py-3.5 hidden md:table-cell">
                        @if($event->zone)
                            <span class="text-xs font-semibold px-2 py-0.5 bg-gray-100 text-gray-600 rounded-md">{{ $event->zone }}</span>
                        @else
                            <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>

                    {{-- Début --}}
                    <td class="px-5 py-3.5 text-gray-700 font-mono text-xs whitespace-nowrap">
                        {{ $event->start_date->format('d/m/Y') }}
                    </td>

                    {{-- Fin --}}
                    <td class="px-5 py-3.5 text-gray-700 font-mono text-xs whitespace-nowrap hidden sm:table-cell">
                        @if($event->start_date->format('Y-m-d') === $event->end_date->format('Y-m-d'))
                            <span class="text-gray-300">—</span>
                        @else
                            {{ $event->end_date->format('d/m/Y') }}
                        @endif
                    </td>

                    {{-- Durée --}}
                    <td class="px-5 py-3.5 text-center hidden lg:table-cell">
                        <span class="text-xs font-semibold {{ $duration > 1 ? 'text-gray-700' : 'text-gray-400' }}">
                            {{ $duration }} j
                        </span>
                    </td>

                    {{-- Source --}}
                    <td class="px-5 py-3.5 text-center hidden lg:table-cell">
                        @if($event->source === 'manual')
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-violet-700 bg-violet-50 border border-violet-200 rounded-full px-2 py-0.5">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                Manuel
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-2 py-0.5">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                API
                            </span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="px-5 py-3.5 text-right">
                        @if($event->source === 'manual')
                        <div class="inline-flex items-center gap-1">
                            {{-- Modifier --}}
                            <button
                                @click="startEdit(
                                    {{ $event->id }},
                                    {{ json_encode($event->name) }},
                                    {{ json_encode($event->country) }},
                                    {{ json_encode($event->type) }},
                                    {{ json_encode($event->start_date->format('Y-m-d')) }},
                                    {{ json_encode($event->end_date->format('Y-m-d')) }},
                                    {{ json_encode($event->zone) }}
                                )"
                                title="Modifier"
                                class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            {{-- Supprimer --}}
                            <button
                                @click="startDelete({{ $event->id }}, {{ json_encode($event->name) }})"
                                title="Supprimer"
                                class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                        @else
                            <span class="text-xs text-gray-300 pr-1">—</span>
                        @endif
                    </td>

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($events->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
        <p class="text-xs text-gray-500">
            Affichage de <span class="font-semibold text-gray-700">{{ $events->firstItem() }}</span>
            à <span class="font-semibold text-gray-700">{{ $events->lastItem() }}</span>
            sur <span class="font-semibold text-gray-700">{{ $events->total() }}</span> résultats
        </p>
        <div class="flex items-center gap-1">
            @if($events->onFirstPage())
                <span class="px-3 py-1.5 text-xs font-medium text-gray-300 border border-gray-100 rounded-lg cursor-not-allowed">‹</span>
            @else
                <a href="{{ $events->previousPageUrl() }}"
                   class="px-3 py-1.5 text-xs font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">‹</a>
            @endif

            @foreach($events->getUrlRange(max(1, $events->currentPage()-2), min($events->lastPage(), $events->currentPage()+2)) as $page => $url)
                @if($page === $events->currentPage())
                    <span class="px-3 py-1.5 text-xs font-semibold text-white bg-amber-500 rounded-lg">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="px-3 py-1.5 text-xs font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">{{ $page }}</a>
                @endif
            @endforeach

            @if($events->hasMorePages())
                <a href="{{ $events->nextPageUrl() }}"
                   class="px-3 py-1.5 text-xs font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">›</a>
            @else
                <span class="px-3 py-1.5 text-xs font-medium text-gray-300 border border-gray-100 rounded-lg cursor-not-allowed">›</span>
            @endif
        </div>
    </div>
    @endif

    @endif
</div>

{{-- ═══════════════════════════════════════════════════════
     MODAL — AJOUTER UN ÉVÉNEMENT
══════════════════════════════════════════════════════════ --}}
<div x-show="openAdd" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"  x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="openAdd = false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-data="{ addType: 'holiday', addCountry: 'MA' }">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-bold text-gray-900">Nouvel événement</h3>
            <button @click="openAdd = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('admin.calendar.manual.store') }}" method="POST" class="px-6 py-5 space-y-4">
            @csrf
            {{-- Pays + Type --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Pays <span class="text-red-500">*</span></label>
                    <select name="country" required x-model="addCountry"
                            class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        <option value="MA">🇲🇦 Maroc</option>
                        <option value="FR">🇫🇷 France</option>
                        <option value="GB">🇬🇧 Royaume-Uni</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Type <span class="text-red-500">*</span></label>
                    <select name="type" required x-model="addType"
                            class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        <option value="holiday">Jour férié</option>
                        <option value="school_vacation">Vacances scolaires</option>
                    </select>
                </div>
            </div>
            {{-- Nom --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nom <span class="text-red-500">*</span></label>
                <input type="text" name="name" required maxlength="255" autofocus
                       placeholder="Ex : Fête du Trône…"
                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent">
            </div>
            {{-- Dates --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Date de début <span class="text-red-500">*</span></label>
                    <input type="date" name="start_date" required
                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Date de fin <span class="text-red-500">*</span></label>
                    <input type="date" name="end_date" required
                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent">
                </div>
            </div>
            {{-- Zone (FR vacances uniquement) --}}
            <div x-show="addType === 'school_vacation' && addCountry === 'FR'" x-cloak>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Zone scolaire</label>
                <select name="zone"
                        class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                    <option value="">Toutes les zones</option>
                    <option value="Zone A">Zone A</option>
                    <option value="Zone B">Zone B</option>
                    <option value="Zone C">Zone C</option>
                </select>
            </div>
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                <button type="button" @click="openAdd = false"
                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 rounded-lg hover:bg-gray-100 transition-colors">
                    Annuler
                </button>
                <button type="submit"
                        class="px-5 py-2 text-sm font-semibold bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors shadow-sm">
                    Créer l'événement
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     MODAL — MODIFIER UN ÉVÉNEMENT
══════════════════════════════════════════════════════════ --}}
<div x-show="openEdit" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"  x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="openEdit = false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-bold text-gray-900">Modifier l'événement</h3>
            <button @click="openEdit = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form :action="`{{ url('admin/calendar/manual') }}/${editId}`" method="POST" class="px-6 py-5 space-y-4">
            @csrf
            @method('PUT')
            {{-- Pays + Type --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Pays <span class="text-red-500">*</span></label>
                    <select name="country" required x-model="editCountry"
                            class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        <option value="MA">🇲🇦 Maroc</option>
                        <option value="FR">🇫🇷 France</option>
                        <option value="GB">🇬🇧 Royaume-Uni</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Type <span class="text-red-500">*</span></label>
                    <select name="type" required x-model="editType"
                            class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        <option value="holiday">Jour férié</option>
                        <option value="school_vacation">Vacances scolaires</option>
                    </select>
                </div>
            </div>
            {{-- Nom --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nom <span class="text-red-500">*</span></label>
                <input type="text" name="name" required maxlength="255" x-model="editName"
                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent">
            </div>
            {{-- Dates --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Date de début <span class="text-red-500">*</span></label>
                    <input type="date" name="start_date" required x-model="editStartDate"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Date de fin <span class="text-red-500">*</span></label>
                    <input type="date" name="end_date" required x-model="editEndDate"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent">
                </div>
            </div>
            {{-- Zone (FR vacances uniquement) --}}
            <div x-show="showZone" x-cloak>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Zone scolaire</label>
                <select name="zone" x-model="editZone"
                        class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                    <option value="">Toutes les zones</option>
                    <option value="Zone A">Zone A</option>
                    <option value="Zone B">Zone B</option>
                    <option value="Zone C">Zone C</option>
                </select>
            </div>
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                <button type="button" @click="openEdit = false"
                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 rounded-lg hover:bg-gray-100 transition-colors">
                    Annuler
                </button>
                <button type="submit"
                        class="px-5 py-2 text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors shadow-sm">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     MODAL — CONFIRMATION DE SUPPRESSION
══════════════════════════════════════════════════════════ --}}
<div x-show="openDelete" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"  x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="openDelete = false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-gray-900">Supprimer cet événement ?</h3>
                <p class="text-sm text-gray-500 mt-0.5" x-text="`« ${deleteName} »`"></p>
            </div>
        </div>
        <p class="text-sm text-gray-600 mb-6">Cette action est <span class="font-semibold text-red-600">irréversible</span>. L'événement sera définitivement supprimé de la base de données.</p>
        <div class="flex gap-3 justify-end">
            <button @click="openDelete = false"
                    class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 rounded-lg hover:bg-gray-100 transition-colors">
                Annuler
            </button>
            <form :action="`{{ url('admin/calendar/manual') }}/${deleteId}`" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Supprimer définitivement
                </button>
            </form>
        </div>
    </div>
</div>

</div>{{-- fin x-data principal --}}

<style>[x-cloak]{display:none!important}</style>
@endsection
