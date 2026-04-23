@extends('emails.layout')

@section('body')
<h1>Annulation de réservation</h1>

<p>La réservation <strong>{{ $reservation->reference }}</strong> ({{ $reservation->agency_name }}) a été annulée.</p>

<div style="text-align:center; margin: 24px 0;">
    <a href="{{ route('admin.reservations.show', $reservation) }}" class="btn">Voir la fiche →</a>
</div>
@endsection
