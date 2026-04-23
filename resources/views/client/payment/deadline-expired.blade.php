@extends('layouts.client')
@section('title', 'Délai dépassé — ' . $reservation->reference)

@section('content')
<div class="max-w-lg mx-auto text-center space-y-6">

    <div class="bg-gray-100 border border-gray-200 rounded-2xl p-10 opacity-75">
        <p class="text-5xl mb-4 grayscale">⏰</p>
        <h1 class="text-2xl font-bold text-gray-500">Délai de paiement dépassé</h1>
        <p class="text-gray-400 mt-3 text-sm">
            La date limite de paiement pour la réservation
            <strong class="font-mono text-gray-500">{{ $reservation->reference }}</strong>
            était le
            <strong class="text-gray-500">{{ $reservation->payment_deadline->format('d/m/Y') }}</strong>.
        </p>
        <p class="text-gray-400 mt-2 text-sm">
            Ce devis n'est plus accessible. Veuillez contacter notre équipe pour toute question.
        </p>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl p-6 text-left opacity-60 pointer-events-none select-none" aria-hidden="true">
        <p class="text-sm font-semibold text-gray-400 mb-3">Aperçu (désactivé)</p>
        <div class="space-y-2 text-sm text-gray-300">
            <div class="flex justify-between">
                <span>Hôtel</span>
                <span class="font-medium">{{ $reservation->hotel->name }}</span>
            </div>
            <div class="flex justify-between">
                <span>Total</span>
                <span class="font-bold">{{ number_format($reservation->total_price, 2, ',', ' ') }} MAD</span>
            </div>
        </div>
    </div>

    <a href="{{ url('/') }}" class="text-sm text-gray-400 hover:text-gray-500 underline">← Retour à l'accueil</a>
</div>
@endsection
