@extends('admin.layouts.app')
@section('title', 'Tarifs calendrier')
@section('page-title', 'Calendrier tarifaire')
@section('page-subtitle', 'Cliquez et glissez pour sélectionner une plage de dates, puis fixez le prix')

@section('header-actions')
    <a href="{{ route('admin.room-prices.table', ['hotel_id' => $hotelId]) }}"
       class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg">
        📊 Vue tableau
    </a>
    <a href="{{ route('admin.room-prices.create') }}"
       class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg">
        + Période manuelle
    </a>
@endsection

@section('content')
<div x-data="pricingCalendar()" x-init="init()" @mouseup.window="onMouseUp()">

    {{-- ── Barre de contrôles ──────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center gap-3 mb-4">

        {{-- Sélecteur hôtel --}}
        <select x-model="hotelId" @change="loadData()"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none bg-white">
            <option value="">— Choisir un hôtel —</option>
            @foreach($hotels as $h)
            <option value="{{ $h->id }}" {{ $hotelId == $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
            @endforeach
        </select>

        {{-- Navigation 30 jours --}}
        <div class="flex items-center gap-0 bg-white border border-gray-300 rounded-lg overflow-hidden">
            <button @click="shiftDays(-30)" class="px-3 py-2 hover:bg-gray-100 text-gray-600 text-sm border-r border-gray-200">‹ 30j</button>
            <span class="px-4 text-sm font-semibold text-gray-700 min-w-[200px] text-center" x-text="periodLabel"></span>
            <button @click="shiftDays(30)"  class="px-3 py-2 hover:bg-gray-100 text-gray-600 text-sm border-l border-gray-200">30j ›</button>
        </div>

        {{-- Revenir à aujourd'hui --}}
        <button @click="goToday()"
            class="text-sm text-amber-600 hover:text-amber-800 font-medium border border-amber-300 hover:border-amber-500 bg-amber-50 hover:bg-amber-100 px-3 py-2 rounded-lg">
            Aujourd'hui
        </button>

        {{-- Légende --}}
        <div class="flex items-center gap-4 ml-auto text-xs text-gray-500 flex-wrap">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-200 border border-emerald-400 inline-block"></span> Tarif défini</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-200 border border-amber-400 inline-block"></span> Sélectionné</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-gray-100 border border-gray-200 inline-block"></span> Aucun tarif</span>
            <span class="flex items-center gap-1.5"><span class="text-[9px] font-mono font-bold bg-amber-100 text-amber-700 px-1.5 rounded">CODE</span> Config d'occupation</span>
        </div>
    </div>

    {{-- ── Pas d'hôtel sélectionné ────────────────────────────────────────────── --}}
    <div x-show="!hotelId" class="bg-blue-50 border border-blue-200 text-blue-700 rounded-xl p-10 text-center text-sm">
        Sélectionnez un hôtel pour afficher le calendrier tarifaire.
    </div>

    {{-- ── Chargement ─────────────────────────────────────────────────────────── --}}
    <div x-show="hotelId && loading" class="flex items-center justify-center py-16 text-gray-400">
        <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
        </svg>
        Chargement…
    </div>

    {{-- ── Grille calendrier ───────────────────────────────────────────────────── --}}
    <div x-show="hotelId && !loading && roomTypes.length > 0"
         class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="border-collapse" style="table-layout:fixed; min-width: max-content;">
                {{-- En-tête jours --}}
                <thead>
                    <tr>
                        <th class="sticky left-0 z-20 bg-slate-800 text-white text-xs font-semibold px-4 py-3 text-left border-r border-slate-700"
                            style="min-width:200px; width:200px;">
                            Chambre / Config occupation
                        </th>
                        <template x-for="day in days" :key="day">
                            <th class="text-center text-xs font-medium border-r border-gray-100 select-none"
                                :class="isToday(day)
                                    ? 'bg-amber-500 text-white'
                                    : isWeekend(day)
                                        ? 'bg-slate-700 text-amber-400'
                                        : 'bg-slate-800 text-slate-200'"
                                style="width:48px; min-width:48px; padding:4px 0;">
                                <div x-text="dayNum(day)" class="text-sm font-bold leading-tight"></div>
                                <div x-text="dayName(day)" class="text-[9px] uppercase opacity-70"></div>
                                <div x-text="dayMonth(day)" class="text-[9px] opacity-50"></div>
                            </th>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="rt in roomTypes" :key="rt.id">
                        <tr class="border-b border-gray-100" :class="rt.is_auto ? 'opacity-75' : ''">
                            {{-- Nom type chambre --}}
                            <td class="sticky left-0 z-10 border-r border-gray-200 px-3 py-2 text-sm font-medium text-gray-800"
                                style="min-width:200px; width:200px;"
                                :class="rt.occupancy_config_id ? 'bg-amber-50/60' : (rt.has_configs === false ? 'bg-gray-50' : 'bg-white')">
                                <div class="flex flex-col gap-0.5">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        {{-- Nom --}}
                                        <span class="truncate max-w-[130px]" :title="rt.name" x-text="rt.has_configs ? rt.name.split(' — ')[0] : rt.name"></span>
                                        <template x-if="!rt.has_configs">
                                            <span class="text-[9px] font-bold bg-gray-200 text-gray-500 px-1.5 py-0.5 rounded shrink-0" title="Aucune config d'occupation">SANS CONFIG</span>
                                        </template>
                                    </div>
                                    {{-- Config d'occupation --}}
                                    <template x-if="rt.occupancy_config_id">
                                        <span class="text-[10px] font-mono text-amber-700 bg-amber-100 px-1.5 py-0.5 rounded w-fit"
                                              x-text="rt.config_code"></span>
                                    </template>
                                    <template x-if="!rt.occupancy_config_id && rt.has_configs === false">
                                        <span class="text-[10px] text-gray-400">→ Ajoutez des configs d'occupation</span>
                                    </template>
                                </div>
                            </td>
                            {{-- Cellules jours --}}
                            <template x-for="day in days" :key="day">
                                <td class="border-r border-gray-100 text-center select-none"
                                    style="width:48px; min-width:48px; height:46px; padding:0;"
                                    :class="[rt.has_configs === false ? 'bg-gray-50 cursor-not-allowed' : (rt.is_auto ? 'cursor-not-allowed' : 'cursor-pointer'), cellClass(rt.id, day)]"
                                    @mousedown.prevent="if (!rt.is_auto && rt.has_configs !== false) startDrag(rt.id, day, rt.room_type_id, rt.occupancy_config_id)"
                                    @mouseover="if (!rt.is_auto && rt.has_configs !== false) continueDrag(rt.id, day)">
                                    <div class="flex items-center justify-center h-full w-full">
                                        <template x-if="rt.prices[day]">
                                            <span class="text-[10px] font-bold leading-tight"
                                                  :class="rt.is_auto ? 'text-purple-600 italic' : (rt.occupancy_config_id ? 'text-amber-700' : '')"
                                                  x-text="formatPrice(rt.prices[day])"></span>
                                        </template>
                                        <template x-if="!rt.prices[day] && !isInSelection(rt.id, day)">
                                            <span class="text-gray-200 text-xs">—</span>
                                        </template>
                                    </div>
                                </td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 text-xs text-gray-400 flex items-center gap-1.5">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Cliquez puis glissez sur une ligne pour sélectionner une plage — relâchez pour définir le prix.
        </div>
    </div>

    {{-- ── Aucun type de chambre trouvé ──────────────────────────────────────── --}}
    <div x-show="hotelId && !loading && roomTypes.length === 0"
         class="bg-amber-50 border border-amber-200 rounded-xl p-12 text-center text-sm space-y-3">
        <p class="text-amber-800 font-semibold text-base">Aucun type de chambre trouvé pour cet hôtel</p>
        <p class="text-amber-600 text-xs">
            Vérifiez que des types de chambres sont bien créés dans
            <strong>Types de chambres</strong> pour cet hôtel,
            puis ajoutez des configs dans <strong>Types de chambres → Occupations</strong>.
        </p>
        <template x-if="debugInfo">
            <div class="mt-4 bg-white border border-amber-300 rounded-lg px-4 py-3 inline-block text-left text-xs font-mono text-gray-600 space-y-1">
                <p class="font-semibold text-gray-800 mb-2">Infos diagnostic :</p>
                <p>Hôtel ID : <span class="text-blue-700" x-text="debugInfo.hotel_id"></span></p>
                <p>Types de chambres en base : <span class="font-bold" :class="debugInfo.room_types_found > 0 ? 'text-green-700' : 'text-red-600'" x-text="debugInfo.room_types_found"></span></p>
                <p>Lignes générées : <span class="font-bold" x-text="debugInfo.rows_generated"></span></p>
            </div>
        </template>
    </div>

    {{-- ── Modal ───────────────────────────────────────────────────────────────── --}}
    <div x-show="modal.open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
         @click.self="modal.open = false">

        <div x-show="modal.open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4"
             @click.stop>

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <h3 class="font-semibold text-gray-900">Définir le tarif</h3>
                    <p class="text-xs text-gray-500 mt-0.5" x-text="modal.roomName"></p>
                    <template x-if="modal.occupancyConfigId">
                        <span class="inline-block text-[10px] font-mono bg-amber-100 text-amber-700 px-2 py-0.5 rounded mt-1">
                            Tarif pour cette occupation uniquement
                        </span>
                    </template>
                </div>
                <button @click="modal.open = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="px-6 py-5 space-y-4">
                {{-- Résumé --}}
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <div class="text-sm">
                        <p class="font-semibold text-amber-800" x-text="modal.roomName"></p>
                        <p class="text-amber-700 mt-0.5">
                            Du <strong x-text="formatDateFr(modal.dateFrom)"></strong>
                            au <strong x-text="formatDateFr(modal.dateTo)"></strong>
                            — <strong x-text="modal.nights + ' nuit' + (modal.nights > 1 ? 's' : '')"></strong>
                        </p>
                    </div>
                </div>

                {{-- Prix --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Prix par nuit <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" x-model="modal.price" min="0" step="50"
                               placeholder="Ex : 1 200"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pr-16 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
                               @keydown.enter="savePrice()"
                               x-ref="priceInput">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-500 font-medium">MAD</span>
                    </div>
                </div>

                {{-- Libellé --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Libellé <span class="text-gray-400 font-normal">(optionnel)</span>
                    </label>
                    <input type="text" x-model="modal.label" placeholder="Ex : Haute saison, Ramadan…"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                </div>

                <p x-show="modal.error" x-text="modal.error" class="text-red-600 text-sm"></p>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 flex gap-3 justify-end">
                <button @click="modal.open = false"
                        class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg border border-gray-200">
                    Annuler
                </button>
                <button @click="savePrice()"
                        :disabled="modal.saving || !modal.price"
                        class="px-5 py-2 text-sm font-medium bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white rounded-lg flex items-center gap-2">
                    <svg x-show="modal.saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    <span x-text="modal.saving ? 'Enregistrement…' : 'Enregistrer'"></span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function pricingCalendar() {
    return {
        // ─── État ─────────────────────────────────────────────────────────────────
        hotelId : '{{ $hotelId ?? '' }}',
        startDate: '',          // YYYY-MM-DD (début fenêtre 30j)
        days    : [],           // 30 dates affichées
        roomTypes: [],
        loading : false,
        debugInfo: null,        // info debug retournée par calendarData

        // Drag
        drag: { active: false, rowId: null, roomTypeId: null, occupancyConfigId: null, startDate: null, endDate: null },

        // Modal
        modal: {
            open: false, saving: false, error: '',
            rowId: null, roomTypeId: null, occupancyConfigId: null, roomName: '',
            dateFrom: null, dateTo: null, nights: 0,
            price: '', label: '',
        },

        // ─── Init ─────────────────────────────────────────────────────────────────
        init() {
            this.goToday();
        },

        goToday() {
            // Fenêtre : aujourd'hui → aujourd'hui + 29 jours
            const today = new Date();
            today.setHours(12, 0, 0, 0);
            this.startDate = this.dateToString(today);
            this.buildDays();
            if (this.hotelId) this.loadData();
        },

        // ─── Navigation ───────────────────────────────────────────────────────────
        shiftDays(n) {
            const d = new Date(this.startDate + 'T12:00:00');
            d.setDate(d.getDate() + n);
            this.startDate = this.dateToString(d);
            this.buildDays();
            if (this.hotelId) this.loadData();
        },

        get periodLabel() {
            if (!this.days.length) return '';
            const a = new Date(this.days[0]  + 'T12:00:00');
            const b = new Date(this.days[this.days.length - 1] + 'T12:00:00');
            const fmt = d => d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
            return fmt(a) + ' → ' + fmt(b);
        },

        buildDays() {
            this.days = [];
            const start = new Date(this.startDate + 'T12:00:00');
            for (let i = 0; i < 30; i++) {
                const d = new Date(start);
                d.setDate(start.getDate() + i);
                this.days.push(this.dateToString(d));
            }
        },

        // ─── Données AJAX ─────────────────────────────────────────────────────────
        async loadData() {
            if (!this.hotelId || !this.days.length) return;
            this.loading = true;
            this.debugInfo = null;
            try {
                const endDate = this.days[this.days.length - 1];
                const url = `{{ route('admin.room-prices.calendar-data') }}?hotel_id=${this.hotelId}&start_date=${this.startDate}&end_date=${endDate}`;
                const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                // Nouveau format : { rows: [...], debug: {...} }
                const rows = Array.isArray(data) ? data : (data.rows || []);
                this.debugInfo = data.debug || null;
                // Assigne une nouvelle référence pour forcer la réactivité Alpine
                this.roomTypes = rows.map(rt => ({ ...rt, prices: { ...rt.prices } }));
                if (this.debugInfo) {
                    console.log('[calendarData debug]', this.debugInfo);
                }
            } catch (e) {
                console.error('loadData error', e);
            } finally {
                this.loading = false;
            }
        },

        // ─── Helpers dates ────────────────────────────────────────────────────────
        dateToString(d) {
            return d.getFullYear() + '-'
                + String(d.getMonth() + 1).padStart(2, '0') + '-'
                + String(d.getDate()).padStart(2, '0');
        },
        dayNum  (date) { return new Date(date + 'T12:00:00').getDate(); },
        dayName (date) { return new Date(date + 'T12:00:00').toLocaleDateString('fr-FR', { weekday: 'short' }).replace('.',''); },
        dayMonth(date) { return new Date(date + 'T12:00:00').toLocaleDateString('fr-FR', { month: 'short' }).replace('.',''); },
        isWeekend(date) { const d = new Date(date + 'T12:00:00').getDay(); return d === 0 || d === 6; },
        isToday(date)   { return date === this.dateToString(new Date()); },

        // ─── Drag & Drop selection ────────────────────────────────────────────────
        startDrag(rowId, date, roomTypeId, occupancyConfigId) {
            this.drag = { active: true, rowId, roomTypeId: roomTypeId ?? rowId, occupancyConfigId: occupancyConfigId ?? null, startDate: date, endDate: date };
        },

        continueDrag(rowId, date) {
            if (!this.drag.active || this.drag.rowId !== rowId) return;
            this.drag.endDate = date;
        },

        onMouseUp() {
            if (!this.drag.active) return;
            const { rowId, roomTypeId, occupancyConfigId, startDate, endDate } = this.drag;
            this.drag.active = false;
            if (!startDate || !endDate || !rowId) return;

            const from = startDate <= endDate ? startDate : endDate;
            const to   = startDate <= endDate ? endDate   : startDate;
            const rt   = this.roomTypes.find(r => r.id === rowId);

            this.modal = {
                open: true, saving: false, error: '',
                rowId, roomTypeId, occupancyConfigId,
                roomName : rt ? rt.name : '',
                dateFrom : from,
                dateTo   : to,
                nights   : this.daysBetween(from, to) + 1,
                price    : rt?.prices?.[from] ?? '',
                label    : '',
            };
            this.$nextTick(() => this.$refs.priceInput?.focus());
        },

        isInSelection(rowId, date) {
            if (this.drag.rowId !== rowId) return false;
            const from = this.drag.startDate <= this.drag.endDate ? this.drag.startDate : this.drag.endDate;
            const to   = this.drag.startDate <= this.drag.endDate ? this.drag.endDate   : this.drag.startDate;
            return date >= from && date <= to;
        },

        cellClass(rowId, date) {
            if (this.isInSelection(rowId, date)) {
                return 'bg-amber-100 border-amber-300';
            }
            const rt = this.roomTypes.find(r => r.id === rowId);
            if (rt?.prices?.[date]) {
                return 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800';
            }
            return this.isToday(date)
                ? 'bg-amber-50 hover:bg-amber-100'
                : 'bg-white hover:bg-gray-50';
        },

        // ─── Sauvegarde ───────────────────────────────────────────────────────────
        async savePrice() {
            if (!this.modal.price) return;
            this.modal.saving = true;
            this.modal.error  = '';

            try {
                const res = await fetch('{{ route('admin.room-prices.bulk-update') }}', {
                    method : 'POST',
                    headers: {
                        'Content-Type'    : 'application/json',
                        'X-CSRF-TOKEN'    : '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        hotel_id             : this.hotelId,
                        room_type_id         : this.modal.roomTypeId,
                        occupancy_config_id  : this.modal.occupancyConfigId,
                        date_from            : this.modal.dateFrom,
                        date_to              : this.modal.dateTo,
                        price_per_night      : this.modal.price,
                        label                : this.modal.label,
                    }),
                });

                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    this.modal.error = err.message ?? 'Erreur lors de l\'enregistrement.';
                    return;
                }

                // ── Mise à jour immédiate des cellules dans Alpine ──────────────
                const price  = parseFloat(this.modal.price);
                const rowId  = this.modal.rowId;
                const fromDate = this.modal.dateFrom;
                const toDate   = this.modal.dateTo;

                this.roomTypes = this.roomTypes.map(rt => {
                    if (rt.id !== rowId) return rt;
                    const updatedPrices = { ...rt.prices };
                    let cur = new Date(fromDate + 'T12:00:00');
                    const end = new Date(toDate + 'T12:00:00');
                    while (cur <= end) {
                        updatedPrices[this.dateToString(cur)] = price;
                        cur.setDate(cur.getDate() + 1);
                    }
                    return { ...rt, prices: updatedPrices };
                });
                // ──────────────────────────────────────────────────────────────

                this.modal.open = false;

            } catch (e) {
                this.modal.error = 'Erreur réseau. Réessayez.';
                console.error(e);
            } finally {
                this.modal.saving = false;
            }
        },

        // ─── Utilitaires ──────────────────────────────────────────────────────────
        daysBetween(a, b) {
            return Math.round((new Date(b + 'T12:00:00') - new Date(a + 'T12:00:00')) / 86400000);
        },

        formatPrice(p) {
            return new Intl.NumberFormat('fr-MA', { maximumFractionDigits: 0 }).format(p);
        },

        formatDateFr(date) {
            if (!date) return '';
            return new Date(date + 'T12:00:00').toLocaleDateString('fr-FR', {
                day: 'numeric', month: 'short', year: 'numeric',
            });
        },
    };
}
</script>
@endpush