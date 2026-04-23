@extends('emails.layout')

@section('body')
<h1>Bienvenue, {{ $link->agency_name }} !</h1>

<p>Magic Hotels vous invite à soumettre vos demandes de réservation groupe via notre portail sécurisé.</p>

<p>Votre accès personnalisé a été créé. Cliquez sur le bouton ci-dessous pour accéder au formulaire de réservation :</p>

<div style="text-align:center; margin: 28px 0;">
    <a href="{{ $link->url }}" class="btn">Accéder au portail de réservation →</a>
</div>

<div class="info-box">
    <div class="info-row">
        <span class="label">Agence</span>
        <span class="value">{{ $link->agency_name }}</span>
    </div>
    @if($link->hotel)
    <div class="info-row">
        <span class="label">Hôtel concerné</span>
        <span class="value">{{ $link->hotel->name }}</span>
    </div>
    @endif
    @if($link->expires_at)
    <div class="info-row">
        <span class="label">Lien valable jusqu'au</span>
        <span class="value">{{ $link->expires_at->format('d/m/Y') }}</span>
    </div>
    @endif
</div>

<div class="alert-box alert-warning">
    <strong>⚠ Important :</strong> Ce lien est personnel et sécurisé. Merci de ne pas le partager.
</div>

<p>Pour toute question, n'hésitez pas à nous contacter à <a href="mailto:{{ config('magic.contact_email', 'reservations@magichotels.ma') }}">{{ config('magic.contact_email', 'reservations@magichotels.ma') }}</a>.</p>
@endsection
