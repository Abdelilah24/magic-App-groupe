@extends('layouts.client')
@section('title', 'Paiement non disponible')

@section('content')
<div class="max-w-md mx-auto text-center py-16">
    <p class="text-5xl mb-4">🔒</p>
    <h1 class="text-xl font-bold text-gray-900 mb-3">Paiement non disponible</h1>
    <p class="text-gray-500 text-sm">
        Le paiement pour la réservation <strong>{{ $reservation->reference }}</strong> n'est pas disponible.<br>
        Statut actuel : <strong>{{ $reservation->status_label }}</strong>
    </p>
</div>
@endsection
