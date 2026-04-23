@extends('admin.layouts.app')
@section('title', 'Nouveau tarif')
@section('page-title', 'Ajouter un tarif')

@section('content')
<div class="max-w-xl" x-data="priceCreateForm">
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <form action="{{ route('admin.room-prices.store') }}" method="POST">
            @csrf
            <div class="space-y-4">

                {{-- Hôtel --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hôtel *</label>
                    <select name="hotel_id" required x-model="hotelId" @change="loadRoomTypes()"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        <option value="">— Choisir —</option>
                        @foreach($hotels as $h)
                        <option value="{{ $h->id }}" {{ old('hotel_id', request('hotel_id')) == $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
                        @endforeach
                    </select>
                    @error('hotel_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Type de chambre --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type de chambre *</label>
                    <select name="room_type_id" required x-model="selectedRoomTypeId"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        <option value="">— Choisir d'abord un hôtel —</option>
                        <template x-for="rt in roomTypes" :key="rt.id">
                            <option :value="rt.id" x-text="rt.name"></option>
                        </template>
                    </select>
                    @error('room_type_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Config d'occupation --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Config d'occupation *</label>
                    <select name="occupancy_config_id" required x-model="selectedConfigId"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
                        :disabled="occupancyConfigs.length === 0">
                        <option value="">— Choisir d'abord un type de chambre —</option>
                        <template x-for="cfg in occupancyConfigs" :key="cfg.id">
                            <option :value="cfg.id" x-text="cfg.label + ' (' + cfg.code + ')'"></option>
                        </template>
                    </select>
                    <p x-show="selectedRoomTypeId && occupancyConfigs.length === 0"
                       class="text-amber-600 text-xs mt-1">
                        Ce type n'a aucune configuration d'occupation. Ajoutez-en dans Configs occupation.
                    </p>
                    @error('occupancy_config_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Dates --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date début *</label>
                        <input type="date" name="date_from" required value="{{ old('date_from') }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date fin *</label>
                        <input type="date" name="date_to" required value="{{ old('date_to') }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                    </div>
                </div>

                {{-- Prix + libellé --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prix par nuit (MAD) *</label>
                        <input type="number" name="price_per_night" required min="0" step="0.01"
                            value="{{ old('price_per_night') }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Libellé période</label>
                        <input type="text" name="label" value="{{ old('label') }}" placeholder="Ex : Haute saison"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                    </div>
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" checked
                        class="rounded border-gray-300 text-amber-500">
                    <span class="text-sm text-gray-700">Actif</span>
                </label>
            </div>

            <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100">
                <button type="submit"
                    :disabled="!selectedConfigId"
                    :class="selectedConfigId
                        ? 'bg-amber-500 hover:bg-amber-600 cursor-pointer'
                        : 'bg-gray-300 cursor-not-allowed'"
                    class="text-white font-medium px-6 py-2 rounded-lg text-sm transition-colors">
                    Créer le tarif
                </button>
                <a href="{{ route('admin.room-prices.index') }}"
                   class="text-gray-500 text-sm px-4 py-2">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('priceCreateForm', () => ({
        hotelId:            '{{ old('hotel_id', request('hotel_id')) }}',
        roomTypes:          @json($roomTypes),   // tableau initial (hôtel présélectionné)
        selectedRoomTypeId: '',
        occupancyConfigs:   [],
        selectedConfigId:   '',

        init() {
            // Quand le type de chambre change, mettre à jour les configs
            this.$watch('selectedRoomTypeId', val => {
                const rt = this.roomTypes.find(r => r.id == val);
                this.occupancyConfigs = rt ? (rt.configs ?? []) : [];
                this.selectedConfigId = '';
            });

            // Si un hôtel est déjà présélectionné, charger ses types
            if (this.hotelId) {
                this.loadRoomTypes();
            }
        },

        loadRoomTypes() {
            if (!this.hotelId) {
                this.roomTypes = [];
                this.occupancyConfigs = [];
                this.selectedRoomTypeId = '';
                this.selectedConfigId = '';
                return;
            }
            fetch('/admin/room-prices/hotel/' + this.hotelId + '/room-types-with-configs')
                .then(r => r.json())
                .then(d => {
                    this.roomTypes = d;
                    this.selectedRoomTypeId = '';
                    this.occupancyConfigs = [];
                    this.selectedConfigId = '';
                });
        },
    }));
});
</script>
@endpush
