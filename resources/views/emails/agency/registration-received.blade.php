@extends('emails.layout')
@section('body')
<h1>Demande de partenariat reçue ✓</h1>
<p>Bonjour <strong>{{ $agency->contact_name }}</strong>,</p>
<p>Nous avons bien reçu la demande de partenariat de <strong>{{ $agency->name }}</strong>. Notre équipe l'examinera sous 24h ouvrables.</p>
<div class="info-box">
    <div class="info-row"><span class="label">Agence</span><span class="value">{{ $agency->name }}</span></div>
    <div class="info-row"><span class="label">Email</span><span class="value">{{ $agency->email }}</span></div>
    <div class="info-row"><span class="label">Ville</span><span class="value">{{ $agency->city ?? '—' }}</span></div>
</div>
<p>Vous recevrez un email dès que votre dossier aura été traité.</p>
@endsection
