@extends('admin.layouts.app')
@section('title', 'Configs d\'occupation')
@section('page-title', 'Configurations d\'occupation')
@section('page-subtitle', 'Définissez les occupations tarifaires par type de chambre (ex : QUAD 2, QUAD 3…)')

@section('content')
<div x-data="occupancyManager()" x-init="init()">

    {{-- ── Sélecteur hôtel ─────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center gap-3 mb-6">
        <select x-model="hotelId" @change="loadRoomTypes()"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none bg-white">
            <option value="">— Choisir un hôtel —</option>
            @foreach($hotels as $h)
            <option value="{{ $h->id }}" {{ $hotel?->id == $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
            @endforeach
        </select>

        <div class="ml-auto flex items-center gap-2 text-xs text-gray-400">
            <span class="w-3 h-3 rounded bg-blue-100 border border-blue-300 inline-block"></span> Type de chambre
            <span class="w-3 h-3 rounded bg-amber-100 border border-amber-300 inline-block ml-2"></span> Config occupation
        </div>
    </div>

    {{-- ── Liste types de chambres + configs ──────────────────────────────── --}}
    <div x-show="!hotelId" class="bg-blue-50 border border-blue-200 text-blue-700 rounded-xl p-10 text-center text-sm">
        Sélectionnez un hôtel pour gérer ses configurations d'occupation.
    </div>

    <div x-show="hotelId && loading" class="flex items-center justify-center py-16 text-gray-400">
        <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
        </svg>
        Chargement…
    </div>

    <div x-show="hotelId && !loading" class="space-y-4">
        <template x-for="rt in roomTypes" :key="rt.id">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm">

                {{-- Header type --}}
                <div class="flex items-center justify-between px-5 py-3 bg-blue-50 border-b border-blue-100">
                    <div>
                        <p class="font-semibold text-blue-900 text-sm" x-text="rt.name"></p>
                        <p class="text-xs text-blue-500 mt-0.5" x-text="rt.configs.length + ' config(s) d\'occupation'"></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="openAddForm(rt.id)" type="button"
                            class="text-xs bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-lg font-medium">
                            + Ajouter
                        </button>
                    </div>
                </div>

                {{-- Liste configs --}}
                <div x-show="rt.configs.length === 0" class="px-5 py-6 text-sm text-gray-400 text-center">
                    Aucune config d'occupation — toutes les occupations au même tarif (comportement par défaut).
                </div>

                <table x-show="rt.configs.length > 0" class="w-full text-sm rounded-b-xl overflow-hidden">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Code</th>
                            <th class="px-4 py-2 text-left font-medium">Libellé</th>
                            <th class="px-4 py-2 text-center font-medium">Adultes</th>
                            <th class="px-4 py-2 text-center font-medium">Enfants</th>
                            <th class="px-4 py-2 text-center font-medium">Bébés</th>
                            <th class="px-4 py-2 text-center font-medium">Actif</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="cfg in rt.configs" :key="cfg.id">
                            <tr class="hover:bg-amber-50/40" :class="!cfg.is_active ? 'opacity-50' : ''">
                                <td class="px-4 py-2.5">
                                    <span class="font-mono text-xs font-bold bg-gray-100 text-gray-700 px-2 py-0.5 rounded" x-text="cfg.code"></span>
                                </td>
                                <td class="px-4 py-2.5 text-gray-800 font-medium" x-text="cfg.label"></td>
                                <td class="px-4 py-2.5 text-center text-gray-600">
                                    <span x-text="cfg.min_adults === cfg.max_adults ? cfg.min_adults : cfg.min_adults + '–' + cfg.max_adults"></span>
                                </td>
                                <td class="px-4 py-2.5 text-center text-gray-600">
                                    <span x-text="cfg.min_children === cfg.max_children ? cfg.min_children : cfg.min_children + '–' + cfg.max_children"></span>
                                </td>
                                <td class="px-4 py-2.5 text-center text-gray-600">
                                    <span x-text="cfg.max_babies > 0 ? '0–' + cfg.max_babies : '—'"></span>
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <span :class="cfg.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                        class="text-xs font-medium px-2 py-0.5 rounded-full"
                                        x-text="cfg.is_active ? '✓' : '✗'"></span>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <button @click="openEditForm(rt.id, cfg)" type="button"
                                        class="text-xs text-blue-600 hover:text-blue-800 font-medium mr-2">Modifier</button>
                                    <button @click="deleteConfig(rt.id, cfg.id)" type="button"
                                        class="text-xs text-red-400 hover:text-red-600">Supprimer</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

            </div>
        </template>
    </div>

    {{-- ── Modal ajouter / modifier ────────────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="form.open" x-transition
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4"
             @click.self="form.open = false">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg" @click.stop>
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900" x-text="form.editId ? 'Modifier la configuration' : 'Nouvelle configuration'"></h3>
                    <button @click="form.open = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Code * <span class="text-gray-400 font-normal">(ex: QUAD_3)</span></label>
                            <input type="text" x-model="form.code" :disabled="!!form.editId"
                                placeholder="QUAD_3"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-amber-400 focus:outline-none disabled:bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Ordre d'affichage</label>
                            <input type="number" x-model.number="form.sort_order" min="0"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Libellé * <span class="text-gray-400 font-normal">(ex: QUAD 3 — 3 adultes + 1 enfant)</span></label>
                        <input type="text" x-model="form.label"
                            placeholder="QUAD 3 — 3 adultes + 1 enfant"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Adultes min</label>
                            <input type="number" x-model.number="form.min_adults" min="0" max="10"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Adultes max</label>
                            <input type="number" x-model.number="form.max_adults" min="0" max="10"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Enfants min</label>
                            <input type="number" x-model.number="form.min_children" min="0" max="10"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Enfants max</label>
                            <input type="number" x-model.number="form.max_children" min="0" max="10"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Bébés max</label>
                            <input type="number" x-model.number="form.max_babies" min="0" max="5"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                Coefficient
                                <span class="text-gray-400 font-normal">(ex: 1.3500)</span>
                            </label>
                            <input type="number" x-model.number="form.coefficient" min="0.0001" max="99" step="0.0001"
                                placeholder="1.0000"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-amber-400 focus:outline-none">
                            <p class="text-[10px] text-gray-400 mt-0.5">Multiplicateur du taux de base → prix = taux × coeff</p>
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" x-model="form.is_active"
                                    class="rounded border-gray-300 text-amber-500">
                                <span class="text-sm text-gray-700 font-medium">Active</span>
                            </label>
                        </div>
                    </div>

                    <p x-show="form.error" x-text="form.error" class="text-red-600 text-sm"></p>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 flex gap-3 justify-end">
                    <button @click="form.open = false" type="button"
                        class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg border border-gray-200">
                        Annuler
                    </button>
                    <button @click="saveConfig()" :disabled="form.saving"
                        class="px-5 py-2 text-sm font-medium bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white rounded-lg flex items-center gap-2">
                        <svg x-show="form.saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <span x-text="form.saving ? 'Enregistrement…' : (form.editId ? 'Modifier' : 'Créer')"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script>
// ── Presets Aqua Mirage ────────────────────────────────────────────────────
const PRESETS = {
    standard: [
        { code: 'SGL_DBL', label: 'SGL/DBL — 1-2 adultes + 0-2 enfants', min_adults:1, max_adults:2, min_children:0, max_children:2, min_babies:0, max_babies:0, sort_order:0 },
        { code: 'STN_DBL', label: 'STN DBL — 2 adultes',                  min_adults:2, max_adults:2, min_children:0, max_children:0, min_babies:0, max_babies:0, sort_order:1 },
        { code: 'TPL',     label: 'TPL — 3 adultes',                       min_adults:3, max_adults:3, min_children:0, max_children:0, min_babies:0, max_babies:0, sort_order:2 },
        { code: 'STN_TPL', label: 'STN TPL — 3 adultes (sans enfant)',     min_adults:3, max_adults:3, min_children:0, max_children:0, min_babies:0, max_babies:0, sort_order:3 },
    ],
    quadruple: [
        { code: 'QUAD_2', label: 'QUAD 2 — 2 adultes + 0-2 enfants',  min_adults:2, max_adults:2, min_children:0, max_children:2, min_babies:0, max_babies:0, sort_order:0 },
        { code: 'QUAN_2', label: 'QUAN 2 — 2 adultes (lit séparé)',    min_adults:2, max_adults:2, min_children:0, max_children:2, min_babies:0, max_babies:0, sort_order:1 },
        { code: 'QUAD_3', label: 'QUAD 3 — 3 adultes + 0-1 enfant',   min_adults:3, max_adults:3, min_children:0, max_children:1, min_babies:0, max_babies:0, sort_order:2 },
        { code: 'QUAN_3', label: 'QUAN 3 — 3 adultes (lit séparé)',    min_adults:3, max_adults:3, min_children:0, max_children:1, min_babies:0, max_babies:0, sort_order:3 },
        { code: 'QUAD_4', label: 'QUAD 4 — 4 adultes',                 min_adults:4, max_adults:4, min_children:0, max_children:0, min_babies:0, max_babies:0, sort_order:4 },
        { code: 'QUAN_4', label: 'QUAN 4 — 4 adultes (lit séparé)',    min_adults:4, max_adults:4, min_children:0, max_children:0, min_babies:0, max_babies:0, sort_order:5 },
    ],
    quintuple: [
        { code: 'QUINT_2', label: 'QUINT 2 — 2 adultes + 0-3 enfants', min_adults:2, max_adults:2, min_children:0, max_children:3, min_babies:0, max_babies:0, sort_order:0 },
        { code: 'QUINT_3', label: 'QUINT 3 — 3 adultes + 0-2 enfants', min_adults:3, max_adults:3, min_children:0, max_children:2, min_babies:0, max_babies:0, sort_order:1 },
        { code: 'QUINT_4', label: 'QUINT 4 — 4 adultes + 0-1 enfant',  min_adults:4, max_adults:4, min_children:0, max_children:1, min_babies:0, max_babies:0, sort_order:2 },
        { code: 'QUINT_5', label: 'QUINT 5 — 5 adultes',               min_adults:5, max_adults:5, min_children:0, max_children:0, min_babies:0, max_babies:0, sort_order:3 },
    ],
    communicante: [
        { code: 'COM_4', label: 'COM 4 — 1-4 adultes + 0-5 enfants', min_adults:1, max_adults:4, min_children:0, max_children:5, min_babies:0, max_babies:0, sort_order:0 },
        { code: 'COM_5', label: 'COM 5 — 5 adultes + 0-1 enfant',     min_adults:5, max_adults:5, min_children:0, max_children:1, min_babies:0, max_babies:0, sort_order:1 },
        { code: 'COM_6', label: 'COM 6 — 6 adultes',                  min_adults:6, max_adults:6, min_children:0, max_children:0, min_babies:0, max_babies:0, sort_order:2 },
    ],
    pmr: [
        { code: 'STD_PMR', label: 'STD PMR — 1-2 adultes + 0-1 enfant', min_adults:1, max_adults:2, min_children:0, max_children:1, min_babies:0, max_babies:0, sort_order:0 },
    ],
};

function occupancyManager() {
    return {
        hotelId  : '{{ $hotel?->id ?? '' }}',
        roomTypes: [],
        loading  : false,

        form: {
            open: false, saving: false, error: '',
            roomTypeId: null, editId: null,
            code: '', label: '',
            min_adults: 1, max_adults: 2,
            min_children: 0, max_children: 0,
            min_babies: 0, max_babies: 0,
            sort_order: 0, is_active: true,
            coefficient: 1.0000,
        },

        init() {
            if (this.hotelId) this.loadRoomTypes();
        },

        async loadRoomTypes() {
            if (!this.hotelId) return;
            this.loading = true;
            try {
                const res  = await fetch(`/admin/occupancy-configs/hotel/${this.hotelId}/room-types`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                this.roomTypes = await res.json();
            } catch(e) { console.error(e); }
            finally { this.loading = false; }
        },

        openAddForm(roomTypeId) {
            this.form = { open:true, saving:false, error:'', roomTypeId, editId:null,
                code:'', label:'', min_adults:1, max_adults:1, min_children:0, max_children:0,
                min_babies:0, max_babies:0, sort_order: (this.roomTypes.find(r=>r.id===roomTypeId)?.configs?.length ?? 0),
                is_active:true, coefficient: 1.0000 };
        },

        openEditForm(roomTypeId, cfg) {
            this.form = { open:true, saving:false, error:'', roomTypeId, editId:cfg.id,
                code: cfg.code, label: cfg.label,
                min_adults: cfg.min_adults, max_adults: cfg.max_adults,
                min_children: cfg.min_children, max_children: cfg.max_children,
                min_babies: cfg.min_babies, max_babies: cfg.max_babies,
                sort_order: cfg.sort_order, is_active: cfg.is_active,
                coefficient: cfg.coefficient ?? 1.0000 };
        },

        async saveConfig() {
            if (!this.form.code || !this.form.label) { this.form.error = 'Code et libellé requis.'; return; }
            this.form.saving = true; this.form.error = '';

            const url    = this.form.editId
                ? `/admin/occupancy-configs/${this.form.editId}`
                : '/admin/occupancy-configs';
            const method = this.form.editId ? 'PUT' : 'POST';

            try {
                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'X-Requested-With':'XMLHttpRequest' },
                    body: JSON.stringify({ ...this.form, room_type_id: this.form.roomTypeId }),
                });
                const data = await res.json();
                if (!res.ok) { this.form.error = data.message ?? 'Erreur.'; return; }

                this.form.open = false;
                await this.loadRoomTypes();
            } catch(e) { this.form.error = 'Erreur réseau.'; }
            finally { this.form.saving = false; }
        },

        async deleteConfig(roomTypeId, configId) {
            if (!confirm('Supprimer cette configuration ?')) return;
            await fetch(`/admin/occupancy-configs/${configId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'X-Requested-With':'XMLHttpRequest' },
            });
            await this.loadRoomTypes();
        },

        async applyPreset(roomTypeId, presetKey) {
            if (!confirm(`Appliquer le preset "${presetKey}" ? Toutes les configs existantes seront remplacées.`)) return;
            const configs = PRESETS[presetKey];
            if (!configs) return;

            await fetch('/admin/occupancy-configs/bulk', {
                method: 'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'X-Requested-With':'XMLHttpRequest' },
                body: JSON.stringify({ room_type_id: roomTypeId, configs }),
            });
            await this.loadRoomTypes();
        },
    };
}
</script>
@endpush
