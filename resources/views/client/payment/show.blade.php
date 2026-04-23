@extends('layouts.client')
@section('title', 'Paiement  ' . $reservation->reference)

@section('content')
<div class="max-w-2xl mx-auto space-y-6"> {{-- En-tête --}}
    <div class="text-center"> <p class="text-4xl mb-3"></p> <h1 class="text-2xl font-bold text-gray-900">Paiement de votre réservation</h1> <p class="text-gray-500 mt-2">Référence : <strong class="font-mono text-amber-600">{{ $reservation->reference }}</strong></p> {{-- Date limite --}}
        @if($reservation->payment_deadline)
        <div class="mt-3 inline-flex items-center gap-2 text-sm
            {{ $reservation->payment_deadline->diffInDays(now(), false) < -3 ? 'text-amber-700' : 'text-red-700' }}
            bg-{{ $reservation->payment_deadline->diffInDays(now(), false) < -3 ? 'amber' : 'red' }}-50
            border border-{{ $reservation->payment_deadline->diffInDays(now(), false) < -3 ? 'amber' : 'red' }}-200
            px-4 py-1.5 rounded-full"> Date limite de paiement : <strong>{{ $reservation->payment_deadline->format('d/m/Y') }}</strong> <span class="text-xs opacity-75">(dans {{ now()->diffInDays($reservation->payment_deadline, false) }} jour(s))</span> </div> @endif
    </div> @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl px-5 py-3 text-sm text-green-700 flex items-center gap-2"> {{ session('success') }}
    </div> @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl px-5 py-3 text-sm text-red-700"> @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
    </div> @endif

    {{-- Récapitulatif --}}
    <div class="bg-white border border-gray-200 rounded-xl p-6"> <h2 class="text-base font-semibold mb-4">Récapitulatif</h2> <div class="grid grid-cols-2 gap-3 text-sm mb-4"> <div><span class="text-gray-500">Hôtel :</span> <strong>{{ $reservation->hotel->name }}</strong></div> <div><span class="text-gray-500">Arrivée :</span> {{ $reservation->check_in->format('d/m/Y') }}</div> <div><span class="text-gray-500">Départ :</span> {{ $reservation->check_out->format('d/m/Y') }}</div> <div><span class="text-gray-500">Durée :</span> {{ $reservation->nights }} nuits</div> </div> @foreach($reservation->rooms as $room)
        <div class="flex justify-between text-sm py-1.5 border-t border-gray-50"> <span>{{ $room->roomType->name }} × {{ $room->quantity }}</span> <span>{{ $room->total_price ? number_format($room->total_price, 0, ',', ' ') . ' MAD' : '' }}</span> </div> @endforeach
        <div class="flex justify-between text-lg font-bold pt-4 mt-2 border-t-2 border-gray-200"> <span>TOTAL</span> <span class="text-amber-600">{{ number_format($reservation->total_price, 2, ',', ' ') }} MAD</span> </div> </div> {{--  --}}
    {{-- MODE ÉCHÉANCIER (si des échéances sont définies)                       --}}
    {{--  --}}
    @if($schedules->isNotEmpty())

    <div class="bg-white border border-gray-200 rounded-xl p-6"> <h2 class="text-base font-semibold mb-1">Échéancier de paiement</h2> <p class="text-xs text-gray-400 mb-4"> Votre paiement est réparti en {{ $schedules->count() }} échéance(s).
 Pour chaque échéance, envoyez votre preuve de virement.
        </p> @php
            $amountPaid = $reservation->payments->where('status','completed')->sum('amount');
        @endphp

        {{-- Barre de progression globale --}}
        @if($amountPaid > 0)
        @php $pctPaid = min(100, round($amountPaid / $reservation->total_price * 100)); @endphp
        <div class="flex items-center gap-3 mb-5"> <div class="flex-1 bg-gray-100 rounded-full h-2"> <div class="h-2 rounded-full bg-emerald-400" style="width:{{ $pctPaid }}%"></div> </div> <span class="text-xs font-bold text-emerald-600">{{ $pctPaid }}% payé</span> </div> @endif

        <div class="space-y-4"> @foreach($schedules as $sch)
        @php
            $cs = $sch->computed_status;
            $isPaid      = $sch->isPaid();
            $hasPending  = $sch->hasPendingProof();
        @endphp

        <div class="rounded-xl border p-4
            {{ $isPaid ? 'bg-green-50 border-green-200' : ($cs === 'overdue' ? 'bg-red-50 border-red-200' : 'bg-white border-gray-200') }}"> {{-- En-tête échéance --}}
            <div class="flex items-start justify-between gap-2"> <div> <div class="flex items-center gap-2 flex-wrap"> <span class="text-xs font-bold text-gray-400">Échéance {{ $sch->installment_number }}</span> @if($sch->label)
                        <span class="text-sm font-semibold text-gray-800">{{ $sch->label }}</span> @endif
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $isPaid ? 'bg-green-100 text-green-700' : ($cs === 'overdue' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}"> {{ $isPaid ? ' Payée' : ($cs === 'overdue' ? ' En retard' : ' En attente') }}
                        </span> </div> <div class="mt-1 flex gap-4 text-sm"> <span class="text-gray-500"> Avant le <strong class="{{ $cs === 'overdue' && ! $isPaid ? 'text-red-600' : 'text-gray-700' }}">{{ $sch->due_date->format('d/m/Y') }}</strong> </span> <span class="font-bold text-amber-700">{{ number_format($sch->amount, 2, ',', ' ') }} MAD</span> </div> </div> </div> {{-- Statut preuve --}}
            @if($hasPending)
            <div class="mt-3 flex items-center gap-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 px-3 py-2 rounded-lg"> Preuve envoyée  en attente de validation par notre équipe.
            </div> @elseif($isPaid)
            <div class="mt-3 flex items-center gap-2 text-xs text-green-700"> Paiement validé
            </div> @else
            {{-- Formulaire upload preuve --}}
            <div class="mt-4 border-t border-gray-100 pt-4" x-data="{ open: false }"> <button type="button" @click="open = !open"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-amber-600 hover:text-amber-700
                           bg-amber-50 hover:bg-amber-100 border border-amber-200 px-4 py-2 rounded-lg transition-colors"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/> </svg> Envoyer ma preuve de paiement
                </button> <div x-show="open" x-transition class="mt-4"> <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4 text-sm text-amber-800"> <p class="font-semibold mb-2"> Instructions pour cette échéance</p> <p>Effectuez un virement de <strong>{{ number_format($sch->amount, 2, ',', ' ') }} MAD</strong> avec la référence <strong class="font-mono">{{ $reservation->reference }}-{{ $sch->installment_number }}</strong> puis uploadez votre preuve ci-dessous.</p> <div class="mt-2 space-y-1 text-xs"> <div class="flex justify-between"><span>Bénéficiaire</span> <strong>{{ config('magic.bank_details.beneficiary', 'Magic Hotels SARL') }}</strong></div> <div class="flex justify-between"><span>RIB</span> <strong class="font-mono">{{ config('magic.bank_details.rib', '') }}</strong></div> </div> </div> <form action="{{ route('client.payment.schedule.proof', [$reservation->payment_token, $sch]) }}"
                          method="POST" enctype="multipart/form-data" class="space-y-3"> @csrf
                        <div class="grid grid-cols-2 gap-3"> <div> <label class="block text-xs font-medium text-gray-600 mb-1">Mode de paiement *</label> <select name="method" required
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-amber-400 focus:outline-none"> <option value="bank_transfer">Virement bancaire</option> <option value="cash">Espèces</option> <option value="check">Chèque</option> <option value="card">Carte bancaire</option> <option value="other">Autre</option> </select> </div> <div> <label class="block text-xs font-medium text-gray-600 mb-1">N° de référence</label> <input type="text" name="reference" placeholder="Optionnel"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> </div> <div> <label class="block text-xs font-medium text-gray-600 mb-1">Pièce jointe (reçu / virement) *</label> <input type="file" name="proof" required accept=".pdf,.jpg,.jpeg,.png"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:bg-amber-50 file:text-amber-700"> <p class="text-xs text-gray-400 mt-1">PDF, JPG ou PNG  max 5 Mo</p> </div> <button type="submit"
                            class="w-full bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2.5 rounded-xl text-sm transition"> Envoyer la preuve
                        </button> </form> </div> </div> @endif

        </div> @endforeach
        </div> </div> {{--  --}}
    {{-- MODE SIMPLE (pas d'échéancier  comportement original)                 --}}
    {{--  --}}
    @else

    {{-- Instructions virement --}}
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6"> <h2 class="text-base font-semibold text-amber-900 mb-4"> Instructions de paiement</h2> <p class="text-sm text-amber-800 mb-4">Effectuez un virement bancaire avec les coordonnées suivantes :</p> <div class="bg-white rounded-lg p-4 space-y-2 text-sm border border-amber-200"> <div class="flex justify-between"> <span class="text-gray-500">Bénéficiaire</span> <strong>{{ $bankInfo['beneficiary'] ?? 'Magic Hotels SARL' }}</strong> </div> <div class="flex justify-between"> <span class="text-gray-500">Banque</span> <strong>{{ $bankInfo['bank'] ?? 'CIH Bank' }}</strong> </div> <div class="flex justify-between"> <span class="text-gray-500">RIB / IBAN</span> <strong class="font-mono">{{ $bankInfo['rib'] ?? '230 780 1234567890123456 78' }}</strong> </div> <div class="flex justify-between"> <span class="text-gray-500">Montant</span> <strong class="text-amber-700">{{ number_format($reservation->total_price, 2, ',', ' ') }} MAD</strong> </div> <div class="flex justify-between"> <span class="text-gray-500">Référence obligatoire</span> <strong class="font-mono text-amber-700">{{ $reservation->reference }}</strong> </div> </div> <p class="text-xs text-amber-600 mt-3"> Merci d'indiquer la référence <strong>{{ $reservation->reference }}</strong> dans le libellé de votre virement.
        </p> </div> @endif

    <div class="text-center"> <a href="{{ route('client.reservation.show', [$reservation->secureLink?->token, $reservation]) }}"
           class="text-sm text-amber-600 hover:underline"> Retour à ma réservation</a> </div> </div>
@endsection

@push('scripts')
<script>
// Alpine.js déjà chargé dans le layout
</script>
@endpush
