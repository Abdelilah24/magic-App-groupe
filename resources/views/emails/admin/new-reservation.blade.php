@extends('emails.layout')

@section('body')
<h1>🔔 Nouvelle demande de réservation</h1>

<p>Une nouvelle demande groupe vient d'être soumise :</p>

<div class="info-box">
    <div class="info-row">
        <span class="label">Référence</span>
        <span class="value">{{ $reservation->reference }}</span>
    </div>
    <div class="info-row">
        <span class="label">Agence</span>
        <span class="value">{{ $reservation->agency_name }}</span>
    </div>
    <div class="info-row">
        <span class="label">Contact</span>
        <span class="value">{{ $reservation->contact_name }}</span>
    </div>
    <div class="info-row">
        <span class="label">Email</span>
        <span class="value">{{ $reservation->email }}</span>
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
        <span class="label">Personnes</span>
        <span class="value">{{ $reservation->total_persons }}</span>
    </div>
</div>

<div style="text-align:center; margin: 24px 0;">
    <a href="{{ route('admin.reservations.show', $reservation) }}" class="btn">Traiter la demande →</a>
</div>
@endsection
