@extends('emails.layout')

@section('body')
<h1>🔄 Modification demandée</h1>

<p>L'agence <strong>{{ $reservation->agency_name }}</strong> a soumis une demande de modification pour la réservation <strong>{{ $reservation->reference }}</strong>.</p>

<div style="text-align:center; margin: 24px 0;">
    <a href="{{ route('admin.reservations.show', $reservation) }}" class="btn">Traiter la modification →</a>
</div>
@endsection
