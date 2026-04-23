@extends('emails.layout')

@section('body')
<h1>Votre réservation a été acceptée ! ✅</h1>

<p>Bonjour <strong>{{ $reservation->contact_name }}</strong>,</p>
<p>Nous avons le plaisir de vous confirmer que votre demande de réservation groupe a été <strong>acceptée</strong>. Voici le détail tarifaire :</p>

<div class="info-box">
    <div class="info-row">
        <span class="label">Référence</span>
        <span class="value">{{ $reservation->reference }}</span>
    </div>
    <div class="info-row">
        <span class="label">Hôtel</span>
        <span class="value">{{ $reservation->hotel->name }}</span>
    </div>
    <div class="info-row">
        <span class="label">Arrivée</span>
        <span class="value">{{ $reservation->check_in->format('d/m/Y') }}</span>
    </div>
    <div class="info-row">
        <span class="label">Départ</span>
        <span class="value">{{ $reservation->check_out->format('d/m/Y') }}</span>
    </div>
    <div class="info-row">
        <span class="label">Durée</span>
        <span class="value">{{ $reservation->nights }} nuit(s)</span>
    </div>
</div>

{{-- Détail par chambre --}}
@if($reservation->rooms->isNotEmpty())
<p style="font-weight:600; margin-bottom:8px;">Détail des chambres :</p>
<div class="info-box">
    @foreach($reservation->rooms as $room)
    <div class="info-row">
        <span class="label">{{ $room->roomType->name }} × {{ $room->quantity }}</span>
        <span class="value">{{ number_format($room->total_price, 2, ',', ' ') }} MAD</span>
    </div>
    @endforeach
    <div class="info-row total-row">
        <span class="label">TOTAL À PAYER</span>
        <span class="value">{{ number_format($reservation->total_price, 2, ',', ' ') }} MAD</span>
    </div>
</div>
@endif

<p>Pour finaliser votre réservation, veuillez procéder au paiement via le lien sécurisé ci-dessous :</p>

@if($reservation->hasValidPaymentToken())
<div style="text-align:center; margin: 28px 0;">
    <a href="{{ route('client.payment', $reservation->payment_token) }}" class="btn">
        💳 Procéder au paiement →
    </a>
    <p style="font-size:12px; color:#94a3b8; margin-top:8px;">
        Lien valable jusqu'au {{ $reservation->payment_token_expires_at->format('d/m/Y') }}
    </p>
</div>
@endif

<div class="alert-box alert-warning">
    <strong>Instructions virement :</strong> Merci d'indiquer la référence <strong>{{ $reservation->reference }}</strong>
    comme libellé de votre virement bancaire.
</div>
@endsection
