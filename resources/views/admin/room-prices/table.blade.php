@extends('admin.layouts.app')
@section('title', 'Tableau tarifaire')
@section('page-title', 'Tableau tarifaire')

@section('header-actions')
    <a href="{{ route('admin.room-prices.index', ['hotel_id' => $hotelId]) }}"
       class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg"> Vue calendrier
    </a>
@endsection

@section('content')
<div x-data="priceGrid" class="space-y-4"> @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-5 py-3 text-sm font-medium flex items-center gap-2"> {{ session('success') }}
    </div> @endif

    {{--  Barre hôtel + grille + actions  --}}
    <div class="flex flex-wrap items-center gap-3"> {{-- Sélecteur hôtel --}}
        <form method="GET" action="{{ route('admin.room-prices.table') }}" id="hotelForm" class="flex items-center gap-2"> <select name="hotel_id" onchange="this.form.submit()"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-amber-400 focus:outline-none"> @foreach($hotels as $h)
                <option value="{{ $h->id }}" {{ $hotelId == $h->id ? 'selected' : '' }}>{{ $h->name }}</option> @endforeach
            </select> </form> {{-- Sélecteur de grille tarifaire --}}
        @if($tariffGrids->isNotEmpty())
        <div class="flex items-center gap-1 bg-white border border-gray-200 rounded-xl p-1"> @foreach($tariffGrids as $grid)
            <a href="{{ route('admin.room-prices.table', ['hotel_id' => $hotelId, 'grid_id' => $grid->id]) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap
                      {{ $activeGridId == $grid->id
                          ? 'bg-amber-500 text-white shadow-sm'
                          : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800' }}"> {{ $grid->code }}
                @if(!$grid->is_base)
                <span class="opacity-70 font-normal ml-0.5 text-[10px]">{{ $grid->formulaLabel() }}</span> @endif
            </a> @endforeach
        </div> @endif

        <span class="text-xs text-gray-400"> <span x-text="visiblePeriods.length"></span>/<span x-text="periods.length"></span> période(s) ·
            {{ $roomTypes->sum(fn($rt) => $rt->occupancyConfigs->count()) }} config(s)
        </span> <div class="ml-auto flex items-center gap-2"> <a href="{{ route('admin.tariff-grids.index', ['hotel_id' => $hotelId]) }}"
               class="text-sm text-gray-500 hover:text-gray-700 border border-gray-200 hover:bg-gray-50 px-3 py-2 rounded-lg"> Grilles
            </a> <button type="button" onclick="window.print()"
                class="text-sm text-gray-600 border border-gray-300 hover:bg-gray-50 px-3 py-2 rounded-lg"> Imprimer
            </button> <a href="{{ route('admin.room-prices.export', ['hotel_id' => $hotelId]) }}"
               class="text-sm text-emerald-700 border border-emerald-300 hover:bg-emerald-50 px-3 py-2 rounded-lg flex items-center gap-1.5"> Export Excel
            </a> @php $activeGrid = $tariffGrids->firstWhere('id', $activeGridId); @endphp
            @if(!$activeGrid || $activeGrid->is_base)
            <button type="button" @click="submitForm()"
                class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-5 py-2 rounded-lg shadow-sm"> Sauvegarder les tarifs NRF
            </button> @else
            <span class="text-xs text-gray-400 italic px-3 py-2"> Vue lecture seule · <a href="{{ route('admin.room-prices.table', ['hotel_id' => $hotelId, 'grid_id' => $tariffGrids->firstWhere('is_base', true)?->id]) }}" class="text-amber-600 hover:underline">Modifier NRF</a> </span> @endif
        </div> </div>

    {{-- Filtre par année / période --}}
    <div class="bg-white border border-gray-200 rounded-xl px-4 py-3 flex flex-wrap items-center gap-3">
        <span class="text-xs font-semibold text-gray-500 shrink-0">Filtrer :</span>
        {{-- Quick buttons : années réellement stockées en DB --}}
        <div class="flex items-center gap-1">
            <a href="{{ route('admin.room-prices.table', ['hotel_id' => $hotelId, 'grid_id' => request('grid_id')]) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ !$yearFilter && !$periodFrom && !$periodTo ? 'bg-amber-500 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                Toutes
            </a>
            @foreach($availableYears as $yr)
            <a href="{{ route('admin.room-prices.table', array_filter(['hotel_id' => $hotelId, 'grid_id' => request('grid_id'), 'year' => $yr])) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $yearFilter === (int)$yr ? 'bg-amber-500 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $yr }}
            </a>
            @endforeach
            @if($availableYears->isEmpty())
            <span class="text-xs text-gray-400 italic px-2">Aucun tarif enregistré</span>
            @endif
        </div>
        <div class="w-px h-5 bg-gray-200 shrink-0"></div>
        {{-- Date range form --}}
        <form method="GET" action="{{ route('admin.room-prices.table') }}" class="flex items-center gap-2 flex-wrap">
            <input type="hidden" name="hotel_id" value="{{ $hotelId }}">
            @if(request('grid_id'))<input type="hidden" name="grid_id" value="{{ request('grid_id') }}">@endif
            <input type="date" name="period_from" value="{{ $periodFrom }}"
                   class="border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-amber-400 focus:outline-none">
            <span class="text-xs text-gray-400">→</span>
            <input type="date" name="period_to" value="{{ $periodTo }}"
                   class="border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-amber-400 focus:outline-none">
            <button type="submit"
                    class="bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors">
                Filtrer
            </button>
        </form>
    </div>

    {{--  Gestion des périodes  --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4"> <div class="flex flex-wrap items-start gap-3"> <span class="text-sm font-semibold text-gray-700 mt-1 shrink-0">Périodes :</span> {{-- Chips des périodes existantes --}}
            <template x-for="(p, idx) in periods" :key="p._id"> <div class="flex items-center gap-1 rounded-lg px-2 py-1 border transition-colors"
                     :class="p.visible
                         ? 'bg-amber-50 border-amber-200'
                         : 'bg-gray-100 border-gray-200 opacity-60'"> <div class="text-xs"> <span class="font-medium"
                              :class="p.visible ? 'text-amber-800' : 'text-gray-400'"
                              x-text="formatDate(p.date_from) + '  ' + formatDate(p.date_to)"></span> <template x-if="p.label"> <span class="ml-1"
                                  :class="p.visible ? 'text-amber-500' : 'text-gray-400'"
                                  x-text="'(' + p.label + ')'"></span> </template> </div> {{-- Bouton afficher/masquer la colonne --}}
                    <button type="button" @click="togglePeriod(idx)"
                        :title="p.visible ? 'Masquer la colonne' : 'Afficher la colonne'"
                        class="ml-1 transition-colors text-sm leading-none"
                        :class="p.visible ? 'text-amber-400 hover:text-amber-600' : 'text-gray-300 hover:text-amber-400'"> <template x-if="p.visible"> <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/> <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/> </svg> </template> <template x-if="!p.visible"> <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/> </svg> </template> </button> {{-- Bouton supprimer --}}
                    <button type="button" @click="removePeriod(idx)"
                        title="Supprimer cette période"
                        class="text-gray-300 hover:text-red-500 transition-colors text-sm leading-none">×</button> </div> </template> {{-- Bouton + formulaire ajout période --}}
            <div x-data="{ open: false }" class="relative"> <button type="button" @click="open = !open"
                    class="flex items-center gap-1 text-sm text-amber-600 hover:text-amber-800 border border-dashed border-amber-300 hover:border-amber-500 bg-amber-50 px-3 py-1.5 rounded-lg transition-colors"> <span class="text-base leading-none">+</span> Ajouter une période
                </button> <div x-show="open" x-transition @click.outside="open = false"
                    class="absolute left-0 top-full mt-2 z-30 bg-white border border-gray-200 shadow-xl rounded-xl p-4 w-72"> <p class="text-xs font-semibold text-gray-700 mb-3">Nouvelle période</p> <div class="space-y-2"> <div class="grid grid-cols-2 gap-2"> <div> <label class="block text-xs text-gray-500 mb-1">Date début</label> <input type="date" x-model="newPeriod.date_from"
                                    class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-amber-400 focus:outline-none"> </div> <div> <label class="block text-xs text-gray-500 mb-1">Date fin</label> <input type="date" x-model="newPeriod.date_to"
                                    class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-amber-400 focus:outline-none"> </div> </div> <div> <label class="block text-xs text-gray-500 mb-1">Libellé (facultatif)</label> <input type="text" x-model="newPeriod.label" placeholder="Ex: Haute saison"
                                class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-amber-400 focus:outline-none"> </div> <div class="flex gap-2 pt-1"> <button type="button"
                                @click="addPeriod(); open = false"
                                :disabled="!newPeriod.date_from || !newPeriod.date_to"
                                class="flex-1 bg-amber-500 hover:bg-amber-600 disabled:bg-gray-200 disabled:cursor-not-allowed text-white text-xs font-semibold px-3 py-2 rounded-lg transition-colors"> Ajouter
                            </button> <button type="button" @click="open = false"
                                class="text-gray-400 text-xs px-2">Annuler</button> </div> </div> </div> </div> </div> <p x-show="periods.length === 0" class="text-xs text-amber-600 mt-2"> Ajoutez au moins une période pour commencer à saisir des tarifs.
        </p> <p x-show="periods.length > 0 && visiblePeriods.length < periods.length" class="text-xs text-gray-400 mt-2"> <span x-text="periods.length - visiblePeriods.length"></span> période(s) masquée(s)  cliquez sur  pour les réafficher.
        </p> </div> {{--  Grille de saisie  --}}
    <div x-show="visiblePeriods.length > 0 && {{ $roomTypes->count() }} > 0"
         class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm"> <form id="priceForm" method="POST" action="{{ route('admin.room-prices.table-save') }}" @submit.prevent="submitForm()"> @csrf
            <input type="hidden" name="hotel_id" value="{{ $hotelId }}"> <table class="border-collapse text-xs w-full" style="min-width: max-content;"> <thead> <tr class="bg-slate-800 text-white"> <th class="sticky left-0 z-20 bg-slate-800 border-r border-slate-600 px-4 py-3 text-left font-semibold" style="min-width:150px;"> Type de chambre
                        </th> <th class="bg-slate-800 border-r border-slate-600 px-4 py-3 text-left font-semibold" style="min-width:220px;"> Capacité
                        </th> <template x-for="(p, pIdx) in visiblePeriods" :key="p._id"> <th class="border-r border-slate-600 px-3 py-2 text-center font-semibold" style="min-width:110px;"> <div class="text-[10px] opacity-60">Du</div> <div class="text-xs" x-text="formatDate(p.date_from)"></div> <div class="text-[10px] opacity-60">au</div> <div class="text-xs" x-text="formatDate(p.date_to)"></div> <template x-if="p.label"> <div class="text-[9px] text-amber-300 mt-0.5" x-text="p.label"></div> </template> </th> </template> </tr> </thead> <tbody> {{--  Ligne Taux de base  --}}
                    @if(!$activeGrid || $activeGrid->is_base)
                    <tr class="bg-blue-900 text-white sticky top-[49px] z-10"> <td class="sticky left-0 z-20 bg-blue-900 border-r border-blue-700 px-4 py-2.5 font-bold text-sm whitespace-nowrap"> Taux de base
                        </td> <td class="border-r border-blue-700 px-3 py-2 text-[11px] text-blue-300 whitespace-nowrap"> Prix / chambre / nuit (MAD)
                        </td> <template x-for="(period, pIdx) in visiblePeriods" :key="period._id"> <td class="border-r border-blue-700 px-1 py-1 text-center" style="min-width:100px;"> <input
                                    type="number"
                                    min="0"
                                    max="99999"
                                    step="0.01"
                                    placeholder="Saisir..."
                                    :value="getBaseTaux(period)"
                                    @input="setBaseTaux(period, $event.target.value)"
                                    class="w-full text-center text-xs font-bold rounded px-1 py-2 border-0 bg-blue-800 hover:bg-blue-700 focus:bg-white focus:text-gray-900 focus:ring-2 focus:ring-amber-400 focus:outline-none text-white placeholder-blue-400 transition-colors"> </td> </template> </tr> @endif

                    @php $rtIdx = 0; @endphp
                    @foreach($roomTypes as $rt)
                    @php
                        $configs = $rt->occupancyConfigs;
                        $rowBg   = $rtIdx % 2 === 0 ? 'bg-white' : 'bg-gray-50/60';
                        $rtIdx++;
                    @endphp

                    @if($configs->isEmpty())
                    <tr class="{{ $rowBg }}"> <td class="sticky left-0 z-10 border-r border-t border-gray-200 px-4 py-3 font-bold text-gray-800 {{ $rowBg }}" colspan="2"> {{ $rt->name }}
                            <span class="text-[10px] font-normal text-gray-400 ml-2"> Aucune configuration d'occupation</span> </td> <td :colspan="periods.length" class="border-t border-gray-100"></td> </tr> @else
                    @foreach($configs as $cfgIdx => $cfg)
                    @php $bg = $cfgIdx % 2 === 0 ? $rowBg : ($rowBg === 'bg-white' ? 'bg-amber-50/30' : 'bg-amber-50/50'); @endphp
                    <tr class="{{ $bg }} hover:bg-amber-50/60 transition-colors"> {{-- Nom type (affiché sur 1ère ligne seulement) --}}
                        @if($cfgIdx === 0)
                        <td class="sticky left-0 z-10 border-r border-t border-gray-200 px-4 py-2.5 font-bold text-gray-900 align-top {{ $rowBg }}"
                            rowspan="{{ $configs->count() }}"> {{ $rt->name }}
                            @if($rt->max_persons)
                            <div class="text-[10px] font-normal text-gray-400">max {{ $rt->max_persons }} pers.</div> @endif
                        </td> @endif

                        {{-- Config + Coefficient --}}
                        <td class="border-r border-t border-gray-100 px-3 py-2"> <div class="flex items-center gap-2"> <span class="font-mono text-[10px] font-bold bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded shrink-0">{{ $cfg->code }}</span> <span class="text-gray-600 text-[11px]"> @php
                                        $p = [];
                                        if($cfg->min_adults === $cfg->max_adults)
                                            $p[] = $cfg->min_adults . ' adulte' . ($cfg->min_adults > 1 ? 's' : '');
                                        else
                                            $p[] = $cfg->min_adults . '' . $cfg->max_adults . ' adultes';
                                        if($cfg->max_children > 0) $p[] = '0' . $cfg->max_children . ' enfant' . ($cfg->max_children > 1 ? 's' : '');
                                        if($cfg->max_babies > 0)   $p[] = '0' . $cfg->max_babies . ' bébé' . ($cfg->max_babies > 1 ? 's' : '');
                                    @endphp
                                    {{ implode(' · ', $p) }}
                                </span> @if(($cfg->coefficient ?? 1.0) != 1.0 || true)
                                <span class="ml-auto shrink-0 font-mono text-[10px] font-bold px-1.5 py-0.5 rounded
                                    {{ ($cfg->coefficient ?? 1.0) == 1.0 ? 'bg-gray-100 text-gray-500' : 'bg-blue-100 text-blue-700' }}"> ×{{ number_format($cfg->coefficient ?? 1.0, 4) }}
                                </span> @endif
                            </div> </td> {{-- Cellules prix par période visible --}}
                        <template x-for="(period, pIdx) in visiblePeriods" :key="period._id"> <td class="border-r border-t border-gray-100 px-1 py-1 text-center" style="min-width:100px;"> @if(!$activeGrid || $activeGrid->is_base)
                                {{-- Grille NRF : cellule éditable --}}
                                <input
                                    type="number"
                                    min="0"
                                    max="99999"
                                    step="0.01"
                                    placeholder=""
                                    :value="getPrice({{ $cfg->id }}, period)"
                                    @input="setPrice({{ $cfg->id }}, period, $event.target.value)"
                                    class="w-full text-center text-xs font-semibold rounded px-1 py-2 border-0 focus:ring-2 focus:ring-amber-400 focus:outline-none bg-transparent hover:bg-white focus:bg-white transition-colors placeholder-gray-200"
                                    :class="getPrice({{ $cfg->id }}, period) ? 'text-gray-900' : 'text-gray-300'"> @else
                                {{-- Grille calculée : lecture seule --}}
                                <span class="text-xs font-semibold"
                                      :class="getBasePrice({{ $cfg->id }}, period) ? 'text-blue-700' : 'text-gray-200'"
                                      x-text="getCalculatedPrice({{ $cfg->id }}, period)"> </span> @endif
                            </td> </template> </tr> @endforeach
                    @endif
                    @endforeach
                </tbody> </table> </form> </div> {{--  Message si pas de période visible --}}
    <div x-show="periods.length === 0"
         class="bg-amber-50 border border-amber-200 rounded-xl p-10 text-center text-sm text-amber-700"> <p class="font-semibold">Ajoutez des périodes pour commencer la saisie des tarifs.</p> <p class="text-xs mt-1">Cliquez sur <strong>+ Ajouter une période</strong> pour définir vos plages de dates.</p> </div> <div x-show="periods.length > 0 && visiblePeriods.length === 0"
         class="bg-gray-50 border border-gray-200 rounded-xl p-6 text-center text-sm text-gray-500"> <p>Toutes les périodes sont masquées. Cliquez sur l'icône  d'une période pour l'afficher.</p> </div> {{-- Bouton bas --}}
    @if(!$activeGrid || $activeGrid->is_base)
    <div x-show="visiblePeriods.length > 0" class="flex justify-end"> <button type="button" @click="submitForm()"
            class="bg-amber-500 hover:bg-amber-600 text-white font-semibold px-8 py-3 rounded-xl shadow-sm text-sm transition-colors"> Sauvegarder les tarifs NRF
        </button> </div> @else
    <div x-show="visiblePeriods.length > 0" class="flex items-center justify-between px-1"> <p class="text-xs text-blue-600"> Grille <strong>{{ $activeGrid->code }}</strong> {{ $activeGrid->formulaLabel() }}  Vue en lecture seule
        </p> <a href="{{ route('admin.room-prices.table', ['hotel_id' => $hotelId, 'grid_id' => $tariffGrids->firstWhere('is_base', true)?->id]) }}"
           class="text-xs text-amber-600 hover:text-amber-800 border border-amber-200 hover:border-amber-400 px-4 py-2 rounded-lg transition-colors"> Modifier les tarifs NRF de base
        </a> </div> @endif

    {{-- Légende --}}
    <div class="flex items-center gap-4 text-xs text-gray-400 px-1"> @if(!$activeGrid || $activeGrid->is_base)
        <span>Cliquez sur une cellule pour saisir le prix en MAD.</span> @else
        <span>Prix calculés automatiquement à partir des tarifs NRF. <a href="{{ route('admin.tariff-grids.index', ['hotel_id' => $hotelId]) }}" class="text-amber-500 hover:underline">Modifier les formules</a></span> @endif
        <span class="ml-auto">* Prix par chambre/nuit · All Inclusive · Hors Taxe de Séjour</span> </div> </div>

    {{-- Historique des modifications (10 dernières) --}}
    @php
        $recentHistory = \App\Models\RoomPriceHistory::where('hotel_id', $hotelId)
            ->with('occupancyConfig.roomType')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    @endphp
    @if($recentHistory->isNotEmpty())
    <div x-data="{ open: false }" class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <button @click="open = !open" class="w-full flex items-center justify-between px-5 py-3 text-left hover:bg-gray-50">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-semibold text-gray-700">Historique des modifications</span>
                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">{{ $recentHistory->count() }} dernière(s)</span>
            </div>
            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="overflow-x-auto border-t border-gray-100">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 uppercase tracking-wide text-[10px]">
                            <th class="px-4 py-2 text-left">Date</th>
                            <th class="px-4 py-2 text-left">Chambre / Config</th>
                            <th class="px-4 py-2 text-left">Période tarifaire</th>
                            <th class="px-4 py-2 text-right">Ancien tarif</th>
                            <th class="px-4 py-2 text-right">Nouveau tarif</th>
                            <th class="px-4 py-2 text-right">Variation</th>
                            <th class="px-4 py-2 text-left">Modifié par</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($recentHistory as $h)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-400 whitespace-nowrap">{{ $h->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2">
                                <div class="font-semibold text-gray-800">{{ $h->occupancyConfig?->roomType?->name ?? '—' }}</div>
                                <div class="text-gray-400 font-mono text-[10px]">{{ $h->occupancyConfig?->code ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-2 text-gray-600 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($h->date_from)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($h->date_to)->format('d/m/Y') }}
                                @if($h->label)<div class="text-gray-400 text-[10px]">{{ $h->label }}</div>@endif
                            </td>
                            <td class="px-4 py-2 text-right text-gray-400 whitespace-nowrap">
                                @if($h->old_price !== null){{ number_format($h->old_price, 2, ',', ' ') }} MAD@else<span class="text-green-500">Nouveau</span>@endif
                            </td>
                            <td class="px-4 py-2 text-right font-semibold text-gray-800 whitespace-nowrap">{{ number_format($h->new_price, 2, ',', ' ') }} MAD</td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                @if($h->delta !== null)
                                    <span class="{{ $h->delta > 0 ? 'text-red-500' : ($h->delta < 0 ? 'text-green-600' : 'text-gray-400') }} font-semibold">
                                        {{ $h->delta > 0 ? '+' : '' }}{{ number_format($h->delta, 2, ',', ' ') }} MAD
                                        @if($h->old_price > 0)
                                        <span class="text-[10px] font-normal">({{ round($h->delta / $h->old_price * 100, 1) > 0 ? '+' : '' }}{{ round($h->delta / $h->old_price * 100, 1) }}%)</span>
                                        @endif
                                    </span>
                                @else<span class="text-gray-300">—</span>@endif
                            </td>
                            <td class="px-4 py-2 text-gray-500">{{ $h->changed_by_name ?? 'Système' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-2 border-t border-gray-50 text-right">
                <a href="{{ route('admin.room-prices.history', ['hotel_id' => $hotelId]) }}" class="text-xs text-amber-600 hover:underline">Voir tout l'historique →</a>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
<script>
// Périodes chargées depuis la DB (plus de session)
const sessionPeriods = @json($periods->values());
const currentHotelId = {{ $hotelId ?: 0 }};
const deletePeriodUrl = '{{ route('admin.room-prices.delete-period') }}';
// Matrice des prix NRF en DB: { configId: { "YYYY-MM-DD_YYYY-MM-DD": price } }
const dbMatrix = @json($priceMatrix);

// Grilles tarifaires: [{ id, code, name, is_base, base_grid_id, operator, operator_value, rounding }]
const tariffGridsData = @json($tariffGrids->values());
// ID de la grille active
const activeGridId = {{ $activeGridId ?? 'null' }};

// Coefficients par configId: { configId: coefficient }
const coefficients = @json(
    $roomTypes->flatMap->occupancyConfigs
        ->mapWithKeys(fn($cfg) => [$cfg->id => (float)($cfg->coefficient ?? 1.0)])
);

document.addEventListener('alpine:init', () => {
 Alpine.data('priceGrid', () => ({
        // Périodes: restaurées depuis la session, avec _id local et visible flag
        periods: sessionPeriods.map((p, i) => ({
            _id:      'p' + i,
            date_from: p.date_from,
            date_to:   p.date_to,
            label:     p.label || '',
            visible:   true,   // true = colonne affichée
        })),

        // Matrice locale des prix: { configId: { periodId: price } }
        localPrices: {},

        // Taux de base par période: { periodId: tauxValue }
        baseTaux: {},

        // Formulaire d'ajout de période
        newPeriod: { date_from: '', date_to: '', label: '' },

        init() {
            // Pré-remplir localPrices depuis la DB pour les périodes restaurées en session
            for (const p of this.periods) {
                const dbKey = p.date_from + '_' + p.date_to;
                for (const [configId, periodMap] of Object.entries(dbMatrix)) {
                    const val = periodMap[dbKey];
                    if (val !== undefined && val !== null && val !== '') {
                        if (!this.localPrices[configId]) this.localPrices[configId] = {};
                        this.localPrices[configId][p._id] = String(val);
                    }
                }
            }
        },

        // Périodes visibles dans le tableau (toggle)
        get visiblePeriods() {
            return this.periods.filter(p => p.visible);
        },

        addPeriod() {
            if (!this.newPeriod.date_from || !this.newPeriod.date_to) return;
            const newP = {
                _id:      'p' + Date.now(),
                date_from: this.newPeriod.date_from,
                date_to:   this.newPeriod.date_to,
                label:     this.newPeriod.label || '',
                visible:   true,
            };
            this.periods.push(newP);
            // Pré-remplir depuis la DB si dates connues
            const dbKey = newP.date_from + '_' + newP.date_to;
            for (const [configId, periodMap] of Object.entries(dbMatrix)) {
                if (periodMap[dbKey] !== undefined) {
                    if (!this.localPrices[configId]) this.localPrices[configId] = {};
                    this.localPrices[configId][newP._id] = periodMap[dbKey];
                }
            }
            this.newPeriod = { date_from: '', date_to: '', label: '' };
        },

        async removePeriod(idx) {
            const p = this.periods[idx];

            // Supprimer localement d'abord (réactivité immédiate)
            for (const prices of Object.values(this.localPrices)) {
                delete prices[p._id];
            }
            this.periods.splice(idx, 1);

            // Si la période existe en DB (elle a un date_from/date_to), supprimer côté serveur
            if (p.date_from && p.date_to && currentHotelId) {
                try {
                    await fetch(deletePeriodUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({
                            hotel_id:  currentHotelId,
                            date_from: p.date_from,
                            date_to:   p.date_to,
                        }),
                    });
                } catch (e) {
                    // Erreur silencieuse  la période est déjà retirée localement
                }
            }
        },

        togglePeriod(idx) {
            this.periods[idx].visible = !this.periods[idx].visible;
        },

        //  Grille NRF (base) 
        getPrice(configId, period) {
            // 1. Priorité aux modifications locales (saisie en cours)
            const local = this.localPrices[String(configId)]?.[period._id];
            if (local !== undefined && local !== null && local !== '') return local;
            // 2. Fallback direct sur la DB
            const dbKey = period.date_from + '_' + period.date_to;
            const dbVal = dbMatrix[String(configId)]?.[dbKey];
            return (dbVal !== undefined && dbVal !== null) ? String(dbVal) : '';
        },

        setPrice(configId, period, value) {
            const cid = String(configId);
            if (!this.localPrices[cid]) this.localPrices[cid] = {};
            this.localPrices[cid][period._id] = value;
        },

        //  Taux de base  recalcule tous les prix de la période 
        setBaseTaux(period, value) {
            this.baseTaux[period._id] = value;
            const taux = parseFloat(value);
            if (isNaN(taux) || taux <= 0) return;
            for (const [configIdStr, coeff] of Object.entries(coefficients)) {
                if (!this.localPrices[configIdStr]) this.localPrices[configIdStr] = {};
                const price = Math.round(taux * coeff * 100) / 100;
                this.localPrices[configIdStr][period._id] = price > 0 ? String(price) : '';
            }
        },

        getBaseTaux(period) {
            return this.baseTaux[period._id] ?? '';
        },

        //  Grilles calculées 
        getBasePrice(configId, period) {
            const nrfPrice = parseFloat(this.getPrice(configId, period));
            return isNaN(nrfPrice) ? 0 : nrfPrice;
        },

        getCalculatedPrice(configId, period) {
            const nrfPrice = this.getBasePrice(configId, period);
            if (!nrfPrice) return '';
            // Trouver la grille active
            const grid = tariffGridsData.find(g => g.id === activeGridId);
            if (!grid || grid.is_base) return nrfPrice ? String(nrfPrice) : '';
            // Calculer récursivement
            const result = this._calcGrid(nrfPrice, grid);
            return result > 0 ? result.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
        },

        _calcGrid(nrfPrice, grid) {
            if (!grid) return nrfPrice;
            if (grid.is_base) return this._applyRound(nrfPrice, grid.rounding);
            // Trouver la grille parente
            const parent = tariffGridsData.find(g => g.id === grid.base_grid_id);
            const parentPrice = parent ? this._calcGrid(nrfPrice, parent) : nrfPrice;
            let result = parentPrice;
            if (grid.operator === 'divide')           result = parentPrice / Math.max(grid.operator_value, 0.0001);
            else if (grid.operator === 'multiply')    result = parentPrice * grid.operator_value;
            else if (grid.operator === 'subtract_percent') result = parentPrice * (1 - grid.operator_value / 100);
            return this._applyRound(result, grid.rounding);
        },

        _applyRound(value, rounding) {
            if (rounding === 'ceil')  return Math.ceil(value * 100) / 100;
            if (rounding === 'floor') return Math.floor(value * 100) / 100;
            if (rounding === 'none')  return value;
            return Math.round(value * 100) / 100; // 'round' par défaut
        },

        formatDate(d) {
            if (!d) return '';
            const plain = d.split('T')[0];
            const [y, m, day] = plain.split('-');
            return `${day}/${m}/${y.slice(2)}`;
        },

        submitForm() {
            const form = document.getElementById('priceForm');

            // Nettoyer les anciens champs dynamiques
            form.querySelectorAll('.dyn-field').forEach(el => el.remove());

            const add = (name, value) => {
                const i = document.createElement('input');
                i.type = 'hidden';
                i.name = name;
                i.value = value ?? '';
                i.className = 'dyn-field';
                form.appendChild(i);
            };

            // 1. Soumettre toutes les périodes visibles
            this.visiblePeriods.forEach((period, pIdx) => {
                add(`periods[${pIdx}][date_from]`, period.date_from);
                add(`periods[${pIdx}][date_to]`,   period.date_to);
                add(`periods[${pIdx}][label]`,     period.label || '');
            });

            // 2. Soumettre les prix : localPrices en priorité, sinon dbMatrix
            // Collecter tous les configIds connus (localPrices + dbMatrix)
            const allConfigIds = new Set([
                ...Object.keys(this.localPrices),
                ...Object.keys(dbMatrix),
            ]);
            allConfigIds.forEach(configId => {
                this.visiblePeriods.forEach((period, pIdx) => {
                    // Priorité: saisie locale
                    let price = this.localPrices[configId]?.[period._id];
                    // Fallback: valeur en DB
                    if (price === undefined || price === '') {
                        const dbKey = period.date_from + '_' + period.date_to;
                        price = dbMatrix[configId]?.[dbKey];
                    }
                    if (price !== undefined && price !== null && price !== '') {
                        add(`prices[${configId}][${pIdx}]`, price);
                    }
                });
            });

            form.submit();
        },
    }));
});
</script>
@endpush

@push('styles')
<style>
@media print {
    nav, aside, header, .print-hidden { display: none !important; }
    body { font-size: 8px; }
    th, td { padding: 2px 4px !important; }
    input { border: none !important; box-shadow: none !important; }
}
</style>
@endpush
