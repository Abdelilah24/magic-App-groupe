@extends('layouts.client')
@section('title', 'Lien invalide')

@section('content')
<div class="max-w-md mx-auto text-center py-16">
    <p class="text-6xl mb-4">🔒</p>
    <h1 class="text-2xl font-bold text-gray-900 mb-3">Lien invalide</h1>
    <p class="text-gray-500">{{ $reason }}</p>
    <p class="text-gray-400 text-sm mt-4">Veuillez contacter Magic Hotels pour obtenir un nouveau lien d'accès.</p>
    <a href="mailto:{{ config('magic.contact_email', 'reservations@magichotels.ma') }}"
       class="inline-block mt-6 text-amber-600 hover:underline text-sm">
        📧 Contacter Magic Hotels
    </a>
</div>
@endsection
