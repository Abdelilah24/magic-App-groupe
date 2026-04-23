@extends('emails.layout')
@section('body')
<h1>Nouvelle demande de partenariat agence</h1>
<p>Une nouvelle agence vient de soumettre une demande de partenariat.</p>
<div class="info-box">
    <div class="info-row"><span class="label">Agence</span><span class="value">{{ $agency->name }}</span></div>
    <div class="info-row"><span class="label">Contact</span><span class="value">{{ $agency->contact_name }}</span></div>
    <div class="info-row"><span class="label">Email</span><span class="value">{{ $agency->email }}</span></div>
    <div class="info-row"><span class="label">Téléphone</span><span class="value">{{ $agency->phone ?? '—' }}</span></div>
    <div class="info-row"><span class="label">Ville</span><span class="value">{{ $agency->city ?? '—' }}</span></div>
    <div class="info-row"><span class="label">Pays</span><span class="value">{{ $agency->country ?? '—' }}</span></div>
</div>
@if($agency->message)
<p><strong>Message :</strong> {{ $agency->message }}</p>
@endif
<p style="text-align:center; margin-top:24px;">
    <a href="{{ url('/admin/agencies/' . $agency->id) }}" style="background:#1d4ed8;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;">Voir la demande dans l'admin</a>
</p>
@endsection
