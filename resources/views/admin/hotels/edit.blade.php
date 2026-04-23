@extends('admin.layouts.app')
@section('title', 'Modifier ' . $hotel->name)
@section('page-title', 'Modifier ' . $hotel->name)
@php use Illuminate\Support\Facades\Storage; @endphp

@section('content')
<div class="max-w-2xl"> <div class="bg-white border border-gray-200 rounded-xl p-6"> <form action="{{ route('admin.hotels.update', $hotel) }}" method="POST" enctype="multipart/form-data"> @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-4"> <div class="col-span-2"> <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label> <input type="text" name="name" required value="{{ old('name', $hotel->name) }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> <div> <label class="block text-sm font-medium text-gray-700 mb-1">Ville</label> <input type="text" name="city" value="{{ old('city', $hotel->city) }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> <div> <label class="block text-sm font-medium text-gray-700 mb-1">Pays</label> <input type="text" name="country" value="{{ old('country', $hotel->country) }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> <div> <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label> <input type="text" name="phone" value="{{ old('phone', $hotel->phone) }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> <div> <label class="block text-sm font-medium text-gray-700 mb-1">Email</label> <input type="email" name="email" value="{{ old('email', $hotel->email) }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> <div> <label class="block text-sm font-medium text-gray-700 mb-1">Étoiles</label> <select name="stars" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"> @for($i=0; $i<=5; $i++)
                        <option value="{{ $i }}" {{ old('stars', $hotel->stars) == $i ? 'selected' : '' }}>{{ $i }} </option> @endfor
                    </select> </div> <div> <label class="block text-sm font-medium text-gray-700 mb-1">Taxe de séjour <span class="text-gray-400 font-normal">(DHS / adulte / nuit)</span></label> <input type="number" name="taxe_sejour" min="0" step="0.01"
                        value="{{ old('taxe_sejour', $hotel->taxe_sejour ?? 19.80) }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> <div> <label class="block text-sm font-medium text-gray-700 mb-1">Régime de pension</label> <select name="meal_plan" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none bg-white"> <option value=""> Non précisé </option> <option value="all_inclusive"     {{ old('meal_plan', $hotel->meal_plan) === 'all_inclusive'     ? 'selected' : '' }}>All Inclusive</option> <option value="bed_and_breakfast" {{ old('meal_plan', $hotel->meal_plan) === 'bed_and_breakfast' ? 'selected' : '' }}>Bed &amp; Breakfast</option> <option value="half_board"        {{ old('meal_plan', $hotel->meal_plan) === 'half_board'        ? 'selected' : '' }}>Demi-Pension</option> <option value="full_board"        {{ old('meal_plan', $hotel->meal_plan) === 'full_board'        ? 'selected' : '' }}>Pension Complète</option> </select> </div> <div> <label class="flex items-center gap-2 mt-6 cursor-pointer"> <input type="checkbox" name="is_active" value="1" {{ old('is_active', $hotel->is_active) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-amber-500"> <span class="text-sm text-gray-700">Actif</span> </label> </div> <div class="col-span-2"> <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label> <input type="text" name="address" value="{{ old('address', $hotel->address) }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> <div class="col-span-2"> <label class="block text-sm font-medium text-gray-700 mb-1">Description</label> <textarea name="description" rows="3"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">{{ old('description', $hotel->description) }}</textarea> </div>

                {{-- Logo --}}
                <div class="col-span-2 pt-2">
                    <div class="border-t border-gray-100 pt-4 mb-4">
                        <p class="text-sm font-semibold text-gray-700">Logo de l'hôtel</p>
                        <p class="text-xs text-gray-400 mt-0.5">JPG, PNG, WebP ou SVG — max 2 Mo</p>
                    </div>
                    @if($hotel->logo)
                    <div class="mb-3 flex items-center gap-4 p-3 bg-gray-50 border border-gray-200 rounded-xl">
                        <img src="{{ Storage::url($hotel->logo) }}" alt="Logo {{ $hotel->name }}"
                             class="h-16 w-16 object-contain rounded-lg border border-gray-200 bg-white p-1">
                        <div>
                            <p class="text-xs font-medium text-gray-700">Logo actuel</p>
                            <p class="text-xs text-gray-400 mt-0.5">Choisir un nouveau fichier pour le remplacer</p>
                        </div>
                    </div>
                    @endif
                    <input type="file" name="logo" accept="image/*"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-500
                                  file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                  file:text-xs file:font-medium file:bg-amber-50 file:text-amber-700
                                  hover:file:bg-amber-100 focus:outline-none">
                    @error('logo')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- RIB / Coordonnées bancaires --}}
                <div class="col-span-2 pt-2">
                    <div class="border-t border-gray-100 pt-4 mb-4">
                        <p class="text-sm font-semibold text-gray-700">Coordonnées bancaires (RIB)</p>
                        <p class="text-xs text-gray-400 mt-0.5">Utilisées sur les devis et factures envoyés aux clients</p>
                    </div>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom de la banque</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name', $hotel->bank_name) }}"
                           placeholder="Ex : Attijariwafa Bank"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Numéro RIB</label>
                    <input type="text" name="bank_rib" value="{{ old('bank_rib', $hotel->bank_rib) }}"
                           placeholder="Ex : 007 780 0000123456789012 34"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-amber-400 focus:outline-none">
                    @error('bank_rib')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IBAN <span class="text-gray-400 font-normal">(optionnel)</span></label>
                    <input type="text" name="bank_iban" value="{{ old('bank_iban', $hotel->bank_iban) }}"
                           placeholder="Ex : MA64007780000123456789"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-amber-400 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code SWIFT / BIC <span class="text-gray-400 font-normal">(optionnel)</span></label>
                    <input type="text" name="bank_swift" value="{{ old('bank_swift', $hotel->bank_swift) }}"
                           placeholder="Ex : BCMAMAMC"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono uppercase focus:ring-2 focus:ring-amber-400 focus:outline-none">
                </div>

                </div> {{-- Promos long séjour --}}
            <div class="col-span-2 border-t border-gray-100 pt-4 mt-2"> <div class="flex items-center justify-between mb-3"> <div> <p class="text-sm font-semibold text-gray-800"> Promo long séjour</p> <p class="text-xs text-gray-400 mt-0.5">Réduction automatique selon le nombre de nuits</p> </div> <label class="flex items-center gap-2 cursor-pointer"> <input type="checkbox" name="promo_long_stay_enabled" value="1"
                            {{ old('promo_long_stay_enabled', $hotel->promo_long_stay_enabled) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-amber-500"
                            onchange="document.getElementById('promo-settings').classList.toggle('hidden', !this.checked)"> <span class="text-sm text-gray-700">Activer</span> </label> </div> <div id="promo-settings" class="{{ old('promo_long_stay_enabled', $hotel->promo_long_stay_enabled) ? '' : 'hidden' }}"> <div class="bg-amber-50 border border-amber-100 rounded-lg p-4 grid grid-cols-2 gap-4"> <div> <label class="block text-xs font-medium text-gray-600 mb-1"> Palier 1  à partir de
                                <span class="text-amber-600">N nuits</span> </label> <div class="flex gap-2 items-center"> <input type="number" name="promo_tier1_nights" min="1" max="30"
                                    value="{{ old('promo_tier1_nights', $hotel->promo_tier1_nights ?? 4) }}"
                                    class="w-20 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> <span class="text-xs text-gray-500">nuits </span> <input type="number" name="promo_tier1_rate" min="0" max="100" step="0.5"
                                    value="{{ old('promo_tier1_rate', $hotel->promo_tier1_rate ?? 10) }}"
                                    class="w-20 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> <span class="text-xs text-gray-500">% de réduction</span> </div> </div> <div> <label class="block text-xs font-medium text-gray-600 mb-1"> Palier 2  à partir de
                                <span class="text-amber-600">N nuits</span> </label> <div class="flex gap-2 items-center"> <input type="number" name="promo_tier2_nights" min="1" max="60"
                                    value="{{ old('promo_tier2_nights', $hotel->promo_tier2_nights ?? 7) }}"
                                    class="w-20 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> <span class="text-xs text-gray-500">nuits </span> <input type="number" name="promo_tier2_rate" min="0" max="100" step="0.5"
                                    value="{{ old('promo_tier2_rate', $hotel->promo_tier2_rate ?? 15) }}"
                                    class="w-20 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> <span class="text-xs text-gray-500">% de réduction</span> </div> </div> <div class="col-span-2 text-xs text-gray-400 border-t border-amber-100 pt-2 mt-1"> Exemple avec les valeurs par défaut : séjour de 4 à 6 nuits  10 %, 7 nuits et plus  15 %.
 La réduction s'applique sur le total des chambres, avant la remise groupe.
                        </div> </div> </div> </div> {{-- Tarification relative --}}
            @if($hotel->activeRoomTypes->isNotEmpty())
            <div class="col-span-2 border-t border-gray-100 pt-4 mt-2"> <div class="flex items-center justify-between mb-3"> <div> <p class="text-sm font-semibold text-gray-800"> Tarification relative</p> <p class="text-xs text-gray-400 mt-0.5">Choisissez un type de chambre "base"  les autres types sont calculés automatiquement en % d'écart</p> </div> </div> <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 space-y-4"
                     x-data="{
                        baseId: '{{ old('pricing_base_room_type_id', $hotel->pricing_base_room_type_id) }}',
                        roomTypes: {{ json_encode($hotel->activeRoomTypes->map(fn($rt) => ['id' => $rt->id, 'name' => $rt->name])) }},
                        offsets: {{ json_encode($hotel->room_type_price_offsets ?? new stdClass()) }}
                     }"> <div> <label class="block text-xs font-medium text-gray-600 mb-1">Type de chambre BASE (prix de référence)</label> <select name="pricing_base_room_type_id" x-model="baseId"
                            class="w-full sm:w-72 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none bg-white"> <option value=""> Pas de tarification relative </option> @foreach($hotel->activeRoomTypes as $rt)
                            <option value="{{ $rt->id }}" {{ old('pricing_base_room_type_id', $hotel->pricing_base_room_type_id) == $rt->id ? 'selected' : '' }}>{{ $rt->name }}</option> @endforeach
                        </select> </div> <template x-if="baseId"> <div class="space-y-2"> <p class="text-xs font-medium text-gray-600">Écarts en % par rapport au type de base :</p> <div class="grid grid-cols-1 sm:grid-cols-2 gap-3"> <template x-for="rt in roomTypes" :key="rt.id"> <template x-if="String(rt.id) !== String(baseId)"> <div class="flex items-center gap-3 bg-white border border-gray-200 rounded-lg px-3 py-2"> <span class="text-sm text-gray-700 flex-1" x-text="rt.name"></span> <div class="flex items-center gap-1"> <input type="number" step="0.5" min="-100" max="500"
                                                    :name="'price_offsets[' + rt.id + ']'"
                                                    :value="offsets[rt.id] ?? 0"
                                                    class="w-20 border border-gray-200 rounded-lg px-2 py-1 text-sm text-center focus:ring-2 focus:ring-blue-400 focus:outline-none"> <span class="text-xs text-gray-500">%</span> </div> </div> </template> </template> </div> <p class="text-xs text-gray-400">Ex : +20% = 20% plus cher que la base · 10% = 10% moins cher</p> </div> </template> </div> </div> @endif

            <div class="col-span-2 flex gap-3 mt-4 pt-4 border-t border-gray-100"> <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-6 py-2 rounded-lg text-sm">Enregistrer</button> <a href="{{ route('admin.hotels.show', $hotel) }}" class="text-gray-500 text-sm px-4 py-2">Annuler</a> </div> </form> </div>
</div>
@endsection
