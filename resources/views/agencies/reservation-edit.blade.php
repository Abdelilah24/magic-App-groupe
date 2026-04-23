<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Modifier la réservation {{ $reservation->reference }}  {{ $agency->name }}</title> <script src="https://cdn.tailwindcss.com"></script> <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script> <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"> <meta name="csrf-token" content="{{ csrf_token() }}"> <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-full"> {{--  Header  --}}
<header class="bg-slate-900 text-white sticky top-0 z-30"> <div class="max-w-4xl mx-auto px-6 py-3 flex items-center justify-between"> <div class="flex items-center gap-4"> <span class="text-amber-400 text-lg font-bold"> Magic Hotels</span> <span class="hidden sm:block text-slate-500 text-sm">|</span> <span class="hidden sm:block text-slate-300 text-sm font-medium">{{ $agency->name }}</span> </div> <a href="{{ route('agency.portal.dashboard') }}"
           class="text-slate-400 hover:text-white text-sm flex items-center gap-1.5"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/> </svg> Retour au tableau de bord
        </a> </div>
</header> <main class="max-w-4xl mx-auto px-6 py-8"> {{-- Flash erreurs --}}
    @if($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm"> <ul class="list-disc list-inside space-y-1"> @foreach($errors->all() as $e)
            <li>{{ $e }}</li> @endforeach
        </ul> </div> @endif

    @php $isDraftEdit = $reservation->status === 'draft'; @endphp
    <div x-data="modificationForm()" class="space-y-6"> {{-- Titre --}}
        <div>
            <h1 class="text-xl font-bold text-gray-900">{{ $isDraftEdit ? 'Confirmer votre demande' : 'Modifier la réservation' }}</h1>
            <p class="text-sm text-gray-500 mt-1"> Référence : <span class="font-mono text-amber-600">{{ $reservation->reference }}</span> · {{ $hotel->name }}</p>
            @if($isDraftEdit)
            <div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800 flex items-start gap-2">
                <svg class="w-4 h-4 shrink-0 mt-0.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Vérifiez et ajustez votre demande, puis cliquez sur <strong>Confirmer et soumettre</strong> pour l'envoyer à notre équipe.</span>
            </div>
            @else
            <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800"> Toute modification sera soumise à validation par notre équipe avant d'être effective.</div>
            @endif
        </div> <form action="{{ route('agency.portal.update-reservation', $reservation) }}" method="POST"> @csrf @method('PATCH')
            <input type="hidden" name="total_persons" :value="totalPersons"> {{--  SÉJOURS  --}}
            <template x-for="(stay, stayIdx) in stays" :key="stayIdx"> <div class="bg-white border border-gray-200 rounded-xl overflow-hidden mb-4 shadow-sm"> {{-- En-tête séjour --}}
                    <div class="flex items-center justify-between px-5 py-3 bg-amber-50 border-b border-amber-100"> <div class="flex items-center gap-2"> <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/> </svg> <span class="text-sm font-semibold text-amber-900"> Séjour <span x-text="stayIdx + 1"></span> <span x-show="nightsFor(stayIdx) > 0" class="font-normal text-amber-700"> <span x-text="nightsFor(stayIdx)"></span> nuit(s)
                                    · <span x-text="personsForStay(stayIdx)"></span> pers.
                                </span> </span> </div> <button type="button" x-show="stays.length > 1" @click="removeStay(stayIdx)"
                            class="text-xs text-red-400 hover:text-red-600 flex items-center gap-1 px-2 py-1 rounded hover:bg-red-50 transition-colors"> <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg> Supprimer ce séjour
                        </button> </div> <div class="p-5 space-y-4"> {{-- Dates --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4"> <div> <label class="block text-xs font-medium text-gray-600 mb-1">Date d'arrivée *</label> <input type="date" :name="`stays[${stayIdx}][check_in]`" required
                                    x-model="stay.check_in"
                                    @change="calculatePriceForStay(stayIdx)"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> <div> <label class="block text-xs font-medium text-gray-600 mb-1">Date de départ *</label> <input type="date" :name="`stays[${stayIdx}][check_out]`" required
                                    x-model="stay.check_out"
                                    :min="stay.check_in || ''"
                                    @change="calculatePriceForStay(stayIdx)"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> </div> {{-- En-têtes colonnes (desktop) --}}
                        <div class="hidden sm:flex gap-2 text-xs font-medium text-gray-400 px-1 items-end"> <div class="flex-1">Chambre &amp; Occupation</div> <div class="w-14 text-center">Qté</div> <div class="w-20 text-center leading-tight">Adult.<br><span class="text-[10px] font-normal text-gray-300">12 ans+</span></div> <div class="w-20 text-center leading-tight">Enf.<br><span class="text-[10px] font-normal text-gray-300">2–11 ans</span></div> <div class="w-20 text-center leading-tight">Bébés<br><span class="text-[10px] font-normal text-gray-300">0–1 an</span></div> <div class="w-8"></div> </div> {{-- Lignes chambres --}}
                        <template x-for="(room, roomIdx) in stay.rooms" :key="roomIdx"> <div class="space-y-1.5 pb-2 border-b border-gray-100 last:border-0 last:pb-0"> <input type="hidden" :name="`stays[${stayIdx}][rooms][${roomIdx}][room_type_id]`"       :value="room.room_type_id"> <input type="hidden" :name="`stays[${stayIdx}][rooms][${roomIdx}][occupancy_config_id]`" :value="room.occupancy_config_id || ''"> <div class="flex flex-wrap sm:flex-nowrap gap-2 items-center"> {{-- Combo chambre + occupation --}}
                                    <div class="w-full sm:flex-1"> <label class="block text-xs text-gray-500 mb-1 sm:hidden">Chambre &amp; Occupation *</label> <select required
                                            x-model="room.comboValue"
                                            @change="selectRoomConfig(stayIdx, roomIdx, room.comboValue)"
                                            class="w-full border rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none bg-white"
                                            :class="!room.occupancy_config_id ? 'border-amber-300' : 'border-gray-200'"> <option value=""> — Choisir chambre &amp; occupation —</option> @foreach($roomTypes as $rt)
                                            @if($rt->activeOccupancyConfigs->isNotEmpty())
                                            <optgroup label="{{ $rt->name }}"> @foreach($rt->activeOccupancyConfigs->sortBy('sort_order') as $cfg)
                                                <option value="{{ $rt->id }}|{{ $cfg->id }}">{{ $cfg->label }}</option> @endforeach
                                            </optgroup> @else
                                            <optgroup label="{{ $rt->name }}"> <option value="{{ $rt->id }}|">{{ $rt->name }}</option> </optgroup> @endif
                                            @endforeach
                                        </select> </div> {{-- Quantité --}}
                                    <div class="w-14 shrink-0"> <label class="block text-xs text-gray-500 mb-1 sm:hidden">Qté</label> <input type="number" :name="`stays[${stayIdx}][rooms][${roomIdx}][quantity]`" required
                                            x-model.number="room.quantity" min="1"
                                            @change="recalculateAllStays()"
                                            class="w-full border border-gray-200 rounded-lg px-2 py-2.5 text-sm text-center focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> {{-- Adultes --}}
                                    <div class="w-20 shrink-0"> <label class="block text-xs text-gray-500 mb-1 sm:hidden">Adultes</label> <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-amber-400"> <span class="px-1.5 text-gray-400 text-xs bg-gray-50 py-2.5 border-r border-gray-200 shrink-0">A</span> <input type="number" :name="`stays[${stayIdx}][rooms][${roomIdx}][adults]`"
                                                x-model.number="room.adults"
                                                :min="getConfigById(room.occupancy_config_id)?.min_adults ?? 0"
                                                :max="getConfigById(room.occupancy_config_id)?.max_adults ?? 20"
                                                @change="clampPersons(stayIdx, roomIdx); calculatePriceForStay(stayIdx)"
                                                class="flex-1 px-1 py-2.5 text-sm text-center focus:outline-none w-0 min-w-0"> </div> </div> {{-- Enfants --}}
                                    <div class="w-20 shrink-0"> <label class="block text-xs text-gray-500 mb-1 sm:hidden">Enfants</label> <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-amber-400"> <span class="px-1.5 text-gray-400 text-xs bg-gray-50 py-2.5 border-r border-gray-200 shrink-0">E</span> <input type="number" :name="`stays[${stayIdx}][rooms][${roomIdx}][children]`"
                                                x-model.number="room.children"
                                                :min="getConfigById(room.occupancy_config_id)?.min_children ?? 0"
                                                :max="getConfigById(room.occupancy_config_id)?.max_children ?? 10"
                                                @change="clampPersons(stayIdx, roomIdx); calculatePriceForStay(stayIdx)"
                                                class="flex-1 px-1 py-2.5 text-sm text-center focus:outline-none w-0 min-w-0"> </div> </div> {{-- Bébés --}}
                                    <div class="w-20 shrink-0"> <label class="block text-xs text-gray-500 mb-1 sm:hidden">Bébés</label> <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-amber-400"> <span class="px-1.5 text-gray-400 text-xs bg-gray-50 py-2.5 border-r border-gray-200 shrink-0">B</span> <input type="number" :name="`stays[${stayIdx}][rooms][${roomIdx}][babies]`"
                                                x-model.number="room.babies"
                                                min="0"
                                                :max="getConfigById(room.occupancy_config_id)?.max_babies ?? 5"
                                                @change="clampPersons(stayIdx, roomIdx); calculatePriceForStay(stayIdx)"
                                                class="flex-1 px-1 py-2.5 text-sm text-center focus:outline-none w-0 min-w-0"> </div> </div> {{-- Supprimer chambre --}}
                                    <div class="w-8 shrink-0 flex justify-end"> <button type="button" x-show="stay.rooms.length > 1"
                                            @click="removeRoom(stayIdx, roomIdx)"
                                            class="p-2 text-red-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> </button> </div> </div> {{-- Plage config sélectionnée --}}
                                <template x-if="getConfigById(room.occupancy_config_id)"> <p class="text-xs text-gray-400 pl-1"> Plage : <span x-text="getConfigById(room.occupancy_config_id)?.min_adults"></span><span x-text="getConfigById(room.occupancy_config_id)?.max_adults"></span> adulte(s)
                                        <template x-if="(getConfigById(room.occupancy_config_id)?.max_children ?? 0) > 0"> <span>, 0<span x-text="getConfigById(room.occupancy_config_id)?.max_children"></span> enfant(s)</span> </template> </p> </template> {{-- Avertissement capacité --}}
                                <div x-show="hasCapacityErrorFor(stayIdx, roomIdx)" x-transition> <div class="flex items-start gap-2 rounded-lg px-3 py-2 text-xs border"
                                        :class="isOverCapacity(stayIdx, roomIdx) ? 'bg-red-50 border-red-200 text-red-700' : 'bg-orange-50 border-orange-200 text-orange-700'"> <span x-show="isOverCapacity(stayIdx, roomIdx)"> <strong>Capacité dépassée :</strong> max <strong x-text="capacityFor(room.room_type_id).max"></strong> pers. par chambre
                                        </span> <span x-show="isUnderCapacity(stayIdx, roomIdx)"> <strong>Minimum non atteint :</strong> minimum <strong x-text="capacityFor(room.room_type_id).min"></strong> pers. par chambre
                                        </span> </div> </div> </div> </template> {{-- Ajouter chambre dans ce séjour --}}
                        <div> <button type="button" @click="addRoom(stayIdx)"
                                class="text-xs text-amber-600 hover:text-amber-700 font-medium flex items-center gap-1 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors"> <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Ajouter un type de chambre
                            </button> </div> {{-- Prix estimé pour ce séjour --}}
                        <template x-if="priceResults[stayIdx]"> <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mt-2"> <template x-for="line in priceResults[stayIdx].breakdown"> <div class="flex justify-between text-xs py-0.5 text-amber-800"> <span> <span x-text="`${line.quantity} × ${line.occupancy_label || line.room_type_name} × ${priceResults[stayIdx].nights} nuits`"></span> <template x-if="line.unit_price_raw && priceResults[stayIdx].nights > 0"> <span class="ml-1"> <template x-if="promoForStay(stayIdx)"> <span> <span class="line-through text-gray-400" x-text="`(${formatTaxe(line.unit_price_raw / priceResults[stayIdx].nights)} MAD / nuit)`"></span> <span class="text-emerald-600 font-semibold ml-1" x-text="`→ ${formatTaxe(line.unit_price_raw / priceResults[stayIdx].nights * (1 - promoForStay(stayIdx).rate / 100))} MAD / nuit`"></span> </span> </template> <template x-if="!promoForStay(stayIdx)"> <span class="text-amber-500" x-text="`(${formatTaxe(line.unit_price_raw / priceResults[stayIdx].nights)} MAD / chambre par nuit)`"></span> </template> </span> </template> </span> <span class="font-semibold"> <template x-if="promoForStay(stayIdx)"> <span x-text="`${formatTaxe(line.line_total * (1 - promoForStay(stayIdx).rate / 100))} MAD`"></span> </template> <template x-if="!promoForStay(stayIdx)"> <span x-text="`${formatTaxe(line.line_total)} MAD`"></span> </template> </span> </div> </template> <template x-if="priceResults[stayIdx].taxe_sejour_total > 0"> <div class="flex justify-between text-xs py-0.5 text-blue-700"> <span x-text="` Taxe de séjour (${priceResults[stayIdx].taxe_sejour_adults} adulte(s) × ${priceResults[stayIdx].nights} nuit(s) × ${formatTaxe(priceResults[stayIdx].taxe_sejour_rate)} DHS)`"></span> <span class="font-semibold" x-text="`${formatTaxe(priceResults[stayIdx].taxe_sejour_total)} MAD`"></span> </div> </template> <template x-if="promoForStay(stayIdx)"> <div class="flex items-center gap-1.5 px-2 py-1.5 bg-emerald-50 border border-emerald-100 rounded-lg mt-1"> <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> <span class="text-xs text-emerald-700 font-medium" x-text="`Réduction long séjour de ${promoForStay(stayIdx).rate}% déjà appliquée sur les prix par nuit.`"></span> </div> </template> <div class="flex justify-between text-sm font-bold mt-2 pt-2 border-t border-amber-200 text-amber-900"> <span>Sous-total séjour <span x-text="stayIdx + 1"></span></span> <span x-text="`${formatTaxe((priceResults[stayIdx].total ?? 0) * (1 - (promoForStay(stayIdx)?.rate || 0) / 100) + (priceResults[stayIdx].taxe_sejour_total ?? 0))} MAD`"></span> </div> </div> </template> </div> </div> </template> {{-- Bouton ajouter un séjour --}}
            <div class="flex justify-center mb-4"> <button type="button" @click="addStay()"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-amber-600 hover:text-amber-700 bg-white border-2 border-dashed border-amber-300 hover:border-amber-400 px-6 py-3 rounded-xl w-full justify-center transition-colors"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Ajouter un autre séjour (autre date)
                </button> </div>

            {{-- Services Extras (lecture seule) --}}
            @if($reservation->extras->isNotEmpty())
            <div class="bg-white border border-amber-200 rounded-xl overflow-hidden mb-4">
                <div class="px-5 py-3 border-b border-amber-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    <h3 class="text-sm font-bold text-amber-800">Services Extras</h3>
                    <span class="text-xs text-amber-500 ml-auto">Gérés par l'administration</span>
                </div>
                <div class="px-5 py-3 divide-y divide-gray-100">
                    @foreach($reservation->extras as $extra)
                    <div class="flex items-center justify-between gap-3 py-2.5 first:pt-0 last:pb-0">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $extra->name }}</p>
                            @if($extra->description)
                            <p class="text-xs text-gray-400">{{ $extra->description }}</p>
                            @endif
                            <p class="text-xs text-gray-500">{{ $extra->quantity }} × {{ number_format($extra->unit_price, 2, ',', ' ') }} MAD</p>
                        </div>
                        <span class="text-sm font-bold text-amber-700 shrink-0">{{ number_format($extra->total_price, 2, ',', ' ') }} MAD</span>
                    </div>
                    @endforeach
                    @if($reservation->extras->count() > 1)
                    <div class="flex justify-between pt-2 text-sm font-semibold text-amber-700">
                        <span>Total extras</span>
                        <span>{{ number_format($extrasTotal, 2, ',', ' ') }} MAD</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Grand total estimé --}}
            <div x-show="grandTotal > 0" class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-4 space-y-2"> <div class="flex items-center justify-between"> <div> <p class="text-sm font-semibold text-amber-900">RÉCAPITULATIF ESTIMÉ</p> <p class="text-xs text-amber-600 mt-0.5"> <span x-text="totalRoomsCount"></span> chb. · <span x-text="stays.length"></span> séjour(s) · <span x-text="totalPersons"></span> pers. (<span x-text="totalAdults"></span> adultes · <span x-text="totalChildren"></span> enfants) </p> </div> </div> <div class="space-y-1 pt-2 border-t border-amber-200"> {{-- Hébergement --}}
                    <div class="flex justify-between text-sm text-amber-800"> <span>Hébergement <template x-if="promoInfo"><span class="text-emerald-700 font-normal text-xs">(promo incluse)</span></template></span> <span class="font-medium" x-text="`${formatTaxe(grandTotal - (promoInfo ? promoInfo.discount : 0))} MAD`"></span> </div> {{-- Taxe de séjour --}}
                    <template x-if="taxeTotalGlobal > 0"> <div class="flex justify-between text-sm text-blue-700"> <span> Taxe de séjour</span> <span class="font-medium" x-text="`${formatTaxe(taxeTotalGlobal)} MAD`"></span> </div> </template>
                    @if($extrasTotal > 0)
                    <div class="flex justify-between text-sm text-amber-700">
                        <span>Services extras</span>
                        <span class="font-medium">+ {{ number_format($extrasTotal, 2, ',', ' ') }} MAD</span>
                    </div>
                    @endif
                    {{-- Suppléments obligatoires --}}
                    <template x-for="sup in mandatorySupplements" :key="sup.id"> <div class="text-sm text-orange-700"> <div class="flex justify-between"> <span> <span x-text="sup.title"></span> <span class="text-xs text-orange-500">(obligatoire · <span x-text="sup.date"></span>)</span> </span> <span class="font-medium" x-text="`${formatPrice(sup.total)} MAD`"></span> </div> <div class="text-xs text-orange-400 pl-5 mt-0.5 space-x-2"> <template x-if="sup.adults > 0 && sup.price_adult > 0"> <span x-text="`${sup.adults} adulte(s) × ${formatPrice(sup.price_adult)} MAD`"></span> </template> <template x-if="sup.children > 0 && sup.price_child > 0"> <span x-text="`· ${sup.children} enfant(s) × ${formatPrice(sup.price_child)} MAD`"></span> </template> <template x-if="sup.babies > 0 && sup.price_baby > 0"> <span x-text="`· ${sup.babies} bébé(s) × ${formatPrice(sup.price_baby)} MAD`"></span> </template> </div> </div> </template> </div> {{-- Suppléments optionnels --}}
                <template x-if="optionalSupplements.length > 0"> <div class="pt-2 border-t border-amber-200"> <p class="text-xs font-semibold text-gray-700 mb-2">Suppléments optionnels :</p> <template x-for="sup in optionalSupplements" :key="sup.id"> <label class="flex items-start justify-between gap-3 cursor-pointer py-1.5 hover:bg-amber-100 rounded-lg px-1 -mx-1 transition-colors"> <span class="flex items-start gap-2"> <input type="checkbox"
                                        :value="sup.id"
                                        :name="`selected_supplements[]`"
                                        x-model="selectedOptionalSupplements"
                                        class="rounded border-gray-300 text-amber-500 mt-0.5 shrink-0"> <span class="text-sm text-gray-700"> <span x-text="sup.title"></span> <span class="text-xs text-gray-400">(<span x-text="sup.date"></span>)</span> <span class="block text-xs text-gray-400 mt-0.5 space-x-1"> <template x-if="sup.adults > 0 && sup.price_adult > 0"> <span x-text="`${sup.adults} adulte(s) × ${formatPrice(sup.price_adult)} MAD`"></span> </template> <template x-if="sup.children > 0 && sup.price_child > 0"> <span x-text="`· ${sup.children} enfant(s) × ${formatPrice(sup.price_child)} MAD`"></span> </template> <template x-if="sup.babies > 0 && sup.price_baby > 0"> <span x-text="`· ${sup.babies} bébé(s) × ${formatPrice(sup.price_baby)} MAD`"></span> </template> </span> </span> </span> <span class="text-sm font-semibold text-gray-800 whitespace-nowrap pt-0.5" x-text="`${formatPrice(sup.total)} MAD`"></span> </label> </template> </div> </template> {{-- Sous-total optionnels sélectionnés --}}
                <template x-if="selectedOptionalTotal > 0"> <div class="flex justify-between text-sm text-purple-700 pt-1"> <span> Suppléments optionnels sélectionnés</span> <span class="font-medium" x-text="`+ ${formatPrice(selectedOptionalTotal)} MAD`"></span> </div> </template> <div class="flex items-center justify-between pt-3 border-t border-amber-300"> <p class="text-base font-bold text-amber-900">TOTAL ESTIMÉ</p> <p class="text-2xl font-bold text-amber-700" x-text="`${formatTaxe(grandTotalWithExtras)} MAD`"></p> </div> <p class="text-xs text-amber-500">* Prix indicatif, confirmé après validation par notre équipe.</p> </div> {{-- Erreur capacité --}}
            <div x-show="hasCapacityError" x-transition
                class="bg-red-50 border border-red-200 rounded-xl px-5 py-3 mb-4 flex items-center gap-2 text-sm text-red-700"> <svg class="w-5 h-5 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/> </svg> <span>Veuillez corriger les erreurs de capacité avant de soumettre.</span> </div> {{-- Personne responsable --}}
            <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Personne responsable</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Nom du responsable <span class="text-red-500">*</span></label>
                        <input type="text" name="contact_name" required maxlength="100"
                               value="{{ old('contact_name', $reservation->contact_name) }}"
                               placeholder="Nom et prénom"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Téléphone du responsable</label>
                        <input type="text" name="phone" maxlength="30"
                               value="{{ old('phone', $reservation->phone) }}"
                               placeholder="+212 6XX XXX XXX"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                    </div>
                </div>
            </div>
            {{-- Demandes spéciales --}}
            <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6"> <label class="block text-sm font-medium text-gray-700 mb-1">Demandes spéciales</label> <textarea name="special_requests" rows="2"
                    placeholder="Transfert aéroport, régime alimentaire, chambres communicantes..."
                    class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">{{ old('special_requests', $reservation->special_requests) }}</textarea> </div> {{-- Alerte minimum 11 chambres --}}
            <div x-show="minRoomsBlocked" x-transition
                 class="bg-red-50 border border-red-200 rounded-xl px-5 py-4 flex items-start gap-3"> <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/> </svg> <div> <p class="text-sm font-bold text-red-800">Modification non disponible via ce portail</p> <p class="text-sm text-red-700 mt-1"> Les réservations de moins de 11 chambres doivent être effectuées directement via notre site web.
                    </p> <a href="{{ $reservation->hotel?->website ?? 'https://magic-emails.eureka-digital.ma' }}"
                       target="_blank"
                       class="inline-flex items-center gap-1.5 mt-2 text-sm font-semibold text-red-700 underline hover:text-red-900"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg> Réserver sur notre site web 
                    </a> </div> </div> {{-- Boutons --}}
            <div class="flex gap-3"> <button type="submit"
                    :disabled="hasCapacityError || minRoomsBlocked"
                    :class="(hasCapacityError || minRoomsBlocked)
                        ? 'bg-gray-300 text-gray-500 cursor-not-allowed font-semibold px-8 py-3 rounded-xl text-sm'
                        : 'bg-amber-500 hover:bg-amber-600 text-white font-semibold px-8 py-3 rounded-xl text-sm transition shadow-sm'">
                    {{ $isDraftEdit ? 'Confirmer et soumettre' : 'Soumettre la modification' }}
                </button> <a href="{{ route('agency.portal.dashboard') }}"
                   class="text-gray-500 hover:text-gray-700 text-sm px-4 py-3">Annuler</a> </div> </form> </div>
