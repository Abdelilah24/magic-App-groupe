@extends('admin.layouts.app')
@section('title', 'Modifier ' . $reservation->reference)
@section('page-title', 'Modifier la réservation')
@section('page-subtitle', $reservation->reference . '  ' . $reservation->hotel->name)

@section('header-actions')
    <a href="{{ route('admin.reservations.show', $reservation) }}"
       class="text-sm text-gray-500 hover:text-gray-700"> Retour à la réservation</a>
@endsection

@section('content')
@php
    $alreadyPaid = $reservation->payments->where('status','completed')->sum('amount');
    $occupancyConfigsJson = $roomTypes->mapWithKeys(fn($rt) => [
        $rt->id => $rt->activeOccupancyConfigs->map(fn($c) => [
            'id'           => $c->id,
            'label'        => $c->label,
            'min_adults'   => $c->min_adults,
            'max_adults'   => $c->max_adults,
            'min_children' => $c->min_children ?? 0,
            'max_children' => $c->max_children ?? 0,
            'max_babies'   => $c->max_babies ?? 0,
        ])->values()->toArray()
    ])->toJson();

    // Données des suppléments passées à Alpine pour recalcul dynamique
    // Charger TOUS les suppléments actifs de l'hôtel (pas seulement ceux déjà attachés)
    // Utiliser les prix unitaires stockés sur la réservation si disponibles,
    // sinon les prix de base du supplément
    $existingSupplements = $reservation->supplements->keyBy('supplement_id');
    $allHotelSupplements = \App\Models\Supplement::where('hotel_id', $reservation->hotel_id)
        ->where('is_active', true)
        ->orderBy('status') // mandatory first
        ->orderBy('date_from')
        ->get();

    $supplementsJson = $allHotelSupplements->map(fn($sup) => [
        'id'           => $sup->id,
        'supplement_id'=> $sup->id,
        'title'        => $sup->title,
        'date'         => $sup->date_from ? (
            $sup->date_from->eq($sup->date_to)
                ? $sup->date_from->format('d/m/Y')
                : $sup->date_from->format('d/m') . '' . $sup->date_to->format('d/m/Y')
        ) : '',
        'date_from'    => $sup->date_from?->toDateString(),
        'date_to'      => $sup->date_to?->toDateString(),
        'price_adult'  => (float) ($existingSupplements[$sup->id]?->unit_price_adult ?? $sup->price_adult ?? 0),
        'price_child'  => (float) ($existingSupplements[$sup->id]?->unit_price_child ?? $sup->price_child ?? 0),
        'price_baby'   => (float) ($existingSupplements[$sup->id]?->unit_price_baby  ?? $sup->price_baby  ?? 0),
        'is_mandatory' => $sup->isMandatory(),
    ])->values()->toJson();

    // IDs des suppléments optionnels déjà sélectionnés
    $selectedOptionalIds = $reservation->supplements
        ->where('is_mandatory', false)
        ->pluck('supplement_id')
        ->values()
        ->toArray();
@endphp

