{{--
 Affiche un snapshot de réservation (old_data ou new_data).
 Variables: $data (array), $side ('old' | 'new')
--}}
@php
    $textColor = $side === 'old' ? 'text-red-700' : 'text-emerald-700';
    $labelColor = $side === 'old' ? 'text-red-400' : 'text-emerald-500';
@endphp

<div class="space-y-1.5 text-xs"> {{-- Statut --}}
    @if(isset($data['status']))
    <div> <span class="{{ $labelColor }} font-semibold">Statut : </span> <span class="{{ $textColor }} font-medium">{{ $data['status'] }}</span> </div> @endif

    {{-- Dates --}}
    @if(isset($data['check_in']))
    <div> <span class="{{ $labelColor }} font-semibold">Dates : </span> <span class="{{ $textColor }}">{{ $data['check_in'] }}  {{ $data['check_out'] ?? '?' }}</span> @if(isset($data['nights']))
        <span class="text-gray-400 ml-1">({{ $data['nights'] }} nuit{{ $data['nights'] > 1 ? 's' : '' }})</span> @endif
    </div> @endif

    {{-- Personnes --}}
    @if(isset($data['total_persons']))
    <div> <span class="{{ $labelColor }} font-semibold">Personnes : </span> <span class="{{ $textColor }}">{{ $data['total_persons'] }}</span> </div> @endif

    {{-- Prix --}}
    @if(isset($data['total_price']))
    <div> <span class="{{ $labelColor }} font-semibold">Total : </span> <span class="{{ $textColor }} font-bold">{{ number_format((float)$data['total_price'], 2, ',', ' ') }} MAD</span> </div> @endif
    @if(isset($data['taxe_total']) && $data['taxe_total'] > 0)
    <div> <span class="{{ $labelColor }} font-semibold">Taxe séjour : </span> <span class="{{ $textColor }}">{{ number_format((float)$data['taxe_total'], 2, ',', ' ') }} MAD</span> </div> @endif
    @if(isset($data['supplement_total']) && $data['supplement_total'] > 0)
    <div> <span class="{{ $labelColor }} font-semibold">Suppléments : </span> <span class="{{ $textColor }}">{{ number_format((float)$data['supplement_total'], 2, ',', ' ') }} MAD</span> </div> @endif

    {{-- Paiement --}}
    @if(isset($data['amount']))
    <div> <span class="{{ $labelColor }} font-semibold">Montant : </span> <span class="{{ $textColor }} font-bold">{{ number_format((float)$data['amount'], 2, ',', ' ') }} MAD</span> @if(isset($data['method']))
        <span class="text-gray-400 ml-1">({{ ['bank_transfer'=>'Virement','cash'=>'Espèces','card'=>'Carte','check'=>'Chèque'][$data['method']] ?? $data['method'] }})</span> @endif
    </div> @endif
    @if(isset($data['total_paid']))
    <div> <span class="{{ $labelColor }} font-semibold">Total payé : </span> <span class="{{ $textColor }}">{{ number_format((float)$data['total_paid'], 2, ',', ' ') }} MAD</span> @if(isset($data['pct']))<span class="text-gray-400 ml-1">({{ $data['pct'] }}%)</span>@endif
    </div> @endif
    @if(isset($data['remaining']) && $data['remaining'] > 0)
    <div> <span class="{{ $labelColor }} font-semibold">Reste : </span> <span class="{{ $textColor }}">{{ number_format((float)$data['remaining'], 2, ',', ' ') }} MAD</span> </div> @endif

    {{-- Séjours / Chambres --}}
    @if(!empty($data['stays']))
    <div class="mt-1.5"> <span class="{{ $labelColor }} font-semibold block mb-1">Séjours :</span> @foreach($data['stays'] as $sIdx => $stay)
        <div class="pl-2 border-l-2 {{ $side === 'old' ? 'border-red-200' : 'border-emerald-200' }} mb-1.5"> <p class="text-gray-600"> {{ $stay['check_in'] ?? '?' }}  {{ $stay['check_out'] ?? '?' }}</p> @foreach($stay['rooms'] ?? [] as $room)
            <p class="{{ $textColor }}"> {{ $room['quantity'] ?? 1 }}×
                @if(!empty($room['config_code']))
                    <span class="font-semibold">{{ $room['config_code'] }}</span> @if(!empty($room['room_type_name'])) <span class="text-gray-400 text-[10px]">({{ $room['room_type_name'] }})</span> @endif
                @else
                    {{ $room['room_type'] ?? '?' }}
                @endif
                @php
                    $occ = [];
                    if (($room['adults']   ?? 0) > 0) $occ[] = ($room['adults'])   . ' adulte'   . (($room['adults']   > 1) ? 's' : '');
                    if (($room['children'] ?? 0) > 0) $occ[] = ($room['children']) . ' enfant'   . (($room['children'] > 1) ? 's' : '');
                    if (($room['babies']   ?? 0) > 0) $occ[] = ($room['babies'])   . ' bébé'     . (($room['babies']   > 1) ? 's' : '');
                @endphp
                @if(count($occ))  {{ implode(' + ', $occ) }} @endif
                @if(isset($room['price']) && $room['price'])
                <span class="text-gray-400">({{ number_format((float)$room['price'], 2, ',', ' ') }} MAD)</span> @endif
            </p> @endforeach
        </div> @endforeach
    </div> @endif

    {{-- Proposition de modification (données enrichies) --}}
    @if(!empty($data['proposed']))
    @php $proposed = $data['proposed']; @endphp
    <div class="mt-1 space-y-1.5"> {{-- Séjours proposés --}}
        <span class="{{ $labelColor }} font-semibold block">Séjours proposés :</span> @foreach($proposed['stays'] ?? [] as $stay)
        <div class="pl-2 border-l-2 border-purple-200"> <p class="text-gray-600 text-[11px]"> {{ $stay['check_in'] ?? '?' }}  {{ $stay['check_out'] ?? '?' }}</p> @foreach($stay['rooms'] ?? [] as $room)
            <p class="{{ $textColor }} text-[11px]"> {{ $room['quantity'] ?? 1 }}×
                @if(!empty($room['config_code']))
                    <span class="font-semibold">{{ $room['config_code'] }}</span> @if(!empty($room['room_type_name'])) <span class="text-gray-400 text-[10px]">({{ $room['room_type_name'] }})</span> @endif
                @else
                    {{ $room['room_type'] ?? '?' }}
                @endif
                @php
                    $occ = [];
                    if (($room['adults']   ?? 0) > 0) $occ[] = ($room['adults'])   . ' adulte'  . (($room['adults']   > 1) ? 's' : '');
                    if (($room['children'] ?? 0) > 0) $occ[] = ($room['children']) . ' enfant'  . (($room['children'] > 1) ? 's' : '');
                    if (($room['babies']   ?? 0) > 0) $occ[] = ($room['babies'])   . ' bébé'    . (($room['babies']   > 1) ? 's' : '');
                @endphp
                @if(count($occ))  {{ implode(' + ', $occ) }} @endif
            </p> @endforeach
        </div> @endforeach

        {{-- Suppléments proposés --}}
        @if(!empty($proposed['supplements']))
        <div class="mt-1"> <span class="{{ $labelColor }} font-semibold block mb-0.5">Suppléments :</span> @foreach($proposed['supplements'] as $sup)
            <p class="{{ $textColor }} text-[11px]"> {{ ($sup['is_mandatory'] ?? false) ? '' : '' }}
                {{ $sup['title'] ?? 'Supplément' }}
                @if(!empty($sup['note']))
                <span class="text-gray-400 italic ml-1">({{ $sup['note'] }})</span> @endif
            </p> @endforeach
        </div> @endif
    </div> @endif

    {{-- Suppléments --}}
    @if(!empty($data['supplements']))
    <div class="mt-1"> <span class="{{ $labelColor }} font-semibold block mb-1">Suppléments :</span> @foreach($data['supplements'] as $sup)
        <p class="{{ $textColor }}"> {{ ($sup['is_mandatory'] ?? false) ? '' : '' }} {{ $sup['title'] ?? 'Supplément' }}
            @if(isset($sup['total_price']))  {{ number_format($sup['total_price'], 2, ',', ' ') }} MAD @endif
        </p> @endforeach
    </div> @endif

    {{-- Échéance --}}
    @if(isset($data['due_date']))
    <div> <span class="{{ $labelColor }} font-semibold">Échéance : </span> <span class="{{ $textColor }}">{{ $data['due_date'] ?? '?' }}</span> @if(isset($data['due_time']))<span class="text-gray-400 ml-1">à {{ $data['due_time'] }}</span>@endif
    </div> @endif
    @if(isset($data['installment']))
    <div> <span class="{{ $labelColor }} font-semibold">Numéro : </span> <span class="{{ $textColor }}">Échéance #{{ $data['installment'] }}</span> </div> @endif
    @if(isset($data['label']) && $data['label'])
    <div> <span class="{{ $labelColor }} font-semibold">Libellé : </span> <span class="{{ $textColor }}">{{ $data['label'] }}</span> </div> @endif

    {{-- Tarif --}}
    @if(isset($data['tariff_code']))
    <div> <span class="{{ $labelColor }} font-semibold">Tarif : </span> <span class="{{ $textColor }} font-mono">{{ $data['tariff_code'] }}</span> </div> @endif
    @if(isset($data['nb_stays']))
    <div> <span class="{{ $labelColor }} font-semibold">Séjours : </span> <span class="{{ $textColor }}">{{ $data['nb_stays'] }}</span> </div> @endif

    {{-- Email --}}
    @if(isset($data['email']))
    <div> <span class="{{ $labelColor }} font-semibold">Destinataire : </span> <span class="{{ $textColor }}">{{ $data['email'] }}</span> </div> @endif

    {{-- Paiement en attente --}}
    @if(isset($data['status']) && $data['status'] === 'pending')
    <div> <span class="{{ $labelColor }} font-semibold">Statut : </span> <span class="text-amber-600 font-medium"> En attente de validation</span> </div> @endif
    @if(isset($data['schedule_installment']))
    <div> <span class="{{ $labelColor }} font-semibold">Lié à : </span> <span class="{{ $textColor }}">Échéance #{{ $data['schedule_installment'] }}</span> </div> @endif

</div>
