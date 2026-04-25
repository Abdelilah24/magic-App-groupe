@extends('admin.layouts.app')
@section('title', 'Réservation ' . $reservation->reference)
@section('page-title', 'Réservation ' . $reservation->reference)
@section('page-subtitle', $reservation->agency_name . '  ' . $reservation->hotel->name)

@section('header-actions')
    <div class="flex items-center gap-3 flex-wrap">
        {{-- Marquer comme non lu --}}
        <form method="POST" action="{{ route('admin.reservations.mark-unread', $reservation) }}">
            @csrf @method('PATCH')
            <button type="submit"
                    title="Marquer comme non lu"
                    class="inline-flex items-center gap-1.5 bg-white border border-gray-200 hover:border-amber-400 hover:text-amber-600 text-gray-500 text-sm font-medium px-3 py-2 rounded-lg transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Non lu
            </button>
        </form>
        {{-- Télécharger proforma PDF --}}
        <a href="{{ route('admin.reservations.proforma', $reservation) }}"
           target="_blank"
           class="inline-flex items-center gap-1.5 bg-white border border-gray-200 hover:border-blue-400 hover:text-blue-700 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Proforma PDF
        </a>
        {{-- Envoyer proforma par email --}}
        <form action="{{ route('admin.reservations.proforma.send', $reservation) }}" method="POST"
              x-data="{ open: false }" class="relative">
            @csrf
            <button type="button" @click="open = !open"
                class="inline-flex items-center gap-1.5 bg-white border border-gray-200 hover:border-blue-400 hover:text-blue-700 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Envoyer proforma
            </button>
            <div x-show="open" x-cloak @click.outside="open = false"
                 class="absolute right-0 top-full mt-1 z-20 bg-white border border-gray-200 rounded-xl shadow-lg p-4 w-72">
                <p class="text-xs text-gray-500 mb-2">Envoyer la facture proforma à :</p>
                <input type="email" name="email"
                       value="{{ $reservation->email }}"
                       placeholder="Email du destinataire"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 mb-3">
                <div class="flex gap-2">
                    <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 rounded-lg transition">
                        Envoyer
                    </button>
                    <button type="button" @click="open = false"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium py-2 rounded-lg transition">
                        Annuler
                    </button>
                </div>
            </div>
        </form>
        <a href="{{ route('admin.reservations.edit', $reservation) }}"
           class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition"> Modifier la réservation
        </a>
        <a href="{{ route('admin.reservations.index') }}" class="text-sm text-gray-500 hover:text-gray-700"> Retour</a>
    </div>
@endsection

