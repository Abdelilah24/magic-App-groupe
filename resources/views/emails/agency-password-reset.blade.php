@extends('emails.layout')

@section('body')
<h1>Réinitialisation de votre mot de passe</h1>

<p>Bonjour <strong>{{ $agencyName }}</strong>,</p>

<p>Vous avez demandé la réinitialisation de votre mot de passe pour votre espace partenaire Magic Hotels. Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe.</p>

<p style="text-align:center; margin: 28px 0;">
    <a href="{{ $resetUrl }}" class="btn">Réinitialiser mon mot de passe</a>
</p>

<p>Si le bouton ne fonctionne pas, copiez-collez ce lien dans votre navigateur :</p>
<p style="word-break: break-all; font-size: 13px; color: #64748b;">{{ $resetUrl }}</p>

<div class="alert-box alert-warning">
    <strong>⚠ Ce lien est valable 60 minutes.</strong><br>
    Si vous n'avez pas fait cette demande, ignorez cet email — votre mot de passe ne sera pas modifié.
</div>
@endsection