<div x-data="reservationEditor()" x-init="init()" class="max-w-5xl mx-auto"> {{-- Avertissement si déjà partiellement payée --}}
    @if($alreadyPaid > 0)
    <div class="bg-amber-50 border border-amber-300 rounded-xl px-5 py-3 mb-6 flex items-start gap-3"> <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/> </svg> <div> <p class="text-sm font-semibold text-amber-900">Paiement partiel déjà enregistré</p> <p class="text-xs text-amber-700 mt-0.5"> {{ number_format($alreadyPaid, 2, ',', ' ') }} MAD déjà validés.
 La modification recalculera le total  le solde restant sera mis à jour automatiquement.
            </p> </div> </div> @endif

    <form action="{{ route('admin.reservations.update', $reservation) }}" method="POST"> @csrf
        @method('PATCH')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6"> {{-- Colonne principale --}}
            <div class="lg:col-span-2 space-y-5"> {{-- Séjours --}}
                <template x-for="(stay, stayIdx) in stays" :key="stayIdx"> <div class="bg-white border border-gray-200 rounded-xl overflow-hidden"> {{-- En-tête séjour --}}
                        <div class="flex items-center justify-between px-5 py-3 bg-amber-50 border-b border-amber-100"> <div class="flex items-center gap-2"> <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/> </svg> <span class="text-sm font-semibold text-amber-900" x-text="'Séjour ' + (stayIdx + 1)"></span> <span class="text-xs text-amber-700" x-show="nightsFor(stayIdx) > 0"
                                    x-text="' ' + nightsFor(stayIdx) + ' nuit' + (nightsFor(stayIdx) > 1 ? 's' : '')"></span> </div> <button type="button" x-show="stays.length > 1" @click="removeStay(stayIdx)"
                                class="text-xs text-red-400 hover:text-red-600 flex items-center gap-1"> <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/> </svg> Supprimer ce séjour
                            </button> </div> <div class="p-5 space-y-4"> {{-- Dates --}}
                            <div class="grid grid-cols-2 gap-4"> <div> <label class="block text-xs font-medium text-gray-600 mb-1">Date d'arrivée *</label> <input type="date" :name="`stays[${stayIdx}][check_in]`" required
                                        x-model="stay.check_in"
                                        @change="recalculateAllStays()"
                                        x-init="$nextTick(() => { if (!$el._flatpickr && window.initDatePickers) window.initDatePickers($el.parentElement); })"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> <div> <label class="block text-xs font-medium text-gray-600 mb-1">Date de départ *</label> <input type="date" :name="`stays[${stayIdx}][check_out]`" required
                                        x-model="stay.check_out"
                                        :min="stay.check_in || ''"
                                        @change="recalculateAllStays()"
                                        x-init="$nextTick(() => { if (!$el._flatpickr && window.initDatePickers) window.initDatePickers($el.parentElement); })"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> </div> {{-- Chambres --}}
                            <div> <div class="hidden sm:flex gap-2 text-xs font-medium text-gray-400 px-1 mb-1 items-end"> <div class="flex-1">Chambre &amp; Occupation</div> <div class="w-14 text-center">Qté</div> <div class="w-16 text-center">Adult.</div> <div class="w-16 text-center">Enf.</div> <div class="w-16 text-center">Bébés</div> <div class="w-6"></div> </div> <template x-for="(room, roomIdx) in stay.rooms" :key="roomIdx"> <div class="flex flex-wrap sm:flex-nowrap gap-2 items-center mb-2"> {{-- Champs cachés --}}
                                        <input type="hidden" :name="`stays[${stayIdx}][rooms][${roomIdx}][room_type_id]`" :value="room.room_type_id"> <input type="hidden" :name="`stays[${stayIdx}][rooms][${roomIdx}][occupancy_config_id]`" :value="room.occupancy_config_id || ''"> {{-- Combo chambre + occupation --}}
                                        <div class="flex-1 min-w-0"> <select required
                                                :value="room.comboValue"
                                                @change="selectRoomConfig(stayIdx, roomIdx, $event.target.value)"
                                                class="w-full border rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none bg-white"
                                                :class="!room.occupancy_config_id ? 'border-amber-300' : 'border-gray-200'"> <option value=""> — Choisir chambre &amp; occupation —</option> @foreach($roomTypes as $rt)
                                                @if($rt->activeOccupancyConfigs->isNotEmpty())
                                                <optgroup label="{{ $rt->name }}"> @foreach($rt->activeOccupancyConfigs->sortBy('sort_order') as $cfg)
                                                    <option value="{{ $rt->id }}|{{ $cfg->id }}">{{ $cfg->label }}</option> @endforeach
                                                </optgroup> @else
                                                <optgroup label="{{ $rt->name }}"> <option value="{{ $rt->id }}|">{{ $rt->name }}</option> </optgroup> @endif
                                                @endforeach
                                            </select> </div> {{-- Quantité --}}
                                        <div class="w-14 shrink-0"> <input type="number" :name="`stays[${stayIdx}][rooms][${roomIdx}][quantity]`" required
                                                x-model.number="room.quantity" min="1"
                                                @change="recalculateAllStays()"
                                                class="w-full border border-gray-200 rounded-lg px-2 py-2.5 text-sm text-center focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> {{-- Adultes --}}
                                        <div class="w-16 shrink-0"> <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-amber-400"> <span class="px-1 text-gray-400 text-xs bg-gray-50 py-2.5 border-r border-gray-200 shrink-0">A</span> <input type="number" :name="`stays[${stayIdx}][rooms][${roomIdx}][adults]`"
                                                    x-model.number="room.adults"
                                                    :min="getConfigById(room.occupancy_config_id)?.min_adults ?? 0"
                                                    :max="getConfigById(room.occupancy_config_id)?.max_adults ?? 20"
                                                    @change="clampPersons(stayIdx, roomIdx); calculatePriceForStay(stayIdx)"
                                                    class="flex-1 px-1 py-2.5 text-sm text-center focus:outline-none w-0 min-w-0"> </div> </div> {{-- Enfants --}}
                                        <div class="w-16 shrink-0"> <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-amber-400"> <span class="px-1 text-gray-400 text-xs bg-gray-50 py-2.5 border-r border-gray-200 shrink-0">E</span> <input type="number" :name="`stays[${stayIdx}][rooms][${roomIdx}][children]`"
                                                    x-model.number="room.children"
                                                    :min="getConfigById(room.occupancy_config_id)?.min_children ?? 0"
                                                    :max="getConfigById(room.occupancy_config_id)?.max_children ?? 10"
                                                    @change="clampPersons(stayIdx, roomIdx); calculatePriceForStay(stayIdx)"
                                                    class="flex-1 px-1 py-2.5 text-sm text-center focus:outline-none w-0 min-w-0"> </div> </div> {{-- Bébés --}}
                                        <div class="w-16 shrink-0"> <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-amber-400"> <span class="px-1 text-gray-400 text-xs bg-gray-50 py-2.5 border-r border-gray-200 shrink-0">B</span> <input type="number" :name="`stays[${stayIdx}][rooms][${roomIdx}][babies]`"
                                                    x-model.number="room.babies"
                                                    min="0"
                                                    :max="getConfigById(room.occupancy_config_id)?.max_babies ?? 5"
                                                    @change="calculatePriceForStay(stayIdx)"
                                                    class="flex-1 px-1 py-2.5 text-sm text-center focus:outline-none w-0 min-w-0"> </div> </div> {{-- Supprimer --}}
                                        <div class="w-6 shrink-0"> <button type="button" x-show="stay.rooms.length > 1" @click="removeRoom(stayIdx, roomIdx)"
                                                class="p-1 text-red-300 hover:text-red-500 rounded"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/> </svg> </button> </div> </div> </template> <button type="button" @click="addRoom(stayIdx)"
                                    class="mt-1 text-xs text-amber-600 hover:text-amber-800 font-medium flex items-center gap-1"> <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/> </svg> Ajouter un type de chambre
                                </button> </div> {{-- Résultat prix séjour --}}
                            <template x-if="priceResults[stayIdx]">
                                <div class="bg-amber-50 border border-amber-100 rounded-lg p-3 text-xs space-y-1">
                                    <template x-for="line in priceResults[stayIdx].breakdown">
                                        <div class="flex justify-between text-gray-700">
                                            <span>
                                                <span x-text="line.quantity + ' × ' + line.room_type_name + ' × ' + line.nights + ' nuit' + (line.nights > 1 ? 's' : '')"></span>
                                                <template x-if="line.unit_price_raw && priceResults[stayIdx].nights > 0">
                                                    <span class="ml-1">
                                                        <span :class="promoForStay(stayIdx) ? 'text-gray-400 line-through' : 'text-amber-500'"
                                                              x-text="promoForStay(stayIdx)
                                                                ? formatTaxe(line.unit_price_raw / priceResults[stayIdx].nights) + ' MAD'
                                                                : '(' + formatTaxe(line.unit_price_raw / priceResults[stayIdx].nights) + ' MAD / chambre par nuit)'">
                                                        </span>
                                                        <span x-show="promoForStay(stayIdx) !== null"
                                                              class="text-emerald-600 font-semibold ml-1"
                                                              x-text="promoForStay(stayIdx) ? '→ ' + formatTaxe(line.unit_price_raw / priceResults[stayIdx].nights * (1 - promoForStay(stayIdx).rate / 100)) + ' MAD / chambre par nuit' : ''">
                                                        </span>
                                                    </span>
                                                </template>
                                            </span>
                                            <span class="font-semibold"
                                                  x-text="promoForStay(stayIdx)
                                                    ? formatTaxe(line.line_total * (1 - promoForStay(stayIdx).rate / 100)) + ' MAD'
                                                    : formatTaxe(line.line_total) + ' MAD'">
                                            </span>
                                        </div>
                                    </template>
                                    <template x-if="priceResults[stayIdx].taxe_sejour_total > 0">
                                        <div class="flex justify-between text-blue-600">
                                            <span x-text="` Taxe de séjour (${priceResults[stayIdx].taxe_sejour_adults} adulte(s) × ${priceResults[stayIdx].nights} nuit(s) × ${formatTaxe(priceResults[stayIdx].taxe_sejour_rate)} DHS)`"></span>
                                            <span class="font-medium" x-text="formatTaxe(priceResults[stayIdx].taxe_sejour_total) + ' MAD'"></span>
                                        </div>
                                    </template>
                                    <template x-if="promoForStay(stayIdx)">
                                        <div class="flex items-center gap-1.5 px-2 py-1.5 bg-emerald-50 border border-emerald-100 rounded-lg mt-1">
                                            <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span class="text-emerald-700 font-medium"
                                                  x-text="promoForStay(stayIdx) ? `Réduction long séjour de ${promoForStay(stayIdx).rate}% déjà appliquée sur les prix par nuit.` : ''">
                                            </span>
                                        </div>
                                    </template>
                                    <div class="flex justify-between font-bold text-gray-900 border-t border-amber-200 pt-1 mt-1">
                                        <span x-text="'Sous-total séjour ' + (stayIdx + 1)"></span>
                                        <span x-text="formatTaxe((priceResults[stayIdx].total ?? 0) * (1 - (promoForStay(stayIdx)?.rate || 0) / 100) + (priceResults[stayIdx].taxe_sejour_total ?? 0)) + ' MAD'"></span>
                                    </div>
                                </div>
                            </template> </div> </div> </template> {{-- Bouton ajouter séjour --}}
                <button type="button" @click="addStay()"
                    class="w-full border-2 border-dashed border-amber-300 hover:border-amber-400 text-amber-600 hover:text-amber-700 rounded-xl py-3 text-sm font-medium flex items-center justify-center gap-2 transition-colors"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/> </svg> Ajouter un autre séjour (autre date)
                </button> {{-- Suppléments obligatoires --}}
                <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 space-y-2"
                     x-show="mandatorySupplements.length > 0"> <p class="text-[10px] font-bold text-orange-600 uppercase tracking-wide flex items-center gap-1"> Suppléments inclus (obligatoires)
                        <span class="text-orange-400 font-normal italic"> Recalculés selon les chambres</span> </p> <template x-for="sup in mandatorySupplements" :key="sup.id"> <div class="flex items-start justify-between gap-3 bg-white rounded-lg px-3 py-2 border border-orange-100"> <div> <p class="text-sm font-semibold text-orange-900"> <span x-text="sup.title"></span> </p> <p class="text-xs text-orange-400 mt-0.5"> <span x-text="sup.date"></span> </p> <p class="text-xs text-orange-500 mt-0.5 space-x-1"> <template x-if="sup.adults > 0 && sup.price_adult > 0"> <span x-text="`${sup.adults} adulte(s) × ${formatPrice(sup.price_adult)} MAD`"></span> </template> <template x-if="sup.children > 0 && sup.price_child > 0"> <span x-text="`· ${sup.children} enfant(s) × ${formatPrice(sup.price_child)} MAD`"></span> </template> <template x-if="sup.babies > 0 && sup.price_baby > 0"> <span x-text="`· ${sup.babies} bébé(s) × ${formatPrice(sup.price_baby)} MAD`"></span> </template> </p> </div> <span class="text-sm font-bold text-orange-700 shrink-0 whitespace-nowrap"
                                  x-text="`+ ${formatPrice(sup.total)} MAD`"></span> </div> </template> </div> {{-- Suppléments optionnels --}}
                <div class="bg-white border border-gray-200 rounded-xl p-4 space-y-2"
                     x-show="optionalSupplements.length > 0"> <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wide mb-1"> Suppléments optionnels
                    </p> <template x-for="sup in optionalSupplements" :key="sup.id"> <label class="flex items-start justify-between gap-3 cursor-pointer py-2 hover:bg-gray-50 rounded-lg px-2 -mx-2 transition-colors"> <span class="flex items-start gap-2"> <input type="checkbox"
                                    :value="sup.id"
                                    name="selected_supplements[]"
                                    x-model="selectedOptionalSupplements"
                                    class="rounded border-gray-300 text-amber-500 mt-0.5 shrink-0"> <span class="text-sm text-gray-700"> <span x-text="sup.title"></span> <span class="text-xs text-gray-400 ml-1">(<span x-text="sup.date"></span>)</span> <span class="block text-xs text-gray-400 mt-0.5"> <template x-if="sup.adults > 0 && sup.price_adult > 0"> <span x-text="`${sup.adults} adulte(s) × ${formatPrice(sup.price_adult)} MAD`"></span> </template> <template x-if="sup.children > 0 && sup.price_child > 0"> <span x-text="`· ${sup.children} enfant(s) × ${formatPrice(sup.price_child)} MAD`"></span> </template> <template x-if="sup.babies > 0 && sup.price_baby > 0"> <span x-text="`· ${sup.babies} bébé(s) × ${formatPrice(sup.price_baby)} MAD`"></span> </template> </span> </span> </span> <span class="text-sm font-semibold text-gray-800 whitespace-nowrap pt-0.5"
                                  x-text="`${formatPrice(sup.total)} MAD`"></span> </label> </template> </div> {{-- Services Extras (lecture seule) --}}
                @if($reservation->extras->isNotEmpty())
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-[10px] font-bold text-amber-700 uppercase tracking-wide flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Services Extras
                        </p>
                        <a href="{{ route('admin.reservations.show', $reservation) }}#extras"
                           class="text-xs text-amber-600 hover:text-amber-800 underline">Gérer</a>
                    </div>
                    <div class="space-y-1.5">
                        @foreach($reservation->extras as $extra)
                        <div class="flex items-start justify-between gap-3 bg-white rounded-lg px-3 py-2 border border-amber-100">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-amber-900 truncate">{{ $extra->name }}</p>
                                @if($extra->description)
                                <p class="text-xs text-amber-500 truncate">{{ $extra->description }}</p>
                                @endif
                                <p class="text-xs text-amber-400 mt-0.5">{{ $extra->quantity }} × {{ number_format($extra->unit_price, 2, ',', ' ') }} MAD</p>
                            </div>
                            <span class="text-sm font-bold text-amber-700 shrink-0 whitespace-nowrap">+ {{ number_format($extra->total_price, 2, ',', ' ') }} MAD</span>
                        </div>
                        @endforeach
                        @if($reservation->extras->count() > 1)
                        <div class="flex justify-between text-sm font-bold text-amber-800 pt-1 border-t border-amber-200">
                            <span>Total extras</span>
                            <span>+ {{ number_format($extrasTotal, 2, ',', ' ') }} MAD</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Notes admin --}}
                <div class="bg-white border border-gray-200 rounded-xl p-5"> <h2 class="text-sm font-semibold text-gray-900 mb-3">Note interne admin</h2> <textarea name="admin_notes" rows="3"
                              class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none resize-none"
                              placeholder="Note interne (non visible par l'agence)...">{{ old('admin_notes', $reservation->admin_notes) }}</textarea> </div> </div> {{-- Colonne droite --}}
            <div class="space-y-5"> {{-- Agence --}}
                <div class="bg-white border border-gray-200 rounded-xl p-5"> <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Agence</h3> <p class="font-semibold text-gray-900 text-sm">{{ $reservation->agency_name }}</p> <p class="text-sm text-gray-600">{{ $reservation->contact_name }}</p> <p class="text-sm text-amber-600">{{ $reservation->email }}</p> @if($reservation->phone)<p class="text-sm text-gray-500">{{ $reservation->phone }}</p>@endif
                    <div class="mt-2 pt-2 border-t border-gray-100"> <span class="text-xs text-gray-400">Tarif appliqué :</span> <span class="ml-1 text-xs font-semibold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-full">{{ $reservation->tariff_code ?? 'NRF' }}</span> </div> </div> {{-- Personnes --}}
                <div class="bg-white border border-gray-200 rounded-xl p-5"> <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Informations</h3> <div> <label class="block text-xs font-medium text-gray-600 mb-1.5">Nombre de personnes *</label> <input type="number" name="total_persons" min="1"
                               value="{{ old('total_persons', $reservation->total_persons) }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> <div class="mt-3"> <label class="block text-xs font-medium text-gray-600 mb-1.5">Demandes spéciales</label> <textarea name="special_requests" rows="3"
                                  class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none resize-none"
                                  placeholder="Chambre vue mer, lit bébé...">{{ old('special_requests', $reservation->special_requests) }}</textarea> </div> </div> {{-- Récapitulatif estimé --}}
                <div class="bg-white border border-gray-200 rounded-xl p-5" x-show="totalEstimated > 0"> <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Récapitulatif estimé</h3> <div class="space-y-1.5 text-sm"> <div class="flex justify-between"> <span class="text-gray-500">Hébergement :</span> <span class="font-semibold" x-text="formatTaxe(totalEstimated) + ' MAD'"></span> </div> <div class="flex justify-between" x-show="totalTaxe > 0"> <span class="text-gray-500">Taxe de séjour :</span> <span class="font-semibold text-blue-600" x-text="formatTaxe(totalTaxe) + ' MAD'"></span> </div> <div class="flex justify-between" x-show="promoTotalDiscount > 0"> <span class="text-gray-500"> Promo long séjour :</span> <span class="font-semibold text-emerald-600" x-text="` ${formatTaxe(promoTotalDiscount)} MAD`"></span> </div> <template x-for="sup in mandatorySupplements" :key="sup.id"> <div class="flex items-start justify-between gap-2 text-orange-700"> <div class="min-w-0"> <span class="text-sm font-medium"> <span x-text="sup.title"></span></span> <span class="block text-xs text-orange-400" x-text="sup.date"></span> </div> <span class="font-semibold text-sm shrink-0" x-text="`+ ${formatTaxe(sup.total)} MAD`"></span> </div> </template> <div class="flex justify-between" x-show="selectedOptionalTotal > 0"> <span class="text-gray-500"> Suppléments optionnels :</span> <span class="font-semibold text-purple-700" x-text="`+ ${formatPrice(selectedOptionalTotal)} MAD`"></span> </div> <div class="flex justify-between" x-show="extrasFixed > 0"> <span class="text-gray-500">Services extras :</span> <span class="font-semibold text-amber-700" x-text="`+ ${formatTaxe(extrasFixed)} MAD`"></span> </div>@if($alreadyPaid > 0)
                        <div class="flex justify-between border-t border-gray-100 pt-1.5"> <span class="text-gray-500">Déjà payé :</span> <span class="font-semibold text-emerald-600">{{ number_format($alreadyPaid, 2, ',', ' ') }} MAD</span> </div> @endif
                    </div> <div class="mt-2 pt-2 border-t border-gray-100 flex justify-between items-center" x-show="totalEstimated > 0"> <span class="text-xs text-gray-400">Total global actuel :</span> <span class="text-sm font-bold text-orange-600" x-text="formatTaxe(totalGlobal) + ' MAD'"></span> </div> </div> {{-- Actions --}}
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 space-y-3"> <h3 class="text-xs font-semibold text-amber-800 uppercase tracking-wide">Enregistrer</h3> <p class="text-xs text-amber-700"> Le prix sera recalculé depuis le calendrier tarifaire.
                        @if($alreadyPaid > 0) Le solde restant sera mis à jour. @endif
                    </p> <button type="submit"
                            class="w-full bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2.5 rounded-lg text-sm transition-colors"> Enregistrer et recalculer
                    </button> <a href="{{ route('admin.reservations.show', $reservation) }}"
                       class="block w-full text-center text-sm text-gray-600 hover:text-gray-800 py-2 rounded-lg border border-gray-200 hover:border-gray-300 bg-white transition-colors"> Annuler
                    </a> </div> </div> </div> </form>
