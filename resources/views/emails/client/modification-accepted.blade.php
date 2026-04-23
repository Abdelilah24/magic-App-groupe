@extends('emails.layout')

@section('body')
<h1>Modification acceptée ✓</h1>

<p>Bonjour <strong>{{ $reservation->contact_name }}</strong>,</p>
<p>Votre demande de modification de la réservation <strong>{{ $reservation->reference }}</strong> a été acceptée et le prix a été recalculé.</p>

<div class="info-box">
    <div class="info-row">
        <span class="label">Arrivée</span>
        <span class="value">{{ $reservation->check_in->format('d/m/Y') }}</span>
    </div>
    <div class="info-row">
        <span class="label">Départ</span>
        <span class="value">{{ $reservation->check_out->format('d/m/Y') }}</span>
    </div>
    <div class="info-row total-row">
        <span class="label">Nouveau total</span>
        <span class="value">{{ number_format($reservation->total_price, 2, ',', ' ') }} MAD</span>
    </div>
</div>

<p>Un nouveau paiement est nécessaire pour finaliser votre réservation.</p>

@if($reservation->hasValidPaymentToken())
<div style="text-align:center; margin:24px 0;">
    <a href="{{ route('client.payment', $reservation->payment_token) }}" class="btn">Procéder au paiement →</a>
</div>
@endif
@endsection
