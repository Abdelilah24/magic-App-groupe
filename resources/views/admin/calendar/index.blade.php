@extends('admin.layouts.app')

@section('title', 'Calendrier')
@section('page-title', 'Calendrier')
@section('page-subtitle', 'Jours fériés et vacances scolaires — Maroc & France')

@section('header-actions')
    <form action="{{ route('admin.calendar.sync') }}" method="POST" class="flex items-center gap-2">
        @csrf
        <select name="year"
                class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
            @for($y = now()->year - 1; $y <= now()->year + 3; $y++)
            <option value="{{ $y }}" {{ $y === $currentYear ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
        <button type="submit"
                class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold text-sm px-4 py-2 rounded-lg transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Synchroniser
        </button>
    </form>
@endsection

@section('content')
<div class="flex gap-6" x-data="{
    countries: ['MA','FR','GB'],
    zone: 'B',
    showModal: false,
    editId: null,
    editName: '',
    editStart: '',
    editEnd: '',
    editCountry: 'MA',
    editType: 'holiday',
    editZone: '',

    toggleCountry(c) {
        const idx = this.countries.indexOf(c);
        if (idx === -1) { this.countries.push(c); }
        else { if (this.countries.length > 1) this.countries.splice(idx, 1); }
        window.__fc && window.__fc.refetchEvents();
    },

    hasCountry(c) { return this.countries.includes(c); },

    changeZone(z) { this.zone = z; window.__fc && window.__fc.refetchEvents(); },

    openCreate() {
        this.editId      = null;
        this.editName    = '';
        this.editStart   = '';
        this.editEnd     = '';
        this.editCountry = 'MA';
        this.editType    = 'holiday';
        this.editZone    = '';
        this.showModal   = true;
    },

    openEdit(id, name, start, end, country, type, zone) {
        this.editId      = id;
        this.editName    = name;
        this.editStart   = start;
        this.editEnd     = end;
        this.editCountry = country;
        this.editType    = type;
        this.editZone    = zone || '';
        this.showModal   = true;
    },

    get showZone() {
        return this.editCountry === 'FR' && this.editType === 'school_vacation';
    }
}">

{{-- ════════════════════════════════════════════════
     PANNEAU LATÉRAL GAUCHE (filtres + légende + CRUD)
════════════════════════════════════════════════ --}}
<aside class="w-72 shrink-0 space-y-4">

    {{-- Filtres --}}
    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
        <h3 class="text-sm font-bold text-gray-900 mb-4">Filtres</h3>

        {{-- Pays --}}
        <div class="mb-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Pays</p>
            <div class="space-y-2">
                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input type="checkbox" :checked="hasCountry('MA')" @change="toggleCountry('MA')"
                           class="w-4 h-4 rounded accent-amber-500">
                    <span class="text-sm font-medium text-gray-700">Maroc</span>
                    <span class="ml-auto text-xs text-gray-400">MA</span>
                </label>
                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input type="checkbox" :checked="hasCountry('FR')" @change="toggleCountry('FR')"
                           class="w-4 h-4 rounded accent-blue-500">
                    <span class="text-sm font-medium text-gray-700">France</span>
                    <span class="ml-auto text-xs text-gray-400">FR</span>
                </label>
                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input type="checkbox" :checked="hasCountry('GB')" @change="toggleCountry('GB')"
                           class="w-4 h-4 rounded accent-orange-500">
                    <span class="text-sm font-medium text-gray-700">Royaume-Uni</span>
                    <span class="ml-auto text-xs text-gray-400">UK</span>
                </label>
            </div>
        </div>

        {{-- Zone scolaire France --}}
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Zone scolaire (France)</p>
            <div class="flex gap-1.5">
                @foreach(['A','B','C'] as $z)
                <button @click="changeZone('{{ $z }}')"
                        :class="zone === '{{ $z }}'
                            ? 'bg-blue-600 text-white border-blue-600'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-blue-300'"
                        class="flex-1 px-3 py-1.5 text-xs font-bold border rounded-lg transition-colors">
                    Zone {{ $z }}
                </button>
                @endforeach
            </div>
            <p class="text-xs text-gray-400 mt-1.5">
                A = Lyon · B = Paris · C = Marseille
            </p>
            <p class="text-xs text-gray-400 mt-0.5">
                Non applicable pour MA et UK.
            </p>
        </div>
    </div>

    {{-- Légende --}}
    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
        <h3 class="text-sm font-bold text-gray-900 mb-3">Légende</h3>
        <div class="space-y-2">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Jours fériés</p>
            <div class="flex items-center gap-3">
                <div class="w-4 h-3.5 rounded-sm shrink-0" style="background:#dc2626"></div>
                <span class="text-xs text-gray-700">France</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-4 h-3.5 rounded-sm shrink-0" style="background:#ffff00;border:1px solid #d1d5db"></div>
                <span class="text-xs text-gray-700">Maroc</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-4 h-3.5 rounded-sm shrink-0" style="background:#ea580c"></div>
                <span class="text-xs text-gray-700">Royaume-Uni</span>
            </div>
            <div class="w-full border-t border-gray-100 my-2"></div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Vacances scolaires</p>
            <div class="flex items-center gap-3">
                <div class="w-4 h-3.5 rounded-sm shrink-0" style="background:#2563eb"></div>
                <span class="text-xs text-gray-700">France</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-4 h-3.5 rounded-sm shrink-0" style="background:#7c3aed"></div>
                <span class="text-xs text-gray-700">Maroc</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-4 h-3.5 rounded-sm shrink-0" style="background:#16a34a"></div>
                <span class="text-xs text-gray-700">Royaume-Uni</span>
            </div>
        </div>
    </div>

    {{-- Événements manuels — CRUD --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900">Événements manuels</h3>
            <button @click="openCreate()"
                    class="text-xs font-semibold text-amber-600 hover:text-amber-700 bg-amber-50 hover:bg-amber-100 px-2.5 py-1 rounded-lg transition-colors">
                + Ajouter
            </button>
        </div>

        @if($manualEvents->isEmpty())
        <div class="px-5 py-6 text-center text-xs text-gray-400">
            Aucun événement manuel saisi.
        </div>
        @else
        <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
            @foreach($manualEvents as $ev)
            @php
                $countryColor = $ev->country === 'MA' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800';
                $typeColor    = $ev->type === 'holiday' ? 'bg-red-100 text-red-700' : 'bg-indigo-100 text-indigo-700';
                $typeLabel    = $ev->type === 'holiday' ? 'Férié' : 'Vacances';
            @endphp
            <div class="px-4 py-3">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 mb-1 flex-wrap">
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded {{ $countryColor }}">{{ $ev->country }}</span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded {{ $typeColor }}">{{ $typeLabel }}</span>
                            @if($ev->zone)
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">{{ $ev->zone }}</span>
                            @endif
                        </div>
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $ev->name }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ $ev->start_date->format('d/m/Y') }}
                            @if($ev->start_date->format('Y-m-d') !== $ev->end_date->format('Y-m-d'))
                            – {{ $ev->end_date->format('d/m/Y') }}
                            @endif
                        </p>
                    </div>
                    <div class="flex gap-1 shrink-0 mt-1">
                        <button @click="openEdit(
                                    {{ $ev->id }},
                                    {{ json_encode($ev->name) }},
                                    '{{ $ev->start_date->format('Y-m-d') }}',
                                    '{{ $ev->end_date->format('Y-m-d') }}',
                                    '{{ $ev->country }}',
                                    '{{ $ev->type }}',
                                    {{ json_encode($ev->zone) }}
                                )"
                                class="text-xs text-blue-600 hover:text-blue-800 px-1.5 py-1 rounded hover:bg-blue-50 transition-colors">
                            Modifier
                        </button>
                        <form action="{{ route('admin.calendar.manual.destroy', $ev) }}" method="POST"
                              onsubmit="return confirm('Supprimer cet événement ?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="text-xs text-red-500 hover:text-red-700 px-1.5 py-1 rounded hover:bg-red-50 transition-colors">
                                Suppr.
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Années disponibles --}}
    @if($availableYears->isNotEmpty())
    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
        <h3 class="text-sm font-bold text-gray-900 mb-2">Années en base</h3>
        <div class="flex flex-wrap gap-1.5">
            @foreach($availableYears as $yr)
            <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $yr === $currentYear ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600' }}">
                {{ $yr }}
            </span>
            @endforeach
        </div>
        <p class="text-xs text-gray-400 mt-2">Utilisez "Synchroniser" pour ajouter une nouvelle année.</p>
    </div>
    @endif

