@extends('emails.layout')
@section('body')

<h1>Félicitations, votre agence est approuvée ! 🎉</h1>

<p>Bonjour <strong>{{ $agency->contact_name }}</strong>,</p>

<p>
    Nous avons le plaisir de vous informer que la demande de partenariat de
    <strong>{{ $agency->name }}</strong> a été approuvée par notre équipe.
    Vous avez désormais accès à votre espace agence Magic Hotels.
</p>

<div class="alert-box alert-success">
    ✅ <strong>{{ $agency->name }}</strong> — Partenaire officiel Magic Hotels
</div>

{{-- ─── Accès Espace Agence ─── --}}
<h2 style="font-size:17px; color:#1e293b; margin:24px 0 8px;">🔐 Vos identifiants de connexion</h2>
<p style="margin-bottom:12px; font-size:14px; color:#64748b;">
    Connectez-vous à votre espace agence pour suivre vos réservations, l'échéancier de paiements et l'état de vos demandes.
</p>

<div class="info-box">
    <div class="info-row">
        <span class="label">Adresse e-mail</span>
        <span class="value">{{ $agency->email }}</span>
    </div>
    @if($plainPassword)
    <div class="info-row">
        <span class="label">Mot de passe temporaire</span>
        <span class="value" style="font-family: monospace; font-size: 15px; letter-spacing: 1px; color: #d97706;">{{ $plainPassword }}</span>
    </div>
    @else
    <div class="info-row">
        <span class="label">Mot de passe</span>
        <span class="value" style="color:#64748b; font-style:italic;">Votre mot de passe existant</span>
    </div>
    @endif
    <div class="info-row">
        <span class="label">URL de connexion</span>
        <span class="value">
            <a href="{{ route('agency.login') }}" style="color:#f59e0b;">{{ route('agency.login') }}</a>
        </span>
    </div>
</div>

<div style="text-align:center; margin: 20px 0;">
    <a href="{{ route('agency.login') }}" class="btn">
        → Accéder à mon espace agence
    </a>
</div>

@if($plainPassword)
<div class="alert-box alert-warning">
    ⚠️ <strong>Sécurité :</strong> Ce mot de passe est temporaire. Nous vous recommandons de le modifier dès votre première connexion depuis les paramètres de votre profil.
</div>
@endif

{{-- ─── Lien de réservation (si disponible) ─── --}}
@if($secureLink)
<h2 style="font-size:17px; color:#1e293b; margin:28px 0 8px;">🔗 Votre lien de réservation groupe</h2>
<p style="margin-bottom:12px; font-size:14px; color:#64748b;">
    Utilisez ce lien sécurisé pour soumettre vos demandes de réservation groupe directement sur notre portail.
</p>

<div class="info-box">
    <div class="info-row">
        <span class="label">Hôtel</span>
        <span class="value">{{ $secureLink->hotel->name ?? '—' }}</span>
    </div>
    @if($secureLink->expires_at)
    <div class="info-row">
        <span class="label">Lien valable jusqu'au</span>
        <span class="value">{{ $secureLink->expires_at->format('d/m/Y') }}</span>
    </div>
    @endif
</div>

<div style="text-align:center; margin: 20px 0;">
    <a href="{{ $secureLink->url }}" class="btn" style="background:#1e293b;">
        → Soumettre une demande de réservation
    </a>
</div>

<div class="alert-box" style="background:#f8fafc; border:1px solid #e2e8f0; color:#475569;">
    🔒 Ce lien est personnel et sécurisé — merci de ne pas le partager en dehors de votre organisation.
</div>

@else
<div style="margin-top:28px; padding:16px 20px; border-radius:8px; background:#f8fafc; border:1px solid #e2e8f0; font-size:14px; color:#64748b;">
    <strong style="color:#1e293b;">📌 Votre lien de réservation</strong><br>
    Notre équipe vous transmettra prochainement votre lien personnalisé pour soumettre des demandes de réservation groupe.
</div>
@endif

{{-- ─── Informations agence ─── --}}
<h2 style="font-size:17px; color:#1e293b; margin:28px 0 8px;">📋 Récapitulatif de votre compte</h2>
<div class="info-box">
    <div class="info-row">
        <span class="label">Agence</span>
        <span class="value">{{ $agency->name }}</span>
    </div>
    <div class="info-row">
        <span class="label">Contact</span>
        <span class="value">{{ $agency->contact_name }}</span>
    </div>
    @if($agency->phone)
    <div class="info-row">
        <span class="label">Téléphone</span>
        <span class="value">{{ $agency->phone }}</span>
    </div>
    @endif
    @if($agency->agencyStatus)
    <div class="info-row">
        <span class="label">Statut tarifaire</span>
        <span class="value">{{ $agency->agencyStatus->name }}</span>
    </div>
    @endif
    <div class="info-row">
        <span class="label">Date d'approbation</span>
        <span class="value">{{ now()->format('d/m/Y') }}</span>
    </div>
</div>

<p style="margin-top:24px;">
    Pour toute question, contactez notre équipe à
    <a href="mailto:{{ config('magic.contact_email', 'reservations@magichotels.ma') }}"
       style="color:#f59e0b;">{{ config('magic.contact_email', 'reservations@magichotels.ma') }}</a>.
</p>

<p>Bienvenue dans la famille Magic Hotels ! 🏨</p>

@endsection