</main> <script>
const roomTypeCapacity = @json($roomTypeCapacity);
const roomTypeConfigs  = @json($roomTypeConfigs);
const hotelId          = '{{ $hotel->id }}';
const csrfToken        = '{{ csrf_token() }}';
const priceUrl         = '{{ route('client.calculate-price') }}';
const tariffCode       = '{{ $reservation->tariff_code ?? 'NRF' }}';

// Données promo long séjour de l'hôtel
@php
$_hotelPromo = [
    'enabled'      => (bool)  $hotel->promo_long_stay_enabled,
    'tier1_nights' => (int)   ($hotel->promo_tier1_nights ?? 0),
    'tier1_rate'   => (float) ($hotel->promo_tier1_rate   ?? 0),
    'tier2_nights' => (int)   ($hotel->promo_tier2_nights ?? 0),
    'tier2_rate'   => (float) ($hotel->promo_tier2_rate   ?? 0),
];
@endphp
const hotelPromo = @json($_hotelPromo);
const agencyStatusSlug = '{{ $agency->agencyStatus?->slug ?? '' }}';

function modificationForm() {
    return {
        stays: @json($staysData).map(stay => ({
            ...stay,
            rooms: stay.rooms.map(r => {
                const comboValue = (r.room_type_id && r.occupancy_config_id)
                    ? String(r.room_type_id) + '|' + String(r.occupancy_config_id)
                    : '';
                return { ...r, baby_bed: r.baby_bed || false, comboValue };
            })
        })),
        // Prix pré-remplis depuis la DB (prix figés  pas de recalcul API au chargement)
        priceResults: @json($initialPriceResults),
        extrasFixed: {{ (float) $extrasTotal }},
        // Snapshot des données originales pour détecter les changements
        _originalStays: @json($staysData),
        selectedOptionalSupplements: @json($selectedSupplementIds),
        init() {
            this.stays = this.stays.map(stay => ({
                ...stay,
                rooms: stay.rooms.map(room => {
                    if (room.comboValue) return room;
                    if (!room.room_type_id) return room;
                    const configs = roomTypeConfigs[room.room_type_id] || [];
                    if (!configs.length) { room.comboValue = String(room.room_type_id) + '|'; return room; }
                    const a = parseInt(room.adults)   || 0;
                    const c = parseInt(room.children) || 0;
                    const b = parseInt(room.babies)   || 0;
                    let matching = configs.filter(cfg => a >= (cfg.min_adults   || 0) && a <= (cfg.max_adults   || 99) &&
                        c >= (cfg.min_children || 0) && c <= (cfg.max_children || 99) &&
                        b >= 0 && b <= (cfg.max_babies || 99)
                    );
                    if (!matching.length) matching = configs;
                    const best = matching.reduce((a, b) => {
                        const rA = (a.max_adults - a.min_adults) + (a.max_children - a.min_children);
                        const rB = (b.max_adults - b.min_adults) + (b.max_children - b.min_children);
                        return rA <= rB ? a : b;
                    });
                    return { ...room, occupancy_config_id: best.id, comboValue: String(room.room_type_id) + '|' + String(best.id) };
                })
            }));
            // NE PAS recalculer au chargement  les prix viennent de la DB
        },

        // Détecte si le séjour a changé structurellement (dates, type chambre, config)
        _stayHasChanged(stayIdx) {
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

        // Après un appel API complet, restaure les anciens tarifs pour les chambres existantes
        // et calcule au tarif actuel uniquement les nouvelles chambres
        _applyHybridPricing(stayIdx, apiResult) {
            const orig   = this._originalStays[stayIdx];
            const cached = this.priceResults[stayIdx];
            const nights = this.nightsFor(stayIdx);
            const stay   = this.stays[stayIdx];

            if (!orig || !cached || !cached.breakdown || !apiResult.breakdown) {
                // Nouveau séjour ou pas de cache  conserver le résultat API tel quel
                this.priceResults[stayIdx] = apiResult;
                this.priceResults = [...this.priceResults];
                return;
            }

            const validRooms = stay.rooms.filter(r => r.room_type_id && r.occupancy_config_id);

            // Pour chaque ligne du résultat API, restaurer l'ancien prix si c'est une chambre originale
            const fixedBreakdown = (apiResult.breakdown || []).map((line, i) => {
                const origRoom   = orig.rooms[i];
                const cachedLine = cached.breakdown[i];
                const formRoom   = validRooms[i];

                if (!origRoom || !cachedLine || !formRoom) return line; // nouvelle chambre  tarif actuel

                // Vérifier que c'est bien la même chambre (même type + config)
                const sameType   = String(formRoom.room_type_id)        === String(origRoom.room_type_id);
                const sameConfig = String(formRoom.occupancy_config_id) === String(origRoom.occupancy_config_id);
                if (!sameType || !sameConfig) return line; // type changé  tarif actuel

                // Chambre originale  restaurer l'ancien prix par nuit
                const oldPPN = cachedLine.unit_price_raw && nights > 0 ? cachedLine.unit_price_raw / nights : 0;
                if (oldPPN <= 0) return line;
                const qty = line.quantity || 1;
                return {
                    ...line,
                    line_total:    Math.round(oldPPN * qty * nights * 100) / 100,
                    unit_price_raw: Math.round(oldPPN * qty * nights / qty * 100) / 100,
                };
            });

            // Fusionner les lignes avec le même tarif (même type + même prix/nuit)
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
            const taxeRate = cached.taxe_sejour_rate || 0;

            this.priceResults[stayIdx] = {
                ...apiResult,
                total:              mergedBreakdown.reduce((s, l) => s + (l.line_total || 0), 0),
                taxe_sejour_adults: totalAdults,
                taxe_sejour_total:  Math.round(totalAdults * nights * taxeRate * 100) / 100,
                breakdown:          mergedBreakdown,
            };
            this.priceResults = [...this.priceResults];
        },

        // Appel API complet du séjour + restauration des anciens prix pour les chambres originales
        async _calcNewRoomLinesOnly(stayIdx) {
            const stay   = this.stays[stayIdx];
            const nights = this.nightsFor(stayIdx);
            if (!stay || !stay.check_in || !stay.check_out || nights <= 0) return;

            const validRooms = stay.rooms.filter(r => r.room_type_id && r.quantity > 0);
            if (!validRooms.length) return;

            try {
                const globalTotalRooms = this.stays.reduce((sum, s) => sum + s.rooms.filter(r => r.room_type_id && r.quantity > 0)
                                 .reduce((s2, r) => s2 + (parseInt(r.quantity) || 1), 0), 0);

                const resp = await fetch(priceUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        hotel_id:    hotelId,
                        check_in:    stay.check_in,
                        check_out:   stay.check_out,
                        total_rooms: globalTotalRooms,
                        rooms: validRooms.map(r => ({
                            room_type_id:        r.room_type_id,
                            quantity:            parseInt(r.quantity) || 1,
                            adults:              parseInt(r.adults)   || 0,
                            children:            parseInt(r.children) || 0,
                            babies:              parseInt(r.babies)   || 0,
                            occupancy_config_id: r.occupancy_config_id || null,
                        })),
                    })
                });
                const data = await resp.json();
                if (!data.success) return;

                // Appliquer le pricing hybride : anciens prix conservés pour les chambres existantes
                this._applyHybridPricing(stayIdx, data);
            } catch(e) {
                console.error('_calcNewRoomLinesOnly error:', e);
            }
        },

        // Recalcule le total d'un séjour localement en utilisant les prix_par_nuit stockés
        // Utilisé quand seule la quantité change (tarif ancien conservé)
        _recalcLocalWithStoredPrices(stayIdx) {
            const cached = this.priceResults[stayIdx];
            if (!cached || !cached.breakdown) return;

            const stay   = this.stays[stayIdx];
            const nights = this.nightsFor(stayIdx);
            const taxeRate = cached.taxe_sejour_rate || 0;

            let newTotal   = 0;
            let newAdults  = 0;
            const newBreakdown = (cached.breakdown || []).map((line, i) => {
                const room     = stay.rooms.filter(r => r.room_type_id)[i];
                const qty      = room ? (parseInt(room.quantity) || 1) : line.quantity;
                const ppn      = line.unit_price_raw && nights > 0
                    ? line.unit_price_raw / nights   // unit_price_raw = ppn × 1 chambre × nights  ppn = /nights
                    : 0;
                const lineTotal = Math.round(ppn * qty * nights * 100) / 100;
                newTotal += lineTotal;

                // Recalculer adults pour la taxe
                if (room) newAdults += (parseInt(room.adults) || 0) * qty;

                return {
                    ...line,
                    quantity:       qty,
                    line_total:     lineTotal,
                    unit_price_raw: qty > 0 ? Math.round(lineTotal / qty * 100) / 100 : line.unit_price_raw,
                };
            });

            const newTaxe = Math.round(newAdults * nights * taxeRate * 100) / 100;

            // Mettre à jour les suppléments avec les nouveaux comptes de personnes
            const newSupps = (cached.supplements || []).map(sup => {
                let adults = 0, children = 0, babies = 0;
                stay.rooms.filter(r => r.room_type_id).forEach(r => {
                    const qty = parseInt(r.quantity) || 1;
                    adults   += (parseInt(r.adults)   || 0) * qty;
                    children += (parseInt(r.children) || 0) * qty;
                    babies   += (parseInt(r.babies)   || 0) * qty;
                });
                return {
                    ...sup,
                    adults, children, babies,
                    total: Math.round(adults * sup.price_adult + children * sup.price_child + babies * sup.price_baby),
                };
            });

            this.priceResults[stayIdx] = {
                ...cached,
                total:              newTotal,
                taxe_sejour_adults: newAdults,
                taxe_sejour_total:  newTaxe,
                breakdown:          newBreakdown,
                supplements:        newSupps,
            };
            this.priceResults = [...this.priceResults];
        },

        get totalPersons() {
            const total = this.stays.reduce((sum, stay) => sum + stay.rooms.reduce((rs, r) => {
                    const qty = parseInt(r.quantity) || 1;
                    return rs + ((parseInt(r.adults)||0) + (parseInt(r.children)||0) + (parseInt(r.babies)||0)) * qty;
                }, 0), 0);
            return total || 1;
        },

        get totalAdults() {
            return this.stays.reduce((sum, stay) => sum + stay.rooms.reduce((rs, r) => {
                const qty = parseInt(r.quantity) || 1;
                return rs + (parseInt(r.adults) || 0) * qty;
            }, 0), 0);
        },

        get totalChildren() {
            return this.stays.reduce((sum, stay) => sum + stay.rooms.reduce((rs, r) => {
                const qty = parseInt(r.quantity) || 1;
                return rs + (parseInt(r.children) || 0) * qty;
            }, 0), 0);
        },

        get grandTotal() {
            return this.priceResults.reduce((sum, r) => sum + (r?.total || 0), 0);
        },

        promoForStay(stayIdx) {
            if (!this.promoInfo) return null;
            return this.promoInfo.details.find(d => d.idx === stayIdx + 1) || null;
        },

        get taxeTotalGlobal() {
            return this.priceResults.reduce((sum, r) => sum + (r?.taxe_sejour_total || 0), 0);
        },

        get allApplicableSupplements() {
            const merged = new Map();
            for (const r of this.priceResults.filter(Boolean)) {
                for (const s of (r.supplements || [])) {
                    if (!merged.has(s.id)) {
                        merged.set(s.id, { ...s });
                    } else {
                        const m = merged.get(s.id);
                        m.adults   = (m.adults   || 0) + (s.adults   || 0);
                        m.children = (m.children || 0) + (s.children || 0);
                        m.babies   = (m.babies   || 0) + (s.babies   || 0);
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

        get mandatorySupplements() { return this.allApplicableSupplements.filter(s => s.is_mandatory); },
        get optionalSupplements()  { return this.allApplicableSupplements.filter(s => !s.is_mandatory); },

        get mandatorySupplementTotal() {
            return this.mandatorySupplements.reduce((sum, s) => sum + (s.total || 0), 0);
        },

        get selectedOptionalTotal() {
            return this.optionalSupplements
                .filter(s => this.selectedOptionalSupplements.some(sel => sel == s.id))
                .reduce((sum, s) => sum + (s.total || 0), 0);
        },

        get promoInfo() {
            if (!hotelPromo.enabled) return null;

            let totalDiscount = 0;
            const stayDetails = [];

            for (let idx = 0; idx < this.stays.length; idx++) {
                const nights    = this.nightsFor(idx);
                const stayTotal = this.priceResults[idx]?.total || 0;
                if (!stayTotal || nights <= 0) continue;

                let rate = 0;
                if (hotelPromo.tier2_nights > 0 && nights >= hotelPromo.tier2_nights) {
                    rate = hotelPromo.tier2_rate;
                } else if (hotelPromo.tier1_nights > 0 && nights >= hotelPromo.tier1_nights) {
                    rate = hotelPromo.tier1_rate;
                }
                if (!rate) continue;

                const discount = Math.round(stayTotal * rate / 100 * 100) / 100;
                totalDiscount += discount;
                stayDetails.push({ idx: idx + 1, nights, rate, discount });
            }

            if (totalDiscount <= 0) return null;

            const rates = [...new Set(stayDetails.map(s => s.rate))];
            let label;
            if (rates.length === 1) {
                const totalNights = stayDetails.reduce((s, d) => s + d.nights, 0);
                label = `Promo long séjour (${rates[0]}% sur ${totalNights} nuits)`;
            } else {
                label = 'Promo long séjour (' + stayDetails.map(s => `Séjour ${s.idx} : ${s.rate}% sur ${s.nights} nuits`).join(' · ') + ')';
            }

            return { discount: Math.round(totalDiscount * 100) / 100, label, details: stayDetails };
        },

        get grandTotalWithExtras() {
            const base     = this.grandTotal + this.taxeTotalGlobal + this.mandatorySupplementTotal + this.selectedOptionalTotal + (this.extrasFixed || 0);
            const discount = this.promoInfo ? this.promoInfo.discount : 0;
            return Math.max(0, base - discount);
        },

        get hasCapacityError() {
            return this.stays.some((stay, si) => stay.rooms.some((_, ri) => this.hasCapacityErrorFor(si, ri)));
        },

        get totalRoomsCount() {
            return this.stays.reduce((sum, s) => sum + s.rooms.filter(r => r.occupancy_config_id && r.quantity > 0)
                             .reduce((rs, r) => rs + (parseInt(r.quantity) || 1), 0), 0);
        },

        get minRoomsBlocked() {
            if (agencyStatusSlug === 'agence-de-voyages') return false;
            return this.totalRoomsCount > 0 && this.totalRoomsCount < 11;
        },

        nightsFor(stayIdx) {
            const s = this.stays[stayIdx];
            if (!s.check_in || !s.check_out) return 0;
            return Math.max(0, Math.round((new Date(s.check_out) - new Date(s.check_in)) / 86400000));
        },

        personsForStay(stayIdx) {
            return this.stays[stayIdx].rooms.reduce((sum, r) => {
                const qty = parseInt(r.quantity) || 1;
                return sum + ((parseInt(r.adults)||0) + (parseInt(r.children)||0) + (parseInt(r.babies)||0)) * qty;
            }, 0);
        },

        capacityFor(roomTypeId) { return roomTypeCapacity[roomTypeId] || { min: 1, max: 999 }; },

        getConfigById(cfgId) {
            if (!cfgId) return null;
            const id = parseInt(cfgId);
            for (const configs of Object.values(roomTypeConfigs)) {
                const found = configs.find(c => c.id === id);
                if (found) return found;
            }
            return null;
        },

        isOverCapacity(stayIdx, roomIdx) {
            const room = this.stays[stayIdx]?.rooms[roomIdx];
            if (!room || !room.room_type_id) return false;
            const cap = this.capacityFor(room.room_type_id);
            const p = (parseInt(room.adults)||0) + (parseInt(room.children)||0) + (parseInt(room.babies)||0);
            return p > cap.max;
        },

        isUnderCapacity(stayIdx, roomIdx) {
            const room = this.stays[stayIdx]?.rooms[roomIdx];
            if (!room || !room.room_type_id) return false;
            const cap = this.capacityFor(room.room_type_id);
            if (!cap.min || cap.min <= 1) return false;
            const p = (parseInt(room.adults)||0) + (parseInt(room.children)||0) + (parseInt(room.babies)||0);
            return p < cap.min;
        },

        hasCapacityErrorFor(stayIdx, roomIdx) {
            return this.isOverCapacity(stayIdx, roomIdx) || this.isUnderCapacity(stayIdx, roomIdx);
        },

        addStay() {
            this.stays.push({ check_in: '', check_out: '', rooms: [{ room_type_id: '', occupancy_config_id: null, comboValue: '', quantity: 1, adults: 1, children: 0, babies: 0, baby_bed: false }] });
            this.priceResults.push(null);
        },

        removeStay(idx) {
            this.stays.splice(idx, 1);
            this.priceResults.splice(idx, 1);
            this.$nextTick(() => this.recalculateAllStays());
        },

        addRoom(stayIdx) {
            this.stays[stayIdx].rooms.push({ room_type_id: '', occupancy_config_id: null, comboValue: '', quantity: 1, adults: 1, children: 0, babies: 0, baby_bed: false });
        },

        removeRoom(stayIdx, roomIdx) {
            this.stays[stayIdx].rooms.splice(roomIdx, 1);
            this.recalculateAllStays();
        },

        selectRoomConfig(stayIdx, roomIdx, value) {
            const room = this.stays[stayIdx]?.rooms[roomIdx];
            if (!room) return;
            room.comboValue = value || '';
            if (!value || !value.includes('|')) {
                room.room_type_id = ''; room.occupancy_config_id = null;
                this.calculatePriceForStay(stayIdx); return;
            }
            const parts = value.split('|');
            const rtId  = parseInt(parts[0]);
            const cfgId = parts[1] ? parseInt(parts[1]) : null;
            const prevCfg = room.occupancy_config_id;
            room.room_type_id        = rtId;
            room.occupancy_config_id = cfgId;
            if (cfgId && cfgId !== prevCfg) {
                const cfg = this.getConfigById(cfgId);
                if (cfg) { room.adults = Math.max(1, cfg.min_adults || 0); room.children = cfg.min_children || 0; room.babies = 0; }
            }
            this.calculatePriceForStay(stayIdx);
        },

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

        async recalculateAllStays() {
            await Promise.all(this.stays.map((_, idx) => this.calculatePriceForStay(idx)));
        },

        async calculatePriceForStay(stayIdx) {
            const cached     = this.priceResults[stayIdx];
            const stay       = this.stays[stayIdx];
            const nights     = this.nightsFor(stayIdx);
            const validRooms = stay ? stay.rooms.filter(r => r.room_type_id && r.quantity > 0) : [];

            if (!stay || !stay.check_in || !stay.check_out || nights <= 0) {
                this.priceResults[stayIdx] = null;
                this.priceResults = [...this.priceResults];
                return;
            }
            if (!validRooms.length) return;

            // Cas 1 : Rien n'a changé structurellement  recalcul local (quantité, personnes)
            const configuredRooms = validRooms.filter(r => r.occupancy_config_id);
            if (!this._stayHasChanged(stayIdx) && cached && configuredRooms.length === (cached.breakdown || []).length) {
                this._recalcLocalWithStoredPrices(stayIdx);
                return;
            }

            // Cas 2 : Nouvelles lignes ajoutées à un séjour existant (dates inchangées)
            // Condition : séjour présent dans l'original + plus de lignes configurées que dans le breakdown
            const origStay = this._originalStays[stayIdx];
            const breakdownLen = (cached && cached.breakdown) ? cached.breakdown.length : 0;
            if (origStay && cached && !this._stayHasChanged(stayIdx) && configuredRooms.length > breakdownLen) {
                await this._calcNewRoomLinesOnly(stayIdx);
                return;
            }
            // Aussi si chambres configurées > chambres originales (même dates inchangées)
            if (origStay && cached && configuredRooms.length > origStay.rooms.length) {
                await this._calcNewRoomLinesOnly(stayIdx);
                return;
            }

            // Cas 3 : Recalcul complet (dates changées, nouveau séjour, type changé)
            try {
                const totalRooms = this.stays.reduce((sum, s) => sum + s.rooms.filter(r => r.room_type_id && r.quantity > 0)
                                 .reduce((s2, r) => s2 + (parseInt(r.quantity) || 1), 0), 0);

                const resp = await fetch(priceUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        hotel_id:    hotelId,
                        check_in:    stay.check_in,
                        check_out:   stay.check_out,
                        total_rooms: totalRooms,
                        rooms: validRooms.map(r => ({
                            room_type_id: r.room_type_id, quantity: r.quantity,
                            adults: r.adults || 0, children: r.children || 0, babies: r.babies || 0,
                            occupancy_config_id: r.occupancy_config_id || null,
                        }))
                    })
                });
                const data = await resp.json();
                if (data.success) {
                    // Si les dates ont changé → prix API tels quels (nouvelles dates = nouveaux tarifs)
                    // Si séjour existant sans changement de dates → pricing hybride (anciens prix conservés)
                    // Si nouveau séjour → prix API tels quels
                    const origStayForCase3 = this._originalStays[stayIdx];
                    const datesChanged = origStayForCase3
                        && (origStayForCase3.check_in !== stay.check_in || origStayForCase3.check_out !== stay.check_out);
                    if (!datesChanged && origStayForCase3 && this.priceResults[stayIdx]) {
                        this._applyHybridPricing(stayIdx, data);
                    } else {
                        this.priceResults[stayIdx] = data;
                        this.priceResults = [...this.priceResults];
                    }
                }
            } catch(e) {}
        },

        /**
         * Génère le libellé du détail tarifaire d'une ligne de chambre.
         * - Si toutes les nuits ont le même tarif : "(640,00 MAD / ch. par nuit)"
         * - Si tarif variable : "(2 nuits 640,00 MAD · 1 nuit 1 728,00 MAD / ch.)"
         * promoRate : taux de remise long séjour à appliquer (0 = aucun)
         */
        nightBreakdown(line, nights, promoRate) {
            const factor = 1 - ((promoRate || 0) / 100);
            const fmt    = n => this.formatTaxe(n);
            const detail = line.night_detail || [];

            if (!detail.length || !nights) {
                const avg = (line.unit_price_raw || 0) / (nights || 1);
                return `(${fmt(avg * factor)} MAD / ch. par nuit)`;
            }

            // Grouper les nuits par tarif unitaire (ordre de première apparition)
            const groups = new Map();
            detail.forEach(n => groups.set(n.unit_price, (groups.get(n.unit_price) || 0) + 1));

            // Un seul tarif → affichage simple
            if (groups.size === 1) {
                const price = [...groups.keys()][0];
                return `(${fmt(price * factor)} MAD / ch. par nuit)`;
            }

            // Tarifs variables → liste "X nuits Y MAD · Z nuit W MAD"
            const parts = [];
            groups.forEach((count, price) => {
                parts.push(`${count} nuit${count > 1 ? 's' : ''} ${fmt(price * factor)} MAD`);
            });
            return `(${parts.join(' · ')} / ch.)`;
        },

        formatPrice(n) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(n)).replace(/[\u202F\u00A0]/g, ' ');
        },

        // Valeur exacte avec 2 décimales (taxe de séjour, prix unitaire chambre)
        formatTaxe(n) {
            return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n).replace(/[\u202F\u00A0]/g, ' ');
        },
    };
}
</script>
</body>
</html>