</aside>

{{-- ════════════════════════════════════════════════
     CALENDRIER FULLCALENDAR
════════════════════════════════════════════════ --}}
<div class="flex-1 min-w-0">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
        <div id="fullcalendar" class="min-h-[620px]"></div>
    </div>
</div>

{{-- ════════════════════════════════════════════════
     MODAL AJOUTER / MODIFIER ÉVÉNEMENT MANUEL
════════════════════════════════════════════════ --}}
<div x-show="showModal" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showModal = false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-900"
                x-text="editId ? 'Modifier l\'événement' : 'Ajouter un événement'"></h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Un seul formulaire dont l'action change selon create/edit --}}
        <form :action="editId
                    ? `{{ url('admin/calendar/manual') }}/${editId}`
                    : '{{ route('admin.calendar.manual.store') }}'"
              method="POST" class="px-6 py-5 space-y-4">
            @csrf
            <input type="hidden" name="_method" :value="editId ? 'PUT' : 'POST'">

            {{-- Pays + Type --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Pays <span class="text-red-500">*</span>
                    </label>
                    <select name="country" x-model="editCountry" required
                            class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent bg-white">
                        <option value="MA">🇲🇦 Maroc</option>
                        <option value="FR">🇫🇷 France</option>
                        <option value="GB">🇬🇧 Royaume-Uni</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Type <span class="text-red-500">*</span>
                    </label>
                    <select name="type" x-model="editType" required
                            class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent bg-white">
                        <option value="holiday">Jour férié</option>
                        <option value="school_vacation">Vacances scolaires</option>
                    </select>
                </div>
            </div>

            {{-- Libellé --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Libellé <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" x-model="editName" required maxlength="255"
                       placeholder="Ex : Aïd Al-Adha, Toussaint…"
                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent">
            </div>

            {{-- Dates --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Début <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="start_date" x-model="editStart" required
                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Fin <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="end_date" x-model="editEnd" required
                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent">
                </div>
            </div>

            {{-- Zone scolaire — uniquement pour les vacances FR --}}
            <div x-show="showZone" x-cloak>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Zone scolaire
                    <span class="text-xs font-normal text-gray-400 ml-1">(ex : Zone B)</span>
                </label>
                <input type="text" name="zone" x-model="editZone" maxlength="100"
                       placeholder="Zone A · Zone B · Zone C · Toutes zones"
                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent">
                <p class="text-xs text-gray-400 mt-1">
                    Laissez vide pour appliquer à toutes les zones.
                </p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                <button type="button" @click="showModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 rounded-lg hover:bg-gray-100 transition-colors">
                    Annuler
                </button>
                <button type="submit"
                        :class="editId
                            ? 'bg-blue-600 hover:bg-blue-700'
                            : 'bg-amber-500 hover:bg-amber-600'"
                        class="px-5 py-2 text-sm font-semibold text-white rounded-lg transition-colors shadow-sm"
                        x-text="editId ? 'Enregistrer' : 'Ajouter'">
                </button>
            </div>
        </form>
    </div>
</div>

</div>{{-- end flex container --}}

<style>
[x-cloak] { display: none !important; }

/* FullCalendar overrides */
#fullcalendar .fc-toolbar-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
}
#fullcalendar .fc-button {
    background: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    color: #475569 !important;
    font-size: 0.8rem !important;
    font-weight: 600 !important;
    border-radius: 0.5rem !important;
    padding: 0.4rem 0.8rem !important;
    text-transform: capitalize !important;
    box-shadow: none !important;
    transition: all 0.15s !important;
}
#fullcalendar .fc-button:hover {
    background: #f1f5f9 !important;
    border-color: #cbd5e1 !important;
    color: #1e293b !important;
}
#fullcalendar .fc-button-primary:not(:disabled).fc-button-active,
#fullcalendar .fc-button-primary:not(:disabled):active {
    background: #f59e0b !important;
    border-color: #f59e0b !important;
    color: #fff !important;
}
#fullcalendar .fc-today-button {
    background: #0f172a !important;
    border-color: #0f172a !important;
    color: #fff !important;
}
#fullcalendar .fc-daygrid-day.fc-day-today {
    background: #fffbeb !important;
}
#fullcalendar .fc-event {
    border-radius: 4px !important;
    font-size: 0.72rem !important;
    font-weight: 600 !important;
    padding: 1px 4px !important;
    cursor: pointer !important;
}
#fullcalendar .fc-col-header-cell {
    background: #f8fafc;
    font-size: 0.75rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
