@extends('emails.layout')

@section('body')
<h1>Concernant votre demande de réservation</h1>

<p>Bonjour <strong>{{ $reservation->contact_name }}</strong>,</p>
<p>Nous avons étudié votre demande de réservation groupe (réf. <strong>{{ $reservation->reference }}</strong>) et sommes dans l'obligation de ne pas pouvoir y donner suite à ce stade.</p>

@if($reservation->refusal_reason)
<div class="info-box">
    <p style="margin:0; font-size:14px; color:#475569;"><strong>Motif communiqué :</strong> {{ $reservation->refusal_reason }}</p>
</div>
@endif

<div class="alert-box alert-danger">
    Nous nous excusons pour les désagréments occasionnés.
</div>

<p>N'hésitez pas à nous recontacter pour explorer d'autres dates ou options disponibles. Notre équipe sera ravie de vous accompagner.</p>

<p>Cordialement,<br>L'équipe Magic Hotels<br>
<a href="mailto:{{ config('magic.contact_email', 'reservations@magichotels.ma') }}">{{ config('magic.contact_email', 'reservations@magichotels.ma') }}</a>
</p>
@endsection
