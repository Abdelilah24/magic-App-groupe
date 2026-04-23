@extends('emails.layout')
@section('body')
<h1>Concernant votre demande de partenariat</h1>
<p>Bonjour <strong>{{ $agency->contact_name }}</strong>,</p>
<p>Après examen de votre dossier, nous ne sommes pas en mesure de donner suite à votre demande de partenariat pour <strong>{{ $agency->name }}</strong> à ce stade.</p>
@if($reason)
<div class="info-box"><p style="margin:0; font-size:14px;"><strong>Motif :</strong> {{ $reason }}</p></div>
@endif
<p>N'hésitez pas à nous recontacter pour toute question : <a href="mailto:{{ config('magic.contact_email') }}">{{ config('magic.contact_email') }}</a></p>
@endsection
