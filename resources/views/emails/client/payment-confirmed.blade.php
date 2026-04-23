@extends('emails.layout')

@section('body')
<h1>Réservation confirmée 🎉</h1>

<p>Bonjour <strong>{{ $reservation->contact_name }}</strong>,</p>
<p>Nous avons bien reçu votre paiement. Votre réservation groupe est désormais <strong>confirmée</strong>.</p>

<div class="alert-box alert-success">
    ✓ Réservation <strong>{{ $reservation->reference }}</strong> — Paiement de <strong>{{ number_format($reservation->total_price, 2, ',', ' ') }} MAD</strong> reçu.
</div>

<div class="info-box">
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
        <span class="label">Chambres</span>
        <span class="value">{{ $reservation->rooms->sum('quantity') }} chambre(s)</span>
    </div>
    <div class="info-row">
        <span class="label">Personnes</span>
        <span class="value">{{ $reservation->total_persons }}</span>
    </div>
</div>

<p>Nous vous souhaitons un excellent séjour au sein de nos établissements et restons à votre disposition pour toute question.</p>

<p style="font-size:14px; color:#64748b;">L'équipe Magic Hotels</p>
@endsection