@section('content')
@php $otherReasonId = $refusalReasons->first(fn($r) => $r->isOther())?->id; @endphp
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6"> {{-- Colonne principale --}}
    <div class="lg:col-span-2 space-y-6"> {{-- Infos générales --}}
        <div class="bg-white border border-gray-200 rounded-xl p-6"> <div class="flex items-center justify-between mb-4"> <h2 class="text-base font-semibold">Informations générales</h2> @include('admin.partials.status-badge', ['status' => $reservation->status, 'label' => $reservation->status_label])
            </div>
            @php
                $computedAdults   = $reservation->rooms->sum(fn($r) => ($r->adults   ?? 0) * max(1, $r->quantity ?? 1));
                $computedChildren = $reservation->rooms->sum(fn($r) => ($r->children ?? 0) * max(1, $r->quantity ?? 1));
                $computedBabies   = $reservation->rooms->sum(fn($r) => ($r->babies   ?? 0) * max(1, $r->quantity ?? 1));
                $computedPersons  = ($computedAdults + $computedChildren + $computedBabies) ?: $reservation->total_persons;
                $sejours     = $reservation->sejours;
                $multiSejour = $sejours->count() > 1;
                $taxeTotal   = 0;
                $totalRooms  = $reservation->rooms->sum('quantity');
                $roomsByType = $reservation->rooms->map(fn($r) => [
                    'qty'   => (int) $r->quantity,
                    'label' => $r->occupancyConfig?->code ?: ($r->roomType?->name ?? 'Chambre'),
                ])->values();
                $firstCheckIn = $reservation->check_in;
                $lastCheckOut = $reservation->check_out;
            @endphp
            <div class="grid grid-cols-2 gap-x-10 gap-y-3 text-sm">
                {{-- ── Colonne gauche ─────────────────────────────────────── --}}
                <div class="space-y-3">
                    <div><span class="text-gray-500">Agence :</span> <strong>{{ $reservation->agency_name }}</strong></div>
                    <div><span class="text-gray-500">Email :</span> <a href="mailto:{{ $reservation->email }}" class="text-amber-600">{{ $reservation->email }}</a></div>
                    <div><span class="text-gray-500">Contact :</span> <strong>{{ $reservation->contact_name }}</strong></div>
                    <div><span class="text-gray-500">Téléphone :</span> {{ $reservation->phone ?? '—' }}</div>
                    <div><span class="text-gray-500">Créée le :</span> {{ $reservation->created_at->format('d/m/Y à H:i') }}</div>
                </div>
                {{-- ── Colonne droite ──────────────────────────────────────── --}}
                <div class="space-y-3">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-gray-500">Hôtel :</span>
                        <strong>{{ $reservation->hotel->name }}</strong>
                        @if($reservation->hotel->meal_plan)
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                            @if($reservation->hotel->meal_plan === 'all_inclusive') bg-emerald-100 text-emerald-700
                            @elseif($reservation->hotel->meal_plan === 'full_board') bg-blue-100 text-blue-700
                            @elseif($reservation->hotel->meal_plan === 'half_board') bg-indigo-100 text-indigo-700
                            @else bg-gray-100 text-gray-600 @endif">{{ $reservation->hotel->meal_plan_label }}</span>
                        @endif
                    </div>
                    <div><span class="text-gray-500">1er check-in :</span> <strong>{{ $firstCheckIn->format('d/m/Y') }}</strong></div>
                    <div><span class="text-gray-500">Dernier check-out :</span> <strong>{{ $lastCheckOut->format('d/m/Y') }}</strong></div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-gray-500">Séjours :</span>
                        @if($multiSejour)
                        <span class="inline-flex items-center gap-1 text-xs bg-amber-100 text-amber-800 font-semibold px-2 py-0.5 rounded-full">
                            {{ $sejours->count() }} séjours · {{ $reservation->nights }} nuits au total
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 text-xs bg-amber-100 text-amber-800 font-semibold px-2 py-0.5 rounded-full">
                            1 séjour · {{ $reservation->nights }} nuits
                        </span>
                        @endif
                    </div>
                    <div>
                        <span class="text-gray-500">Personnes :</span> {{ $computedPersons }}
                        @if($computedAdults + $computedChildren + $computedBabies > 0)
                        <span class="text-gray-400 text-xs ml-1">(
                            @if($computedAdults > 0){{ $computedAdults }} adulte{{ $computedAdults > 1 ? 's' : '' }}@endif
                            @if($computedChildren > 0) · {{ $computedChildren }} enfant{{ $computedChildren > 1 ? 's' : '' }}@endif
                            @if($computedBabies > 0) · {{ $computedBabies }} bébé{{ $computedBabies > 1 ? 's' : '' }}@endif
                        )</span>
                        @endif
                    </div>
                    <div><span class="text-gray-500">Nb chambres :</span> <strong>{{ $totalRooms }}</strong></div>
                </div>
            </div> @if($reservation->special_requests)
            <div class="mt-4 p-3 bg-gray-50 rounded-lg"> <p class="text-xs text-gray-500 font-medium mb-1">Demandes spéciales :</p> <p class="text-sm text-gray-700">{{ $reservation->special_requests }}</p> </div> @endif
            @if(($reservation->flexible_dates ?? false) || ($reservation->flexible_hotel ?? false))
            <div class="mt-3 flex items-center gap-3 flex-wrap"> @if($reservation->flexible_dates ?? false)
                <span class="text-xs bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-1 rounded-full"> Dates flexibles</span> @endif
                @if($reservation->flexible_hotel ?? false)
                <span class="text-xs bg-purple-50 text-purple-700 border border-purple-200 px-2.5 py-1 rounded-full"> Hôtel flexible</span> @endif
            </div> @endif
        </div> {{-- Chambres groupées par séjour --}}
        <div class="bg-white border border-gray-200 rounded-xl p-6">
        <h2 class="text-base font-semibold mb-4">Chambres demandées</h2>

        @php
                // Double index price_breakdown pour fallback label occupation
                $breakdownIndex  = [];
                $breakdownByRtId = [];
                foreach (($reservation->price_breakdown ?? []) as $line) {
                    $rtId = $line['room_type_id'] ?? '';
                    $key  = $rtId . '_' . ($line['occupancy_config_id'] ?? '');
                    $breakdownIndex[$key] = $line;
                    if (!isset($breakdownByRtId[$rtId])) {
                        $breakdownByRtId[$rtId] = $line;
                    }
                }
            @endphp
            @foreach($sejours as $i => $sejour)
            {{-- Conteneur séjour --}}
            @if($multiSejour)
            <div class="p-4 {{ $i > 0 ? 'mt-4' : '' }}" style="{{ $i % 2 !== 0 ? 'background-color:#b0bfdd17;' : '' }}">
            {{-- En-tête séjour --}}
            <div class="flex items-center gap-2 mb-3"> <span class="text-xs font-bold text-amber-700 bg-amber-100 px-2 py-1 rounded-lg"> Séjour {{ $i + 1 }}
                </span> <span class="text-sm font-semibold text-gray-800"> {{ $sejour['check_in']->format('d/m/Y') }}  {{ $sejour['check_out']->format('d/m/Y') }}
                </span> <span class="text-xs text-gray-400">({{ $sejour['nights'] }} nuit{{ $sejour['nights'] > 1 ? 's' : '' }})</span> </div>
            @endif
            @php
                // Promo long séjour pour CE séjour — calculé ICI avant la boucle des chambres
                $_sejNights    = $sejour['nights'] ?? 0;
                $_sejRoomsSum  = (float) $sejour['rooms']->sum(fn($r) => $r->total_price ?? 0);
                $_sejPromoRate = 0;
                $_sejPromoAmt  = 0;
                try {
                    $_sejPromoRate = $reservation->hotel?->getPromoRate($_sejNights) ?? 0;
                    if ($_sejPromoRate > 0 && $_sejRoomsSum > 0) {
                        $_sejPromoAmt = round($_sejRoomsSum * $_sejPromoRate / 100, 2);
                    }
                } catch (\Exception $_e) {}
            @endphp

            <table class="min-w-full text-sm {{ $multiSejour ? 'mb-1' : '' }}"> <thead> <tr class="border-b border-gray-100"> <th class="pb-2 text-left font-medium text-gray-500">Type de chambre</th> <th class="pb-2 text-left font-medium text-gray-500">Personnes</th> <th class="pb-2 text-left font-medium text-gray-500">Prix/nuit</th> <th class="pb-2 text-left font-medium text-gray-500">Total</th> </tr> </thead> <tbody class="divide-y divide-gray-50"> @foreach($sejour['rooms'] as $room)
                    @php
                        $rNights = ($room->check_in && $room->check_out)
                            ? (int) $room->check_in->diffInDays($room->check_out)
                            : $sejour['nights'];
                        $rParts = [];
                        if ($room->adults)   $rParts[] = $room->adults   . ' adulte'  . ($room->adults   > 1 ? 's' : '') . '/ch.';
                        if ($room->children) $rParts[] = $room->children . ' enfant'  . ($room->children > 1 ? 's' : '') . '/ch.';
                        if ($room->babies)   $rParts[] = $room->babies   . ' bébé'    . ($room->babies   > 1 ? 's' : '') . '/ch.';
                        // Fallback occupation label : DB  relation  price_breakdown exact  price_breakdown par rtId  nom type
                        $bKeyExact   = $room->room_type_id . '_' . ($room->occupancy_config_id ?? '');
                        $occupLabel  = $room->occupancy_config_label
                            ?: ($room->occupancyConfig?->label
                                ?: ($breakdownIndex[$bKeyExact]['occupancy_label'] ?? null)
                                    ?: ($breakdownByRtId[$room->room_type_id]['occupancy_label'] ?? null));
                        // Comparaison avant/après modification manuelle de prix
                        $_puMatch     = (bool) ($room->price_override ?? false);
                        $_puDiffPpn   = $_puMatch ? round(($room->price_per_night ?? 0) - ($room->original_price_per_night ?? 0), 2) : 0;
                        $_puDiffTotal = $_puMatch ? round(($room->total_price     ?? 0) - ($room->original_total_price     ?? 0), 2) : 0;
                    @endphp
                    <tr> <td class="py-2"> <p class="font-medium text-gray-900"> {{ $room->quantity }}×
                                @if($occupLabel)
                                    <span class="text-amber-700">{{ $occupLabel }}</span> <span class="text-gray-400 text-xs font-normal">({{ $room->roomType?->name ?? '' }})</span> @else
                                    {{ $room->roomType?->name ?? '' }}
                                @endif
                            </p> <p class="text-xs text-gray-400 mt-0.5">{{ $rNights }} nuit{{ $rNights > 1 ? 's' : '' }}</p> </td> <td class="py-2 text-gray-600 text-xs"> {{ count($rParts) ? implode(', ', $rParts) : '' }}
                            @if($room->baby_bed)
                            <span class="block text-blue-500 mt-0.5"> Lit bébé</span> @endif
                        </td>
                            @php
                                $_rNightsEdit = ($room->check_in && $room->check_out)
                                    ? (int) $room->check_in->diffInDays($room->check_out)
                                    : ($sejour['nights'] ?? 0);
                                $_rQtyEdit    = max(1, (int)($room->quantity ?? 1));
                                $_roomPpn     = (float)($room->price_per_night ?? 0);
                                $_roomTotal   = (float)($room->total_price ?? 0);
                                $_roomId      = (int)$room->id;

                                // Détection tarif variable + groupes de nuits consécutives au même prix
                                $_priceDetail    = is_array($room->price_detail) ? $room->price_detail : [];
                                $_uniquePrices   = collect($_priceDetail)->pluck('unit_price')->unique()->count();
                                $_isVariableRate = !($room->price_override ?? false) && $_uniquePrices > 1;

                                // Construire les groupes : [{price, count, dateStart, dateEnd}]
                                $_nightGroups = [];
                                if ($_isVariableRate) {
                                    $__currentPrice = null;
                                    $__groupCount   = 0;
                                    $__groupStart   = null;
                                    $__groupLast    = null;
                                    foreach ($_priceDetail as $_nd) {
                                        $_ndPrice = (float)($_nd['unit_price'] ?? 0);
                                        $_ndDate  = $_nd['date'] ?? null;
                                        if ($_ndPrice !== $__currentPrice) {
                                            if ($__currentPrice !== null) {
                                                // Fin de période : check-out = lendemain de la dernière nuit
                                                $_nightGroups[] = [
                                                    'price'      => $__currentPrice,
                                                    'count'      => $__groupCount,
                                                    'dateStart'  => \Carbon\Carbon::parse($__groupStart)->format('d/m/Y'),
                                                    'dateEnd'    => \Carbon\Carbon::parse($__groupLast)->addDay()->format('d/m/Y'),
                                                ];
                                            }
                                            $__currentPrice = $_ndPrice;
                                            $__groupStart   = $_ndDate;
                                            $__groupCount   = 1;
                                        } else {
                                            $__groupCount++;
                                        }
                                        $__groupLast = $_ndDate;
                                    }
                                    // Dernier groupe
                                    if ($__currentPrice !== null) {
                                        $_nightGroups[] = [
                                            'price'     => $__currentPrice,
                                            'count'     => $__groupCount,
                                            'dateStart' => \Carbon\Carbon::parse($__groupStart)->format('d/m/Y'),
                                            'dateEnd'   => \Carbon\Carbon::parse($__groupLast)->addDay()->format('d/m/Y'),
                                        ];
                                    }
                                }
                                // Moyenne pondérée calculée depuis les groupes (plus fiable que price_per_night en DB)
                                $_ngTotalNights = array_sum(array_column($_nightGroups, 'count'));
                                $_ngWeightedSum = array_sum(array_map(fn($g) => $g['price'] * $g['count'], $_nightGroups));
                                $_ngAvgPpn      = $_ngTotalNights > 0 ? round($_ngWeightedSum / $_ngTotalNights, 2) : (float)($room->price_per_night ?? 0);
                            @endphp
                            {{-- ── Cellule Prix/nuit ───────────────────────────────── --}}
                            <td class="py-2 text-gray-600"
                                x-data="{ editing: false, ppn: {{ $_roomPpn }} }">
                                {{-- Vue normale / pending (quand pas en mode édition) --}}
                                <div x-show="!editing" class="flex items-start gap-1">
                                    <div class="space-y-0.5">

                                        {{-- EN ATTENTE (store Alpine) --}}
                                        <template x-if="$store.priceDraft.has({{ $_roomId }})">
                                            <div class="space-y-0.5">
                                                {{-- Ancien prix barré --}}
                                                <div class="flex items-center gap-1 text-xs text-gray-400">
                                                    <span class="line-through">{{ number_format($_roomPpn, 2, ',', ' ') }} MAD</span>
                                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                                </div>
                                                @if($_sejPromoRate > 0)
                                                {{-- Nouveau prix pré-promo barré --}}
                                                <span class="line-through text-gray-400 text-xs"
                                                    x-text="$store.priceDraft.fmt($store.priceDraft.pending[{{ $_roomId }}].newPpn) + ' MAD'"></span>
                                                {{-- Nouveau prix post-promo (vert) --}}
                                                <span class="block text-emerald-600 font-bold text-sm"
                                                    x-text="$store.priceDraft.fmt($store.priceDraft.pending[{{ $_roomId }}].newPpn * {{ 1 - $_sejPromoRate / 100 }}) + ' MAD'"></span>
                                                @else
                                                {{-- Nouveau prix (orange) --}}
                                                <span class="text-orange-600 font-bold text-sm"
                                                    x-text="$store.priceDraft.fmt($store.priceDraft.pending[{{ $_roomId }}].newPpn) + ' MAD'"></span>
                                                @endif
                                                {{-- Delta par nuit (pré-promo) --}}
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-xs font-semibold"
                                                        :class="$store.priceDraft.pending[{{ $_roomId }}].newPpn > {{ $_roomPpn }} ? 'text-emerald-600' : ($store.priceDraft.pending[{{ $_roomId }}].newPpn < {{ $_roomPpn }} ? 'text-red-500' : 'text-gray-400')"
                                                        x-text="($store.priceDraft.pending[{{ $_roomId }}].newPpn > {{ $_roomPpn }} ? '+' : '') + $store.priceDraft.fmt($store.priceDraft.pending[{{ $_roomId }}].newPpn - {{ $_roomPpn }}) + ' MAD/nuit'"></span>
                                                    <button type="button"
                                                        @click="$store.priceDraft.remove({{ $_roomId }}); ppn = {{ $_roomPpn }}"
                                                        class="text-gray-300 hover:text-red-400 transition text-xs leading-none" title="Annuler cette modification">✕</button>
                                                </div>
                                            </div>
                                        </template>

                                        {{-- AFFICHÉ (depuis DB, pas de pending) --}}
                                        <template x-if="!$store.priceDraft.has({{ $_roomId }})">
                                            <div class="space-y-0.5">
                                                @if($_puMatch)
                                                <div class="flex items-center gap-1 text-xs text-gray-400">
                                                    <span class="line-through">{{ number_format($room->original_price_per_night, 2, ',', ' ') }} MAD</span>
                                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                                </div>
                                                @endif
                                                @if($room->price_per_night)
                                                    @if($_isVariableRate)
                                                    {{-- Tarif variable : liste des tranches avec dates --}}
                                                    <div class="space-y-0.5">
                                                        @foreach($_nightGroups as $_ng)
                                                        <div class="text-xs leading-tight">
                                                            @if($_sejPromoRate > 0)
                                                            <span class="line-through text-gray-400">{{ number_format($_ng['price'], 2, ',', ' ') }} MAD</span>
                                                            <span class="text-gray-400 mx-0.5">→</span>
                                                            <span class="font-semibold text-emerald-600">{{ number_format($_ng['price'] * (1 - $_sejPromoRate / 100), 2, ',', ' ') }} MAD</span>
                                                            @else
                                                            <span class="font-semibold text-indigo-700">{{ number_format($_ng['price'], 2, ',', ' ') }} MAD</span>
                                                            @endif
                                                            <span class="text-gray-500"> — {{ $_ng['count'] }} nuit{{ $_ng['count'] > 1 ? 's' : '' }}</span>
                                                            <span class="block text-gray-400 text-[10px]">{{ $_ng['dateStart'] }} → {{ $_ng['dateEnd'] }}</span>
                                                        </div>
                                                        @endforeach
                                                        @if($_sejPromoRate > 0)
                                                        <span class="block text-[10px] text-gray-400">≈ {{ number_format($_ngAvgPpn, 2, ',', ' ') }} MAD moy./nuit → <span class="text-emerald-600">{{ number_format($_ngAvgPpn * (1 - $_sejPromoRate / 100), 2, ',', ' ') }} MAD après réd.</span></span>
                                                        @else
                                                        <span class="block text-[10px] text-gray-400">≈ {{ number_format($_ngAvgPpn, 2, ',', ' ') }} MAD moy./nuit</span>
                                                        @endif
                                                    </div>
                                                    @elseif($_sejPromoRate > 0)
                                                        <span class="line-through text-gray-400 text-xs">{{ number_format($room->price_per_night, 2, ',', ' ') }} MAD</span>
                                                        <span class="block text-emerald-600 font-semibold text-sm">{{ number_format($room->price_per_night * (1 - $_sejPromoRate / 100), 2, ',', ' ') }} MAD</span>
                                                    @else
                                                        <span class="{{ $_puMatch ? 'font-bold text-blue-700' : 'text-gray-700' }} text-sm">{{ number_format($room->price_per_night, 2, ',', ' ') }} MAD</span>
                                                    @endif
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                                @if($_puMatch)
                                                <div class="text-xs font-semibold {{ $_puDiffPpn <= 0 ? 'text-red-500' : 'text-emerald-600' }}">
                                                    {{ $_puDiffPpn >= 0 ? '+' : '' }}{{ number_format($_puDiffPpn, 2, ',', ' ') }} MAD/nuit
                                                </div>
                                                @endif
                                            </div>
                                        </template>
                                    </div>

                                    {{-- Bouton crayon --}}
                                    <button type="button" @click="editing = true"
                                        class="shrink-0 text-gray-300 hover:text-amber-500 transition mt-0.5" title="Modifier le prix/nuit">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                </div>

                                {{-- Formulaire inline (sans soumission DB) --}}
                                <div x-show="editing" x-cloak>
                                    <div class="flex flex-col gap-1.5">
                                        @if($_isVariableRate)
                                        <div class="text-xs text-indigo-600 bg-indigo-50 border border-indigo-200 rounded px-2 py-1 space-y-0.5">
                                            <div class="font-semibold">⚠ Tarif variable par nuit :</div>
                                            @foreach($_nightGroups as $_ng)
                                            <div>· {{ $_ng['count'] }} nuit{{ $_ng['count'] > 1 ? 's' : '' }} ({{ $_ng['dateStart'] }} → {{ $_ng['dateEnd'] }}) : {{ number_format($_ng['price'], 2, ',', ' ') }} MAD</div>
                                            @endforeach
                                            <div class="text-indigo-400 pt-0.5">Un prix uniforme remplacera toutes les nuits.</div>
                                        </div>
                                        @endif
                                        <div class="flex items-center gap-1">
                                            <input type="number" x-model.number="ppn"
                                                step="0.01" min="0"
                                                @keydown.escape="editing = false"
                                                class="w-28 border border-amber-400 rounded-lg px-2 py-1 text-xs font-semibold focus:ring-2 focus:ring-amber-400 focus:outline-none">
                                            <span class="text-xs text-gray-400">MAD</span>
                                        </div>
                                        <p class="text-xs text-gray-400">
                                            × {{ $_rQtyEdit }} ch. × {{ $_rNightsEdit }} nuit{{ $_rNightsEdit > 1 ? 's' : '' }}
                                            = <span x-text="$store.priceDraft.fmt(ppn * {{ $_rQtyEdit }} * {{ $_rNightsEdit }}) + ' MAD'" class="font-semibold text-gray-600"></span>
                                        </p>
                                        <div class="flex gap-1">
                                            <button type="button"
                                                @click="$store.priceDraft.add({{ $_roomId }}, { newPpn: ppn, newTotal: Math.round(ppn * {{ $_rQtyEdit }} * {{ $_rNightsEdit }} * 100) / 100, oldTotal: {{ $_roomTotal }}, promoRate: {{ $_sejPromoRate }} }); editing = false"
                                                class="text-xs bg-amber-500 hover:bg-amber-600 text-white px-2.5 py-1 rounded-lg font-semibold transition">✓ Appliquer</button>
                                            <button type="button" @click="editing = false; ppn = {{ $_roomPpn }}"
                                                class="text-xs text-gray-400 hover:text-gray-600 px-2 py-1 rounded-lg hover:bg-gray-100 transition">✕</button>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- ── Cellule Total ───────────────────────────────────── --}}
                            <td class="py-2 font-medium" x-data>
                                <div class="space-y-0.5">

                                    {{-- EN ATTENTE --}}
                                    <template x-if="$store.priceDraft.has({{ $_roomId }})">
                                        <div class="space-y-0.5">
                                            {{-- Ancien total barré (valeur affichée avant modif, post-promo si applicable) --}}
                                            <div class="flex items-center gap-1 text-xs text-gray-400">
                                                @if($_sejPromoRate > 0)
                                                <span class="line-through">{{ number_format($_roomTotal * (1 - $_sejPromoRate / 100), 2, ',', ' ') }} MAD</span>
                                                @else
                                                <span class="line-through">{{ number_format($_roomTotal, 2, ',', ' ') }} MAD</span>
                                                @endif
                                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                            </div>
                                            @if($_sejPromoRate > 0)
                                            {{-- Nouveau total pré-promo barré --}}
                                            <span class="line-through text-gray-400 text-xs"
                                                x-text="$store.priceDraft.fmt($store.priceDraft.pending[{{ $_roomId }}].newTotal) + ' MAD'"></span>
                                            {{-- Nouveau total post-promo vert --}}
                                            <span class="block text-emerald-600 font-bold"
                                                x-text="$store.priceDraft.fmt($store.priceDraft.pending[{{ $_roomId }}].newTotal * {{ 1 - $_sejPromoRate / 100 }}) + ' MAD'"></span>
                                            @else
                                            {{-- Nouveau total (orange) --}}
                                            <span class="text-orange-600 font-bold"
                                                x-text="$store.priceDraft.fmt($store.priceDraft.pending[{{ $_roomId }}].newTotal) + ' MAD'"></span>
                                            @endif
                                            {{-- Delta total (affiché en valeur post-promo) --}}
                                            <div class="text-xs font-semibold"
                                                :class="($store.priceDraft.pending[{{ $_roomId }}].newTotal - {{ $_roomTotal }}) > 0 ? 'text-emerald-600' : (($store.priceDraft.pending[{{ $_roomId }}].newTotal - {{ $_roomTotal }}) < 0 ? 'text-red-500' : 'text-gray-400')"
                                                x-text="(($store.priceDraft.pending[{{ $_roomId }}].newTotal - {{ $_roomTotal }}) > 0 ? '+' : '') + $store.priceDraft.fmt(($store.priceDraft.pending[{{ $_roomId }}].newTotal - {{ $_roomTotal }}) * {{ 1 - $_sejPromoRate / 100 }}) + ' MAD'">
                                            </div>
                                        </div>
                                    </template>

                                    {{-- AFFICHÉ (depuis DB) --}}
                                    <template x-if="!$store.priceDraft.has({{ $_roomId }})">
                                        <div class="space-y-0.5">
                                            @if($_puMatch)
                                            <div class="flex items-center gap-1 text-xs text-gray-400">
                                                <span class="line-through">{{ number_format($room->original_total_price, 2, ',', ' ') }} MAD</span>
                                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                            </div>
                                            @endif
                                            @if($room->total_price)
                                                @if($_sejPromoRate > 0)
                                                    <span class="line-through text-gray-400 text-xs">{{ number_format($room->total_price, 2, ',', ' ') }} MAD</span>
                                                    <span class="block text-emerald-600 font-semibold">{{ number_format($room->total_price * (1 - $_sejPromoRate / 100), 2, ',', ' ') }} MAD</span>
                                                @else
                                                    <span class="{{ $_puMatch ? 'font-bold text-blue-700' : '' }}">{{ number_format($room->total_price, 2, ',', ' ') }} MAD</span>
                                                @endif
                                            @endif
                                            @if($_puMatch)
                                            <div class="text-xs font-semibold {{ $_puDiffTotal <= 0 ? 'text-red-500' : 'text-emerald-600' }}">
                                                {{ $_puDiffTotal >= 0 ? '+' : '' }}{{ number_format($_puDiffTotal, 2, ',', ' ') }} MAD
                                            </div>
                                            @endif
                                        </div>
                                    </template>
                                </div>
                            </td>
                            </tr> @endforeach
                </tbody> </table> {{-- Personnes + remise du séjour --}}
            @php
                // Personnes totales du séjour : par chambre × quantité
                $adultes  = $sejour['rooms']->sum(fn($r) => ($r->adults   ?? 0) * max(1, $r->quantity ?? 1));
                $enfants  = $sejour['rooms']->sum(fn($r) => ($r->children ?? 0) * max(1, $r->quantity ?? 1));
                $bebes    = $sejour['rooms']->sum(fn($r) => ($r->babies   ?? 0) * max(1, $r->quantity ?? 1));
                $pParts   = [];
                if ($adultes) $pParts[] = $adultes . ' adulte' . ($adultes > 1 ? 's' : '');
                if ($enfants) $pParts[] = $enfants . ' enfant' . ($enfants > 1 ? 's' : '');
                if ($bebes)   $pParts[] = $bebes   . ' bébé'   . ($bebes   > 1 ? 's' : '');
                $babyBeds = $sejour['rooms']->where('baby_bed', true)->count();
                $_sejRoomsTotal = (float) $sejour['rooms']->sum(fn($r) => $r->total_price ?? 0);
            @endphp
            @if($multiSejour)
            <p class="text-xs text-gray-400 mt-1 pl-1"> {{ implode(', ', $pParts) ?: 'Personnes non précisées' }}
                @if($babyBeds > 0) ·  {{ $babyBeds }} lit(s) bébé@endif
            </p> @endif

            {{-- Remise long séjour + sous-total du séjour --}}
            @php
                $_sejRoomsTotal = (float) $sejour['rooms']->sum(fn($r) => $r->total_price ?? 0);
            @endphp
            @if($_sejPromoAmt > 0 || $multiSejour)
            <div class="mt-1 border-t border-gray-300 pt-1 space-y-0.5">
                @if($_sejPromoAmt > 0)
                <div class="flex items-center gap-1.5 px-2 py-1.5 bg-emerald-50 border border-emerald-100 rounded-lg">
                    <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-xs text-emerald-700 font-medium">Réduction long séjour de {{ $_sejPromoRate }}% déjà appliquée sur les prix par nuit.</span>
                </div>
                @endif
                @if($multiSejour)
                <div class="flex justify-between items-center text-sm font-bold text-gray-800 pt-0.5 border-t border-gray-300"> <span>Sous-total séjour {{ $i + 1 }}</span> <span>{{ number_format($_sejRoomsTotal - $_sejPromoAmt, 2, ',', ' ') }} MAD</span> </div>
                @endif
            </div>
            @endif
            @if($multiSejour)</div>@endif
            @endforeach

            @if($reservation->total_price)
            @php
                // Vérifier la cohérence : somme des room.total_price vs reservation.total_price - supplements - extras
                $_roomSum        = (float) $reservation->rooms->sum(fn($r) => $r->total_price ?? 0);
                $_suppSum        = (float) ($reservation->supplement_total ?? 0);
                $_extrasSumCheck = (float) $reservation->extras->sum('total_price');
                $_hebergDB       = round(($reservation->total_price ?? 0) - $_suppSum - $_extrasSumCheck, 2);
                $_ecart          = abs(round($_roomSum - $_hebergDB, 2));

                // Ignorer l'écart s'il s'explique par la remise long séjour :
                // elle est appliquée sur reservation.total_price uniquement, sans modifier room.total_price.
                $_promoAmount = (float) ($reservation->promo_discount_amount ?? 0);
                $_ecartNet    = abs(round($_ecart - $_promoAmount, 2));
            @endphp
            @if($_ecart > 1 && $_ecartNet > 1)
            <div class="mt-2 text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 flex items-start gap-1.5"> <span></span> <span>Les prix par chambre affichés ({{ number_format($_roomSum, 2, ',', ' ') }} MAD) diffèrent du total hébergement calculé ({{ number_format($_hebergDB, 2, ',', ' ') }} MAD). Le <strong>Total global</strong> ci-dessous est la valeur exacte. Acceptez la réservation pour resynchroniser les lignes.</span> </div> @endif
            {{-- Promo long séjour  affichée inline dans chaque séjour (voir @foreach ci-dessus) --}}
            {{-- Section globale supprimée pour éviter le doublon avec les lignes par séjour --}}

            {{-- Suppléments --}}
            @if($reservation->supplements->isNotEmpty())
            <div class="mt-4 pt-3 border-t border-gray-100"> <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2"> Suppléments / Événements</p> @foreach($reservation->supplements as $rs)
                @php
                    $sup = $rs->supplement;
                    $dateRange = $sup->date_from->eq($sup->date_to)
                        ? $sup->date_from->format('d/m/Y')
                        : $sup->date_from->format('d/m/Y') . '  ' . $sup->date_to->format('d/m/Y');
                @endphp
                <div class="flex items-start gap-3 py-2 border-b border-gray-50 last:border-0"> <div class="flex-1 min-w-0"> <div class="flex items-center gap-2 flex-wrap"> <span class="font-semibold text-gray-900 text-sm">{{ $sup->title }}</span> <span class="text-xs font-semibold px-1.5 py-0.5 rounded-full
                                {{ $rs->is_mandatory ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}"> {{ $rs->is_mandatory ? ' Obligatoire' : ' Optionnel' }}
                            </span> </div> <p class="text-xs text-gray-400 mt-0.5"> {{ $dateRange }}
                        </p> <p class="text-xs text-gray-400 mt-0.5"> @if($rs->adults_count) {{ $rs->adults_count }} adulte(s) × {{ number_format($rs->unit_price_adult, 0, ',', ' ') }} MAD @endif
                            @if($rs->children_count) · {{ $rs->children_count }} enf. × {{ number_format($rs->unit_price_child, 0, ',', ' ') }} MAD @endif
                            @if($rs->babies_count) · {{ $rs->babies_count }} bébé(s) × {{ number_format($rs->unit_price_baby, 0, ',', ' ') }} MAD @endif
                        </p> </div> <span class="font-bold text-sm text-amber-700 shrink-0"> + {{ number_format($rs->total_price, 2, ',', ' ') }} MAD
                    </span> </div> @endforeach
                @if($reservation->supplement_total > 0)
                <div class="flex justify-between items-center text-sm text-amber-700 font-semibold mt-2 pt-2 border-t border-amber-100 bg-amber-50 rounded-lg px-3 py-2"> <span>Total événements / suppléments</span> <span>+ {{ number_format($reservation->supplement_total, 2, ',', ' ') }} MAD</span> </div> @endif
            </div> @endif

            {{-- Services Extras (résumé financier) --}}
            @if($reservation->extras->isNotEmpty())
            <div class="mt-4 pt-3 border-t border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Services Extras</p>
                @foreach($reservation->extras as $extra)
                <div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0">
                    <span class="text-sm text-gray-700">{{ $extra->name }} <span class="text-xs text-gray-400">× {{ $extra->quantity }}</span></span>
                    <span class="font-semibold text-sm text-amber-700">+ {{ number_format($extra->total_price, 2, ',', ' ') }} MAD</span>
                </div>
                @endforeach
                <div class="flex justify-between items-center text-sm text-amber-700 font-semibold mt-2 pt-2 border-t border-amber-100 bg-amber-50 rounded-lg px-3 py-2">
                    <span>Total services extras</span>
                    <span>+ {{ number_format($reservation->extras->sum('total_price'), 2, ',', ' ') }} MAD</span>
                </div>
            </div>
            @endif

            {{-- Taxe de séjour  calculée par séjour (adultes × nuits par séjour × taux) --}}
            @php
                $taxeRate  = 0;
                try { $taxeRate = $reservation->hotel->taxe_sejour ?? 0; } catch(\Exception $e) {}
                $taxeTotal = 0;
                $taxeLines = [];
                foreach ($sejours as $s) {
                    $sAdults = (int) $s['rooms']->sum(fn($r) => ($r->adults ?? 0) * max(1, $r->quantity ?? 1));
                    $sNights = (int) ($s['nights'] ?? 0);
                    if ($sAdults > 0 && $sNights > 0 && $taxeRate > 0) {
                        $sLine   = round($sAdults * $sNights * $taxeRate, 2);
                        $taxeTotal += $sLine;
                        $taxeLines[] = [
                            'adults' => $sAdults,
                            'nights' => $sNights,
                            'sub'    => $sLine,
                            'label'  => $multiSejour
                                ? ($s['check_in']->format('d/m') . '' . $s['check_out']->format('d/m'))
                                : null,
                        ];
                    }
                }
                // $taxeTotal is already the sum of rounded per-séjour values (avoids round-of-sum vs sum-of-rounds discrepancy)
                $grandTotal = round(($reservation->total_price ?? 0) + $taxeTotal, 2);
            @endphp
            @if($taxeTotal > 0)
            <div class="mt-3 pt-3 border-t border-gray-100 space-y-0.5"> @foreach($taxeLines as $tl)
                <div class="flex justify-between items-center text-sm text-blue-700"> <span> Taxe de séjour
                        <span class="text-xs font-normal text-blue-500"> {{ $tl['adults'] }} adulte{{ $tl['adults'] > 1 ? 's' : '' }}
 × {{ $tl['nights'] }} nuit{{ $tl['nights'] > 1 ? 's' : '' }}
 × {{ number_format($taxeRate, 2, ',', ' ') }} DHS
                            @if($tl['label']) <em>({{ $tl['label'] }})</em> @endif
                        </span> </span> <span class="font-semibold">{{ number_format($tl['sub'], 2, ',', ' ') }} MAD</span> </div> @endforeach
                @if(count($taxeLines) > 1)
                <div class="flex justify-between items-center text-sm text-blue-700 font-semibold mt-2 pt-2 border-t border-blue-100 bg-blue-50 rounded-lg px-3 py-2"> <span>Sous-total taxe de séjour</span> <span>+ {{ number_format($taxeTotal, 2, ',', ' ') }} MAD</span> </div> @endif
            </div> @endif

            @php
                // Grand total = hébergement (reservation.total_price inclut les suppléments) + taxe
                // Pour les réservations en attente avec promo recalculée : ajuster le total affiché
                if (isset($displayPromoAmount) && $displayPromoAmount !== null) {
                    // Reconstruire : rooms_sum - promo recalculée + supplements + extras + taxe
                    $_roomsSum        = (float) $reservation->rooms->sum(fn($r) => $r->total_price ?? 0);
                    $_suppSum         = (float) ($reservation->supplement_total ?? 0);
                    $_extrasSum       = (float) $reservation->extras->sum('total_price');
                    $grandTotalDisplay = round($_roomsSum - $displayPromoAmount + $_suppSum + $_extrasSum + $taxeTotal, 2);
                } else {
                    $grandTotalDisplay = round(($reservation->total_price ?? 0) + $taxeTotal, 2);
                }
            @endphp
            <div class="flex justify-between items-center border-t-2 border-gray-200 mt-3 pt-4"> <span class="font-semibold text-gray-900">Total global <span class="text-xs font-normal text-gray-400">(hébergement + suppléments + extras + taxe de séjour)</span></span> <span class="font-bold text-lg text-amber-600">{{ number_format($grandTotalDisplay, 2, ',', ' ') }} MAD</span> </div>

            {{-- ── Bouton Valider les tarifs (visible seulement si des changements sont en attente) ── --}}
            <div x-data x-show="$store.priceDraft.count > 0" x-cloak x-transition
                 class="mt-4 rounded-xl border-2 border-orange-300 bg-orange-50 px-4 py-4">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div>
                        <p class="font-semibold text-orange-900 flex items-center gap-1.5 text-sm">
                            <svg class="w-4 h-4 text-orange-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                            <span x-text="$store.priceDraft.count + ' tarif' + ($store.priceDraft.count > 1 ? 's' : '') + ' modifié' + ($store.priceDraft.count > 1 ? 's' : '') + ' en attente de validation'"></span>
                        </p>
                        <div class="mt-2 flex items-center gap-3 flex-wrap">
                            <div class="text-xs text-orange-700">
                                Total global actuel :
                                <span class="font-semibold text-gray-700 line-through">{{ number_format($grandTotalDisplay, 2, ',', ' ') }} MAD</span>
                            </div>
                            <svg class="w-4 h-4 text-orange-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            <div class="text-xs text-orange-700">
                                Nouveau total :
                                <span class="font-bold text-base"
                                    :class="$store.priceDraft.delta >= 0 ? 'text-emerald-700' : 'text-red-600'"
                                    x-text="$store.priceDraft.fmt({{ $grandTotalDisplay }} + $store.priceDraft.delta) + ' MAD'">
                                </span>
                                <span class="font-semibold text-xs ml-1"
                                    :class="$store.priceDraft.delta >= 0 ? 'text-emerald-600' : 'text-red-500'"
                                    x-text="'(' + ($store.priceDraft.delta >= 0 ? '+' : '') + $store.priceDraft.fmt($store.priceDraft.delta) + ' MAD)'">
                                </span>
                            </div>
                        </div>
                    </div>
                    <button type="button" @click="$store.priceDraft.reset()"
                        class="text-xs text-orange-400 hover:text-orange-700 font-medium transition shrink-0 mt-0.5">
                        Tout annuler
                    </button>
                </div>
                <form action="{{ route('admin.reservations.rooms.batch-price', $reservation) }}" method="POST">
                    @csrf
                    <input type="hidden" name="changes" :value="$store.priceDraft.json">
                    <button type="submit"
                        class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-2.5 px-4 rounded-xl text-sm transition flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Valider les tarifs et enregistrer
                    </button>
                </form>
            </div>

            @endif
        </div> {{-- Modification en attente  comparaison AVANT / APRÈS  --}}
        @if($reservation->status === 'modification_pending' && $reservation->modification_data)
        @php
            $mod = $reservation->modification_data;

            //  Données AVANT (état actuel de la réservation) 
            $beforeStayGroups = $reservation->rooms->groupBy(fn($r) => ($r->check_in?->format('Y-m-d')  ?? $reservation->check_in->format('Y-m-d')) . '_' .
                ($r->check_out?->format('Y-m-d') ?? $reservation->check_out->format('Y-m-d'))
            );
            $beforeStays = $beforeStayGroups->map(fn($rooms) => [
                'check_in'  => $rooms->first()->check_in  ?? $reservation->check_in,
                'check_out' => $rooms->first()->check_out ?? $reservation->check_out,
                'nights'    => (int)(($rooms->first()->check_in ?? $reservation->check_in)
                                ->diffInDays($rooms->first()->check_out ?? $reservation->check_out)),
                'rooms'     => $rooms->map(fn($r) => [
                    'label'    => ($r->occupancyConfig ? $r->occupancyConfig->code . '  ' . $r->occupancyConfig->occupancy_description : $r->roomType?->name ?? '?'),
                    'qty'      => $r->quantity ?? 1,
                    'adults'   => $r->adults   ?? 0,
                    'children' => $r->children ?? 0,
                    'babies'   => $r->babies   ?? 0,
                ])->values(),
            ])->values();
            $beforeNights  = $reservation->nights;
            $beforePersons = $reservation->total_persons;

            //  Données APRÈS (proposition du client) 
            $afterStays = collect($mod['stays'] ?? []);
            $afterNights  = $afterStays->sum(fn($s) => \Carbon\Carbon::parse($s['check_in'])->diffInDays(\Carbon\Carbon::parse($s['check_out']))
            );
            $afterPersons = $mod['total_persons'] ?? $reservation->total_persons;

            // Résoudre les noms de types et configs
            $afterStaysResolved = $afterStays->map(fn($stay) => [
                'check_in'  => \Carbon\Carbon::parse($stay['check_in']),
                'check_out' => \Carbon\Carbon::parse($stay['check_out']),
                'nights'    => \Carbon\Carbon::parse($stay['check_in'])->diffInDays(\Carbon\Carbon::parse($stay['check_out'])),
                'rooms'     => collect($stay['rooms'] ?? [])->map(fn($r) => [
                    'label'    => (isset($r['occupancy_config_id']) && $r['occupancy_config_id']
                        ? (\App\Models\RoomOccupancyConfig::find($r['occupancy_config_id'])?->code . '  ' .
                           \App\Models\RoomOccupancyConfig::find($r['occupancy_config_id'])?->occupancy_description)
                        : \App\Models\RoomType::find($r['room_type_id'])?->name ?? 'type #' . ($r['room_type_id'] ?? '?')),
                    'qty'      => $r['quantity']  ?? 1,
                    'adults'   => $r['adults']    ?? 0,
                    'children' => $r['children']  ?? 0,
                    'babies'   => $r['babies']    ?? 0,
                ]),
            ]);

            // Changements détectés
            $datesChanged   = $beforeNights !== $afterNights ||
                              ($reservation->check_in->format('Y-m-d')  !== ($mod['check_in']  ?? $reservation->check_in->format('Y-m-d'))) ||
                              ($reservation->check_out->format('Y-m-d') !== ($mod['check_out'] ?? $reservation->check_out->format('Y-m-d')));
            $personsChanged = $beforePersons != $afterPersons;
        @endphp

        <div class="bg-purple-50 border-l-4 border-purple-500 rounded-xl overflow-hidden"> {{-- Titre --}}
            <div class="px-6 py-4 flex items-center justify-between"> <div class="flex items-center gap-3"> <div class="w-9 h-9 rounded-xl bg-purple-100 flex items-center justify-center"> <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.772-8.772z"/></svg> </div> <div> <h2 class="font-bold text-purple-900"> Modification demandée par le client</h2> <p class="text-xs text-purple-500 mt-0.5">Comparez les données avant d'approuver ou refuser</p> </div> </div> </div> {{-- Tableau AVANT / APRÈS  --}}
            <div class="grid grid-cols-2 divide-x divide-purple-200 border-t border-purple-200"> {{-- AVANT --}}
                <div class="p-5 bg-red-50/50"> <p class="text-[11px] font-extrabold text-red-500 uppercase tracking-widest mb-3">AVANT (actuel)</p> <div class="space-y-2 text-sm"> <div class="{{ $datesChanged ? 'text-red-700 font-semibold' : 'text-gray-700' }}"> {{ $reservation->check_in->format('d/m/Y') }}  {{ $reservation->check_out->format('d/m/Y') }}
                            <span class="text-xs font-normal text-gray-400 ml-1">({{ $beforeNights }} nuit{{ $beforeNights > 1 ? 's' : '' }})</span> </div> <div class="{{ $personsChanged ? 'text-red-700 font-semibold' : 'text-gray-700' }}"> {{ $beforePersons }} personne{{ $beforePersons > 1 ? 's' : '' }}
                        </div> @foreach($beforeStays as $bIdx => $bStay)
                        <div class="mt-2 pl-2 border-l-2 border-red-200"> @if($beforeStays->count() > 1)
                            <p class="text-[10px] font-bold text-red-400 uppercase mb-1">Séjour {{ $bIdx + 1 }}</p> <p class="text-xs text-gray-500 mb-1"> {{ $bStay['check_in']->format('d/m/Y') }}  {{ $bStay['check_out']->format('d/m/Y') }}
                                · {{ $bStay['nights'] }} nuit{{ $bStay['nights'] > 1 ? 's' : '' }}
                            </p> @endif
                            @foreach($bStay['rooms'] as $br)
                            <p class="text-sm text-red-800"> {{ $br['qty'] }}× {{ $br['label'] }}
                                @if($br['adults']) <span class="text-xs text-gray-400"> {{ $br['adults'] }} adulte{{ $br['adults'] > 1 ? 's' : '' }}@if($br['children']) + {{ $br['children'] }} enf.@endif</span> @endif
                            </p> @endforeach
                        </div> @endforeach

                    </div> </div> {{-- APRÈS --}}
                <div class="p-5 bg-emerald-50/50"> <p class="text-[11px] font-extrabold text-emerald-600 uppercase tracking-widest mb-3">APRÈS (proposé)</p> <div class="space-y-2 text-sm"> @php
                            $afterCheckIn  = \Carbon\Carbon::parse($mod['check_in']  ?? $afterStaysResolved->first()?->get('check_in')->format('Y-m-d'));
                            $afterCheckOut = \Carbon\Carbon::parse($mod['check_out'] ?? $afterStaysResolved->last()?->get('check_out')->format('Y-m-d'));
                        @endphp
                        <div class="{{ $datesChanged ? 'text-emerald-700 font-semibold' : 'text-gray-700' }}"> {{ $afterCheckIn->format('d/m/Y') }}  {{ $afterCheckOut->format('d/m/Y') }}
                            <span class="text-xs font-normal text-gray-400 ml-1">({{ $afterNights }} nuit{{ $afterNights > 1 ? 's' : '' }})</span> </div> <div class="{{ $personsChanged ? 'text-emerald-700 font-semibold' : 'text-gray-700' }}"> {{ $afterPersons }} personne{{ $afterPersons > 1 ? 's' : '' }}
                        </div> @foreach($afterStaysResolved as $aIdx => $aStay)
                        <div class="mt-2 pl-2 border-l-2 border-emerald-200"> @if($afterStaysResolved->count() > 1)
                            <p class="text-[10px] font-bold text-emerald-500 uppercase mb-1">Séjour {{ $aIdx + 1 }}</p> <p class="text-xs text-gray-500 mb-1"> {{ $aStay['check_in']->format('d/m/Y') }}  {{ $aStay['check_out']->format('d/m/Y') }}
                                · {{ $aStay['nights'] }} nuit{{ $aStay['nights'] > 1 ? 's' : '' }}
                            </p> @endif
                            @foreach($aStay['rooms'] as $ar)
                            <p class="text-sm text-emerald-800"> {{ $ar['qty'] }}× {{ $ar['label'] }}
                                @if($ar['adults']) <span class="text-xs text-gray-400"> {{ $ar['adults'] }} adulte{{ $ar['adults'] > 1 ? 's' : '' }}@if($ar['children']) + {{ $ar['children'] }} enf.@endif</span> @endif
                            </p> @endforeach
                        </div> @endforeach

                        @if(!empty($mod['special_requests']))
                        <div class="text-xs text-gray-500 mt-2 italic"> {{ $mod['special_requests'] }}
                        </div> @endif
                    </div> </div> </div> {{-- Boutons décision  --}}
            <div class="px-6 py-4 bg-white border-t border-purple-100 flex flex-wrap gap-3 items-center"> <form action="{{ route('admin.reservations.accept-modification', $reservation) }}" method="POST"> @csrf @method('PATCH')
                    <button class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-semibold text-sm px-5 py-2.5 rounded-xl transition-colors shadow-sm"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Accepter la modification
                    </button> </form>

                {{-- Refuser modification : multi-select motifs --}}
                @php $otherReasonId = $refusalReasons->first(fn($r) => $r->isOther())?->id; @endphp
                <div x-data="{
                        open: false,
                        selectedIds: [],
                        customReason: '',
                        get hasOther() {
                            const oid = {{ $otherReasonId ?? 'null' }};
                            return oid !== null && this.selectedIds.includes(String(oid));
                        },
                        submit(form) {
                            if (this.selectedIds.length === 0) {
                                alert('Veuillez sélectionner au moins un motif de refus.');
                                return;
                            }
                            form.submit();
                        }
                    }" class="flex-1">
                    <button type="button" @click="open = true"
                            class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-sm px-5 py-2.5 rounded-xl transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Refuser la modification
                    </button>

                    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
                         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="open = false"></div>
                        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg"
                             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-red-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-900">Refuser la modification</h3>
                                        <p class="text-xs text-gray-400 mt-0.5">Sélectionnez un ou plusieurs motifs</p>
                                    </div>
                                </div>
                                <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <form action="{{ route('admin.reservations.refuse-modification', $reservation) }}" method="POST"
                                  @submit.prevent="submit($el)" x-ref="modifForm" id="refuse-modif-form">
                                @csrf @method('PATCH')
                                <div class="px-6 py-5">
                                    <p class="text-sm font-semibold text-gray-700 mb-3">Motif(s) de refus <span class="text-red-500">*</span></p>
                                    <div class="space-y-2.5 max-h-56 overflow-y-auto pr-1">
                                        @foreach($refusalReasons as $reason)
                                        <label class="flex items-start gap-3 cursor-pointer group">
                                            <input type="checkbox" name="reason_ids[]" value="{{ $reason->id }}"
                                                   x-model="selectedIds"
                                                   class="mt-0.5 w-4 h-4 rounded accent-red-600 shrink-0">
                                            <span class="text-sm text-gray-700 group-hover:text-gray-900 leading-tight">{{ $reason->label }}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                    <div x-show="hasOther" x-cloak class="mt-4">
                                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Précisez le motif <span class="text-red-500">*</span></label>
                                        <textarea name="custom_reason" x-model="customReason" rows="3"
                                                  placeholder="Décrivez le motif de refus..."
                                                  class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent resize-none"></textarea>
                                    </div>
                                </div>
                                <div class="flex items-center justify-end gap-3 px-6 pb-5 border-t border-gray-100 pt-4">
                                    <button type="button" @click="open = false"
                                            class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 rounded-lg hover:bg-gray-100 transition-colors">
                                        Annuler
                                    </button>
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-sm px-5 py-2.5 rounded-xl transition-colors shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Confirmer le refus
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                </div> </div> @endif

        {{-- ── Services Extras ─────────────────────────────────────── --}}
        @if(session('success_extras'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success_extras') }}
        </div>
        @endif

        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden" x-data="extraForm">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-gray-900">Services Extras</h2>
                    <p class="text-xs text-gray-400">Services additionnels facturables hors hébergement</p>
                </div>
            </div>

            {{-- Liste des extras existants --}}
            @if($reservation->extras->isNotEmpty())
            <div class="px-6 py-3 border-b border-gray-100 divide-y divide-gray-50">
                @foreach($reservation->extras as $extra)
                <div class="flex items-start gap-3 py-2.5 first:pt-0 last:pb-0" x-data="{ confirmDelete: false }">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900">{{ $extra->name }}</p>
                        @if($extra->description)
                        <p class="text-xs text-gray-400 mt-0.5">{{ $extra->description }}</p>
                        @endif
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $extra->quantity }} × {{ number_format($extra->unit_price, 2, ',', ' ') }} MAD
                            @if($extra->notes) · <em class="text-gray-400">{{ $extra->notes }}</em> @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-sm font-bold text-amber-600">{{ number_format($extra->total_price, 2, ',', ' ') }} MAD</span>
                        {{-- Bouton déclencheur --}}
                        <button type="button" @click="confirmDelete = true"
                                class="text-xs text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 px-2 py-1 rounded-md transition-colors font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>

                    {{-- Modale de confirmation --}}
                    <div x-show="confirmDelete" x-cloak
                         class="fixed inset-0 z-50 flex items-center justify-center p-4"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0">

                        {{-- Overlay --}}
                        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="confirmDelete = false"></div>

                        {{-- Panneau --}}
                        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-auto p-6"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100">

                            {{-- Icône --}}
                            <div class="flex justify-center mb-4">
                                <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center">
                                    <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </div>
                            </div>

                            {{-- Titre & description --}}
                            <h3 class="text-base font-bold text-gray-900 text-center mb-1">Supprimer ce service extra ?</h3>
                            <p class="text-sm text-gray-500 text-center mb-1">
                                <span class="font-semibold text-gray-700">{{ $extra->name }}</span>
                            </p>
                            <p class="text-xs text-gray-400 text-center mb-6">
                                {{ $extra->quantity }} × {{ number_format($extra->unit_price, 2, ',', ' ') }} MAD
                                = <strong class="text-red-600">{{ number_format($extra->total_price, 2, ',', ' ') }} MAD</strong> seront déduits du total.
                            </p>

                            {{-- Actions --}}
                            <div class="flex gap-3">
                                <button type="button" @click="confirmDelete = false"
                                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-sm py-2.5 rounded-xl transition-colors">
                                    Annuler
                                </button>
                                <form action="{{ route('admin.reservations.extras.destroy', [$reservation, $extra]) }}" method="POST" class="flex-1">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold text-sm py-2.5 rounded-xl transition-colors flex items-center justify-center gap-1.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
                <div class="flex justify-between items-center pt-2 text-sm font-semibold text-amber-700">
                    <span>Total extras</span>
                    <span>{{ number_format($reservation->extras->sum('total_price'), 2, ',', ' ') }} MAD</span>
                </div>
            </div>
            @endif

            {{-- Formulaire d'ajout --}}
            <form action="{{ route('admin.reservations.extras.store', $reservation) }}" method="POST" class="px-6 py-5 space-y-4">
                @csrf
                {{-- Sélecteur catalogue --}}
                @if($extraServices->isNotEmpty())
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Choisir depuis le catalogue <span class="text-gray-400">(optionnel)</span></label>
                    <select x-model="catalogId" @change="fillFromCatalog($event)"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        <option value="">— Saisie manuelle —</option>
                        @foreach($extraServices as $es)
                        <option value="{{ $es->id }}" data-name="{{ $es->name }}" data-desc="{{ $es->description }}" data-price="{{ $es->price }}">
                            {{ $es->name }} — {{ number_format($es->price, 2, ',', ' ') }} MAD
                        </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="extra_service_id" :value="catalogId ?: ''">
                </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Nom du service <span class="text-red-500">*</span></label>
                        <input type="text" name="name" x-model="name" required
                               placeholder="Ex : Transport aéroport"
                               class="w-full border @error('name') border-red-400 bg-red-50 @else border-gray-200 @enderror rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                        @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Description <span class="text-gray-400">(optionnel)</span></label>
                        <input type="text" name="description" x-model="description"
                               placeholder="Détails du service…"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Prix unitaire (MAD) <span class="text-red-500">*</span></label>
                        <input type="number" name="unit_price" x-model="unitPrice" min="0" step="0.01" required
                               class="w-full border @error('unit_price') border-red-400 bg-red-50 @else border-gray-200 @enderror rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                        @error('unit_price')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Quantité <span class="text-red-500">*</span></label>
                        <input type="number" name="quantity" x-model="quantity" min="1" step="1" required
                               class="w-full border @error('quantity') border-red-400 bg-red-50 @else border-gray-200 @enderror rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                        @error('quantity')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Notes internes <span class="text-gray-400">(optionnel)</span></label>
                    <textarea name="notes" rows="2" placeholder="Remarques pour ce service…"
                              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 resize-none"></textarea>
                </div>

                {{-- Aperçu total + bouton --}}
                <div class="flex items-center justify-between gap-4">
                    <p class="text-sm text-gray-500" x-show="unitPrice && quantity">
                        Total : <span class="font-bold text-amber-600" x-text="formatMAD(parseFloat(unitPrice || 0) * parseInt(quantity || 1))"></span>
                    </p>
                    <button type="submit"
                            class="ml-auto inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Ajouter le service
                    </button>
                </div>
            </form>
        </div>

        {{-- Fiches de police --}}
        @php
            $guests = $reservation->guestRegistrations ?? collect();
            $guestsTotal = $reservation->rooms->sum(fn($r) => (($r->adults ?? 0) + ($r->children ?? 0)) * max(1, $r->quantity ?? 1));
        @endphp
        <div class="bg-white border border-gray-200 rounded-xl p-6"> <div class="flex items-center justify-between mb-4"> <h2 class="text-base font-semibold"> Fiches de police</h2> <div class="flex items-center gap-3"> <span class="text-xs {{ $guests->filter(fn($g) => $g->isComplete())->count() >= $guestsTotal && $guestsTotal > 0 ? 'text-emerald-600 bg-emerald-50' : 'text-amber-600 bg-amber-50' }} px-2 py-1 rounded-full"> {{ $guests->filter(fn($g) => $g->isComplete())->count() }} / {{ $guestsTotal }} complète(s)
                    </span> @if(!$guests->isEmpty())
                    <a href="{{ route('admin.reservations.guests.export', $reservation) }}"
                       class="inline-flex items-center gap-1.5 text-xs font-medium bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg transition-colors"> <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /> </svg> Exporter Excel
                    </a> @endif
                </div> </div> @if($guests->isEmpty())
                <p class="text-sm text-gray-400 italic">Aucune fiche remplie pour le moment.</p> @else
            <div class="overflow-x-auto"> <table class="w-full text-xs"> <thead> <tr class="bg-gray-50 text-gray-500 uppercase tracking-wide"> <th class="text-left px-3 py-2">#</th> <th class="text-left px-3 py-2">Type</th> <th class="text-left px-3 py-2">Nom complet</th> <th class="text-left px-3 py-2">Naissance</th> <th class="text-left px-3 py-2">Âge</th> <th class="text-left px-3 py-2">Nationalité</th> <th class="text-left px-3 py-2">Document</th> <th class="text-left px-3 py-2">N° Document</th> <th class="text-left px-3 py-2">Pays résidence</th> <th class="text-left px-3 py-2">Profession</th> <th class="text-left px-3 py-2">N° entrée Maroc</th> <th class="text-left px-3 py-2">Statut</th> </tr> </thead> <tbody class="divide-y divide-gray-100"> @foreach($guests as $g)
                        <tr class="hover:bg-gray-50"> <td class="px-3 py-2 text-gray-400">{{ $g->guest_index }}</td> <td class="px-3 py-2"> {{ $g->guest_type === 'adult' ? ' Adulte' : ($g->guest_type === 'child' ? ' Enfant' : ' Bébé') }}
                            </td> <td class="px-3 py-2 font-medium text-gray-900">{{ $g->full_name }}</td> <td class="px-3 py-2 text-gray-600"> {{ $g->date_naissance?->format('d/m/Y') ?? '' }}
                                @if($g->lieu_naissance) <span class="text-gray-400">· {{ $g->lieu_naissance }}</span> @endif
                            </td>
                            <td class="px-3 py-2 text-center">
                                @if($g->date_naissance)
                                <span class="inline-block bg-gray-100 text-gray-700 font-semibold px-2 py-0.5 rounded-full text-xs">{{ $g->date_naissance->age }} ans</span>
                                @else
                                <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-600">{{ $g->nationalite ?? '' }}</td> <td class="px-3 py-2 text-gray-600"> {{ match($g->type_document) {
                                    'passeport'    => 'Passeport',
                                    'cni'          => 'CNI',
                                    'titre_sejour' => 'Titre séjour',
                                    default        => $g->type_document ?? ''
                                } }}
                            </td> <td class="px-3 py-2 font-mono text-gray-800">{{ $g->numero_document ?? '' }}</td> <td class="px-3 py-2 text-gray-600">{{ $g->pays_residence ?? '' }}</td> <td class="px-3 py-2 text-gray-600">{{ $g->profession ?? '' }}</td>
                            <td class="px-3 py-2 font-mono text-gray-800">{{ $g->numero_entree_maroc ?? '' }}</td>
                            <td class="px-3 py-2"> @if($g->isComplete())
                                    <span class="text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full"> Complet</span> @else
                                    <span class="text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full"> Incomplet</span> @endif
                            </td> </tr> @endforeach
                    </tbody> </table> </div> @endif
        </div> {{--  LOG Détaillé  --}}
        @php $logs = $reservation->logs()->orderBy('created_at','desc')->get(); @endphp
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden" x-data="{ openLog: null }"> <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between"> <div class="flex items-center gap-3"> <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center"> <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg> </div> <h2 class="font-bold text-gray-900">Journal des modifications</h2> </div> <span class="text-xs bg-gray-100 text-gray-500 px-2.5 py-1 rounded-full font-medium">{{ $logs->count() }} entrée(s)</span> </div> @if($logs->isEmpty())
            <div class="px-6 py-8 text-center text-sm text-gray-400"> <p>Aucun événement enregistré pour le moment.</p> <p class="text-xs mt-1">Les futurs changements (modifications, paiements...) apparaîtront ici.</p> </div> @else
            <div class="divide-y divide-gray-50"> @foreach($logs as $log)
            @php
                $style = $log->event_style;
                $colorMap = [
                    'blue'   => ['dot'=>'bg-blue-500',   'badge'=>'bg-blue-100 text-blue-700',   'border'=>'border-blue-100'],
                    'green'  => ['dot'=>'bg-emerald-500','badge'=>'bg-emerald-100 text-emerald-700','border'=>'border-emerald-100'],
                    'red'    => ['dot'=>'bg-red-500',    'badge'=>'bg-red-100 text-red-700',     'border'=>'border-red-100'],
                    'amber'  => ['dot'=>'bg-amber-500',  'badge'=>'bg-amber-100 text-amber-700', 'border'=>'border-amber-100'],
                    'purple' => ['dot'=>'bg-purple-500', 'badge'=>'bg-purple-100 text-purple-700','border'=>'border-purple-100'],
                    'gray'   => ['dot'=>'bg-gray-400',   'badge'=>'bg-gray-100 text-gray-600',   'border'=>'border-gray-100'],
                ];
                $c = $colorMap[$style['color']] ?? $colorMap['gray'];
                $hasDetails = !empty($log->old_data) || !empty($log->new_data) || $log->reason;
            @endphp
            <div class="px-5 py-4"> <div class="flex items-start gap-3"> {{-- Dot --}}
                    <div class="w-2.5 h-2.5 rounded-full {{ $c['dot'] }} mt-1.5 shrink-0"></div> <div class="flex-1 min-w-0"> {{-- En-tête log --}}
                        <div class="flex items-start justify-between gap-2"> <div class="flex items-center gap-2 flex-wrap"> <span class="text-base leading-none">{{ $style['icon'] }}</span> <span class="text-sm font-semibold text-gray-900">{{ $log->summary }}</span> <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $c['badge'] }}">{{ $style['label'] }}</span> </div> <div class="text-right shrink-0"> <p class="text-xs text-gray-400">{{ $log->created_at->format('d/m/Y') }}</p> <p class="text-xs text-gray-400">{{ $log->created_at->format('H:i') }}</p> </div> </div> {{-- Acteur + raison --}}
                        <p class="text-xs text-gray-500 mt-1"> par <strong>{{ $log->actor_label }}</strong> @if($log->reason)
                            · <em class="text-gray-600">{{ Str::limit($log->reason, 80) }}</em> @endif
                        </p> {{-- Bouton détails --}}
                        @if($hasDetails)
                        <button type="button"
                            @click="openLog = (openLog === {{ $log->id }}) ? null : {{ $log->id }}"
                            class="mt-2 text-xs font-medium text-amber-600 hover:text-amber-800 flex items-center gap-1"> <svg class="w-3 h-3 transition-transform" :class="openLog === {{ $log->id }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg> <span x-text="openLog === {{ $log->id }} ? 'Masquer les détails' : 'Voir les détails'">Voir les détails</span> </button> {{-- Détails expandables --}}
                        <div x-show="openLog === {{ $log->id }}" x-transition class="mt-3 rounded-xl border {{ $c['border'] }} bg-gray-50 overflow-hidden"> @if(!empty($log->old_data) && !empty($log->new_data))
                            <div class="grid grid-cols-2 divide-x divide-gray-200"> {{-- Avant --}}
                                <div class="p-3"> <p class="text-[10px] font-bold text-red-500 uppercase tracking-wide mb-2">AVANT</p> @include('admin.reservations.partials.log-snapshot', ['data' => $log->old_data, 'side' => 'old'])
                                </div> {{-- Après --}}
                                <div class="p-3"> <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-wide mb-2">APRÈS</p> @include('admin.reservations.partials.log-snapshot', ['data' => $log->new_data, 'side' => 'new'])
                                </div> </div> @elseif(!empty($log->new_data))
                            <div class="p-3"> @include('admin.reservations.partials.log-snapshot', ['data' => $log->new_data, 'side' => 'new'])
                            </div> @endif
                            @if($log->reason)
                            <div class="px-3 py-2 border-t border-gray-100 bg-white"> <p class="text-xs text-gray-500"><span class="font-semibold">Raison :</span> {{ $log->reason }}</p> </div> @endif
                        </div> @endif
                    </div> </div> </div> @endforeach
            </div> @endif
        </div> {{-- Historique des statuts (compact) --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5"> <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-3">Historique des statuts</h2> <div class="space-y-2.5"> @foreach($reservation->statusHistories as $history)
                <div class="flex gap-3 text-sm"> <div class="w-2 h-2 rounded-full bg-gray-400 mt-1.5 shrink-0"></div> <div> <p class="font-medium text-gray-800 text-xs"> {{ $history->from_status_label }}  {{ $history->to_status_label }}
                            <span class="font-normal text-gray-400">par {{ $history->actor_label }}</span> </p> @if($history->comment)<p class="text-gray-500 text-xs mt-0.5">{{ $history->comment }}</p>@endif
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ $history->created_at->format('d/m/Y à H:i') }}</p> </div> </div> @endforeach
            </div> </div> </div> {{-- Colonne actions --}}
    <div class="space-y-4"> @php
            $amountPaid      = $reservation->payments->where('status','completed')->sum('amount');
            $pendingPay      = $reservation->payments->where('status','pending')->sum('amount');
            $totalPrice      = round(($reservation->total_price ?? 0) + ($taxeTotal ?? 0), 2);
            $remaining       = max(0, $totalPrice - $amountPaid);
            $pct             = $totalPrice > 0 ? min(100, round($amountPaid / $totalPrice * 100)) : 0;
            $canRegister     = in_array($reservation->status, ['waiting_payment','accepted','partially_paid']) && $remaining > 0;
            $schedules       = $reservation->paymentSchedules()->orderBy('installment_number')->get();
            $schedPlanned    = round($schedules->sum('amount'), 2);
            $scheduleIs100   = $totalPrice > 0 && ($totalPrice - $schedPlanned) <= 0.5;
            $schedPlanPct    = $totalPrice > 0 ? min(100, round($schedPlanned / $totalPrice * 100)) : 0;
        @endphp

        {{--  Alerte : modification approuvée, échéancier réinitialisé  --}}
        @php
            $lastHistory = $reservation->statusHistories->sortByDesc('created_at')->first();
            $wasModificationApproval = $lastHistory
                && $lastHistory->from_status === 'modification_pending'
                && $lastHistory->to_status   === 'waiting_payment';
        @endphp
        @if($wasModificationApproval)
        <div class="bg-orange-50 border-l-4 border-orange-500 rounded-xl px-4 py-4 space-y-3"> <div class="flex items-start gap-3"> <svg class="w-5 h-5 text-orange-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/> </svg> <div class="flex-1"> <p class="text-sm font-bold text-orange-800">Modification approuvée le {{ $lastHistory->created_at->format('d/m/Y à H:i') }}</p> <p class="text-xs text-orange-700 mt-1"> Le prix a été recalculé.<br> Nouveau total :
                        <strong>{{ number_format($totalPrice, 2, ',', ' ') }} MAD</strong>.
                        @if($schedules->isEmpty())
 L'ancien échéancier a été supprimé.
                            <strong>Créez un nouvel échéancier</strong> ci-dessous, puis renvoyez le devis au client.
                        @else
 L'échéancier a été mis à jour.
                            <strong>Renvoyez le devis</strong> pour informer le client du nouveau montant.
                        @endif
                    </p> </div> </div> {{-- Étapes à suivre --}}
            <div class="flex flex-wrap gap-2 pl-8"> <div class="flex items-center gap-1.5 text-xs {{ $schedules->isEmpty() ? 'text-orange-700 font-semibold' : 'text-emerald-700 line-through opacity-60' }}"> @if($schedules->isEmpty())
                        <span class="w-5 h-5 rounded-full bg-orange-200 text-orange-700 font-bold flex items-center justify-center shrink-0">1</span> @else
                        <span class="w-5 h-5 rounded-full bg-emerald-200 text-emerald-700 font-bold flex items-center justify-center shrink-0"></span> @endif
 Créer le nouvel échéancier
                </div> <span class="text-orange-300"></span> <div class="flex items-center gap-1.5 text-xs {{ !$schedules->isEmpty() ? 'text-orange-700 font-semibold' : 'text-orange-400' }}"> <span class="w-5 h-5 rounded-full {{ !$schedules->isEmpty() ? 'bg-orange-200 text-orange-700' : 'bg-orange-100 text-orange-400' }} font-bold flex items-center justify-center shrink-0">2</span> Renvoyer le devis au client
                </div> </div> @if(! $schedules->isEmpty())
            <div class="pl-8"> <form action="{{ route('admin.reservations.resend-quote', $reservation) }}" method="POST"> @csrf
                    <button {{ $scheduleIs100 ? '' : 'disabled' }}
                        class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg transition-colors
                            {{ $scheduleIs100
                                ? 'bg-orange-500 hover:bg-orange-600 text-white cursor-pointer'
                                : 'bg-gray-100 text-gray-400 border border-gray-200 cursor-not-allowed' }}"
                        title="{{ $scheduleIs100 ? '' : 'Complétez l\'échéancier à 100% avant d\'envoyer le devis (actuellement ' . $schedPlanPct . '%)' }}"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/> </svg> Renvoyer le devis à {{ $reservation->email }}
                        @if(! $scheduleIs100)
                        <span class="text-xs font-normal">(échéancier {{ $schedPlanPct }}%)</span> @endif
                    </button> </form> </div> @endif
        </div> @endif

        {{--  Échéancier de paiement  --}}
        @if(in_array($reservation->status, ['pending','waiting_payment','accepted','partially_paid','confirmed','modification_pending']))
        <div class="bg-white border {{ $wasModificationApproval ? 'border-orange-300' : 'border-gray-200' }} rounded-xl p-5"
             x-data="{ showForm: {{ ($schedules->isEmpty() || $wasModificationApproval) ? 'true' : 'false' }} }"> <div class="flex items-center justify-between mb-1"> <h3 class="font-semibold text-gray-900"> Échéancier de paiement
                    @if($wasModificationApproval)
                    <span class="ml-2 text-xs font-semibold text-orange-600 bg-orange-100 px-2 py-0.5 rounded-full"> À recréer</span> @endif
                </h3> <button type="button" @click="showForm = !showForm"
                    class="text-xs text-amber-600 hover:text-amber-800 font-medium"> <span x-show="!showForm">+ Ajouter</span> <span x-show="showForm">Masquer</span> </button> </div> @if($reservation->status === 'pending')
            <p class="text-xs text-blue-600 bg-blue-50 border border-blue-100 rounded px-2 py-1.5 mb-3"> Les dates définies ici seront incluses dans le devis envoyé au client.
            </p> @elseif($reservation->status === 'modification_pending')
            <p class="text-xs text-purple-700 bg-purple-50 border border-purple-100 rounded px-2 py-1.5 mb-3"> Modification en cours de révision  l'échéancier ci-dessous est celui de l'ancienne réservation. Il sera supprimé automatiquement si vous approuvez la modification.
            </p> @endif

            {{-- Échéances existantes --}}
            @if($schedules->isNotEmpty())
            @php $schedTotalPre = $schedules->sum('amount'); @endphp
            <div class="space-y-2 mb-3"> @foreach($schedules as $sch)

                @php $schedOthers = $schedules->where('id', '!=', $sch->id)->sum('amount'); @endphp

                {{-- ── Macro : formulaire d'édition complet (partagé pending + post-accept) ──────── --}}
                @php
                    $editFormId   = 'sched-edit-' . $sch->id;
                    $alpineInit   = 'scheduleEditForm' . $sch->id . '()';
                @endphp

                @if($reservation->status === 'pending')
                {{-- Pending : carte amber avec Modifier / Supprimer --}}
                <div class="rounded-lg bg-amber-50 border border-amber-100 overflow-hidden">
                    <div class="flex items-start gap-2 p-2.5">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-xs font-bold text-amber-700">#{{ $sch->installment_number }}</span>
                                @if($sch->label)<span class="text-xs font-semibold text-gray-800">{{ $sch->label }}</span>@endif
                            </div>
                            <div class="flex items-center gap-3 mt-0.5 text-xs text-gray-500 flex-wrap">
                                <span>{{ $sch->due_date->format('d/m/Y') }}{{ $sch->due_time ? ' · ' . \Illuminate\Support\Carbon::parse($sch->due_time)->format('H:i') : '' }}</span>
                                <span class="font-semibold text-amber-700">{{ number_format($sch->amount, 2, ',', ' ') }} MAD
                                    @if($totalPrice > 0)<span class="text-gray-400 font-normal">({{ round($sch->amount / $totalPrice * 100) }}%)</span>@endif
                                </span>
                            </div>
                        </div>
                        <div class="flex gap-1 shrink-0">
                            <button type="button" onclick="toggleScheduleEdit({{ $sch->id }})"
                                class="text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 px-2 py-1 rounded font-medium inline-flex items-center gap-1">
                                    <svg class="w-3 h-3 -rotate-45" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536M9 11l6.768-6.768a2 2 0 112.828 2.828L11.828 13.828 9 14l.232-3z"/></svg>
                                    Modif
                                </button>
                            <form action="{{ route('admin.reservations.schedules.destroy', [$reservation, $sch]) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="button" onclick="openDeleteModal(this.closest('form'))"
                                        class="text-xs bg-gray-100 hover:bg-red-100 text-gray-500 hover:text-red-600 px-2 py-1 rounded">✕ Supprimer</button>
                            </form>
                        </div>
                    </div>
                    <div id="{{ $editFormId }}" class="hidden px-3 pb-3 pt-2 border-t border-amber-200 bg-white" x-data="{{ $alpineInit }}">
                        @include('admin.partials.schedule-edit-form', ['reservation' => $reservation, 'sch' => $sch, 'totalPrice' => $totalPrice, 'firstCheckIn' => $firstCheckIn, 'schedOthers' => $schedOthers])
                    </div>
                </div>
                @else
                {{-- Post-acceptation : affichage riche avec boutons paiement --}}
                @php $cs = $sch->computed_status; @endphp
                <div class="rounded-lg border {{ $cs === 'paid' ? 'bg-green-50 border-green-100' : ($cs === 'overdue' ? 'bg-red-50 border-red-100' : 'bg-gray-50 border-gray-100') }} overflow-hidden">
                    <div class="flex items-start gap-2 p-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-bold text-gray-500">#{{ $sch->installment_number }}</span>
                                @if($sch->label)<span class="text-sm font-medium text-gray-800">{{ $sch->label }}</span>@endif
                                <span class="text-xs px-1.5 py-0.5 rounded-full font-medium {{ $cs === 'paid' ? 'bg-green-100 text-green-700' : ($cs === 'overdue' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">{{ $sch->status_label }}</span>
                            </div>
                            <div class="flex items-center gap-3 mt-1 text-xs text-gray-500">
                                <span>{{ $sch->due_date->format('d/m/Y') }}{{ $sch->due_time ? ' · ' . \Illuminate\Support\Carbon::parse($sch->due_time)->format('H:i') : '' }}</span>
                                <span class="font-semibold text-gray-800">{{ number_format($sch->amount, 2, ',', ' ') }} MAD</span>
                            </div>
                            @if($sch->isPaid() && $sch->payment?->proof_path)
                            <div class="mt-1"><a href="{{ \Illuminate\Support\Facades\Storage::url($sch->payment->proof_path) }}" target="_blank" class="text-xs text-gray-400 hover:text-gray-600">Voir preuve</a></div>
                            @endif
                        </div>
                        <div class="flex gap-1 shrink-0">
                            @if(! $sch->isPaid())
                            <button type="button" onclick="togglePayForm({{ $sch->id }})"
                                class="text-xs bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded font-medium">Payer</button>
                            <button type="button" onclick="toggleScheduleEdit({{ $sch->id }})"
                                class="text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 px-2 py-1 rounded font-medium inline-flex items-center gap-1">
                                    <svg class="w-3 h-3 -rotate-45" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536M9 11l6.768-6.768a2 2 0 112.828 2.828L11.828 13.828 9 14l.232-3z"/></svg>
                                    Modif
                                </button>
                            <form action="{{ route('admin.reservations.schedules.destroy', [$reservation, $sch]) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="button" onclick="openDeleteModal(this.closest('form'))"
                                        class="text-xs bg-gray-200 hover:bg-red-100 text-gray-500 hover:text-red-600 px-2 py-1 rounded">✕ Supprimer</button>
                            </form>
                            @else
                            @if($sch->payment?->proof_path)
                            <a href="{{ \Illuminate\Support\Facades\Storage::url($sch->payment->proof_path) }}" target="_blank"
                               class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 px-2 py-1 rounded font-medium">PJ</a>
                            @endif
                            @endif
                        </div>
                    </div>
                    @if($sch->hasPendingProof())
                    <div class="flex items-center gap-2 px-3 py-2 border-t border-amber-200 bg-amber-50">
                        <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-xs text-amber-700 font-medium flex-1">Preuve soumise · en attente de validation</span>
                        @if($sch->payment->proof_path)
                        <a href="{{ \Illuminate\Support\Facades\Storage::url($sch->payment->proof_path) }}" target="_blank"
                           class="text-xs text-amber-600 hover:text-amber-800 font-medium underline shrink-0">Voir la preuve</a>
                        @endif
                    </div>
                    @endif
                    @if(! $sch->isPaid())
                    {{-- Formulaire payer --}}
                    <div id="pay-form-{{ $sch->id }}" class="hidden px-3 pb-3 pt-2 border-t border-gray-100">
                        <form action="{{ route('admin.reservations.schedules.pay', [$reservation, $sch]) }}" method="POST" enctype="multipart/form-data" class="space-y-2">
                            @csrf @method('PATCH')
                            <div class="grid grid-cols-2 gap-2">
                                <div><label class="block text-xs text-gray-500 mb-0.5">Montant reçu (MAD)</label><input type="number" name="amount" value="{{ $sch->amount }}" step="0.01" min="0.01" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs font-semibold focus:ring-1 focus:ring-green-400 focus:outline-none"></div>
                                <div><label class="block text-xs text-gray-500 mb-0.5">Mode de paiement</label><select name="method" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-green-400 focus:outline-none"><option value="bank_transfer">Virement</option><option value="cash">Espèces</option><option value="check">Chèque</option><option value="card">Carte</option><option value="other">Autre</option></select></div>
                            </div>
                            <div><label class="block text-xs text-gray-500 mb-0.5">Référence (optionnel)</label><input type="text" name="reference" placeholder="N° virement..." class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-green-400 focus:outline-none"></div>
                            <div><label class="block text-xs text-gray-500 mb-0.5">Pièce jointe (optionnel)</label><input type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-gray-100 file:text-gray-700"></div>
                            <div class="flex gap-2 pt-1">
                                <button class="text-xs bg-green-600 hover:bg-green-700 text-white px-4 py-1.5 rounded font-medium">Confirmer le paiement</button>
                                <button type="button" onclick="togglePayForm({{ $sch->id }})" class="text-xs text-gray-400 hover:text-gray-600">Annuler</button>
                            </div>
                        </form>
                    </div>
                    {{-- Formulaire modifier complet --}}
                    <div id="{{ $editFormId }}" class="hidden px-3 pb-3 pt-2 border-t border-gray-100" x-data="{{ $alpineInit }}">
                        @include('admin.partials.schedule-edit-form', ['reservation' => $reservation, 'sch' => $sch, 'totalPrice' => $totalPrice, 'firstCheckIn' => $firstCheckIn, 'schedOthers' => $schedOthers])
                    </div>
                    @endif
                </div>
                @endif

                @endforeach

                @if($totalPrice > 0)
                <div class="flex justify-between items-center text-xs px-1 pt-1 border-t border-amber-100"> <span class="text-gray-500">Total planifié</span> <span class="font-semibold {{ abs($totalPrice - $schedTotalPre) < 1 ? 'text-green-600' : 'text-amber-600' }}"> {{ number_format($schedTotalPre, 2, ',', ' ') }} / {{ number_format($totalPrice, 2, ',', ' ') }} MAD
                    </span> </div> @endif
            </div> @else
            <p class="text-xs text-gray-400 italic mb-3">Aucune échéance définie.</p> @endif

            {{-- Formulaire ajout --}}
            @php
            $firstCheckIn = $reservation->rooms
                ->filter(fn($r) => $r->check_in)
                ->sortBy(fn($r) => $r->check_in->timestamp)
                ->first()?->check_in?->format('Y-m-d')
                ?? $reservation->check_in?->format('Y-m-d')
                ?? null;
            @endphp
            <div x-show="showForm" x-transition> <form action="{{ route('admin.reservations.schedules.store', $reservation) }}" method="POST"
                      x-data="scheduleEntryForm({{ $totalPrice }}, {{ $schedules->sum('amount') }}, '{{ $firstCheckIn ?? '' }}')"
                      @submit.prevent="submitSchedule($el)"> @csrf

                    {{-- Libellé --}}
                    <div class="mb-2"> <label class="block text-xs text-gray-500 mb-1">Libellé</label> <input type="text" name="label" x-model="label"
                            placeholder="ex: Acompte 1re tranche"
                            class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div>

                    {{-- Délai automatique avant le 1er check-in --}}
                    <template x-if="firstCheckIn">
                        <div class="mb-2 p-2.5 bg-blue-50 border border-blue-100 rounded-lg">
                            <p class="text-xs text-blue-700 font-medium mb-1.5">
                                <svg class="w-3.5 h-3.5 inline-block mr-0.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Délai avant le 1<sup>er</sup> check-in (<span x-text="firstCheckInFormatted" class="font-semibold"></span>)
                            </p>
                            <div class="flex flex-wrap gap-1 items-center">
                                <input type="number" min="1" max="365"
                                    x-model.number="daysBeforeCheckIn"
                                    @input="calcDueDateFromDays()"
                                    placeholder="nb jours"
                                    class="w-20 border border-blue-200 rounded-lg px-2 py-1 text-xs focus:ring-2 focus:ring-blue-400 focus:outline-none">
                                <button type="button" @click="applyDays(7)"
                                    :class="daysBeforeCheckIn === 7 ? 'bg-blue-600 text-white' : 'bg-white text-blue-600 border border-blue-200 hover:bg-blue-100'"
                                    class="text-xs font-semibold px-2 py-1 rounded-md transition">7j</button>
                                <button type="button" @click="applyDays(14)"
                                    :class="daysBeforeCheckIn === 14 ? 'bg-blue-600 text-white' : 'bg-white text-blue-600 border border-blue-200 hover:bg-blue-100'"
                                    class="text-xs font-semibold px-2 py-1 rounded-md transition">14j</button>
                                <button type="button" @click="applyDays(30)"
                                    :class="daysBeforeCheckIn === 30 ? 'bg-blue-600 text-white' : 'bg-white text-blue-600 border border-blue-200 hover:bg-blue-100'"
                                    class="text-xs font-semibold px-2 py-1 rounded-md transition">30j</button>
                                <button type="button" @click="applyDays(60)"
                                    :class="daysBeforeCheckIn === 60 ? 'bg-blue-600 text-white' : 'bg-white text-blue-600 border border-blue-200 hover:bg-blue-100'"
                                    class="text-xs font-semibold px-2 py-1 rounded-md transition">60j</button>
                            </div>
                        </div>
                    </template>

                    {{-- Date + Heure --}}
                    <div class="grid grid-cols-2 gap-2 mb-2"> <div> <label class="block text-xs text-gray-500 mb-1">Date limite *</label> <input type="date" name="due_date" required
                                x-model="dueDate"
                                class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> <div> <label class="block text-xs text-gray-500 mb-1">Heure limite</label> <input type="time" name="due_time" value="12:00"
                                class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> </div> {{-- Boutons preset % --}}
                    @if($totalPrice > 0)
                    <div class="flex gap-1 flex-wrap mb-2"> <span class="text-xs text-gray-400 self-center mr-0.5">Rapide :</span> @foreach([25, 30, 50, 70, 100] as $p)
                        <button type="button" @click="setPct({{ $p }})"
                            :class="Math.abs(pct - {{ $p }}) < 0.1 ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-amber-50 hover:text-amber-700'"
                            class="text-xs font-semibold px-2 py-1 rounded-md transition"> {{ $p }}%
                        </button> @endforeach
                        <button type="button" @click="setRemaining()"
                            class="text-xs font-semibold px-2 py-1 rounded-md bg-blue-50 text-blue-600 hover:bg-blue-100 transition ml-1"> Reste
                        </button> </div> @endif

                    {{-- Champs %  MAD liés --}}
                    <div class="grid grid-cols-2 gap-2 mb-2"> <div> <label class="block text-xs text-gray-500 mb-1">Pourcentage</label> <div class="relative"> <input type="number" min="0" max="100" step="0.1"
                                    x-model.lazy="pct"
                                    @change="updateFromPct()"
                                    @keyup="updateFromPct()"
                                    :disabled="{{ $totalPrice > 0 ? 'false' : 'true' }}"
                                    placeholder="{{ $totalPrice > 0 ? '50' : '' }}"
                                    class="w-full border border-gray-200 rounded-lg pl-2 pr-6 py-1.5 text-xs focus:ring-2 focus:ring-amber-400 focus:outline-none disabled:bg-gray-50 disabled:text-gray-400"> <span class="absolute right-2 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">%</span> </div> </div> <div> <label class="block text-xs text-gray-500 mb-1">Montant (MAD) *</label> <div class="relative"> <input type="number" min="0.01" step="0.01"
                                    x-model.lazy="amt"
                                    @change="updateFromAmt()"
                                    @keyup="updateFromAmt()"
                                    required
                                    placeholder="ex: 5 000"
                                    class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> </div> </div> {{-- Champ caché soumis --}}
                    <input type="hidden" name="amount_input" :value="amt"> {{-- Barre de progression --}}
                    @if($totalPrice > 0)
                    <div class="mb-2"> <div class="flex justify-between text-xs mb-1"> <span class="text-gray-400">Déjà planifié</span> <span class="font-semibold" :class="overBudget ? 'text-red-600' : 'text-gray-600'"
                                  x-text="fmtAmt(alreadyScheduled + (parseFloat(amt)||0)) + ' / ' + fmtAmt(totalPrice) + ' MAD'"> </span> </div> <div class="h-2 bg-gray-100 rounded-full overflow-hidden"> <div class="h-2 rounded-full transition-all"
                                 :class="overBudget ? 'bg-red-400' : 'bg-amber-400'"
                                 :style="'width:' + Math.min(100, totalCoveredPct) + '%'"> </div> </div> <p x-show="overBudget" class="text-xs text-red-600 mt-0.5"> Dépasse le total de la réservation</p> <p x-show="!overBudget && remainingAfter > 0" class="text-xs text-gray-400 mt-0.5"
                           x-text="'Reste à planifier : ' + fmtAmt(remainingAfter) + ' MAD  ' + totalCoveredPct + '% planifié'"> </p> <p x-show="!overBudget && remainingAfter <= 0 && (parseFloat(amt)||0) > 0"
                           class="text-xs text-green-600 font-medium mt-0.5"> Total entièrement couvert</p> </div> @endif

                    <button type="submit" :disabled="!(parseFloat(amt) > 0) || overBudget"
                        class="w-full bg-amber-500 hover:bg-amber-600 disabled:bg-gray-200 disabled:cursor-not-allowed disabled:text-gray-400 text-white text-xs font-semibold py-2 rounded-lg transition"> + Ajouter cette échéance
                    </button> </form> </div> </div> <script> function scheduleEntryForm(totalPrice, alreadyScheduled, firstCheckIn) {
            return {
                pct: '',
                amt: '',
                label: '',
                dueDate: '',
                daysBeforeCheckIn: null,
                totalPrice,
                alreadyScheduled,
                firstCheckIn: firstCheckIn || '',

                get firstCheckInFormatted() {
                    if (!this.firstCheckIn) return '';
                    const [y, m, d] = this.firstCheckIn.split('-');
                    return `${d}/${m}/${y}`;
                },

                calcDueDateFromDays() {
                    const days = parseInt(this.daysBeforeCheckIn);
                    if (!days || days <= 0 || !this.firstCheckIn) return;
                    const ref = new Date(this.firstCheckIn + 'T00:00:00');
                    ref.setDate(ref.getDate() - days);
                    this.dueDate = ref.toISOString().split('T')[0];
                    // Synchroniser l'instance Flatpickr (altInput visuel)
                    const input = document.querySelector('input[name="due_date"]');
                    if (input && input._flatpickr) input._flatpickr.setDate(this.dueDate, false);
                },

                applyDays(d) {
                    this.daysBeforeCheckIn = d;
                    this.calcDueDateFromDays();
                },

                get totalCoveredPct() {
                    if (!totalPrice) return 0;
                    return Math.round((alreadyScheduled + (parseFloat(this.amt) || 0)) / totalPrice * 100);
                },
                get remainingAfter() {
                    return Math.max(0, totalPrice - alreadyScheduled - (parseFloat(this.amt) || 0));
                },
                get remainingPct() {
                    if (!totalPrice) return 0;
                    return Math.round(this.remainingAfter / totalPrice * 100);
                },
                get overBudget() {
                    if (!totalPrice) return false;
                    return (alreadyScheduled + (parseFloat(this.amt) || 0)) > totalPrice + 0.01;
                },

                setPct(p) {
                    this.pct = p;
                    if (totalPrice > 0) {
                        this.amt = Math.round((p / 100) * totalPrice * 100) / 100;
                        if (!this.label) this.label = 'Acompte ' + p + '%';
                    }
                },
                setRemaining() {
                    const rem = Math.max(0, totalPrice - alreadyScheduled);
                    this.amt = Math.round(rem * 100) / 100;
                    if (totalPrice > 0) this.pct = Math.round(rem / totalPrice * 10000) / 100;
                    if (!this.label) this.label = 'Solde';
                },
                updateFromPct() {
                    const p = parseFloat(this.pct);
                    if (!isNaN(p) && totalPrice > 0) {
                        this.amt = Math.round((p / 100) * totalPrice * 100) / 100;
                    }
                },
                updateFromAmt() {
                    const a = parseFloat(this.amt);
                    if (!isNaN(a) && totalPrice > 0) {
                        this.pct = Math.round(a / totalPrice * 10000) / 100;
                    }
                },
                fmtAmt(v) {
                    return (Math.round(v * 100) / 100).toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                },
                submitSchedule(form) {
                    form.querySelector('[name=amount_input]').value = this.amt;
                    form.submit();
                },
            };
        }
        </script> @endif

        {{--  Actions selon statut  --}}
        @if($reservation->status === 'pending')
        @php
            $schedTotalSum   = $schedules->sum('amount');
            $scheduledPct    = ($totalPrice > 0 && $schedules->isNotEmpty())
                                ? round($schedTotalSum / $totalPrice * 100, 1)
                                : null;
            // Bloquer si aucun échéancier, ou si l'échéancier ne couvre pas 100 % (tolérance 0.5 MAD)
            $scheduleBlocked = $schedules->isEmpty()
                             || ($totalPrice > 0 && ($totalPrice - $schedTotalSum) > 0.5);
        @endphp
        <div class="bg-white border border-gray-200 rounded-xl p-5"> <h3 class="font-semibold text-gray-900 mb-4">Actions</h3> {{-- Badge état échéancier --}}
            <div class="flex items-center justify-between text-xs rounded-lg px-3 py-2 mb-3
                {{ $scheduleBlocked ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-green-50 border border-green-200 text-green-700' }}"> <span> @if(! $scheduleBlocked)
 Échéancier complet  {{ $schedules->count() }} échéance(s) · 100%
                    @elseif($schedules->isEmpty())
 Aucune échéance  ajoutez l'échéancier ci-dessus
                    @else
 Échéancier : <strong>{{ $scheduledPct }}%</strong> il manque <strong>{{ round(100 - $scheduledPct, 1) }}%</strong> ({{ number_format($totalPrice - $schedTotalSum, 2, ',', ' ') }} MAD)
                    @endif
                </span> @if($scheduleBlocked && $schedules->isNotEmpty())
                <span class="font-bold">{{ $scheduledPct }}%</span> @endif
            </div> @if($scheduleBlocked && $schedules->isNotEmpty() && $totalPrice > 0)
            {{-- Barre de progression partielle --}}
            <div class="h-1.5 bg-red-100 rounded-full mb-3 overflow-hidden"> <div class="h-1.5 bg-red-400 rounded-full" style="width: {{ min(100, $scheduledPct) }}%"></div> </div> @endif

            {{-- Accepter --}}
            @if(! $scheduleBlocked)
            <form action="{{ route('admin.reservations.accept', $reservation) }}" method="POST" class="mb-3"> @csrf @method('PATCH')
                <textarea name="notes" rows="2" placeholder="Note interne (optionnel)..."
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm mb-2 focus:outline-none focus:ring-2 focus:ring-amber-400"></textarea> <button class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 rounded-lg text-sm"> Accepter & envoyer devis
                </button> </form> @else
            <div class="bg-gray-50 border border-dashed border-gray-300 rounded-lg px-4 py-3 mb-3 text-center"> <p class="text-xs text-gray-400 font-medium"> Complétez l'échéancier à 100% pour pouvoir accepter</p> </div> @endif

            {{-- Refuser --}}
            <div x-data="{
                    open: false,
                    step: 'type',
                    refusalType: '',
                    selectedIds: [],
                    customReason: '',
                    get hasOther() {
                        const oid = {{ $otherReasonId ?? 'null' }};
                        return oid !== null && this.selectedIds.includes(String(oid));
                    },
                    chooseType(type) {
                        this.refusalType = type;
                        this.step = 'reasons';
                    },
                    back() {
                        this.step = 'type';
                        this.refusalType = '';
                    },
                    close() {
                        this.open = false;
                        this.step = 'type';
                        this.refusalType = '';
                        this.selectedIds = [];
                        this.customReason = '';
                    },
                    submit(form) {
                        if (this.selectedIds.length === 0) {
                            alert('Veuillez sélectionner au moins un motif de refus.');
                            return;
                        }
                        form.submit();
                    }
                }">
                <button type="button" @click="open = true"
                        class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 rounded-lg text-sm">
                    Refuser la demande
                </button>

                <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
                     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="close()"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg"
                         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

                        {{-- En-tête --}}
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-red-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900">Refuser la réservation</h3>
                                    <p class="text-xs text-gray-400 mt-0.5"
                                       x-text="step === 'type' ? 'Choisissez le type de refus' : 'Sélectionnez un ou plusieurs motifs'"></p>
                                </div>
                            </div>
                            <button type="button" @click="close()" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        {{-- Étape 1 : choix du type de refus --}}
                        <div x-show="step === 'type'" class="px-6 py-6 space-y-3">
                            {{-- Refus définitif --}}
                            <button type="button" @click="chooseType('definitive')"
                                    class="w-full text-left flex items-start gap-4 p-4 rounded-xl border-2 border-gray-200 hover:border-red-400 hover:bg-red-50 transition-colors group">
                                <div class="w-10 h-10 rounded-xl bg-red-100 group-hover:bg-red-200 flex items-center justify-center shrink-0 transition-colors">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 115.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-900 text-sm">Refus définitif</p>
                                    <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">La réservation est refusée sans possibilité de modification. Le client en est informé par e-mail.</p>
                                </div>
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-red-500 shrink-0 self-center transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>

                            {{-- Refus avec suggestion --}}
                            <button type="button" @click="chooseType('with_suggestion')"
                                    class="w-full text-left flex items-start gap-4 p-4 rounded-xl border-2 border-gray-200 hover:border-amber-400 hover:bg-amber-50 transition-colors group">
                                <div class="w-10 h-10 rounded-xl bg-amber-100 group-hover:bg-amber-200 flex items-center justify-center shrink-0 transition-colors">
                                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-900 text-sm">Refus avec suggestion</p>
                                    <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">Le client peut copier cette réservation et la modifier pour soumettre une nouvelle demande.</p>
                                </div>
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-amber-500 shrink-0 self-center transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Étape 2 : motifs de refus --}}
                        <form x-show="step === 'reasons'"
                              action="{{ route('admin.reservations.refuse', $reservation) }}" method="POST"
                              @submit.prevent="submit($el)">
                            @csrf @method('PATCH')
                            <input type="hidden" name="refusal_type" :value="refusalType">

                            {{-- Bandeau rappel du type choisi --}}
                            <div class="mx-6 mt-5 mb-1 rounded-xl px-4 py-2.5 flex items-center gap-3 text-sm font-medium"
                                 :class="refusalType === 'definitive'
                                    ? 'bg-red-50 text-red-700 border border-red-200'
                                    : 'bg-amber-50 text-amber-700 border border-amber-200'">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          x-bind:d="refusalType === 'definitive'
                                            ? 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 115.636 5.636m12.728 12.728L5.636 5.636'
                                            : 'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z'"/>
                                </svg>
                                <span x-text="refusalType === 'definitive' ? 'Refus définitif' : 'Refus avec suggestion (le client pourra copier et modifier)'"></span>
                            </div>

                            <div class="px-6 py-4">
                                <p class="text-sm font-semibold text-gray-700 mb-3">Motif(s) de refus <span class="text-red-500">*</span></p>
                                <div class="space-y-2.5 max-h-52 overflow-y-auto pr-1">
                                    @foreach($refusalReasons as $reason)
                                    <label class="flex items-start gap-3 cursor-pointer group">
                                        <input type="checkbox" name="reason_ids[]" value="{{ $reason->id }}"
                                               x-model="selectedIds"
                                               class="mt-0.5 w-4 h-4 rounded accent-red-600 shrink-0">
                                        <span class="text-sm text-gray-700 group-hover:text-gray-900 leading-tight">{{ $reason->label }}</span>
                                    </label>
                                    @endforeach
                                </div>
                                <div x-show="hasOther" x-cloak class="mt-4">
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Précisez le motif <span class="text-red-500">*</span></label>
                                    <textarea name="custom_reason" x-model="customReason" rows="3"
                                              placeholder="Décrivez le motif de refus..."
                                              class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent resize-none"></textarea>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-3 px-6 pb-5 border-t border-gray-100 pt-4">
                                <button type="button" @click="back()"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    Retour
                                </button>
                                <div class="flex gap-2">
                                    <button type="button" @click="close()"
                                            class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 rounded-lg hover:bg-gray-100 transition-colors">
                                        Annuler
                                    </button>
                                    <button type="submit"
                                            :class="refusalType === 'definitive'
                                                ? 'bg-red-600 hover:bg-red-700'
                                                : 'bg-amber-500 hover:bg-amber-600'"
                                            class="inline-flex items-center gap-2 text-white font-semibold text-sm px-5 py-2.5 rounded-xl transition-colors shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  x-bind:d="refusalType === 'definitive'
                                                    ? 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 115.636 5.636m12.728 12.728L5.636 5.636'
                                                    : 'M5 13l4 4L19 7'"/>
                                        </svg>
                                        <span x-text="refusalType === 'definitive' ? 'Confirmer le refus' : 'Confirmer'"></span>
                                    </button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div> </div> @endif


        {{-- Suivi paiement --}}
        @if($totalPrice > 0)
        <div class="bg-white border border-gray-200 rounded-xl p-5"> <h3 class="font-semibold text-gray-900 mb-3">Suivi du paiement</h3> <div class="space-y-1.5 text-sm mb-3"> <div class="flex justify-between"> <span class="text-gray-500">Total :</span> <span class="font-bold text-gray-900">{{ number_format($totalPrice, 2, ',', ' ') }} MAD</span> </div> <div class="flex justify-between"> <span class="text-gray-500">Payé (validé) :</span> <span class="font-semibold text-emerald-600">{{ number_format($amountPaid, 2, ',', ' ') }} MAD</span> </div> @if($pendingPay > 0)
                <div class="flex justify-between"> <span class="text-gray-500">En attente validation :</span> <span class="font-semibold text-amber-500">{{ number_format($pendingPay, 2, ',', ' ') }} MAD</span> </div> @endif
                <div class="flex justify-between border-t border-gray-100 pt-1.5"> <span class="text-gray-700 font-medium">Reste à payer :</span> <span class="font-bold {{ $remaining > 0 ? 'text-red-600' : 'text-emerald-600' }}"> {{ $remaining > 0 ? number_format($remaining, 2, ',', ' ') . ' MAD' : ' Soldé' }}
                    </span> </div> </div> {{-- Barre de progression --}}
            <div class="flex items-center gap-2"> <div class="flex-1 bg-gray-100 rounded-full h-2.5"> <div class="h-2.5 rounded-full {{ $pct >= 100 ? 'bg-emerald-500' : 'bg-amber-400' }}"
                         style="width:{{ $pct }}%"></div> </div> <span class="text-xs font-bold {{ $pct >= 100 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $pct }}%</span> </div> {{-- Renvoyer le devis --}}
            @if(in_array($reservation->status, ['waiting_payment', 'accepted', 'partially_paid']))
            <div class="mt-4 pt-3 border-t border-gray-100"> <form action="{{ route('admin.reservations.resend-quote', $reservation) }}" method="POST"> @csrf
                    <button {{ $scheduleIs100 ? '' : 'disabled' }}
                        class="w-full inline-flex items-center justify-center gap-2 text-sm font-medium px-4 py-2 rounded-lg transition-colors
                            {{ $scheduleIs100
                                ? 'bg-white hover:bg-blue-50 text-blue-700 border border-blue-200 hover:border-blue-300 cursor-pointer'
                                : 'bg-gray-50 text-gray-400 border border-dashed border-gray-200 cursor-not-allowed' }}"
                        title="{{ $scheduleIs100 ? 'Envoyer le devis avec l\'échéancier au client' : 'Échéancier à ' . $schedPlanPct . '%  complétez à 100% pour activer' }}"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/> </svg> Renvoyer le devis
                        @if(! $scheduleIs100)
                        <span class="text-xs font-normal text-gray-400">({{ $schedPlanPct }}% planifié)</span> @endif
                    </button> </form> </div> @endif
        </div> @endif

 {{-- Historique paiements validés --}}
        @php $completedPayments = $reservation->payments->where('status','completed'); @endphp
        @if($completedPayments->isNotEmpty())
        <div class="bg-white border border-gray-200 rounded-xl p-5"> <h3 class="font-semibold text-gray-900 mb-3">Paiements enregistrés</h3> @foreach($completedPayments->sortByDesc('created_at') as $payment)
            <div class="text-sm border-b border-gray-50 pb-3 mb-3 last:border-0 last:mb-0 last:pb-0"> <div class="flex justify-between items-start"> <div> <p class="font-medium text-gray-800"> {{ ['bank_transfer'=>'Virement','cash'=>'Espèces','card'=>'Carte','check'=>'Chèque','other'=>'Autre'][$payment->method] ?? $payment->method }}
                        </p> @if($payment->reference)<p class="text-xs text-gray-400">Réf : {{ $payment->reference }}</p>@endif
                        <p class="text-xs text-gray-400">{{ ($payment->paid_at ?? $payment->created_at)->format('d/m/Y') }}</p> </div> <p class="font-bold text-emerald-600">{{ number_format($payment->amount, 2, ',', ' ') }} MAD</p> </div> @if($payment->proof_path)
                <div class="mt-1.5"> <a href="{{ \Illuminate\Support\Facades\Storage::url($payment->proof_path) }}" target="_blank"
                       class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 hover:underline"> Voir la preuve de paiement
                    </a> </div> @endif
            </div> @endforeach
        </div> @endif

    </div>
</div> @push('scripts')
<script>
function toggleDateEdit(id) {
    document.getElementById('date-edit-' + id)?.classList.toggle('hidden');
    document.getElementById('pay-form-' + id)?.classList.add('hidden');
    document.getElementById('sched-edit-' + id)?.classList.add('hidden');
}
function togglePayForm(id) {
    document.getElementById('pay-form-' + id)?.classList.toggle('hidden');
    document.getElementById('date-edit-' + id)?.classList.add('hidden');
    document.getElementById('sched-edit-' + id)?.classList.add('hidden');
}
function toggleScheduleEdit(id) {
    document.getElementById('sched-edit-' + id)?.classList.toggle('hidden');
    document.getElementById('pay-form-' + id)?.classList.add('hidden');
    document.getElementById('date-edit-' + id)?.classList.add('hidden');
}
</script>
@endpush

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('priceDraft', {
        pending: {},

        add(roomId, data) {
            this.pending = { ...this.pending, [roomId]: data };
        },

        remove(roomId) {
            const p = { ...this.pending };
            delete p[roomId];
            this.pending = p;
        },

        reset() {
            this.pending = {};
        },

        has(roomId) {
            return Object.prototype.hasOwnProperty.call(this.pending, roomId);
        },

        get count() {
            return Object.keys(this.pending).length;
        },

        get delta() {
            return Object.values(this.pending).reduce((sum, c) => {
                const factor = 1 - ((c.promoRate ?? 0) / 100);
                return sum + (c.newTotal - (c.oldTotal ?? 0)) * factor;
            }, 0);
        },

        get json() {
            const out = {};
            Object.entries(this.pending).forEach(([id, c]) => { out[id] = c.newPpn; });
            return JSON.stringify(out);
        },

        fmt(v) {
            return new Intl.NumberFormat('fr-FR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(v ?? 0).replace(/[\u202F\u00A0]/g, ' ');
        },
    });

    // ── Extra Service form helper ──────────────────────────────────────────────
    Alpine.data('extraForm', () => ({
        catalogId: '',
        name: '',
        description: '',
        unitPrice: '',
        quantity: 1,

        fillFromCatalog(event) {
            const opt = event.target.selectedOptions[0];
            if (opt && opt.value) {
                this.name        = opt.dataset.name  || '';
                this.description = opt.dataset.desc  || '';
                this.unitPrice   = opt.dataset.price || '';
            } else {
                this.name        = '';
                this.description = '';
                this.unitPrice   = '';
            }
        },

        formatMAD(value) {
            return new Intl.NumberFormat('fr-FR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(value || 0).replace(/[\u202F\u00A0]/g, ' ') + ' MAD';
        },
    }));
});
</script>
@endpush

{{-- ── Modale de confirmation suppression échéance ──────────────────────────── --}}
<div id="delete-confirm-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center"
     style="background:rgba(0,0,0,0.45)">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full mx-4 text-center">
        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/>
            </svg>
        </div>
        <h3 class="text-base font-bold text-gray-900 mb-1">Supprimer l'échéance ?</h3>
        <p class="text-sm text-gray-500 mb-6">Cette action est irréversible. L'échéance sera définitivement supprimée.</p>
        <div class="flex gap-3 justify-center">
            <button onclick="closeDeleteModal()"
                    class="flex-1 px-4 py-2 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                Annuler
            </button>
            <button id="delete-confirm-btn"
                    class="flex-1 px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-semibold transition">
                Supprimer
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
let _pendingDeleteForm = null;

function openDeleteModal(formEl) {
    _pendingDeleteForm = formEl;
    const modal = document.getElementById('delete-confirm-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeDeleteModal() {
    _pendingDeleteForm = null;
    const modal = document.getElementById('delete-confirm-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.getElementById('delete-confirm-btn').addEventListener('click', function () {
    if (_pendingDeleteForm) {
        _pendingDeleteForm.submit();
    }
    closeDeleteModal();
});

// Fermer en cliquant sur le fond
document.getElementById('delete-confirm-modal').addEventListener('click', function (e) {
    if (e.target === this) closeDeleteModal();
});
</script>
@endpush

@endsection
