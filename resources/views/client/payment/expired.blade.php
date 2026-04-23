@extends('layouts.client')
@section('title', 'Lien expiré')

@section('content')
<div class="max-w-md mx-auto text-center py-16">
    <p class="text-5xl mb-4">⏰</p>
    <h1 class="text-xl font-bold text-gray-900 mb-3">Lien de paiement expiré</h1>
    <p class="text-gray-500 text-sm">Ce lien de paiement n'est plus valide. Veuillez contacter Magic Hotels pour obtenir un nouveau lien.</p>
    <p class="mt-4"><a href="mailto:{{ config('magic.contact_email') }}" class="text-amber-600 hover:underline">{{ config('magic.contact_email') }}</a></p>
    <p class="text-xs text-gray-400 mt-2">Réf. : {{ $reservation->reference }}</p>
</div>
@endsection