#fullcalendar .fc-daygrid-day-number {
    font-size: 0.8rem;
    color: #374151;
    font-weight: 500;
    padding: 4px 6px !important;
}
</style>
@endsection

@push('scripts')
{{-- FullCalendar 6 --}}
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<script>
document.addEventListener('alpine:initialized', function () {
    const calEl = document.getElementById('fullcalendar');

    // On remonte depuis #fullcalendar vers son ancêtre [x-data] pour cibler
    // EXACTEMENT le composant Alpine du calendrier — et non le premier [x-data]
    // du DOM qui appartient au layout (x-data="{ sidebarOpen: false }").
    const xEl   = calEl.closest('[x-data]');
    const state = Alpine.$data(xEl); // proxy réactif Alpine 3

    const calendar = new FullCalendar.Calendar(calEl, {
        locale:              'fr',
        initialView:         'dayGridMonth',
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,dayGridYear',
        },
        height:              'auto',
        firstDay:            1,
        fixedWeekCount:      false,
        showNonCurrentDates: false,

        // Source d'événements dynamique (AJAX)
        // `state` est le proxy Alpine — countries et zone sont toujours à jour
        events: function (info, successCallback, failureCallback) {
            const countries = (state.countries ?? ['MA', 'FR', 'GB']).join(',');
            const zone      = state.zone ?? 'B';
            const url = `{{ route('admin.calendar.events') }}?start=${info.startStr}&end=${info.endStr}&countries=${countries}&zone=${zone}`;

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r  => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                .then(data => successCallback(data))
                .catch(err => {
                    console.error('FullCalendar events error:', err);
                    failureCallback(err);
                });
        },

        // Tooltip au survol
        eventDidMount: function (info) {
            const p = info.event.extendedProps;
            info.el.title =
                `${info.event.title}\n${p.countryLabel} · ${p.typeLabel}` +
                (p.zone ? `\n${p.zone}` : '');
        },

        eventClick: function (info) {
            // Extensible : ouvrir un modal de détail ici
        },
    });

    calendar.render();

    // Expose globalement pour que toggleCountry/changeZone (Alpine) puissent appeler refetchEvents()
    // window.__fc est fiable même si Alpine re-crée son proxy interne
    window.__fc = calendar;
});
</script>
@endpush