</div> <script>
const occupancyConfigs = {!! $occupancyConfigsJson !!};
const priceUrl  = '{{ route('client.calculate-price') }}';
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
const hotelId   = {{ $reservation->hotel_id }};
const tariffCode = '{{ $reservation->tariff_code ?? 'NRF' }}';

// Données promo long séjour de l'hôtel
@php
$_editHotelPromo = [
    'enabled'      => (bool)  $reservation->hotel->promo_long_stay_enabled,
    'tier1_nights' => (int)   ($reservation->hotel->promo_tier1_nights ?? 0),
    'tier1_rate'   => (float) ($reservation->hotel->promo_tier1_rate   ?? 0),
    'tier2_nights' => (int)   ($reservation->hotel->promo_tier2_nights ?? 0),
    'tier2_rate'   => (float) ($reservation->hotel->promo_tier2_rate   ?? 0),
];
@endphp
const hotelPromoEdit = @json($_editHotelPromo);

// Prix initiaux pré-remplis depuis les rooms Eloquent (prix figés)
@php
$_adminTaxeRate = 0;
try { $_adminTaxeRate = (float)($reservation->hotel->taxe_sejour ?? 0); } catch (\Exception $e) {}

// Grouper les rooms Eloquent par séjour (pour avoir total_price, price_per_night, labels)
$_adminRoomsByStay = $reservation->rooms->groupBy(fn($r) => ($r->check_in?->format('Y-m-d')  ?? $reservation->check_in->format('Y-m-d')) . '_' .
    ($r->check_out?->format('Y-m-d') ?? $reservation->check_out->format('Y-m-d'))
);

