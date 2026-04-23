@extends('layouts.client')
@section('title', 'Réservation ' . $reservation->reference)

@section('content')
<div class="space-y-6"> {{-- En-tête statut --}}
    <div class="bg-white border border-gray-200 rounded-xl p-6 text-center"> <p class="text-4xl mb-3"> @switch($reservation->status)
                @case('pending')  @break
                @case('accepted')  @break
                @case('waiting_payment')  @break
                @case('confirmed')  @break
                @case('refused')  @break
                @case('cancelled')  @break
                @case('modification_pending')  @break
                @default 
            @endswitch
        </p> <h1 class="text-xl font-bold text-gray-900">{{ $reservation->status_label }}</h1> <p class="text-gray-500 text-sm mt-1">Référence : <strong class="font-mono text-amber-600">{{ $reservation->reference }}</strong></p> @if(in_array($reservation->status, ['waiting_payment','partially_paid']) && $reservation->hasValidPaymentToken())
        @if($reservation->isPaymentDeadlineExpired())
        <div class="mt-4 p-3 bg-gray-100 border border-gray-200 rounded-xl text-center"> <p class="text-sm text-gray-400 font-medium"> Délai de paiement dépassé</p> <p class="text-xs text-gray-400 mt-1">Date limite : {{ $reservation->payment_deadline->format('d/m/Y') }}</p> </div> @else
        <div class="mt-4 space-y-2"> @if($reservation->payment_deadline)
            <p class="text-xs text-amber-600"> Paiement à effectuer avant le {{ $reservation->payment_deadline->format('d/m/Y') }}</p> @endif
            <a href="{{ route('client.payment', $reservation->payment_token) }}"
               class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold px-6 py-3 rounded-xl text-sm"> Accéder au paiement
            </a> </div> @endif
        @endif

        @if($reservation->status === 'refused' && $reservation->refusal_reason)
        <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-left"> <p class="text-sm text-red-700"><strong>Motif :</strong> {{ $reservation->refusal_reason }}</p> </div> @endif
    </div> {{-- Résumé --}}
    <div class="bg-white border border-gray-200 rounded-xl p-6"> <h2 class="text-base font-semibold mb-4">Résumé de votre réservation</h2> @php
            $sejours      = $reservation->sejours;
            $multiSejour  = $sejours->count() > 1;

            // Recalcul des personnes depuis les chambres (valeur DB peut être obsolète)
            $computedPersons = $reservation->rooms->sum(
                fn($r) => (($r->adults ?? 0) + ($r->children ?? 0) + ($r->babies ?? 0)) * max(1, $r->quantity ?? 1)
            ) ?: $reservation->total_persons;

            // Pré-calculer la taxe de séjour pour l'afficher dans le header
            // (elle n'est pas dans total_price, calculée à la volée)
            $_taxeRate       = (float) ($reservation->hotel->taxe_sejour ?? 19.80);
            $_preTaxeTotal   = 0;
            foreach ($sejours as $_s) {
                $_sAdults = (int) $_s['rooms']->sum(fn($r) => ($r->adults ?? 0) * max(1, $r->quantity ?? 1));
                $_sNights = (int) ($_s['nights'] ?? 0);
                if ($_sAdults > 0 && $_sNights > 0) {
                    $_preTaxeTotal += round($_sAdults * $_sNights * $_taxeRate, 2);
                }
            }
            // Calculer le total header depuis les composants réels (cohérence avec TOTAL ESTIMÉ)
            $_roomsTotal       = $sejours->sum(fn($_s) => $_s['rooms']->sum(fn($_r) => (float)($_r->total_price ?? 0)));
            $_supplementsTotal = $reservation->supplements->sum('total_price');
            $headerGrandTotal  = round(
                ($_roomsTotal > 0 ? $_roomsTotal : max(0, (float)($reservation->total_price ?? 0) - $_supplementsTotal))
                + $_supplementsTotal + $_preTaxeTotal,
                2
            );
        @endphp
        <div class="grid grid-cols-2 gap-3 text-sm mb-4"> <div><span class="text-gray-500">Hôtel :</span> <strong>{{ $reservation->hotel->name }}</strong></div> <div><span class="text-gray-500">Personnes :</span> {{ $computedPersons }}</div> @if($multiSejour)
            <div class="col-span-2"> <span class="text-gray-500">Séjours :</span> <span class="inline-flex items-center text-xs bg-amber-100 text-amber-800 font-semibold px-2 py-0.5 rounded-full ml-1"> {{ $sejours->count() }} séjours · {{ $reservation->nights }} nuits au total
                </span> </div> @else
            <div><span class="text-gray-500">Arrivée :</span> <strong>{{ $reservation->check_in->format('d/m/Y') }}</strong></div> <div><span class="text-gray-500">Départ :</span> <strong>{{ $reservation->check_out->format('d/m/Y') }}</strong></div> <div><span class="text-gray-500">Durée :</span> {{ $reservation->nights }} nuits</div> @endif
            @if($reservation->total_price)
            {{-- Promo long séjour --}}
            @if($reservation->promo_discount_amount > 0)
            <div class="text-xs text-purple-700 font-medium"> Promo long séjour {{ $reservation->promo_discount_rate }}% appliquée
            </div> @endif
            {{-- Suppléments --}}
            @if($reservation->supplements && $reservation->supplements->isNotEmpty())
            @foreach($reservation->supplements as $rs)
            <div class="text-xs text-gray-600"> {{ $rs->supplement->title }}
                <span class="{{ $rs->is_mandatory ? 'text-red-500' : 'text-blue-500' }}"> ({{ $rs->is_mandatory ? 'obligatoire' : 'optionnel' }})
                </span> {{ number_format($rs->total_price, 0, ',', ' ') }} MAD
            </div> @endforeach
            @endif
            <div><span class="text-gray-500">Total :</span> <strong class="text-amber-600">{{ number_format(round($headerGrandTotal), 0, ',', ' ') }} MAD</strong></div> @endif
        </div> {{-- Chambres groupées par séjour --}}
        @php
            $hotel            = $reservation->hotel;
            $taxeRate         = (float) ($hotel->taxe_sejour ?? 19.80);
            $taxeTotalGlobal  = 0;
            $roomsTotalGlobal = 0;

            // Indexer price_breakdown par clé composite pour fallback prix + label
            $breakdownIndex   = [];   // clé "rtId_cfgId"
            $breakdownByRtId  = [];   // clé "rtId"  fallback quand occupancy_config_id est null
            foreach (($reservation->price_breakdown ?? []) as $line) {
                $rtId = $line['room_type_id'] ?? '';
                $key  = $rtId . '_' . ($line['occupancy_config_id'] ?? '');
                $breakdownIndex[$key] = $line;
                // Premier match par room_type seulement (pour les resa sans config_id)
                if (!isset($breakdownByRtId[$rtId])) {
                    $breakdownByRtId[$rtId] = $line;
                }
            }
        @endphp
        <div class="border-t border-gray-100 pt-4 space-y-5"> @foreach($sejours as $i => $sejour)
            @php
                $sejourNights     = $sejour['nights'];
                $sejourAdults     = $sejour['rooms']->sum(fn($r) => ($r->adults ?? 0) * max(1, $r->quantity ?? 1));
                // Calculer le total du séjour : total_price de la chambre ou fallback price_breakdown
                $sejourRoomsTotal = $sejour['rooms']->sum(function ($r) use ($breakdownIndex) {
                    if ($r->total_price) return $r->total_price;
                    $key = $r->room_type_id . '_' . ($r->occupancy_config_id ?? '');
                    return $breakdownIndex[$key]['line_total'] ?? 0;
                });
                $sejourTaxe       = round($sejourAdults * $sejourNights * $taxeRate, 2);
                $taxeTotalGlobal  += $sejourTaxe;
                $roomsTotalGlobal += $sejourRoomsTotal;
            @endphp

            <div class="{{ $multiSejour ? 'border border-amber-100 rounded-lg overflow-hidden' : '' }}"> @if($multiSejour)
                <div class="flex items-center gap-2 px-3 py-2 bg-amber-50 border-b border-amber-100"> <span class="text-xs font-bold text-amber-700">Séjour {{ $i + 1 }}</span> <span class="text-sm font-semibold text-gray-800"> {{ $sejour['check_in']->format('d/m/Y') }}  {{ $sejour['check_out']->format('d/m/Y') }}
                    </span> <span class="text-xs text-gray-400">({{ $sejourNights }} nuit{{ $sejourNights > 1 ? 's' : '' }})</span> </div> @else
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Détail des chambres</p> @endif

                <div class="{{ $multiSejour ? 'p-3 ' : '' }}space-y-1"> {{-- En-têtes --}}
                    <div class="hidden sm:flex text-xs text-gray-400 font-medium pb-1 border-b border-gray-100"> <span class="flex-1">Chambre / Occupation</span> <span class="w-24 text-center">Personnes</span> <span class="w-28 text-right">Montant</span> </div> @foreach($sejour['rooms'] as $room)
                    @php
                        $roomPrice = $room->total_price;
                        if (! $roomPrice) {
                            $bKey      = $room->room_type_id . '_' . ($room->occupancy_config_id ?? '');
                            $roomPrice = $breakdownIndex[$bKey]['line_total'] ?? null;
                        }
                        // Chaîne de fallback :
                        // 1. Colonne DB occupancy_config_label
                        // 2. Relation OccupancyConfig (si occupancy_config_id présent)
                        // 3. price_breakdown ligne exacte (rtId_cfgId)
                        // 4. price_breakdown par room_type_id seulement (ancienne resa sans config_id)
                        // 5. Nom du type de chambre
                        $bKeyExact = $room->room_type_id . '_' . ($room->occupancy_config_id ?? '');
                        $roomLabel = $room->occupancy_config_label
                            ?: ($room->occupancyConfig?->label
                                ?: ($breakdownIndex[$bKeyExact]['occupancy_label'] ?? null)
                                    ?: ($breakdownByRtId[$room->room_type_id]['occupancy_label'] ?? $room->roomType->name));
                    @endphp
                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-1 py-1.5 border-b border-gray-50 last:border-0"> <div class="flex-1 text-sm text-gray-800"> <span class="font-medium">{{ $room->quantity }} ×</span> {{ $roomLabel }}
                            <span class="text-xs text-gray-400 block sm:inline sm:ml-1"> × {{ $sejourNights }} nuit{{ $sejourNights > 1 ? 's' : '' }}
                            </span> </div> <div class="w-24 text-center text-xs text-gray-500"> {{ $room->adults ?? 0 }} ad.
                            @if($room->children) · {{ $room->children }} enf. @endif
                            @if($room->babies) · {{ $room->babies }} bébé @endif
                            <span class="block text-gray-400">par chambre</span> </div> <div class="w-28 text-right text-sm font-semibold text-gray-700"> {{ $roomPrice ? number_format($roomPrice, 0, ',', ' ') . ' MAD' : '' }}
                        </div> </div> @endforeach

                    {{-- Taxe de séjour pour ce séjour --}}
                    @if($sejourTaxe > 0)
                    <div class="flex items-center justify-between pt-1 text-xs text-blue-600"> <span>Taxe de séjour ({{ $sejourAdults }} adulte(s) × {{ $sejourNights }} nuit(s) × {{ number_format($taxeRate, 2, ',', ' ') }} DHS)</span> <span class="font-semibold">{{ number_format($sejourTaxe, 2, ',', ' ') }} MAD</span> </div> @endif

                    @if($multiSejour)
                    <div class="flex justify-between text-sm font-bold pt-2 border-t border-amber-100 text-amber-800"> <span>Sous-total séjour {{ $i + 1 }}</span> <span>{{ number_format($sejourRoomsTotal + $sejourTaxe, 0, ',', ' ') }} MAD</span> </div> @endif
                </div> </div> @endforeach
        </div> {{-- Récapitulatif financier --}}
        @if($reservation->total_price)
        @php
            // Calcul depuis les composants réels pour éviter les incohérences avec total_price en DB
            // (total_price peut varier selon l'état de la réservation ou des migrations passées)
            $supplementsTotal  = $reservation->supplements->sum('total_price');
            $displayRoomsTotal = $roomsTotalGlobal > 0
                ? $roomsTotalGlobal
                : max(0, (float)$reservation->total_price - $supplementsTotal);
            // Total = chambres + suppléments + taxe (calculé depuis les lignes, pas total_price)
            $clientGrandTotal  = round($displayRoomsTotal + $supplementsTotal + $taxeTotalGlobal, 2);
        @endphp
        <div class="mt-4 border-t border-gray-100 pt-4 space-y-1.5"> <div class="flex justify-between text-sm text-gray-700"> <span>Hébergement (chambres)</span> <span>{{ number_format($displayRoomsTotal, 0, ',', ' ') }} MAD</span> </div> @if($taxeTotalGlobal > 0)
            <div class="flex justify-between text-sm text-blue-600"> <span>Taxe de séjour</span> <span>{{ number_format($taxeTotalGlobal, 2, ',', ' ') }} MAD</span> </div> @endif
            @foreach($reservation->supplements as $rs)
            <div class="flex justify-between text-sm {{ $rs->is_mandatory ? 'text-orange-600' : 'text-purple-600' }}"> <span> {{ $rs->supplement->title }}
                    <span class="text-xs opacity-70">({{ $rs->is_mandatory ? 'obligatoire' : 'optionnel' }})</span> </span> <span>{{ number_format($rs->total_price, 0, ',', ' ') }} MAD</span> </div> @endforeach
            <div class="flex justify-between text-base font-bold text-amber-700 pt-2 border-t border-gray-200"> <span>TOTAL ESTIMÉ</span> <span>{{ number_format(round($clientGrandTotal), 0, ',', ' ') }} MAD</span> </div> <p class="text-xs text-gray-400">* Prix indicatif, confirmé après validation par notre équipe.</p> </div> @endif

        @if($reservation->special_requests)
        <div class="mt-4 p-3 bg-gray-50 rounded-lg"> <p class="text-xs font-medium text-gray-500 mb-1">Demandes spéciales :</p> <p class="text-sm text-gray-700">{{ $reservation->special_requests }}</p> </div> @endif
    </div> {{-- Historique --}}
    <div class="bg-white border border-gray-200 rounded-xl p-6"> <h2 class="text-base font-semibold mb-4">Suivi de votre demande</h2> <div class="space-y-3"> @foreach($reservation->statusHistories as $h)
            <div class="flex gap-3 text-sm"> <div class="w-2 h-2 rounded-full bg-amber-400 mt-2 shrink-0"></div> <div> <p class="font-medium text-gray-800">{{ $h->to_status_label }}</p> @if($h->comment && $h->actor_type !== 'client')
                    <p class="text-gray-500 text-xs mt-0.5">{{ $h->comment }}</p> @endif
                    <p class="text-xs text-gray-400">{{ $h->created_at->format('d/m/Y à H:i') }}</p> </div> </div> @endforeach
        </div> </div> {{-- Blocage si échéance en retard --}}
    @if($reservation->hasOverdueSchedule())
    <div class="bg-red-50 border border-red-200 rounded-xl p-5"> <div class="flex items-start gap-3"> <span class="text-2xl"></span> <div> <p class="font-semibold text-red-800 text-sm">Accès restreint  échéance de paiement dépassée</p> <p class="text-red-600 text-xs mt-1"> Une ou plusieurs échéances de paiement ne sont pas réglées et leur date limite est dépassée.
 Veuillez contacter votre gestionnaire pour régulariser la situation ou obtenir un report de date.
                </p> </div> </div> </div> @else
    {{-- Actions client --}}
    @php
        $modifiableStatuses  = [\App\Models\Reservation::STATUS_PENDING, \App\Models\Reservation::STATUS_ACCEPTED, \App\Models\Reservation::STATUS_WAITING_PAYMENT, \App\Models\Reservation::STATUS_PARTIALLY_PAID, \App\Models\Reservation::STATUS_CONFIRMED];
        $statusAllowsMod     = in_array($reservation->status, $modifiableStatuses);
        $checkInCarbon       = $reservation->check_in instanceof \Carbon\Carbon ? $reservation->check_in : \Carbon\Carbon::parse($reservation->check_in);
        $daysUntilArrival    = (int) now()->startOfDay()->diffInDays($checkInCarbon->copy()->startOfDay(), false);
        $blockedBy7DayRule   = $statusAllowsMod && ! $reservation->canBeModifiedByClient() && $daysUntilArrival <= 7 && $daysUntilArrival >= 0;
    @endphp

    @if($blockedBy7DayRule)
    <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 flex items-start gap-3">
        <svg class="w-5 h-5 text-orange-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <div>
            <p class="text-sm font-semibold text-orange-800">Modification non disponible</p>
            <p class="text-xs text-orange-700 mt-0.5">
                L'arrivée est prévue dans <strong>{{ $daysUntilArrival }} jour{{ $daysUntilArrival > 1 ? 's' : '' }}</strong>.
                Les modifications ne sont plus acceptées à moins de 7 jours avant la date d'arrivée.
                Pour toute demande, veuillez contacter directement l'hôtel.
            </p>
        </div>
    </div>
    @endif

    <div class="flex flex-wrap gap-3"> @if($reservation->canBeModifiedByClient())
        <a href="{{ route('client.reservation.edit', [$token, $reservation]) }}"
           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2.5 rounded-xl"> Modifier ma demande
        </a> @endif

        {{-- Fiche de police : accessible dès que la réservation est acceptée --}}
        @if(in_array($reservation->status, ['waiting_payment','partially_paid','confirmed','accepted']))
        @php
            $guestsFilled = $reservation->guestRegistrations->filter(fn($g) => $g->isComplete())->count();
            $guestsTotal  = $reservation->rooms->sum(fn($r) => (($r->adults ?? 0) + ($r->children ?? 0)) * max(1, $r->quantity ?? 1));
        @endphp
        <a href="{{ route('client.reservation.guests.form', [$token, $reservation]) }}"
           class="inline-flex items-center gap-2 text-sm font-medium px-5 py-2.5 rounded-xl border
                  {{ $guestsFilled >= $guestsTotal && $guestsTotal > 0
                      ? 'bg-emerald-50 border-emerald-300 text-emerald-700'
                      : 'bg-amber-50 border-amber-300 text-amber-700' }}"> Fiche de police
            @if($guestsTotal > 0)
            <span class="text-xs opacity-75">({{ $guestsFilled }}/{{ $guestsTotal }})</span> @endif
        </a> @endif

        @if($reservation->canBeCancelledByClient())
        <form action="{{ route('client.reservation.cancel', [$token, $reservation]) }}" method="POST"
              onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')"> @csrf @method('PATCH')
            <button class="inline-flex items-center gap-2 bg-gray-100 hover:bg-red-50 text-red-600 text-sm font-medium px-5 py-2.5 rounded-xl border border-red-200"> Annuler ma réservation
            </button> </form> @endif
    </div> @endif

</div>
@endsection
