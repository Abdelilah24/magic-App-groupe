@extends('emails.layout')

@section('body')
<h1>Demande de modification</h1>

<p>Bonjour <strong>{{ $reservation->contact_name }}</strong>,</p>
<p>Votre demande de modification de la réservation <strong>{{ $reservation->reference }}</strong> n'a malheureusement pas pu être traitée.</p>

<div class="info-box">
    <p style="margin:0; font-size:14px;"><strong>Motif :</strong> {{ $reason }}</p>
</div>

<p>Votre réservation reste active dans son état précédent. Pour toute question, contactez-nous.</p>
@endsection