$_adminInitialPriceResults = collect($stayGroups)->map(function($stay) use ($_adminTaxeRate, $_adminRoomsByStay) {
    $stayKey = $stay['check_in'] . '_' . $stay['check_out'];
    $rooms   = $_adminRoomsByStay[$stayKey] ?? collect();
    $nights  = (int)(\Carbon\Carbon::parse($stay['check_in'])->diffInDays(\Carbon\Carbon::parse($stay['check_out'])));
    $adults  = $rooms->sum(fn($r) => ($r->adults ?? 0) * max(1, $r->quantity ?? 1));

    return [
        'total'              => (float) $rooms->sum(fn($r) => $r->total_price ?? 0),
        'nights'             => $nights,
        'taxe_sejour_rate'   => $_adminTaxeRate,
        'taxe_sejour_adults' => $adults,
        'taxe_sejour_total'  => round($adults * $nights * $_adminTaxeRate, 2),
        'breakdown'          => $rooms->map(fn($r) => [
            'room_type_name'  => $r->roomType?->name ?? '',
            'occupancy_label' => ($r->occupancyConfig?->code ?? '') . '  ' . ($r->occupancyConfig?->occupancy_description ?? ''),
            'quantity'        => $r->quantity ?? 1,
            'nights'          => $nights,
            'line_total'      => (float)($r->total_price ?? 0),
            'unit_price_raw'  => ($r->total_price && ($r->quantity ?? 1) > 0)
                ? round((float)$r->total_price / ($r->quantity ?? 1), 2) : null,
        ])->values()->all(),
        'supplements' => [],
        'success'     => true,
    ];
})->values()->all();
@endphp
@php
$_adminOriginalStays = collect($stayGroups)->map(fn($s) => [
    'check_in'  => $s['check_in'],
    'check_out' => $s['check_out'],
    'rooms'     => collect($s['rooms'] ?? [])->map(fn($r) => [
        'room_type_id'        => $r['room_type_id'],
        'occupancy_config_id' => $r['occupancy_config_id'] ?? null,
        'quantity'            => $r['quantity'] ?? 1,
    ])->values()->all(),
])->values()->all();
@endphp
const adminInitialPriceResults = @json($_adminInitialPriceResults);
const adminOriginalStays = @json($_adminOriginalStays);

