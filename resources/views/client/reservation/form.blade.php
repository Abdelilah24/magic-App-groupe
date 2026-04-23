@extends('layouts.client')
@section('title', 'Demande de réservation groupe')

@section('content')
<div x-data="reservationForm()" class="space-y-6">

    {{-- Header --}}
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Demande de réservation groupe</h1>
        <p class="text-gray-500 mt-2">Bonjour <strong>{{ $link->agency_name }}</strong>, veuillez remplir le formulaire ci-dessous.</p>
        @if($hotel)
        <p class="text-amber-600 font-medium mt-1">{{ $hotel->name }}</p>
        @if($hotel->meal_plan)
        <span class="inline-block mt-1 text-xs font-semibold px-3 py-1 rounded-full
            @if($hotel->meal_plan === 'all_inclusive') bg-emerald-100 text-emerald-700
            @elseif($hotel->meal_plan === 'full_board') bg-blue-100 text-blue-700
            @elseif($hotel->meal_plan === 'half_board') bg-indigo-100 text-indigo-700
            @else bg-gray-100 text-gray-700 @endif">
            🍽️ {{ $hotel->meal_plan_label }}
        </span>
        @endif
        @endif
    </div>

    <form id="reservationForm" action="{{ route('client.reservation.store', $token) }}" method="POST">
        @csrf
        <input type="hidden" name="hotel_id" value="{{ $hotel?->id }}">
        <input type="hidden" name="check_in"       :value="overallCheckIn">
        <input type="hidden" name="check_out"      :value="overallCheckOut">
        <input type="hidden" name="total_persons"  :value="totalPersons">

        {{-- Infos agence --}}
        <div class="bg-white border border-gray-200 rounded-xl p-6 mb-4">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Informations agence</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'agence *</label>
                    <input type="text" name="agency_name" required
                        value="{{ old('agency_name', $link->agency_name) }}"
                        class="w-full border @error('agency_name') border-red-300 @else border-gray-200 @enderror rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                    @error('agency_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom du responsable *</label>
                    <input type="text" name="contact_name" required
                        value="{{ old('contact_name', $link->contact_name) }}"
                        class="w-full border @error('contact_name') border-red-300 @else border-gray-200 @enderror rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                    @error('contact_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input type="email" name="email" required
                        value="{{ old('email', $link->agency_email) }}"
                        class="w-full border @error('email') border-red-300 @else border-gray-200 @enderror rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                    @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                    <input type="text" name="phone" value="{{ old('phone', $link->contact_phone) }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                </div>
            </div>
        </div>

        {{-- ── SÉJOURS ─────────────────────────────────────────────────────── --}}
        <template x-for="(stay, stayIdx) in stays" :key="stayIdx">
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden mb-4 shadow-sm">

                {{-- En-tête séjour --}}
                <div class="flex items-center justify-between px-5 py-3 bg-amber-50 border-b border-amber-100">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-sm font-semibold text-amber-900">
                            Séjour <span x-text="stayIdx + 1"></span>
                            <span x-show="nightsFor(stayIdx) > 0" class="font-normal text-amber-700">
                                — <span x-text="nightsFor(stayIdx)"></span> nuit(s)
                                · <span x-text="personsForStay(stayIdx)"></span> pers.
                            </span>
                        </span>
                    </div>
                    <button type="button" x-show="stays.length > 1" @click="removeStay(stayIdx)"
                        class="text-xs text-red-400 hover:text-red-600 flex items-center gap-1 px-2 py-1 rounded hover:bg-red-50 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Supprimer ce séjour
                    </button>
                </div>

                <div class="p-5 space-y-4">

                    {{-- Dates --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Date d'arrivée *</label>
                            <input type="date" :name="`stays[${stayIdx}][check_in]`" required
                                x-model="stay.check_in"
                                min="{{ date('Y-m-d') }}"
                                @change="calculatePriceForStay(stayIdx)"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Date de départ *</label>
                            <input type="date" :name="`stays[${stayIdx}][check_out]`" required
                                x-model="stay.check_out"
                                :min="stay.check_in || '{{ date('Y-m-d', strtotime('+1 day')) }}'"
                                @change="calculatePriceForStay(stayIdx)"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        </div>
                    </div>

                    {{-- En-têtes colonnes (desktop) --}}
                    <div class="hidden sm:flex gap-2 text-xs font-medium text-gray-400 px-1 items-end">
                        <div class="flex-1">Chambre &amp; Occupation</div>
                        <div class="w-14 text-center">Qté</div>
                        <div class="w-20 text-center">Adult.</div>
                        <div class="w-20 text-center">Enf.</div>
                        <div class="w-20 text-center">Bébés</div>
                        <div class="w-8"></div>
                    </div>

                    {{-- Lignes chambres --}}
                    <template x-for="(room, roomIdx) in stay.rooms" :key="roomIdx">
                        <div class="space-y-1.5 pb-2 border-b border-gray-100 last:border-0 last:pb-0">

                            {{-- Champs cachés pour la soumission du formulaire --}}
                            <input type="hidden" :name="`stays[${stayIdx}][rooms][${roomIdx}][room_type_id]`" :value="room.room_type_id">
                            <input type="hidden" :name="`stays[${stayIdx}][rooms][${roomIdx}][occupancy_config_id]`" :value="room.occupancy_config_id || ''">

                            {{-- Ligne principale : combo + qté + personnes + suppr --}}
                            <div class="flex flex-wrap sm:flex-nowrap gap-2 items-center">

                                {{-- ▶ Combo chambre + occupation --}}
                                <div class="w-full sm:flex-1">
                                    <label class="block text-xs text-gray-500 mb-1 sm:hidden">Chambre &amp; Occupation *</label>
                                    <select required
                                        :value="room.room_type_id && room.occupancy_config_id
                                                ? room.room_type_id + '|' + room.occupancy_config_id
                                                : ''"
                                        @change="selectRoomConfig(stayIdx, roomIdx, $event.target.value)"
                                        class="w-full border rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none bg-white"
                                        :class="!room.occupancy_config_id ? 'border-amber-300' : 'border-gray-200'">
                                        <option value="">— Choisir chambre &amp; occupation —</option>
                                        @foreach($roomTypes as $rt)
                                        @if($rt->activeOccupancyConfigs->isNotEmpty())
                                        <optgroup label="{{ $rt->name }}">
                                            @foreach($rt->activeOccupancyConfigs->sortBy('sort_order') as $cfg)
                                            <option value="{{ $rt->id }}|{{ $cfg->id }}">{{ $cfg->label }}</option>
                                            @endforeach
                                        </optgroup>
                                        @else
                                        <optgroup label="{{ $rt->name }}">
                                            <option value="{{ $rt->id }}|">{{ $rt->name }}</option>
                                        </optgroup>
                                        @endif
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Quantité --}}
                                <div class="w-14 shrink-0">
                                    <label class="block text-xs text-gray-500 mb-1 sm:hidden">Qté</label>
                                    <input type="number" :name="`stays[${stayIdx}][rooms][${roomIdx}][quantity]`" required
                                        x-model.number="room.quantity" min="1"
                                        @change="recalculateAllStays()"
                                        class="w-full border border-gray-200 rounded-lg px-2 py-2.5 text-sm text-center focus:ring-2 focus:ring-amber-400 focus:outline-none">
                                </div>

                                {{-- Adultes --}}
                                <div class="w-20 shrink-0">
                                    <label class="block text-xs text-gray-500 mb-1 sm:hidden">Adultes</label>
                                    <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-amber-400">
                                        <span class="px-1.5 text-gray-400 text-xs bg-gray-50 py-2.5 border-r border-gray-200 shrink-0">A</span>
                                        <input type="number" :name="`stays[${stayIdx}][rooms][${roomIdx}][adults]`"
                                            x-model.number="room.adults"
                                            :min="getConfigById(room.occupancy_config_id)?.min_adults ?? 0"
                                            :max="getConfigById(room.occupancy_config_id)?.max_adults ?? 20"
                                            @change="clampPersons(stayIdx, roomIdx); calculatePriceForStay(stayIdx)"
                                            class="flex-1 px-1 py-2.5 text-sm text-center focus:outline-none w-0 min-w-0">
                                    </div>
                                </div>

                                {{-- Enfants --}}
                                <div class="w-20 shrink-0">
                                    <label class="block text-xs text-gray-500 mb-1 sm:hidden">Enfants</label>
                                    <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-amber-400">
                                        <span class="px-1.5 text-gray-400 text-xs bg-gray-50 py-2.5 border-r border-gray-200 shrink-0">E</span>
                                        <input type="number" :name="`stays[${stayIdx}][rooms][${roomIdx}][children]`"
                                            x-model.number="room.children"
                                            :min="getConfigById(room.occupancy_config_id)?.min_children ?? 0"
                                            :max="getConfigById(room.occupancy_config_id)?.max_children ?? 10"
                                            @change="clampPersons(stayIdx, roomIdx); calculatePriceForStay(stayIdx)"
                                            class="flex-1 px-1 py-2.5 text-sm text-center focus:outline-none w-0 min-w-0">
                                    </div>
                                </div>

                                {{-- Bébés --}}
                                <div class="w-20 shrink-0">
                                    <label class="block text-xs text-gray-500 mb-1 sm:hidden">Bébés</label>
                                    <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-amber-400">
                                        <span class="px-1.5 text-gray-400 text-xs bg-gray-50 py-2.5 border-r border-gray-200 shrink-0">B</span>
                                        <input type="number" :name="`stays[${stayIdx}][rooms][${roomIdx}][babies]`"
                                            x-model.number="room.babies"
                                            min="0"
                                            :max="getConfigById(room.occupancy_config_id)?.max_babies ?? 5"
                                            @change="clampPersons(stayIdx, roomIdx); calculatePriceForStay(stayIdx)"
                                            class="flex-1 px-1 py-2.5 text-sm text-center focus:outline-none w-0 min-w-0">
                                    </div>
                                </div>

                                {{-- Supprimer chambre --}}
                                <div class="w-8 shrink-0 flex justify-end">
                                    <button type="button" x-show="stay.rooms.length > 1"
                                        @click="removeRoom(stayIdx, roomIdx)"
                                        class="p-2 text-red-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Indication plage de personnes de la config sélectionnée --}}
                            <template x-if="getConfigById(room.occupancy_config_id)">
                                <p class="text-xs text-gray-400 pl-1">
                                    Plage : <span x-text="getConfigById(room.occupancy_config_id)?.min_adults"></span>–<span x-text="getConfigById(room.occupancy_config_id)?.max_adults"></span> adulte(s)
                                    <template x-if="(getConfigById(room.occupancy_config_id)?.max_children ?? 0) > 0">
                                        <span>, 0–<span x-text="getConfigById(room.occupancy_config_id)?.max_children"></span> enfant(s)</span>
                                    </template>
                                    <template x-if="(getConfigById(room.occupancy_config_id)?.max_babies ?? 0) > 0">
                                        <span>, 0–<span x-text="getConfigById(room.occupancy_config_id)?.max_babies"></span> bébé(s)</span>
                                    </template>
                                </p>
                            </template>

                            {{-- Lit bébé supplémentaire (conditionnel) --}}
                            <div x-show="capacityFor(room.room_type_id).babyBed" x-transition>
                                <label class="inline-flex items-center gap-2 cursor-pointer p-2.5 bg-blue-50 border border-blue-100 rounded-lg w-full sm:w-auto">
                                    <input type="checkbox"
                                        :name="`stays[${stayIdx}][rooms][${roomIdx}][baby_bed]`"
                                        value="1"
                                        x-model="room.baby_bed"
                                        class="rounded border-gray-300 text-amber-500">
                                    <span class="text-xs text-gray-700 font-medium">🍼 Demander un lit bébé supplémentaire</span>
                                </label>
                            </div>

                            {{-- Avertissement capacité --}}
                            <div x-show="hasCapacityErrorFor(stayIdx, roomIdx)" x-transition>
                                <div class="flex items-start gap-2 rounded-lg px-3 py-2 text-xs border"
                                    :class="isOverCapacity(stayIdx, roomIdx) ? 'bg-red-50 border-red-200 text-red-700' : 'bg-orange-50 border-orange-200 text-orange-700'">
                                    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                    </svg>
                                    <span x-show="isOverCapacity(stayIdx, roomIdx)">
                                        <strong>Capacité dépassée :</strong>
                                        max <strong x-text="capacityFor(room.room_type_id).max"></strong> pers. par chambre
                                        (vous avez entré <strong x-text="(room.adults||0)+(room.children||0)+(room.babies||0)"></strong>).
                                    </span>
                                    <span x-show="isUnderCapacity(stayIdx, roomIdx)">
                                        <strong>Minimum non atteint :</strong>
                                        minimum <strong x-text="capacityFor(room.room_type_id).min"></strong> pers. par chambre
                                        (vous avez entré <strong x-text="(room.adults||0)+(room.children||0)+(room.babies||0)"></strong>).
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Ajouter chambre dans ce séjour --}}
                    <div>
                        <button type="button" @click="addRoom(stayIdx)"
                            class="text-xs text-amber-600 hover:text-amber-700 font-medium flex items-center gap-1 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Ajouter un type de chambre
                        </button>
                    </div>

                    {{-- Prix estimé pour ce séjour --}}
                    <template x-if="priceResults[stayIdx]">
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mt-2">
                            <template x-for="line in priceResults[stayIdx].breakdown">
                                <div class="flex justify-between text-xs py-0.5 text-amber-800">
                                    <span x-text="`${line.quantity} × ${line.occupancy_label || line.room_type_name} × ${priceResults[stayIdx].nights} nuits`"></span>
                                    <span class="font-semibold" x-text="`${formatPrice(line.line_total)} MAD`"></span>
                                </div>
                            </template>
                            {{-- Taxe de séjour --}}
                            <template x-if="priceResults[stayIdx].taxe_sejour_total > 0">
                                <div class="flex justify-between text-xs py-0.5 text-blue-700">
                                    <span x-text="`🏨 Taxe de séjour (${priceResults[stayIdx].taxe_sejour_adults} adulte(s) × ${priceResults[stayIdx].nights} nuit(s) × ${formatPrice(priceResults[stayIdx].taxe_sejour_rate)} DHS)`"></span>
                                    <span class="font-semibold" x-text="`${formatPrice(priceResults[stayIdx].taxe_sejour_total)} MAD`"></span>
                                </div>
                            </template>
                            <div class="flex justify-between text-sm font-bold mt-2 pt-2 border-t border-amber-200 text-amber-900">
                                <span>Sous-total séjour <span x-text="stayIdx + 1"></span></span>
                                <span x-text="`${formatPrice(priceResults[stayIdx].total + priceResults[stayIdx].taxe_sejour_total)} MAD`"></span>
                            </div>
                        </div>
                    </template>

                </div>
            </div>
        </template>

        {{-- Bouton ajouter un séjour --}}
        <div class="flex justify-center mb-4">
            <button type="button" @click="addStay()"
                class="inline-flex items-center gap-2 text-sm font-semibold text-amber-600 hover:text-amber-700 bg-white border-2 border-dashed border-amber-300 hover:border-amber-400 px-6 py-3 rounded-xl w-full justify-center transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Ajouter un autre séjour (autre date)
            </button>
        </div>

        {{-- Grand total --}}
        <div x-show="grandTotal > 0" class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-4 space-y-2">

            {{-- Titre --}}
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-amber-900">RÉCAPITULATIF ESTIMÉ</p>
                    <p class="text-xs text-amber-600 mt-0.5">
                        <span x-text="stays.length"></span> séjour(s) ·
                        <span x-text="totalPersons"></span> personne(s)
                    </p>
                </div>
            </div>

            {{-- Détail lignes --}}
            <div class="space-y-1 pt-2 border-t border-amber-200">
                {{-- Chambres --}}
                <div class="flex justify-between text-sm text-amber-800">
                    <span>Hébergement (chambres)</span>
                    <span class="font-medium" x-text="`${formatPrice(grandTotal)} MAD`"></span>
                </div>

                {{-- Remise groupe --}}
                <template x-if="groupFreePersons > 0">
                    <div class="flex justify-between text-sm text-green-700">
                        <span>🎁 Remise groupe (<span x-text="groupFreePersons"></span> pers. gratuite(s), 1/20 payants)</span>
                        <span class="font-medium" x-text="`− ${formatPrice(groupDiscountAmount)} MAD`"></span>
                    </div>
                </template>

                {{-- Taxe de séjour --}}
                <template x-if="taxeTotalGlobal > 0">
                    <div class="flex justify-between text-sm text-blue-700">
                        <span>🏨 Taxe de séjour (adultes uniquement)</span>
                        <span class="font-medium" x-text="`${formatPrice(taxeTotalGlobal)} MAD`"></span>
                    </div>
                </template>

                {{-- Suppléments obligatoires --}}
                <template x-for="sup in mandatorySupplements" :key="sup.id">
                    <div class="text-sm text-orange-700">
                        <div class="flex justify-between">
                            <span>🍽️ <span x-text="sup.title"></span> <span class="text-xs text-orange-500">(obligatoire · <span x-text="sup.date"></span>)</span></span>
                            <span class="font-medium" x-text="`${formatPrice(sup.total)} MAD`"></span>
                        </div>
                        <div class="text-xs text-orange-400 pl-5 mt-0.5 space-x-2">
                            <template x-if="sup.adults > 0 && sup.price_adult > 0">
                                <span x-text="`${sup.adults} adulte(s) × ${formatPrice(sup.price_adult)} MAD`"></span>
                            </template>
                            <template x-if="sup.children > 0 && sup.price_child > 0">
                                <span x-text="`${sup.children} enfant(s) × ${formatPrice(sup.price_child)} MAD`"></span>
                            </template>
                            <template x-if="sup.babies > 0 && sup.price_baby > 0">
                                <span x-text="`${sup.babies} bébé(s) × ${formatPrice(sup.price_baby)} MAD`"></span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Suppléments optionnels --}}
            <template x-if="optionalSupplements.length > 0">
                <div class="pt-2 border-t border-amber-200">
                    <p class="text-xs font-semibold text-gray-700 mb-2">Suppléments optionnels :</p>
                    <template x-for="sup in optionalSupplements" :key="sup.id">
                        <label class="flex items-start justify-between gap-3 cursor-pointer py-1.5 hover:bg-amber-50 rounded-lg px-1 -mx-1 transition-colors">
                            <span class="flex items-start gap-2">
                                <input type="checkbox"
                                    :value="sup.id"
                                    :name="`selected_supplements[]`"
                                    x-model="selectedOptionalSupplements"
                                    class="rounded border-gray-300 text-amber-500 mt-0.5 shrink-0">
                                <span class="text-sm text-gray-700">
                                    🎪 <span x-text="sup.title"></span>
                                    <span class="text-xs text-gray-400">(<span x-text="sup.date"></span>)</span>
                                    {{-- Détail par type de personne --}}
                                    <span class="block text-xs text-gray-400 mt-0.5 space-x-1">
                                        <template x-if="sup.adults > 0 && sup.price_adult > 0">
                                            <span x-text="`${sup.adults} adulte(s) × ${formatPrice(sup.price_adult)} MAD`"></span>
                                        </template>
                                        <template x-if="sup.children > 0 && sup.price_child > 0">
                                            <span x-text="`· ${sup.children} enfant(s) × ${formatPrice(sup.price_child)} MAD`"></span>
                                        </template>
                                        <template x-if="sup.babies > 0 && sup.price_baby > 0">
                                            <span x-text="`· ${sup.babies} bébé(s) × ${formatPrice(sup.price_baby)} MAD`"></span>
                                        </template>
                                    </span>
                                </span>
                            </span>
                            <span class="text-sm font-semibold text-gray-800 whitespace-nowrap pt-0.5" x-text="`${formatPrice(sup.total)} MAD`"></span>
                        </label>
                    </template>
                </div>
            </template>

            {{-- Ligne récap suppléments optionnels sélectionnés --}}
            <template x-if="selectedOptionalTotal > 0">
                <div class="flex justify-between text-sm text-purple-700 pt-1">
                    <span>🎪 Suppléments optionnels sélectionnés</span>
                    <span class="font-medium" x-text="`+ ${formatPrice(selectedOptionalTotal)} MAD`"></span>
                </div>
            </template>

            {{-- Grand total final --}}
            <div class="flex items-center justify-between pt-3 border-t border-amber-300">
                <p class="text-base font-bold text-amber-900">TOTAL ESTIMÉ</p>
                <p class="text-2xl font-bold text-amber-700" x-text="`${formatPrice(grandTotalWithExtras)} MAD`"></p>
            </div>

            <p class="text-xs text-amber-500">* Prix indicatif, confirmé après validation par notre équipe.</p>
        </div>

        {{-- Options flexibles --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5 mb-4">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">Options de flexibilité</h2>
            <div class="space-y-3">
                <label class="flex items-start gap-3 cursor-pointer group">
                    <input type="checkbox" name="flexible_dates" value="1"
                        class="mt-0.5 rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                    <div>
                        <p class="text-sm font-medium text-gray-800 group-hover:text-amber-700">📅 Dates flexibles</p>
                        <p class="text-xs text-gray-500 mt-0.5">Si les dates demandées ne sont pas disponibles, j'accepte qu'on me propose des dates alternatives proches.</p>
                    </div>
                </label>
                <label class="flex items-start gap-3 cursor-pointer group">
                    <input type="checkbox" name="flexible_hotel" value="1"
                        class="mt-0.5 rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                    <div>
                        <p class="text-sm font-medium text-gray-800 group-hover:text-amber-700">🏨 Hôtel flexible</p>
                        <p class="text-xs text-gray-500 mt-0.5">Si cet hôtel n'est pas disponible, j'accepte qu'on me propose un hôtel de niveau équivalent.</p>
                    </div>
                </label>
            </div>
        </div>

        {{-- Erreur capacité globale --}}
        <div x-show="hasCapacityError" x-transition
            class="bg-red-50 border border-red-200 rounded-xl px-5 py-3 mb-4 flex items-center gap-2 text-sm text-red-700">
            <svg class="w-5 h-5 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <span>Veuillez corriger les erreurs de capacité avant de soumettre.</span>
        </div>

        {{-- Demandes spéciales --}}
        <div class="bg-white border border-gray-200 rounded-xl p-6 mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Demandes spéciales</label>
            <textarea name="special_requests" rows="2"
                placeholder="Transfert aéroport, régime alimentaire, chambres communicantes..."
                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">{{ old('special_requests') }}</textarea>
        </div>

        {{-- Soumettre --}}
        <div class="flex justify-end">
            <button type="submit"
                :disabled="hasCapacityError"
                :class="hasCapacityError
                    ? 'inline-flex items-center gap-2 bg-gray-300 text-gray-500 cursor-not-allowed font-semibold px-8 py-3 rounded-xl text-sm'
                    : 'inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold px-8 py-3 rounded-xl text-sm transition shadow-sm hover:shadow'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Soumettre ma demande
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// Capacité par type de chambre
const roomTypeCapacity = {
    @foreach($roomTypes as $rt)
    {{ $rt->id }}: {
        min: {{ $rt->min_persons ?? 1 }},
        max: {{ $rt->max_persons ?? 999 }},
        maxAdults: {{ $rt->max_adults !== null ? $rt->max_adults : 'null' }},
        maxChildren: {{ $rt->max_children !== null ? $rt->max_children : 'null' }},
        babyBed: {{ $rt->baby_bed_available ? 'true' : 'false' }},
    },
    @endforeach
};

// Configurations d'occupation par type de chambre
const roomTypeConfigs = @json($roomTypeConfigs);

function reservationForm() {
    return {
        stays: [{
            check_in: '',
            check_out: '',
            rooms: [{ room_type_id: '', quantity: 1, adults: 1, children: 0, babies: 0, baby_bed: false, occupancy_config_id: null }]
        }],
        priceResults: [null],
        selectedHotelId: '{{ $hotel?->id }}',
        selectedOptionalSupplements: [],

        // ── Computed ──────────────────────────────────────────────────────────

        get totalPersons() {
            const total = this.stays.reduce((sum, stay) => {
                return sum + stay.rooms.reduce((rs, r) => {
                    const qty = parseInt(r.quantity) || 1;
                    return rs + ((parseInt(r.adults)||0) + (parseInt(r.children)||0) + (parseInt(r.babies)||0)) * qty;
                }, 0);
            }, 0);
            return total || 1;
        },

        // Total chambres uniquement (base pour remise groupe)
        get grandTotal() {
            return this.priceResults.reduce((sum, r) => sum + (r?.total || 0), 0);
        },

        // Taxe de séjour globale (somme de tous les séjours)
        get taxeTotalGlobal() {
            return this.priceResults.reduce((sum, r) => sum + Math.round(r?.taxe_sejour_total || 0), 0);
        },

        // Suppléments applicables, avec fusion des personnes pour les suppléments
        // dont la date chevauche plusieurs séjours (ex: soirée 17/04 dans séjour 1
        // ET séjour 2 → adultes et enfants des deux séjours sont cumulés).
        get allApplicableSupplements() {
            const merged = new Map(); // id → données fusionnées
            for (const r of this.priceResults.filter(Boolean)) {
                for (const s of (r.supplements || [])) {
                    if (!merged.has(s.id)) {
                        merged.set(s.id, { ...s });
                    } else {
                        // Fusionner les personnes de ce séjour supplémentaire
                        const m = merged.get(s.id);
                        m.adults   = (m.adults   || 0) + (s.adults   || 0);
                        m.children = (m.children || 0) + (s.children || 0);
                        m.babies   = (m.babies   || 0) + (s.babies   || 0);
                        // Recalculer le total avec les personnes cumulées
                        m.total = Math.round(
                            m.adults   * (m.price_adult || 0) +
                            m.children * (m.price_child || 0) +
                            m.babies   * (m.price_baby  || 0)
                        );
                    }
                }
            }
            return Array.from(merged.values());
        },

        get mandatorySupplements() {
            return this.allApplicableSupplements.filter(s => s.is_mandatory);
        },

        get optionalSupplements() {
            return this.allApplicableSupplements.filter(s => !s.is_mandatory);
        },

        get mandatorySupplementTotal() {
            return this.mandatorySupplements.reduce((sum, s) => sum + (s.total || 0), 0);
        },

        get selectedOptionalTotal() {
            return this.optionalSupplements
                .filter(s => this.selectedOptionalSupplements.some(sel => sel == s.id))
                .reduce((sum, s) => sum + (s.total || 0), 0);
        },

        // Grand total final = chambres − remise groupe + taxe + suppléments
        get grandTotalWithExtras() {
            return Math.max(0, this.grandTotal - this.groupDiscountAmount + this.taxeTotalGlobal + this.mandatorySupplementTotal + this.selectedOptionalTotal);
        },

        // Remise groupe : 1 gratuit pour 20 payants → floor(total / 21) gratuits
        get groupFreePersons() {
            return Math.floor(this.totalPersons / 21);
        },

        // Montant estimé de la remise groupe (50% sur les chambres les moins chères)
        get groupDiscountAmount() {
            const free = this.groupFreePersons;
            if (free === 0 || this.grandTotal === 0) return 0;

            // Regrouper toutes les lignes de prix de tous les séjours
            const allLines = this.priceResults
                .filter(Boolean)
                .flatMap(r => (r.breakdown || []).map(b => ({
                    ...b,
                    unit_per_night: b.line_total / (b.quantity * (r.nights || 1)),
                })))
                .sort((a, b) => a.unit_per_night - b.unit_per_night); // moins chers en premier

            let discount = 0;
            let remaining = free;
            for (const line of allLines) {
                if (remaining <= 0) break;
                const units = Math.min(remaining, line.quantity);
                discount += line.unit_per_night * (line.nights || 1) * 0.50 * units;
                remaining -= units;
            }
            return Math.round(discount * 100) / 100;
        },

        get overallCheckIn() {
            const dates = this.stays.map(s => s.check_in).filter(Boolean);
            return dates.length ? dates.reduce((a, b) => a < b ? a : b) : '';
        },

        get overallCheckOut() {
            const dates = this.stays.map(s => s.check_out).filter(Boolean);
            return dates.length ? dates.reduce((a, b) => a > b ? a : b) : '';
        },

        // Vrai si au moins une ligne a une erreur de capacité (min ou max)
        get hasCapacityError() {
            return this.stays.some((stay, si) =>
                stay.rooms.some((room, ri) => this.hasCapacityErrorFor(si, ri))
            );
        },

        // ── Helpers ───────────────────────────────────────────────────────────

        nightsFor(stayIdx) {
            const s = this.stays[stayIdx];
            if (!s.check_in || !s.check_out) return 0;
            const d1 = new Date(s.check_in), d2 = new Date(s.check_out);
            return Math.max(0, Math.round((d2 - d1) / 86400000));
        },

        personsForStay(stayIdx) {
            return this.stays[stayIdx].rooms.reduce((sum, r) => {
                const qty = parseInt(r.quantity) || 1;
                return sum + ((parseInt(r.adults)||0) + (parseInt(r.children)||0) + (parseInt(r.babies)||0)) * qty;
            }, 0);
        },

        capacityFor(roomTypeId) {
            return roomTypeCapacity[roomTypeId] || { min: 1, max: 999 };
        },

        // ── Configs d'occupation ──────────────────────────────────────────────

        hasConfigs(roomTypeId) {
            return (roomTypeConfigs[roomTypeId] || []).length > 0;
        },

        // Retourne les configs compatibles avec les effectifs saisis
        configsFor(roomTypeId, adults, children, babies) {
            const all = roomTypeConfigs[roomTypeId] || [];
            if (!all.length) return [];
            const a = parseInt(adults)   || 0;
            const c = parseInt(children) || 0;
            const b = parseInt(babies)   || 0;
            // Si aucun effectif saisi, afficher toutes les configs
            if (a === 0 && c === 0 && b === 0) return all;
            return all.filter(cfg =>
                a >= cfg.min_adults   && a <= cfg.max_adults &&
                c >= cfg.min_children && c <= cfg.max_children &&
                b >= (cfg.min_babies  || 0) && b <= (cfg.max_babies || 0)
            );
        },

        // Sélectionne automatiquement la config la plus spécifique parmi celles qui correspondent
        autoSelectConfig(stayIdx, roomIdx) {
            const room = this.stays[stayIdx]?.rooms[roomIdx];
            if (!room || !room.room_type_id) return;

            const configs = this.configsFor(room.room_type_id, room.adults, room.children, room.babies);

            if (configs.length === 0) {
                room.occupancy_config_id = null;
                return;
            }

            // Si la config actuelle correspond encore, la garder
            if (room.occupancy_config_id && configs.find(c => c.id == room.occupancy_config_id)) return;

            // Sinon, choisir la config la plus spécifique :
            // celle dont la plage (max_adults - min_adults) + (max_children - min_children) est la plus petite
            const best = configs.reduce((a, b) => {
                const rangeA = (a.max_adults - a.min_adults) + (a.max_children - a.min_children);
                const rangeB = (b.max_adults - b.min_adults) + (b.max_children - b.min_children);
                return rangeA <= rangeB ? a : b;
            });
            room.occupancy_config_id = best.id;
        },

        // Applique la sélection du combo chambre+occupation (valeur "rtId|cfgId")
        selectRoomConfig(stayIdx, roomIdx, value) {
            const room = this.stays[stayIdx]?.rooms[roomIdx];
            if (!room) return;

            if (!value || !value.includes('|')) {
                room.room_type_id        = '';
                room.occupancy_config_id = null;
                this.calculatePriceForStay(stayIdx);
                return;
            }

            const parts = value.split('|');
            const rtId  = parseInt(parts[0]);
            const cfgId = parts[1] ? parseInt(parts[1]) : null;

            room.room_type_id        = rtId;
            room.occupancy_config_id = cfgId;

            // Pré-remplir les effectifs à partir de la config sélectionnée
            if (cfgId) {
                const cfg = this.getConfigById(cfgId);
                if (cfg) {
                    room.adults   = Math.max(1, cfg.min_adults   || 0);
                    room.children = cfg.min_children || 0;
                    room.babies   = 0;
                }
            }

            this.calculatePriceForStay(stayIdx);
        },

        // Retourne la config d'occupation par son id (cherche dans roomTypeConfigs)
        getConfigById(cfgId) {
            if (!cfgId) return null;
            const id = parseInt(cfgId);
            for (const configs of Object.values(roomTypeConfigs)) {
                const found = configs.find(c => c.id === id);
                if (found) return found;
            }
            return null;
        },

        // Clamp adults/children/babies selon les limites de la config sélectionnée
        clampPersons(stayIdx, roomIdx) {
            const room = this.stays[stayIdx]?.rooms[roomIdx];
            if (!room) return;
            const cfg = this.getConfigById(room.occupancy_config_id);
            if (!cfg) return;
            const clamp = (v, min, max) => Math.min(Math.max(parseInt(v) || 0, min), max);
            room.adults   = clamp(room.adults,   cfg.min_adults   ?? 0, cfg.max_adults   ?? 99);
            room.children = clamp(room.children, cfg.min_children ?? 0, cfg.max_children ?? 99);
            room.babies   = clamp(room.babies,   0,                     cfg.max_babies   ?? 99);
        },

        // Vrai si une limite de capacité est dépassée
        // adults/children/babies sont PAR chambre → on compare aux limites de la config, pas × qty
        isOverCapacity(stayIdx, roomIdx) {
            const room = this.stays[stayIdx]?.rooms[roomIdx];
            if (!room || !room.room_type_id) return false;
            const cap = this.capacityFor(room.room_type_id);
            const adults   = parseInt(room.adults)   || 0;
            const children = parseInt(room.children) || 0;
            const babies   = parseInt(room.babies)   || 0;
            const persons  = adults + children + babies;
            if (persons > cap.max) return true;
            if (cap.maxAdults   !== null && adults   > cap.maxAdults)   return true;
            if (cap.maxChildren !== null && children > cap.maxChildren) return true;
            return false;
        },

        // Vrai si personnes par chambre < min_persons (et qu'on a sélectionné un type)
        isUnderCapacity(stayIdx, roomIdx) {
            const room = this.stays[stayIdx]?.rooms[roomIdx];
            if (!room || !room.room_type_id) return false;
            const cap     = this.capacityFor(room.room_type_id);
            if (!cap.min || cap.min <= 1) return false;
            const persons = (parseInt(room.adults)||0) + (parseInt(room.children)||0) + (parseInt(room.babies)||0);
            return persons < cap.min;
        },

        hasCapacityErrorFor(stayIdx, roomIdx) {
            return this.isOverCapacity(stayIdx, roomIdx) || this.isUnderCapacity(stayIdx, roomIdx);
        },

        // ── Séjours ───────────────────────────────────────────────────────────

        addStay() {
            this.stays.push({
                check_in: '',
                check_out: '',
                rooms: [{ room_type_id: '', quantity: 1, adults: 1, children: 0, babies: 0, baby_bed: false, occupancy_config_id: null }]
            });
            this.priceResults.push(null);
        },

        removeStay(idx) {
            this.stays.splice(idx, 1);
            this.priceResults.splice(idx, 1);
            this.$nextTick(() => this.recalculateAllStays());
        },

        // ── Chambres ─────────────────────────────────────────────────────────

        addRoom(stayIdx) {
            this.stays[stayIdx].rooms.push({ room_type_id: '', quantity: 1, adults: 1, children: 0, babies: 0, baby_bed: false, occupancy_config_id: null });
        },

        removeRoom(stayIdx, roomIdx) {
            this.stays[stayIdx].rooms.splice(roomIdx, 1);
            this.recalculateAllStays();
        },

        // ── Prix ─────────────────────────────────────────────────────────────

        // Recalcule tous les séjours — nécessaire quand la quantité de chambres change
        // car le tarif appliqué dépend du total global de chambres (NRF vs GROUPE_DIRECT)
        async recalculateAllStays() {
            await Promise.all(this.stays.map((_, idx) => this.calculatePriceForStay(idx)));
        },

        async calculatePriceForStay(stayIdx) {
            const stay   = this.stays[stayIdx];
            const nights = this.nightsFor(stayIdx);
            if (!stay.check_in || !stay.check_out || nights <= 0) {
                this.priceResults[stayIdx] = null;
                this.priceResults = [...this.priceResults];
                return;
            }
            const validRooms = stay.rooms.filter(r => r.room_type_id && r.quantity > 0);
            if (!validRooms.length) return;

            try {
                const roomsPayload = validRooms.map(r => ({
                    room_type_id:        r.room_type_id,
                    quantity:            r.quantity,
                    adults:              r.adults   || 0,
                    children:            r.children || 0,
                    babies:              r.babies   || 0,
                    occupancy_config_id: r.occupancy_config_id || null,
                }));
                // Total de toutes les chambres sur tous les séjours (pour choisir le bon tarif)
                const totalRooms = this.stays.reduce((sum, s) =>
                    sum + s.rooms.filter(r => r.room_type_id && r.quantity > 0)
                                 .reduce((s2, r) => s2 + (parseInt(r.quantity) || 1), 0), 0);

                const resp = await fetch('{{ route('client.calculate-price') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        hotel_id:    this.selectedHotelId,
                        check_in:    stay.check_in,
                        check_out:   stay.check_out,
                        rooms:       roomsPayload,
                        token:       '{{ $token }}',
                        total_rooms: totalRooms,
                    })
                });
                const data = await resp.json();
                if (data.success) {
                    this.priceResults[stayIdx] = data;
                    this.priceResults = [...this.priceResults]; // force Alpine reactivity
                }
            } catch(e) {}
        },

        formatPrice(n) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(n));
        },
    }
}
</script>
@endpush
