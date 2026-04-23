@extends('layouts.client')
@section('title', 'Inscription envoyée')

@section('content')
<div class="max-w-md mx-auto text-center py-16"> <p class="text-6xl mb-4"></p> <h1 class="text-2xl font-bold text-gray-900 mb-3">Demande reçue !</h1> <p class="text-gray-600"> Merci <strong>{{ session('agency_name') }}</strong> !<br> Votre demande de partenariat a bien été enregistrée.
    </p> <div class="mt-6 bg-amber-50 border border-amber-200 rounded-xl p-5 text-left"> <p class="text-sm font-semibold text-amber-900 mb-2">Prochaines étapes :</p> <ol class="text-sm text-amber-800 space-y-2"> <li>1. Notre équipe examine votre dossier sous <strong>24h ouvrables</strong>.</li> <li>2. Vous recevrez un email de confirmation avec votre lien d'accès.</li> <li>3. Vous pourrez alors soumettre vos demandes de réservation groupe directement.</li> </ol> </div> <p class="mt-6 text-sm text-gray-400"> Une question ? Contactez-nous : <a href="mailto:{{ config('magic.contact_email') }}" class="text-amber-600 hover:underline">{{ config('magic.contact_email') }}</a> </p>
</div>
@endsection
