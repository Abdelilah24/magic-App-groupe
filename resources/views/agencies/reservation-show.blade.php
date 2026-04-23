<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-50">
<head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>{{ $reservation->reference }}  Magic Hotels</title> <script src="https://cdn.tailwindcss.com"></script> <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script> <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"> <meta name="csrf-token" content="{{ csrf_token() }}"> <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-full" x-data="{ payModal: { show: false, scheduleId: null, amount: 0, label: '' }, cancelModal: false }"> {{-- Header --}}
<header class="bg-slate-900 text-white sticky top-0 z-30"> <div class="max-w-5xl mx-auto px-6 py-3 flex items-center justify-between"> <div class="flex items-center gap-4"> <span class="text-amber-400 text-lg font-bold"> Magic Hotels</span> <span class="hidden sm:block text-slate-500 text-sm">|</span> <span class="hidden sm:block text-slate-300 text-sm font-medium">{{ $agency->name }}</span> </div> <div class="flex items-center gap-4"> <a href="{{ route('agency.portal.dashboard') }}"
               class="text-slate-400 hover:text-white text-sm flex items-center gap-1.5"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/> </svg> Tableau de bord
            </a> </div> </div>
</header> <main class="max-w-5xl mx-auto px-6 py-8"> {{-- Flash --}}
    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm flex items-center gap-2 mb-6"> <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> {{ session('success') }}
    </div> @endif

    @php
        $statusColors = [
            'draft'               => 'bg-gray-100 text-gray-600',
            'pending'             => 'bg-yellow-100 text-yellow-700',
            'accepted'            => 'bg-blue-100 text-blue-700',
            'refused'             => 'bg-red-100 text-red-700',
            'waiting_payment'     => 'bg-orange-100 text-orange-700',
            'partially_paid'      => 'bg-indigo-100 text-indigo-700',
            'paid'                => 'bg-teal-100 text-teal-700',
            'confirmed'           => 'bg-emerald-100 text-emerald-700',
            'modification_pending'=> 'bg-purple-100 text-purple-700',
            'cancelled'           => 'bg-gray-100 text-gray-400',
        ];
        $statusBar = [
            'confirmed'           => 'bg-emerald-500',
            'waiting_payment'     => 'bg-orange-500',
            'partially_paid'      => 'bg-indigo-500',
            'accepted'            => 'bg-blue-500',
            'refused'             => 'bg-red-400',
            'cancelled'           => 'bg-red-400',
            'modification_pending'=> 'bg-purple-500',
            'pending'             => 'bg-yellow-400',
            'draft'               => 'bg-gray-300',
        ];
        $hasPending = $reservation->payments->where('status', 'pending')->count() > 0;
        $pendingPay = $reservation->payments->where('status', 'pending')->sum('amount');
        $hasSchedules = $reservation->paymentSchedules && $reservation->paymentSchedules->isNotEmpty();
        $mandatorySupps = $reservation->supplements->where('is_mandatory', true)->values();
        $optionalSupps  = $reservation->supplements->where('is_mandatory', false)->values();
        $totalPersons   = $reservation->rooms->sum(fn($r) => (($r->adults ?? 0) + ($r->children ?? 0) + ($r->babies ?? 0)) * max(1, $r->quantity ?? 1)) ?: $reservation->total_persons;
        $methods = ['bank_transfer'=>'Virement bancaire','cash'=>'Espèces','card'=>'Carte bancaire','check'=>'Chèque','other'=>'Autre'];
    @endphp

    {{--  En-tête réservation  --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-6"> <div class="flex"> <div class="w-2 shrink-0 {{ $statusBar[$reservation->status] ?? 'bg-gray-300' }}"></div> <div class="flex-1 px-6 py-5"> <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4"> <div> <div class="flex items-center gap-3 flex-wrap"> <span class="font-mono text-xl font-extrabold text-gray-900 tracking-tight">{{ $reservation->reference }}</span> <span class="text-sm font-semibold px-3 py-1 rounded-full {{ $statusColors[$reservation->status] ?? 'bg-gray-100 text-gray-600' }}"> {{ $reservation->status_label }}
                            </span> @if($hasPending)
                            <span class="text-xs bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1 rounded-full font-medium flex items-center gap-1"> <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Paiement en attente
                            </span> @endif
                        </div> <p class="text-sm text-gray-400 mt-1.5">Créée le {{ $reservation->created_at->format('d/m/Y à H:i') }}</p> @if($reservation->status === 'refused' && $reservation->refusal_reason)
                        <p class="text-sm text-red-600 mt-2 bg-red-50 rounded-lg px-3 py-2"> Motif de refus : {{ $reservation->refusal_reason }}</p>
                        @if($reservation->refused_with_suggestion && ! $reservation->suggestion_copied)
                        <p class="text-sm text-amber-700 mt-2 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Utilisez le bouton <strong>Copier et modifier</strong> pour soumettre une nouvelle demande basée sur celle-ci.
                        </p>
                        @endif @endif
                    </div> <div class="flex flex-wrap gap-2 shrink-0"> @if($canPay && !$hasSchedules)
                        <button @click="payModal = { show: true, scheduleId: null, amount: {{ $remaining }}, label: 'Paiement libre' }"
                                class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors shadow-sm"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Soumettre un paiement
                        </button> @endif
                        @if($canEdit)
                        <a href="{{ route('agency.portal.edit-reservation', $reservation) }}"
                           class="inline-flex items-center gap-2 bg-slate-700 hover:bg-slate-800 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors shadow-sm"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.772-8.772z"/></svg> Modifier
                        </a>
                        @else
                        @php
                            $modifiableStatuses = ['draft','pending','accepted','waiting_payment','partially_paid','modification_pending'];
                            $_ciShow   = $reservation->check_in instanceof \Carbon\Carbon ? $reservation->check_in : \Carbon\Carbon::parse($reservation->check_in);
                            $_daysShow = (int) now()->startOfDay()->diffInDays($_ciShow->copy()->startOfDay(), false);
                            $blockedBy7Show = in_array($reservation->status, $modifiableStatuses) && $_daysShow < 7 && $_daysShow >= 0;
                        @endphp
                        @if($blockedBy7Show)
                        <span class="inline-flex items-center gap-2 bg-orange-50 text-orange-600 border border-orange-200 text-sm font-semibold px-4 py-2.5 rounded-xl cursor-default">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Modification verrouillée
                        </span>
                        @endif
                        @endif

                        {{-- Bouton Annuler (immédiat pour En attente et En attente de paiement) --}}
                        @if(in_array($reservation->status, ['pending', 'waiting_payment']))
                        <form id="cancel-reservation-form" action="{{ route('agency.portal.cancel-reservation', $reservation) }}" method="POST">
                            @csrf @method('PATCH')
                        </form>
                        <button type="button" @click="cancelModal = true"
                                class="inline-flex items-center gap-2 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Annuler la réservation
                        </button>
                        @endif

                        {{-- Bouton Copier et modifier (refus avec suggestion, usage unique) --}}
                        @if($reservation->status === 'refused' && $reservation->refused_with_suggestion)
                            @if(! $reservation->suggestion_copied)
                            <form action="{{ route('agency.portal.duplicate-reservation', $reservation) }}" method="POST"
                                  onsubmit="return confirm('Créer une nouvelle demande basée sur {{ $reservation->reference }} ?')">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    Copier et modifier
                                </button>
                            </form>
                            @else
                            <span class="inline-flex items-center gap-2 bg-gray-100 text-gray-400 text-sm font-medium px-4 py-2.5 rounded-xl cursor-default">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Déjà copié
                            </span>
                            @endif
                        @endif
                    </div> </div>
                @if($blockedBy7Show ?? false)
                <div class="mt-3 bg-orange-50 border border-orange-200 rounded-xl px-4 py-3 flex items-start gap-3">
                    <svg class="w-5 h-5 text-orange-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-orange-800">Modification non disponible</p>
                        <p class="text-xs text-orange-700 mt-0.5">
                            L'arrivée est prévue dans <strong>{{ $_daysShow }} jour{{ $_daysShow > 1 ? 's' : '' }}</strong>.
                            Les modifications ne sont plus acceptées à moins de 7 jours avant la date d'arrivée.
                            Pour toute demande, veuillez contacter directement l'hôtel.
                        </p>
                    </div>
                </div>
                @endif
                {{-- Infos hôtel + dates --}}
                <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 border-t border-gray-100"> <div> <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Hôtel</p> <p class="text-sm font-bold text-gray-900">{{ $reservation->hotel->name }}</p> @if($reservation->hotel->meal_plan)
                        <span class="inline-block text-xs font-semibold px-2 py-0.5 rounded-full mt-1
                            @if($reservation->hotel->meal_plan === 'all_inclusive') bg-emerald-100 text-emerald-700
                            @elseif($reservation->hotel->meal_plan === 'full_board') bg-blue-100 text-blue-700
                            @elseif($reservation->hotel->meal_plan === 'half_board') bg-indigo-100 text-indigo-700
                            @else bg-gray-100 text-gray-600 @endif"> {{ $reservation->hotel->meal_plan_label }}
                        </span> @endif
                    </div> <div> <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Séjour</p> <p class="text-sm font-semibold text-gray-800"> {{ $reservation->check_in->format('d/m/Y') }}  {{ $reservation->check_out->format('d/m/Y') }}
                        </p> <p class="text-xs text-gray-500 mt-0.5">{{ $reservation->nights }} nuit{{ $reservation->nights > 1 ? 's' : '' }}</p> </div> <div> @php $totalRoomsShow = $reservation->rooms->sum(fn($r) => max(1, (int)($r->quantity ?? 1))); @endphp <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Chambres</p> <p class="text-sm font-semibold text-gray-800">{{ $totalRoomsShow }} chambre{{ $totalRoomsShow > 1 ? 's' : '' }}</p> </div> <div> <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Voyageurs</p> <p class="text-sm font-semibold text-gray-800">{{ $totalPersons }} personne{{ $totalPersons > 1 ? 's' : '' }}</p> @php
                            $pAdults   = $reservation->rooms->sum(fn($r) => ($r->adults   ?? 0) * max(1, $r->quantity ?? 1));
                            $pChildren = $reservation->rooms->sum(fn($r) => ($r->children ?? 0) * max(1, $r->quantity ?? 1));
                            $pBabies   = $reservation->rooms->sum(fn($r) => ($r->babies   ?? 0) * max(1, $r->quantity ?? 1));
                        @endphp
                        <p class="text-xs text-gray-400 mt-0.5"> @if($pAdults){{ $pAdults }} adulte{{ $pAdults > 1 ? 's' : '' }}@endif
                            @if($pChildren) · {{ $pChildren }} enfant{{ $pChildren > 1 ? 's' : '' }}@endif
                            @if($pBabies) · {{ $pBabies }} bébé{{ $pBabies > 1 ? 's' : '' }}@endif
                        </p> </div> </div> </div> </div>
                {{-- Personne responsable --}}
                @if($reservation->contact_name || $reservation->phone)
                <div class="mt-4 pt-4 pb-4 px-4 border-t border-gray-100 flex flex-wrap gap-6">
                    @if($reservation->contact_name)
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Responsable</p>
                        <p class="text-sm font-semibold text-gray-800">{{ $reservation->contact_name }}</p>
                    </div>
                    @endif
                    @if($reservation->phone)
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Téléphone</p>
                        <p class="text-sm font-semibold text-gray-800">{{ $reservation->phone }}</p>
                    </div>
                    @endif
                </div>
                @endif
                </div> </div> <div class="grid grid-cols-1 lg:grid-cols-3 gap-6"> {{--  Colonne gauche (2/3)  --}}
        <div class="lg:col-span-2 space-y-6"> {{-- Séjours & chambres --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden"> <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3"> <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center"> <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg> </div> <h2 class="font-bold text-gray-900">Détail des séjours</h2> </div> <div class="p-5 space-y-5"> @php
                    $stayGroups  = $reservation->rooms->groupBy(fn($r) => ($r->check_in?->format('Y-m-d') ?? 'x') . '_' .
                        ($r->check_out?->format('Y-m-d') ?? 'x')
                    );
                    $multiStay   = $stayGroups->count() > 1;
                    $stayCounter = 0;
                @endphp
                @foreach($stayGroups as $stayRooms)
                @php
                    $stayCounter++;
                    $stayFirst   = $stayRooms->first();
                    $stayCheckIn = $stayFirst->check_in  ?? $reservation->check_in;
                    $stayCheckOut= $stayFirst->check_out ?? $reservation->check_out;
                    $stayNights  = ($stayCheckIn && $stayCheckOut) ? (int)$stayCheckIn->diffInDays($stayCheckOut) : $reservation->nights;
                    $stayAdults  = $stayRooms->sum(fn($r) => ($r->adults   ?? 0) * max(1, $r->quantity ?? 1));
                    $stayPersons = $stayRooms->sum(fn($r) => (($r->adults ?? 0) + ($r->children ?? 0) + ($r->babies ?? 0)) * max(1, $r->quantity ?? 1));
                    $stayTotal   = $stayRooms->sum(fn($r) => $r->total_price ?? 0);
                @endphp
                <div class="{{ $multiStay ? 'bg-gray-50 border border-gray-200 rounded-xl p-4' : '' }}"> @if($multiStay)
                    <div class="flex items-center gap-3 mb-3"> <span class="text-xs font-bold text-amber-700 bg-amber-100 px-2.5 py-1 rounded-full uppercase tracking-wide">Séjour {{ $stayCounter }}</span> <div class="text-xs text-gray-500 flex items-center gap-3"> <span> {{ $stayCheckIn->format('d/m/Y') }}  {{ $stayCheckOut->format('d/m/Y') }}</span> <span> {{ $stayNights }} nuit{{ $stayNights > 1 ? 's' : '' }}</span> <span> {{ $stayPersons }} pers.</span> </div> </div> @endif

                    <table class="w-full text-sm"> <thead> <tr class="border-b border-gray-100"> <th class="pb-2 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Chambre</th> <th class="pb-2 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Occupation</th> <th class="pb-2 text-right text-xs font-semibold text-gray-400 uppercase tracking-wide">Prix/nuit</th> <th class="pb-2 text-right text-xs font-semibold text-gray-400 uppercase tracking-wide">Total</th> </tr> </thead> <tbody class="divide-y divide-gray-50"> @foreach($stayRooms as $room)
                        @php
                            $rNights   = $stayNights;
                            $rParts    = [];
                            if ($room->adults)   $rParts[] = $room->adults   . ' adulte'  . ($room->adults   > 1 ? 's' : '');
                            if ($room->children) $rParts[] = $room->children . ' enfant'  . ($room->children > 1 ? 's' : '');
                            if ($room->babies)   $rParts[] = $room->babies   . ' bébé'    . ($room->babies   > 1 ? 's' : '');

                            // Prix après réduction (même logique que le proforma PDF)
                            $discountPromo  = (float)($reservation->promo_discount_amount ?? 0);
                            $sPromoRate     = ($discountPromo > 0) ? (float)$reservation->hotel->getPromoRate($stayNights) : 0.0;
                            $priceOriginal  = (float)($room->price_per_night ?? 0);
                            // Fallback si price_per_night non renseigné
                            if ($priceOriginal <= 0 && $room->total_price && ($room->quantity ?? 1) > 0 && $rNights > 0) {
                                $priceOriginal = round($room->total_price / (($room->quantity ?? 1) * $rNights), 2);
                                // Inverser la réduction pour retrouver le prix original
                                if ($sPromoRate > 0) {
                                    $priceOriginal = round($priceOriginal / (1 - $sPromoRate / 100), 2);
                                }
                            }
                            $priceReduced   = ($sPromoRate > 0 && $priceOriginal > 0)
                                ? round($priceOriginal * (1 - $sPromoRate / 100), 2)
                                : $priceOriginal;
                            $hasPromo       = $sPromoRate > 0 && $priceOriginal > 0 && $priceReduced < $priceOriginal;
                        @endphp
                        <tr>
                            <td class="py-2.5 font-medium text-gray-900">
                                {{ $room->quantity }}× {{ $room->roomType->name }}
                                <span class="text-xs text-gray-400 font-normal">× {{ $rNights }} nuit{{ $rNights > 1 ? 's' : '' }}</span>
                            </td>
                            <td class="py-2.5 text-gray-600 text-xs">{{ implode(' + ', $rParts) ?: '' }}</td>
                            <td class="py-2.5 text-right">
                                @if($hasPromo)
                                    <span class="line-through text-gray-400 text-xs block">{{ number_format($priceOriginal, 2, ',', ' ') }} MAD</span>
                                    <span class="font-semibold text-emerald-700">{{ number_format($priceReduced, 2, ',', ' ') }} MAD</span>
                                @elseif($priceOriginal > 0)
                                    <span class="text-gray-600">{{ number_format($priceOriginal, 2, ',', ' ') }} MAD</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="py-2.5 text-right font-semibold text-gray-900">
                                {{ $room->total_price ? number_format($room->total_price, 2, ',', ' ') . ' MAD' : '' }}
                            </td>
                        </tr>
                        @endforeach
                        </tbody> @if($taxeTotal > 0)
                        <tfoot> @if($multiStay && count($taxeLines))
                            @foreach($taxeLines as $tl)
                            <tr class="border-t border-blue-100"> <td colspan="3" class="pt-2 text-xs text-blue-700"> Taxe de séjour ({{ $tl['adults'] }} adulte{{ $tl['adults'] > 1 ? 's' : '' }} × {{ $tl['nights'] }} nuit{{ $tl['nights'] > 1 ? 's' : '' }} × {{ number_format($taxeRate, 2, ',', ' ') }} DHS)
                                </td> <td class="pt-2 text-right text-xs font-semibold text-blue-700">{{ number_format($tl['sub'], 2, ',', ' ') }} MAD</td> </tr> @endforeach
                            @endif
                        </tfoot> @endif
                    </table> </div> @endforeach

                {{-- Taxe ligne unique (séjour simple) --}}
                @if($taxeTotal > 0 && !$multiStay)
                @php $resAdults = $reservation->rooms->sum(fn($r) => ($r->adults ?? 0) * max(1, $r->quantity ?? 1)); @endphp
                <div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 flex justify-between text-sm text-blue-700"> <span> Taxe de séjour ({{ $resAdults }} adulte{{ $resAdults > 1 ? 's' : '' }} × {{ $reservation->nights }} nuit{{ $reservation->nights > 1 ? 's' : '' }} × {{ number_format($taxeRate, 2, ',', ' ') }} DHS)</span> <span class="font-semibold">{{ number_format($taxeTotal, 2, ',', ' ') }} MAD</span> </div> @endif

                </div> </div> {{-- Suppléments obligatoires --}}
            @if($mandatorySupps->isNotEmpty())
            <div class="bg-white rounded-2xl border border-orange-200 shadow-sm overflow-hidden"> <div class="px-5 py-4 border-b border-orange-100 flex items-center gap-3"> <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center"> <span class="text-base"></span> </div> <h2 class="font-bold text-orange-800">Suppléments inclus (obligatoires)</h2> </div> <div class="p-5 space-y-3"> @foreach($mandatorySupps as $ms)
                @php
                    $s = $ms->supplement;
                    $dateLabel = $s ? (
                        $s->date_from && $s->date_to && $s->date_from->ne($s->date_to)
                            ? $s->date_from->format('d/m') . '' . $s->date_to->format('d/m/Y')
                            : ($s->date_from ? $s->date_from->format('d/m/Y') : '')
                    ) : '';
                @endphp
                <div class="flex items-start justify-between gap-4 bg-orange-50 rounded-xl px-4 py-3"> <div> <p class="text-sm font-semibold text-orange-900">{{ $s?->title ?? 'Supplément' }}</p> @if($dateLabel)<p class="text-xs text-orange-400 mt-0.5"> {{ $dateLabel }}</p>@endif
                        <p class="text-xs text-orange-500 mt-1 space-x-2"> @if($ms->adults_count > 0 && $ms->unit_price_adult > 0)<span>{{ $ms->adults_count }} adulte(s) × {{ number_format($ms->unit_price_adult, 0, ',', ' ') }} MAD</span>@endif
                            @if($ms->children_count > 0 && $ms->unit_price_child > 0)<span>· {{ $ms->children_count }} enf. × {{ number_format($ms->unit_price_child, 0, ',', ' ') }} MAD</span>@endif
                            @if($ms->babies_count > 0 && $ms->unit_price_baby > 0)<span>· {{ $ms->babies_count }} bébé(s) × {{ number_format($ms->unit_price_baby, 0, ',', ' ') }} MAD</span>@endif
                        </p> </div> <span class="text-sm font-bold text-orange-700 shrink-0 whitespace-nowrap">{{ number_format($ms->total_price, 2, ',', ' ') }} MAD</span> </div> @endforeach
                </div> </div> @endif

            {{-- Suppléments optionnels --}}
            @if($optionalSupps->isNotEmpty())
            <div class="bg-white rounded-2xl border border-purple-200 shadow-sm overflow-hidden"> <div class="px-5 py-4 border-b border-purple-100 flex items-center gap-3"> <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center"> <span class="text-base"></span> </div> <h2 class="font-bold text-purple-800">Suppléments optionnels</h2> </div> <div class="p-5 space-y-3"> @foreach($optionalSupps as $os)
                @php
                    $s = $os->supplement;
                    $dateLabel = $s ? (
                        $s->date_from && $s->date_to && $s->date_from->ne($s->date_to)
                            ? $s->date_from->format('d/m') . '' . $s->date_to->format('d/m/Y')
                            : ($s->date_from ? $s->date_from->format('d/m/Y') : '')
                    ) : '';
                @endphp
                <div class="flex items-start justify-between gap-4 bg-purple-50 rounded-xl px-4 py-3"> <div> <p class="text-sm font-semibold text-purple-900">{{ $s?->title ?? 'Supplément' }}</p> @if($dateLabel)<p class="text-xs text-purple-400 mt-0.5"> {{ $dateLabel }}</p>@endif
                        <p class="text-xs text-purple-500 mt-1 space-x-2"> @if($os->adults_count > 0 && $os->unit_price_adult > 0)<span>{{ $os->adults_count }} adulte(s) × {{ number_format($os->unit_price_adult, 0, ',', ' ') }} MAD</span>@endif
                            @if($os->children_count > 0 && $os->unit_price_child > 0)<span>· {{ $os->children_count }} enf. × {{ number_format($os->unit_price_child, 0, ',', ' ') }} MAD</span>@endif
                            @if($os->babies_count > 0 && $os->unit_price_baby > 0)<span>· {{ $os->babies_count }} bébé(s) × {{ number_format($os->unit_price_baby, 0, ',', ' ') }} MAD</span>@endif
                        </p> </div> <span class="text-sm font-bold text-purple-700 shrink-0 whitespace-nowrap">{{ number_format($os->total_price, 2, ',', ' ') }} MAD</span> </div> @endforeach
                </div> </div> @endif

            {{-- Services Extras --}}
            @if($reservation->extras->isNotEmpty())
            <div class="bg-white rounded-2xl border border-amber-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-amber-100 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                    <h2 class="font-bold text-amber-800">Services Extras</h2>
                </div>
                <div class="p-5 divide-y divide-gray-100 space-y-0">
                    @foreach($reservation->extras as $extra)
                    <div class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900">{{ $extra->name }}</p>
                            @if($extra->description)
                            <p class="text-xs text-gray-400 mt-0.5">{{ $extra->description }}</p>
                            @endif
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $extra->quantity }} × {{ number_format($extra->unit_price, 2, ',', ' ') }} MAD
                            </p>
                        </div>
                        <span class="text-sm font-bold text-amber-700 shrink-0 whitespace-nowrap">
                            {{ number_format($extra->total_price, 2, ',', ' ') }} MAD
                        </span>
                    </div>
                    @endforeach
                    @if($reservation->extras->count() > 1)
                    <div class="flex justify-between items-center pt-3 text-sm font-semibold text-amber-700">
                        <span>Total services extras</span>
                        <span>{{ number_format($extrasTotal, 2, ',', ' ') }} MAD</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Fiche de police --}}
            @php
                $guests        = $reservation->guestRegistrations ?? collect();
                $totalSlots    = $reservation->rooms->sum(fn($r) => (($r->adults ?? 0) + ($r->children ?? 0) + ($r->babies ?? 0)) * max(1, $r->quantity ?? 1));
                $filledGuests  = $guests->filter(fn($g) => $g->isComplete())->count();
                $draftGuests   = $guests->filter(fn($g) => !$g->isComplete())->count();
                $policeAllowed = in_array($reservation->status, ['partially_paid', 'paid', 'confirmed']);
            @endphp
            @if($totalSlots > 0)
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden"> <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between"> <div class="flex items-center gap-3"> <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center"> <span class="text-base">📋</span> </div> <h2 class="font-bold text-gray-900">Fiche de police</h2> </div> <div class="flex items-center gap-3">
                        @if($policeAllowed)
                        <div class="flex items-center gap-2">
                            @if($filledGuests > 0)
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">
                                ✅ {{ $filledGuests }} terminé{{ $filledGuests > 1 ? 's' : '' }}
                            </span>
                            @endif
                            @if($draftGuests > 0)
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-700">
                                🔶 {{ $draftGuests }} brouillon{{ $draftGuests > 1 ? 's' : '' }}
                            </span>
                            @endif
                            @if($guests->count() < $totalSlots)
                            <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-500">
                                {{ $totalSlots - $guests->count() }} non saisi{{ ($totalSlots - $guests->count()) > 1 ? 's' : '' }}
                            </span>
                            @endif
                        </div>
                        <a href="{{ route('agency.portal.guest-form', $reservation) }}"
                           class="inline-flex items-center gap-1.5 text-xs font-semibold bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-lg transition-colors"> <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.772-8.772z"/></svg> {{ $filledGuests > 0 ? 'Modifier' : 'Remplir' }}
                        </a>
                        @else
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-400 bg-gray-50 border border-gray-200 px-3 py-1.5 rounded-lg">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Disponible après confirmation
                        </span>
                        @endif
                        </div> </div> <div class="p-5"> @if($guests->isEmpty())
                    <p class="text-sm text-gray-400 italic">Aucune fiche remplie pour le moment.</p> @else
                    <div class="overflow-x-auto rounded-xl border border-gray-200"> <table style="border-collapse:collapse; width:100%; font-size:12px;"> <thead> <tr style="background:#f9fafb; border-bottom:1px solid #e5e7eb;"> <th style="padding:8px 12px; text-align:left; font-weight:600; color:#6b7280;">#</th> <th style="padding:8px 12px; text-align:left; font-weight:600; color:#6b7280;">Nom & Prénom</th> <th style="padding:8px 12px; text-align:left; font-weight:600; color:#6b7280;">Naissance</th> <th style="padding:8px 12px; text-align:left; font-weight:600; color:#6b7280;">Nationalité</th> <th style="padding:8px 12px; text-align:left; font-weight:600; color:#6b7280;">Document</th> <th style="padding:8px 12px; text-align:left; font-weight:600; color:#6b7280;">N°</th> <th style="padding:8px 12px; text-align:center; font-weight:600; color:#6b7280;">Statut</th> </tr> </thead> <tbody> @foreach($guests->sortBy('guest_index') as $g)
                        @php
                            $isOk = $g->isComplete();
                            $typeIcon = $g->guest_type === 'adult' ? '' : ($g->guest_type === 'child' ? '' : '');
                            $docLabels = ['passeport'=>'Passeport','cni'=>'CNI','titre_sejour'=>'Titre séjour','autre'=>'Autre'];
                        @endphp
                        <tr style="background:{{ $isOk ? '#f0fdf4' : '#fffbeb' }}; border-bottom:1px solid #f3f4f6;"> <td style="padding:8px 12px; color:#6b7280; white-space:nowrap;">{{ $typeIcon }} {{ $g->guest_type === 'adult' ? 'Adulte' : ($g->guest_type === 'child' ? 'Enfant' : 'Bébé') }} {{ $g->guest_index + 1 }}</td> <td style="padding:8px 12px; font-weight:600; color:#111827;">{{ $g->civilite ? $g->civilite . ' ' : '' }}{{ strtoupper($g->nom ?? '') }} {{ $g->prenom ?? '' }}</td> <td style="padding:8px 12px; color:#374151;">{{ $g->date_naissance?->format('d/m/Y') ?? '' }}</td> <td style="padding:8px 12px; color:#374151;">{{ $g->nationalite ?? '' }}</td> <td style="padding:8px 12px; color:#374151;">{{ $docLabels[$g->type_document ?? ''] ?? ($g->type_document ?? '') }}</td> <td style="padding:8px 12px; color:#374151; font-family:monospace;">{{ strtoupper($g->numero_document ?? '') }}</td> <td style="padding:8px 12px; text-align:center;"> @if($isOk)
                                    <span style="background:#dcfce7; color:#15803d; font-size:10px; font-weight:700; padding:2px 8px; border-radius:999px;">✅ Terminé</span> @else
                                    <span style="background:#fef9c3; color:#a16207; font-size:10px; font-weight:600; padding:2px 8px; border-radius:999px;">🔶 Brouillon</span> @endif
                            </td> </tr> @endforeach
                        </tbody> </table> </div> @endif
                </div> </div> @endif

        </div>{{-- /col gauche --}}

        {{--  Colonne droite (1/3)  --}}
        <div class="space-y-6"> {{-- Récapitulatif financier --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden"> <div class="px-5 py-4 border-b border-gray-100"> <h2 class="font-bold text-gray-900"> Récapitulatif</h2> </div> @php
                    $promoDiscountAmt  = (float) ($reservation->promo_discount_amount ?? 0);
                    // Hébergement brut = prix chambres après grille tarifaire, AVANT remise long séjour
                    $roomsGross = round($roomsTotal + $promoDiscountAmt, 2);
                @endphp
                <div class="p-5 space-y-2.5 text-sm"> @if($roomsGross > 0)
                    <div class="flex justify-between"> <span class="text-gray-500">Hébergement</span> <span class="font-semibold text-gray-900">{{ number_format($roomsGross, 2, ',', ' ') }} MAD</span> </div> @endif
                    @if($promoDiscountAmt > 0)
                    <div class="flex justify-between items-center"> <span class="text-emerald-700 flex items-center gap-1"> <span></span> <span>Remise long séjour</span> </span> <span class="font-semibold text-emerald-700"> {{ number_format($promoDiscountAmt, 2, ',', ' ') }} MAD</span> </div> @endif
                    @if($suppTotal > 0)
                    <div class="flex justify-between"> <span class="text-gray-500">Suppléments</span> <span class="font-semibold text-amber-700">+ {{ number_format($suppTotal, 2, ',', ' ') }} MAD</span> </div> @endif
                    @if($extrasTotal > 0)
                    <div class="flex justify-between"> <span class="text-gray-500">Services extras</span> <span class="font-semibold text-amber-700">+ {{ number_format($extrasTotal, 2, ',', ' ') }} MAD</span> </div> @endif
                    @if($taxeTotal > 0)
                    <div class="flex justify-between"> <span class="text-gray-500">Taxe de séjour</span> <span class="font-semibold text-blue-700">{{ number_format($taxeTotal, 2, ',', ' ') }} MAD</span> </div> @endif
                    <div class="flex justify-between items-center pt-3 border-t-2 border-gray-200 mt-1"> <span class="font-bold text-gray-900">TOTAL</span> <span class="text-lg font-extrabold text-amber-600">{{ number_format($grandTotal, 2, ',', ' ') }} MAD</span> </div> </div> {{-- Barre de progression --}}
                <div class="px-5 pb-5"> <div class="flex justify-between text-xs text-gray-500 mb-2"> <span>Payé : <strong class="text-gray-800">{{ number_format($amountPaid, 2, ',', ' ') }} MAD</strong> @if($hasPending)
                            <span class="text-amber-600 ml-1">(+ {{ number_format($pendingPay, 2, ',', ' ') }} en attente)</span> @endif
                        </span> <span class="font-bold {{ $pct >= 100 ? 'text-emerald-600' : ($pct > 0 ? 'text-amber-600' : 'text-gray-400') }}">{{ $pct }}%</span> </div> <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden"> <div class="h-3 rounded-full transition-all {{ $pct >= 100 ? 'bg-emerald-500' : 'bg-amber-400' }}"
                             style="width: {{ $pct }}%"></div> </div> @if($remaining > 0)
                    <p class="text-xs text-orange-600 font-medium mt-2">Reste à payer : {{ number_format($remaining, 2, ',', ' ') }} MAD</p> @elseif($pct >= 100)
                    <p class="text-xs text-emerald-600 font-semibold mt-2"> Intégralement réglé</p> @endif
                </div> </div> {{-- Échéancier --}}
            @if($hasSchedules)
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden"> <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3"> <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center"> <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg> </div> <h2 class="font-bold text-gray-900">Échéancier</h2> </div> <div class="p-4 space-y-3"> @php
                    // Première échéance impayée (par date) : seule celle-ci peut être réglée.
                    // Le paiement suivant n'est accessible qu'après validation du précédent.
                    $firstUnpaidId = $reservation->paymentSchedules
                        ->sortBy('due_date')
                        ->first(fn($s) => $s->computed_status !== 'paid' && !($s->payment && $s->payment->status === 'pending'))
                        ?->id;
                @endphp
                @foreach($reservation->paymentSchedules->sortBy('due_date') as $sch)
                @php
                    $schStatus  = $sch->computed_status;
                    $schPending = $sch->payment && $sch->payment->status === 'pending';
                    // Payer uniquement la prochaine échéance impayée (séquentiel par date)
                    $schCanPay  = $canPay && $sch->id === $firstUnpaidId;
                @endphp
                <div class="flex items-center justify-between gap-3 rounded-xl px-4 py-3
                    {{ $schStatus === 'paid' ? 'bg-emerald-50 border border-emerald-200' :
                       ($schStatus === 'overdue' ? 'bg-red-50 border border-red-200' :
                        ($schPending ? 'bg-amber-50 border border-amber-200' : 'bg-gray-50 border border-gray-200')) }}"> <div class="flex-1 min-w-0"> <p class="text-sm font-bold {{ $schStatus === 'paid' ? 'text-emerald-700' : ($schStatus === 'overdue' ? 'text-red-700' : 'text-gray-800') }}"> {{ number_format($sch->amount, 2, ',', ' ') }} MAD
                        </p> <p class="text-xs text-gray-500 mt-0.5"> {{ $sch->due_date->format('d/m/Y') }}
                            @if($sch->label) · {{ $sch->label }}@endif
                        </p> @if($schPending)<p class="text-xs text-amber-600 font-medium mt-0.5"> En attente de validation</p>@endif
                    </div> <div class="flex items-center gap-2 shrink-0"> @if($schStatus === 'paid')
                            <span class="text-xs font-bold text-emerald-600 bg-emerald-100 px-2 py-1 rounded-lg"> Réglée</span> @elseif($schStatus === 'overdue')
                            <span class="text-xs font-bold text-red-600 bg-red-100 px-2 py-1 rounded-lg"> En retard</span> @elseif($schPending)
                            <span class="text-xs font-bold text-amber-600 bg-amber-100 px-2 py-1 rounded-lg">En attente</span> @else
                            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-lg">À payer</span> @endif
                        @if($schCanPay)
                        <button type="button"
                            @click="payModal = { show: true, scheduleId: {{ $sch->id }}, amount: {{ $sch->amount }}, label: 'Échéance du {{ $sch->due_date->format('d/m/Y') }}' }"
                            class="text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-lg"> Payer
                        </button> @endif
                    </div> </div> @endforeach
                </div> </div> @endif

            {{-- Historique paiements --}}
            @if($reservation->payments->count() > 0)
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden"> <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3"> <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center"> <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg> </div> <h2 class="font-bold text-gray-900">Paiements</h2> </div> <div class="divide-y divide-gray-100"> @foreach($reservation->payments->sortByDesc('created_at') as $pay)
                <div class="px-4 py-3 {{ $pay->status === 'completed' ? 'bg-emerald-50/60' : ($pay->status === 'pending' ? 'bg-amber-50/60' : 'bg-red-50/60') }}"> <div class="flex items-center justify-between gap-3"> <div class="flex items-center gap-2 min-w-0"> <span class="w-2 h-2 rounded-full shrink-0 {{ $pay->status === 'completed' ? 'bg-emerald-500' : ($pay->status === 'pending' ? 'bg-amber-400' : 'bg-red-400') }}"></span> <span class="text-sm font-semibold text-gray-800 truncate">{{ $methods[$pay->method] ?? $pay->method }}</span> </div> <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full shrink-0 {{ $pay->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($pay->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}"> {{ $pay->status === 'completed' ? ' Validé' : ($pay->status === 'pending' ? ' En attente' : ' Refusé') }}
                        </span> </div> <div class="flex items-center justify-between mt-1.5 pl-4"> <p class="text-xs text-gray-400"> {{ $pay->created_at->format('d/m/Y') }}
                            @if($pay->reference) · <span class="font-mono">{{ $pay->reference }}</span>@endif
                            @if($pay->proof_path) · <a href="{{ \Illuminate\Support\Facades\Storage::url($pay->proof_path) }}" target="_blank" class="text-blue-500 hover:underline"> Preuve</a>@endif
                        </p> <span class="text-sm font-extrabold text-gray-900 shrink-0">{{ number_format($pay->amount, 2, ',', ' ') }} MAD</span> </div> </div> @endforeach
                </div> </div> @endif

        </div>{{-- /col droite --}}

    </div>{{-- /grid --}}

</main> {{-- Modal paiement --}}
<div x-show="payModal.show" x-transition
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4"
     @click.self="payModal.show = false"> <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.stop> <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100"> <h3 class="font-bold text-gray-900" x-text="payModal.label"></h3> <button @click="payModal.show = false" class="text-gray-400 hover:text-gray-600 p-1"> <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> </button> </div> <form action="{{ route('agency.portal.pay', $reservation) }}" method="POST" enctype="multipart/form-data"> @csrf
            <input type="hidden" name="payment_schedule_id" :value="payModal.scheduleId || ''"> <div class="px-6 py-5 space-y-4"> <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm text-amber-800"> <span>Montant : </span> <strong x-text="new Intl.NumberFormat('fr-FR', {minimumFractionDigits:2,maximumFractionDigits:2}).format(payModal.amount ?? 0) + ' MAD'"></strong> <span x-show="!payModal.scheduleId" class="text-amber-600 text-xs ml-1">(modifiable ci-dessous)</span> </div> {{-- Paiement libre : champ montant modifiable --}}
                <div x-show="!payModal.scheduleId"> <label class="block text-xs font-medium text-gray-700 mb-1">Montant (MAD) <span class="text-red-500">*</span></label> <input type="number" name="amount" :value="payModal.amount" min="1" step="0.01" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> {{-- Échéance : montant fixe --}}
                <input x-show="payModal.scheduleId" type="hidden" name="amount" :value="payModal.amount"> <div class="grid grid-cols-2 gap-3"> <div> <label class="block text-xs font-medium text-gray-700 mb-1">Mode de paiement</label> <select name="method" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none bg-white"> <option value="bank_transfer">Virement bancaire</option> <option value="cash">Espèces</option> <option value="check">Chèque</option> <option value="card">Carte bancaire</option> <option value="other">Autre</option> </select> </div> <div> <label class="block text-xs font-medium text-gray-700 mb-1">Référence</label> <input type="text" name="reference" placeholder="Ex : VIR-2026-001"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> </div> <div> <label class="block text-xs font-medium text-gray-700 mb-1"> Preuve de paiement
                        <span class="text-gray-400 font-normal">(PDF, JPG, PNG · max 5 Mo)</span> </label> <input type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png"
                           class="w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-amber-100 file:text-amber-700 hover:file:bg-amber-200 cursor-pointer"> </div> </div> <div class="px-6 py-4 border-t border-gray-100 flex gap-3 justify-end"> <button type="button" @click="payModal.show = false"
                        class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg border border-gray-200"> Annuler
                </button> <button type="submit"
                        class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold px-5 py-2.5 rounded-xl"> Soumettre le paiement
                </button> </div> </form> </div>
</div>

{{-- Modal confirmation annulation --}}
<div x-show="cancelModal" x-transition
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4"
     @click.self="cancelModal = false"
     style="display:none;">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm" @click.stop>
        <div class="px-6 pt-6 pb-4 text-center">
            <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-1">Annuler la réservation ?</h3>
            <p class="text-sm text-gray-500 mb-1">Réservation <span class="font-semibold text-gray-700">{{ $reservation->reference }}</span></p>
            <p class="text-xs text-red-600 font-medium mt-3 bg-red-50 rounded-lg px-3 py-2 border border-red-100">
                ⚠️ Cette action est irréversible. La réservation sera définitivement annulée.
            </p>
        </div>
        <div class="px-6 pb-6 flex gap-3">
            <button type="button" @click="cancelModal = false"
                    class="flex-1 px-4 py-2.5 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                Retour
            </button>
            <button type="button" @click="document.getElementById('cancel-reservation-form').submit()"
                    class="flex-1 px-4 py-2.5 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors">
                Confirmer l'annulation
            </button>
        </div>
    </div>
</div>

</body>
</html>