function reservationEditor() {
    return {
        stays: @json($stayGroups),
        priceResults: adminInitialPriceResults,
        _originalStays: adminOriginalStays,

        // Données des suppléments (tous ceux de l'hôtel)
        supplementsData: {!! $supplementsJson !!},
        // Suppléments optionnels sélectionnés (pré-cochés depuis la réservation)
        selectedOptionalSupplements: @json($selectedOptionalIds),

        // Extras (valeur fixe chargée depuis la DB, gérés séparément)
        extrasFixed: {{ (float) $extrasTotal }},

        get totalEstimated() {
            return this.priceResults.reduce((sum, r) => sum + (r ? (r.total ?? 0) : 0), 0);
        },

        get totalTaxe() {
            return this.priceResults.reduce((sum, r) => sum + (r ? (r.taxe_sejour_total ?? 0) : 0), 0);
        },

        //  Promo long séjour par séjour 
        nightsForStay(stayIdx) {
            const s = this.stays[stayIdx];
            if (!s || !s.check_in || !s.check_out) return 0;
            return Math.max(0, Math.round((new Date(s.check_out) - new Date(s.check_in)) / 86400000));
        },

        promoForStay(stayIdx) {
            if (!hotelPromoEdit.enabled) return null;
            const nights    = this.nightsForStay(stayIdx);
            const stayTotal = this.priceResults[stayIdx]?.total || 0;
            if (!stayTotal || nights <= 0) return null;
            let rate = 0;
            if (hotelPromoEdit.tier2_nights > 0 && nights >= hotelPromoEdit.tier2_nights) {
                rate = hotelPromoEdit.tier2_rate;
            } else if (hotelPromoEdit.tier1_nights > 0 && nights >= hotelPromoEdit.tier1_nights) {
                rate = hotelPromoEdit.tier1_rate;
            }
            if (!rate) return null;
            const discount = Math.round(stayTotal * rate / 100 * 100) / 100;
            return { nights, rate, discount };
        },

        get promoTotalDiscount() {
            let total = 0;
            for (let i = 0; i < this.stays.length; i++) {
                total += this.promoForStay(i)?.discount || 0;
            }
            return Math.round(total * 100) / 100;
        },

        /**
         * Recalcule chaque supplément dynamiquement selon les chambres actuelles.
         * Même logique que le formulaire agence : lastNight = check_out - 1 jour.
         */
        get allApplicableSupplements() {
            return this.supplementsData.map(sup => {
                const supFrom = sup.date_from ? new Date(sup.date_from) : null;
                const supTo   = sup.date_to   ? new Date(sup.date_to)   : null;

                let adults = 0, children = 0, babies = 0;

                for (const stay of this.stays) {
                    const roomIn    = new Date(stay.check_in);
                    const roomOut   = new Date(stay.check_out);
                    const lastNight = new Date(roomOut.getTime() - 86400000);
                    const overlaps  = (!supFrom || !supTo) ||
                        (supFrom <= lastNight && supTo >= roomIn);

                    if (overlaps) {
                        for (const room of (stay.rooms ?? [])) {
                            const qty = Math.max(1, parseInt(room.quantity) || 1);
                            adults   += (parseInt(room.adults)   || 0) * qty;
                            children += (parseInt(room.children) || 0) * qty;
                            babies   += (parseInt(room.babies)   || 0) * qty;
                        }
                    }
                }

                const total = adults * sup.price_adult + children * sup.price_child + babies * sup.price_baby;
                return { ...sup, adults, children, babies, total: Math.round(total) };
            }).filter(s => s.total > 0 || !s.date_from); // masquer ceux hors période avec total=0
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

        get totalSupplements() {
            return this.mandatorySupplementTotal + this.selectedOptionalTotal;
        },

        get totalGlobal() {
            return this.totalEstimated
                 - this.promoTotalDiscount
                 + this.totalSupplements
                 + this.totalTaxe
                 + (this.extrasFixed || 0);
        },

        init() {
            // Calculer les prix initiaux si les données sont déjà remplies
            this.$nextTick(() => this.recalculateAllStays());
        },

        nightsFor(stayIdx) {
            const s = this.stays[stayIdx];
            if (!s.check_in || !s.check_out) return 0;
            const d = (new Date(s.check_out) - new Date(s.check_in)) / 86400000;
            return d > 0 ? d : 0;
        },

        //  Séjours 
        addStay() {
            this.stays.push({
                check_in: '',
                check_out: '',
                rooms: [{ room_type_id: '', occupancy_config_id: null, comboValue: '', quantity: 1, adults: 1, children: 0, babies: 0 }]
            });
            this.priceResults.push(null);
        },

        removeStay(idx) {
            this.stays.splice(idx, 1);
            this.priceResults.splice(idx, 1);
            this.$nextTick(() => this.recalculateAllStays());
        },

        //  Chambres 
        addRoom(stayIdx) {
            this.stays[stayIdx].rooms.push({ room_type_id: '', occupancy_config_id: null, comboValue: '', quantity: 1, adults: 1, children: 0, babies: 0 });
        },

        removeRoom(stayIdx, roomIdx) {
            this.stays[stayIdx].rooms.splice(roomIdx, 1);
            this.recalculateAllStays();
        },

        //  Config occupation 
        selectRoomConfig(stayIdx, roomIdx, value) {
            const room = this.stays[stayIdx]?.rooms[roomIdx];
            if (!room) return;
            const [rtId, cfgId] = value.split('|');
            room.room_type_id        = rtId  || '';
            room.occupancy_config_id = cfgId || null;
            room.comboValue          = value;

            if (cfgId) {
                const cfg = this.getConfigById(cfgId);
                if (cfg) {
                    room.adults   = cfg.min_adults   ?? 1;
                    room.children = cfg.min_children ?? 0;
                    room.babies   = 0;
                }
            }
            this.recalculateAllStays();
        },

        getConfigById(cfgId) {
            if (!cfgId) return null;
            for (const rtId in occupancyConfigs) {
                const found = occupancyConfigs[rtId].find(c => String(c.id) === String(cfgId));
                if (found) return found;
            }
            return null;
        },

        clampPersons(stayIdx, roomIdx) {
            const room = this.stays[stayIdx]?.rooms[roomIdx];
            const cfg  = room ? this.getConfigById(room.occupancy_config_id) : null;
            if (!cfg || !room) return;
            room.adults   = Math.min(Math.max(room.adults,   cfg.min_adults   ?? 0), cfg.max_adults   ?? 20);
            room.children = Math.min(Math.max(room.children, cfg.min_children ?? 0), cfg.max_children ?? 10);
            room.babies   = Math.min(Math.max(room.babies,   0),                     cfg.max_babies   ?? 5);
        },

        //  Calcul prix 
        async recalculateAllStays() {
            await Promise.all(this.stays.map((_, idx) => this.calculatePriceForStay(idx)));
        },

        // Détecte si le séjour a changé structurellement (dates, type, config) par rapport à l'original
        _adminStayHasChanged(stayIdx) {
            const orig = this._originalStays[stayIdx];
            const curr = this.stays[stayIdx];
            if (!orig) return true;
            if (orig.check_in !== curr.check_in || orig.check_out !== curr.check_out) return true;
            const currRooms = curr.rooms.filter(r => r.room_type_id);
            for (let i = 0; i < orig.rooms.length; i++) {
                const o = orig.rooms[i];
                const r = currRooms[i];
                if (!r) return true;
                if (r.room_type_id !== o.room_type_id) return true;
                if (String(r.occupancy_config_id) !== String(o.occupancy_config_id)) return true;
            }
            return false;
        },

        // Après appel API, restaure anciens prix pour les chambres originales (par position)
        _adminApplyHybridPricing(stayIdx, apiResult) {
            const orig   = this._originalStays[stayIdx];
            const cached = this.priceResults[stayIdx];
            const nights = this.nightsFor(stayIdx);
            const stay   = this.stays[stayIdx];
            if (!orig || !cached || !cached.breakdown || !apiResult.breakdown) {
                this.priceResults[stayIdx] = apiResult;
                this.priceResults = [...this.priceResults];
                return;
            }
            const validRooms = stay.rooms.filter(r => r.room_type_id && r.occupancy_config_id);
            const fixedBreakdown = (apiResult.breakdown || []).map((line, i) => {
                const origRoom   = orig.rooms[i];
                const cachedLine = cached.breakdown[i];
                const formRoom   = validRooms[i];
                if (!origRoom || !cachedLine || !formRoom) return line;
                const sameType   = String(formRoom.room_type_id)        === String(origRoom.room_type_id);
                const sameConfig = String(formRoom.occupancy_config_id) === String(origRoom.occupancy_config_id);
                if (!sameType || !sameConfig) return line;
                const oldPPN = cachedLine.unit_price_raw && nights > 0 ? cachedLine.unit_price_raw / nights : 0;
                if (oldPPN <= 0) return line;
                const qty = line.quantity || 1;
                return { ...line,
                    line_total:     Math.round(oldPPN * qty * nights * 100) / 100,
                    unit_price_raw: Math.round(oldPPN * qty * nights / qty * 100) / 100,
                };
            });
            // Fusionner lignes de même tarif
            const mergedMap = new Map();
            fixedBreakdown.forEach(line => {
                const ppn = line.unit_price_raw && nights > 0 ? Math.round(line.unit_price_raw / nights) : 0;
                const key = (line.occupancy_label || line.room_type_name || '') + '|' + ppn;
                if (mergedMap.has(key)) {
                    const ex  = mergedMap.get(key);
                    const qty = (ex.quantity || 0) + (line.quantity || 0);
                    const pn  = ex.unit_price_raw && nights > 0 ? ex.unit_price_raw / nights : 0;
                    mergedMap.set(key, { ...ex, quantity: qty, line_total: Math.round(pn * qty * nights * 100) / 100 });
                } else {
                    mergedMap.set(key, { ...line });
                }
            });
            const mergedBreakdown = Array.from(mergedMap.values());
            let totalAdults = 0;
            validRooms.forEach(r => { totalAdults += (parseInt(r.adults) || 0) * (parseInt(r.quantity) || 1); });
            const taxeRate = apiResult.taxe_sejour_rate || 0;
            this.priceResults[stayIdx] = {
                ...apiResult,
                total:              mergedBreakdown.reduce((s, l) => s + (l.line_total || 0), 0),
                taxe_sejour_adults: totalAdults,
                taxe_sejour_total:  Math.round(totalAdults * nights * taxeRate * 100) / 100,
                breakdown:          mergedBreakdown,
            };
            this.priceResults = [...this.priceResults];
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

            // Total de toutes les chambres sur tous les séjours
            const totalRooms = this.stays.reduce((sum, s) => sum + s.rooms.filter(r => r.room_type_id && r.quantity > 0)
                             .reduce((s2, r) => s2 + (parseInt(r.quantity) || 1), 0), 0);

            try {
                const resp = await fetch(priceUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        hotel_id:    hotelId,
                        check_in:    stay.check_in,
                        check_out:   stay.check_out,
                        tariff_code: tariffCode,
                        total_rooms: totalRooms,
                        rooms: validRooms.map(r => ({
                            room_type_id:        r.room_type_id,
                            quantity:            r.quantity,
                            adults:              r.adults   || 0,
                            children:            r.children || 0,
                            babies:              r.babies   || 0,
                            occupancy_config_id: r.occupancy_config_id || null,
                        }))
                    })
                });
                const data = await resp.json();
                if (data.success) {
                    // Séjour existant  hybrid pricing (anciens prix préservés pour lignes originales)
                    if (this._originalStays[stayIdx] && this.priceResults[stayIdx]) {
                        this._adminApplyHybridPricing(stayIdx, data);
                    } else {
                        this.priceResults[stayIdx] = data;
                        this.priceResults = [...this.priceResults];
                    }
                }
            } catch(e) {}
        },

        formatPrice(n) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(n ?? 0));
        },

        formatTaxe(n) {
            return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n ?? 0).replace(/[\u202F\u00A0]/g, ' ');
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
        },
    }
}
</script>
@endsection
