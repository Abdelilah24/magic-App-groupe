<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [

            // ─── Approbation agence ───────────────────────────────────────────
            [
                'key'         => 'agency_approved',
                'name'        => 'Approbation agence',
                'description' => 'Envoyé quand une agence est approuvée, avec les identifiants de connexion.',
                'subject'     => '✅ Votre agence a été approuvée — Magic Hotels',
                'placeholders' => [
                    ['key' => 'contact_name',   'label' => 'Nom du contact'],
                    ['key' => 'agency_name',     'label' => 'Nom de l\'agence'],
                    ['key' => 'email',           'label' => 'Email de connexion'],
                    ['key' => 'password',        'label' => 'Mot de passe temporaire'],
                    ['key' => 'login_url',       'label' => 'URL de connexion'],
                    ['key' => 'portal_url',      'label' => 'URL espace agence'],
                    ['key' => 'tariff_status',   'label' => 'Statut tarifaire'],
                    ['key' => 'approval_date',   'label' => 'Date d\'approbation'],
                    ['key' => 'contact_email',   'label' => 'Email contact Magic Hotels'],
                ],
                'html_body' => <<<'HTML'
<h1>Félicitations, votre agence est approuvée ! 🎉</h1>

<p>Bonjour <strong>{{ contact_name }}</strong>,</p>

<p>Nous avons le plaisir de vous informer que la demande de partenariat de <strong>{{ agency_name }}</strong> a été approuvée par notre équipe. Vous avez désormais accès à votre espace agence Magic Hotels.</p>

<div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:14px 18px; margin:16px 0; font-size:14px; color:#166534;">
  ✅ <strong>{{ agency_name }}</strong> — Partenaire officiel Magic Hotels
</div>

<h2 style="font-size:17px; color:#1e293b; margin:24px 0 8px;">🔐 Vos identifiants de connexion</h2>
<p style="font-size:14px; color:#64748b; margin-bottom:12px;">Connectez-vous à votre espace agence pour suivre vos réservations, l'échéancier de paiements et l'état de vos demandes.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:14px;">
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8; width:45%;">Adresse e-mail</td>
    <td style="padding:10px 16px; font-weight:600; color:#1e293b;">{{ email }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8;">Mot de passe temporaire</td>
    <td style="padding:10px 16px; font-weight:700; color:#d97706; font-family:monospace; font-size:15px; letter-spacing:1px;">{{ password }}</td>
  </tr>
  <tr>
    <td style="padding:10px 16px; color:#94a3b8;">URL de connexion</td>
    <td style="padding:10px 16px;"><a href="{{ login_url }}" style="color:#f59e0b;">{{ login_url }}</a></td>
  </tr>
</table>

<div style="text-align:center; margin:20px 0;">
  <a href="{{ login_url }}" style="display:inline-block; background:#f59e0b; color:#fff; font-weight:600; padding:12px 28px; border-radius:8px; text-decoration:none; font-size:15px;">→ Accéder à mon espace agence</a>
</div>

<div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:14px 18px; margin:16px 0; font-size:14px; color:#92400e;">
  ⚠️ <strong>Sécurité :</strong> Ce mot de passe est temporaire. Nous vous recommandons de le modifier dès votre première connexion depuis les paramètres de votre profil.
</div>

<h2 style="font-size:17px; color:#1e293b; margin:28px 0 8px;">📋 Récapitulatif de votre compte</h2>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:14px;">
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8; width:45%;">Agence</td>
    <td style="padding:10px 16px; font-weight:600; color:#1e293b;">{{ agency_name }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8;">Statut tarifaire</td>
    <td style="padding:10px 16px; font-weight:600; color:#1e293b;">{{ tariff_status }}</td>
  </tr>
  <tr>
    <td style="padding:10px 16px; color:#94a3b8;">Date d'approbation</td>
    <td style="padding:10px 16px; font-weight:600; color:#1e293b;">{{ approval_date }}</td>
  </tr>
</table>

<p style="margin-top:24px;">Pour toute question, contactez notre équipe à <a href="mailto:{{ contact_email }}" style="color:#f59e0b;">{{ contact_email }}</a>.</p>

<p>Bienvenue dans la famille Magic Hotels ! 🏨</p>
HTML,
            ],

            // ─── Rejet agence ─────────────────────────────────────────────────
            [
                'key'         => 'agency_rejected',
                'name'        => 'Rejet agence',
                'description' => 'Envoyé quand une demande de partenariat est refusée.',
                'subject'     => 'Votre demande de partenariat — Magic Hotels',
                'placeholders' => [
                    ['key' => 'contact_name', 'label' => 'Nom du contact'],
                    ['key' => 'agency_name',  'label' => 'Nom de l\'agence'],
                    ['key' => 'reason',       'label' => 'Motif du refus'],
                    ['key' => 'contact_email','label' => 'Email contact Magic Hotels'],
                ],
                'html_body' => <<<'HTML'
<h1>Réponse à votre demande de partenariat</h1>

<p>Bonjour <strong>{{ contact_name }}</strong>,</p>

<p>Nous avons bien étudié la demande de partenariat de <strong>{{ agency_name }}</strong> et nous sommes au regret de vous informer qu'elle n'a pas pu être retenue à ce stade.</p>

<div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:14px 18px; margin:16px 0; font-size:14px; color:#991b1b;">
  <strong>Motif :</strong> {{ reason }}
</div>

<p>N'hésitez pas à nous contacter si vous souhaitez plus d'informations ou si votre situation évolue. Nous serions heureux de reconsidérer votre dossier.</p>

<p>Contactez notre équipe à <a href="mailto:{{ contact_email }}" style="color:#f59e0b;">{{ contact_email }}</a>.</p>

<p>Cordialement,<br>L'équipe Magic Hotels</p>
HTML,
            ],

            // ─── Confirmation inscription agence ──────────────────────────────
            [
                'key'         => 'agency_registration_received',
                'name'        => 'Confirmation inscription agence',
                'description' => 'Envoyé à l\'agence quand sa demande de partenariat est reçue.',
                'subject'     => '📋 Demande de partenariat reçue — Magic Hotels',
                'placeholders' => [
                    ['key' => 'contact_name', 'label' => 'Nom du contact'],
                    ['key' => 'agency_name',  'label' => 'Nom de l\'agence'],
                    ['key' => 'contact_email','label' => 'Email contact Magic Hotels'],
                ],
                'html_body' => <<<'HTML'
<h1>Demande de partenariat reçue !</h1>

<p>Bonjour <strong>{{ contact_name }}</strong>,</p>

<p>Nous avons bien reçu la demande de partenariat de <strong>{{ agency_name }}</strong> et nous vous remercions de l'intérêt porté à Magic Hotels.</p>

<div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:14px 18px; margin:16px 0; font-size:14px; color:#166534;">
  ✅ Votre dossier est en cours d'examen par notre équipe commerciale.
</div>

<p>Notre équipe va étudier votre dossier et vous recontactera dans les meilleurs délais pour vous informer de la suite donnée à votre demande.</p>

<p>Pour toute question, contactez-nous à <a href="mailto:{{ contact_email }}" style="color:#f59e0b;">{{ contact_email }}</a>.</p>

<p>Cordialement,<br>L'équipe Magic Hotels</p>
HTML,
            ],

            // ─── Invitation portail (SecureLink) ──────────────────────────────
            [
                'key'         => 'invitation',
                'name'        => 'Invitation portail réservation',
                'description' => 'Envoyé avec le lien sécurisé pour accéder au formulaire de réservation.',
                'subject'     => '✦ Votre accès portail réservation — Magic Hotels',
                'placeholders' => [
                    ['key' => 'agency_name',   'label' => 'Nom de l\'agence'],
                    ['key' => 'hotel_name',    'label' => 'Nom de l\'hôtel'],
                    ['key' => 'portal_url',    'label' => 'URL du formulaire de réservation'],
                    ['key' => 'expires_at',    'label' => 'Date d\'expiration du lien'],
                    ['key' => 'contact_email', 'label' => 'Email contact Magic Hotels'],
                ],
                'html_body' => <<<'HTML'
<h1>Bienvenue, {{ agency_name }} !</h1>

<p>Magic Hotels vous invite à soumettre vos demandes de réservation groupe via notre portail sécurisé.</p>

<p>Votre accès personnalisé a été créé. Cliquez sur le bouton ci-dessous pour accéder au formulaire de réservation :</p>

<div style="text-align:center; margin:28px 0;">
  <a href="{{ portal_url }}" style="display:inline-block; background:#f59e0b; color:#fff; font-weight:600; padding:12px 28px; border-radius:8px; text-decoration:none; font-size:15px;">Accéder au portail de réservation →</a>
</div>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; margin:16px 0;">
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8; width:45%;">Agence</td>
    <td style="padding:10px 16px; font-weight:600; color:#1e293b;">{{ agency_name }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8;">Hôtel concerné</td>
    <td style="padding:10px 16px; font-weight:600; color:#1e293b;">{{ hotel_name }}</td>
  </tr>
  <tr>
    <td style="padding:10px 16px; color:#94a3b8;">Lien valable jusqu'au</td>
    <td style="padding:10px 16px; font-weight:600; color:#1e293b;">{{ expires_at }}</td>
  </tr>
</table>

<div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:14px 18px; margin:16px 0; font-size:14px; color:#92400e;">
  ⚠️ <strong>Important :</strong> Ce lien est personnel et sécurisé. Merci de ne pas le partager en dehors de votre organisation.
</div>

<p>Pour toute question, contactez-nous à <a href="mailto:{{ contact_email }}" style="color:#f59e0b;">{{ contact_email }}</a>.</p>
HTML,
            ],

            // ─── Confirmation réservation reçue ───────────────────────────────
            [
                'key'         => 'client_reservation_received',
                'name'        => 'Confirmation réservation reçue',
                'description' => 'Envoyé au client après soumission d\'une demande de réservation.',
                'subject'     => '📋 Demande de réservation reçue — {{ reference }}',
                'placeholders' => [
                    ['key' => 'contact_name',     'label' => 'Nom du contact'],
                    ['key' => 'reference',        'label' => 'Référence réservation'],
                    ['key' => 'hotel_name',       'label' => 'Nom de l\'hôtel'],
                    ['key' => 'total_persons',    'label' => 'Nombre de personnes'],
                    ['key' => 'sejours_label',    'label' => 'Résumé séjours (ex: 2 séjours · 10 nuits)'],
                    ['key' => 'sejours_detail',   'label' => 'Détail complet des séjours, chambres et taxes (HTML)'],
                    ['key' => 'financial_recap',  'label' => 'Récapitulatif financier total (HTML)'],
                    ['key' => 'special_requests', 'label' => 'Demandes spéciales'],
                    ['key' => 'contact_email',    'label' => 'Email contact Magic Hotels'],
                ],
                'html_body' => <<<'HTML'
<h1>Demande de réservation reçue !</h1>

<p>Bonjour <strong>{{ contact_name }}</strong>,</p>

<p>Nous avons bien reçu votre demande de réservation groupe. Notre équipe va l'étudier et vous répondra dans les plus brefs délais.</p>

<div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:14px 18px; margin:16px 0; font-size:14px; color:#166534;">
  ✅ Référence : <strong>{{ reference }}</strong>
</div>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; margin:16px 0;">
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8; width:45%;">Hôtel</td>
    <td style="padding:10px 16px; font-weight:600; color:#1e293b;">{{ hotel_name }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8;">Personnes</td>
    <td style="padding:10px 16px; font-weight:600; color:#1e293b;">{{ total_persons }}</td>
  </tr>
  <tr>
    <td style="padding:10px 16px; color:#94a3b8;">Séjours</td>
    <td style="padding:10px 16px; font-weight:600; color:#d97706;">{{ sejours_label }}</td>
  </tr>
</table>

{{ sejours_detail }}

{{ financial_recap }}

{{ special_requests }}

<p>Vous pouvez suivre l'état de votre demande en temps réel. Vous recevrez un email dès que notre équipe aura traité votre dossier.</p>

<p>Pour toute question, contactez notre équipe à <a href="mailto:{{ contact_email }}" style="color:#f59e0b;">{{ contact_email }}</a>.</p>

<p>Cordialement,<br>L'équipe Magic Hotels</p>
HTML,
            ],

            // ─── Demande de paiement ──────────────────────────────────────────
            [
                'key'         => 'payment_request',
                'name'        => 'Demande de paiement',
                'description' => 'Envoyé après acceptation de la réservation avec le lien de paiement.',
                'subject'     => '💳 Votre réservation est acceptée — Procédez au paiement ({{ reference }})',
                'placeholders' => [
                    ['key' => 'contact_name',  'label' => 'Nom du contact'],
                    ['key' => 'reference',     'label' => 'Référence réservation'],
                    ['key' => 'hotel_name',    'label' => 'Nom de l\'hôtel'],
                    ['key' => 'check_in',      'label' => 'Date d\'arrivée'],
                    ['key' => 'check_out',     'label' => 'Date de départ'],
                    ['key' => 'total',         'label' => 'Total à payer (MAD)'],
                    ['key' => 'payment_url',   'label' => 'Lien de paiement'],
                    ['key' => 'expires_at',    'label' => 'Date limite de paiement'],
                    ['key' => 'contact_email', 'label' => 'Email contact Magic Hotels'],
                ],
                'html_body' => <<<'HTML'
<h1>Votre réservation est acceptée ! 🎉</h1>

<p>Bonjour <strong>{{ contact_name }}</strong>,</p>

<p>Excellente nouvelle ! Votre demande de réservation <strong>{{ reference }}</strong> a été acceptée. Pour confirmer définitivement votre réservation, veuillez procéder au paiement via le lien ci-dessous.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; margin:16px 0;">
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8; width:45%;">Hôtel</td>
    <td style="padding:10px 16px; font-weight:600; color:#1e293b;">{{ hotel_name }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8;">Séjour</td>
    <td style="padding:10px 16px; font-weight:600; color:#1e293b;">{{ check_in }} → {{ check_out }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8; font-weight:600;">Total à régler</td>
    <td style="padding:10px 16px; font-weight:700; color:#f59e0b; font-size:16px;">{{ total }} MAD</td>
  </tr>
  <tr>
    <td style="padding:10px 16px; color:#94a3b8;">Date limite</td>
    <td style="padding:10px 16px; font-weight:600; color:#dc2626;">{{ expires_at }}</td>
  </tr>
</table>

<div style="text-align:center; margin:24px 0;">
  <a href="{{ payment_url }}" style="display:inline-block; background:#f59e0b; color:#fff; font-weight:600; padding:14px 32px; border-radius:8px; text-decoration:none; font-size:16px;">💳 Procéder au paiement</a>
</div>

<div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:14px 18px; margin:16px 0; font-size:14px; color:#991b1b;">
  ⚠️ Ce lien de paiement est personnel et expire le <strong>{{ expires_at }}</strong>. Passé ce délai, votre réservation pourrait être annulée.
</div>

<p>Pour toute question, contactez notre équipe à <a href="mailto:{{ contact_email }}" style="color:#f59e0b;">{{ contact_email }}</a>.</p>
HTML,
            ],

            // ─── Devis / Disponibilités réservation ──────────────────────────
            [
                'key'         => 'reservation_quote',
                'name'        => 'Devis — Disponibilités & tarifs',
                'description' => 'Email envoyé en réponse à une demande de réservation groupe avec disponibilités et proposition tarifaire.',
                'subject'     => 'Réponse à votre demande de réservation – {{ hotel_nom }}',
                'placeholders' => [
                    ['key' => 'client_civilite',       'label' => 'Civilité (M., Mme)'],
                    ['key' => 'client_nom',            'label' => 'Nom du client'],
                    ['key' => 'hotel_nom',             'label' => 'Nom de l\'hôtel'],
                    ['key' => 'dates',                 'label' => 'Dates du séjour (ex: Du 24 au 26 Avril 2026, soit 2 nuits)'],
                    ['key' => 'nombre_chambres',       'label' => 'Nombre de chambres demandées'],
                    ['key' => 'nombre_personnes',      'label' => 'Nombre de personnes'],
                    ['key' => 'regime',                'label' => 'Régime (All Inclusive, Demi-Pension…)'],
                    ['key' => 'chambres_detail',       'label' => 'Détail des chambres et tarifs (HTML)'],
                    ['key' => 'taxe_sejour',           'label' => 'Taxe de séjour par personne/nuit (MAD)'],
                    ['key' => 'date_limite_paiement',  'label' => 'Date limite paiement 50%'],
                    ['key' => 'schedule_detail',       'label' => 'Tableau échéancier de paiement (HTML)'],
                    ['key' => 'commercial_nom',        'label' => 'Nom du commercial'],
                    ['key' => 'commercial_titre',      'label' => 'Titre / Poste du commercial'],
                    ['key' => 'commercial_tel',        'label' => 'Téléphone du commercial'],
                    ['key' => 'site_web',              'label' => 'Site web de l\'hôtel'],
                ],
                'html_body' => <<<'HTML'
<p>Bonjour <strong>{{ client_civilite }} {{ client_nom }}</strong>,</p>

<p>Nous vous remercions pour l'intérêt porté à l'égard de <strong>{{ hotel_nom }}</strong>.</p>

<p>Faisant suite à votre demande, nous avons le plaisir de vous confirmer nos disponibilités et vous communiquer notre meilleure proposition tarifaire, comme suit&nbsp;:</p>

<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; margin:16px 0;">
  <tr style="border-bottom:1px solid #e2e8f0;">
    <td style="padding:10px 16px; color:#64748b; width:42%; font-weight:600;">Dates</td>
    <td style="padding:10px 16px; color:#1e293b;">{{ dates }}</td>
  </tr>
  <tr style="border-bottom:1px solid #e2e8f0;">
    <td style="padding:10px 16px; color:#64748b; font-weight:600;">Nombre de chambres</td>
    <td style="padding:10px 16px; color:#1e293b;">{{ nombre_chambres }}</td>
  </tr>
  <tr style="border-bottom:1px solid #e2e8f0;">
    <td style="padding:10px 16px; color:#64748b; font-weight:600;">Nombre de personnes</td>
    <td style="padding:10px 16px; color:#1e293b;">{{ nombre_personnes }}</td>
  </tr>
  <tr>
    <td style="padding:10px 16px; color:#64748b; font-weight:600;">Régime</td>
    <td style="padding:10px 16px; color:#1e293b;">{{ regime }}</td>
  </tr>
</table>

<h2 style="font-size:15px; font-weight:700; color:#1e293b; border-bottom:2px solid #f59e0b; padding-bottom:6px; margin:24px 0 12px;">Disponibilité et tarif des chambres</h2>

{{ chambres_detail }}

<p style="font-size:13px; color:#64748b; margin:12px 0 4px;">Nos tarifs sont en Dirhams Marocain, incluant la TVA et hors taxes de séjour.</p>
<p style="font-size:14px; margin:0 0 24px;"><strong>Taxes de séjour&nbsp;:</strong> {{ taxe_sejour }} MAD par personne de 12 ans et plus et par nuit.</p>

<h2 style="font-size:15px; font-weight:700; color:#1e293b; border-bottom:2px solid #f59e0b; padding-bottom:6px; margin:24px 0 12px;">Conditions de l'hôtel</h2>

<p style="margin:0 0 6px;"><strong>Horaires d'accès :</strong></p>
<ul style="margin:0 0 14px; padding-left:20px; font-size:14px;">
  <li style="margin-bottom:5px;"><strong>Check-In&nbsp;:</strong> À partir de 15h00. Accès à l'hôtel strictement interdit pour toute arrivée avant cette heure-ci.</li>
  <li style="margin-bottom:5px;"><strong>Check-Out&nbsp;:</strong> À midi. Les clients sont priés de libérer les chambres à 12h00 au plus tard. Ils peuvent laisser les bagages à la réception, prendre leur déjeuner et quitter l'hôtel à 15h00 au plus tard.</li>
</ul>

<p style="margin:0 0 6px;"><strong>Conditions enfant :</strong></p>
<ul style="margin:0 0 14px; padding-left:20px; font-size:14px;">
  <li style="margin-bottom:4px;">Est considérée bébé toute personne ayant moins de 24 mois le jour du départ.</li>
  <li style="margin-bottom:4px;">Est considérée enfant toute personne ayant moins de 12 ans le jour du départ.</li>
  <li style="margin-bottom:4px;">Est considérée adulte toute personne ayant 12 ans et plus le jour du départ.</li>
  <li style="margin-bottom:4px;">Un changement de catégorie de chambre pourra être appliqué selon le nombre et l'âge des clients, ainsi que la disponibilité des chambres, moyennant un supplément.</li>
</ul>

<p style="margin:0 0 6px;"><strong>Supplément arrivée anticipée et départ tardif :</strong></p>

<p style="margin:8px 0 4px; font-style:italic; font-size:13px;"><strong>1. Accès à l'hôtel (sans chambres)</strong></p>
<ul style="margin:0 0 12px; padding-left:20px; font-size:13px; color:#374151;">
  <li style="margin-bottom:4px;">Accès à partir de 09h00 : <strong>300 dh/adulte — 150 dh/enfant</strong>. Attribution des chambres à 15h00.</li>
  <li style="margin-bottom:4px;">Accès à partir de 11h00 : <strong>150 dh/adulte — 75 dh/enfant</strong>. Attribution des chambres à 15h00.</li>
  <li style="margin-bottom:4px;">Départ tardif jusqu'à 18h00 : <strong>150 dh/adulte — 75 dh/enfant</strong>. Libération des chambres à 12h00.</li>
  <li style="margin-bottom:4px;">Départ tardif jusqu'à 20h00 : <strong>300 dh/adulte — 150 dh/enfant</strong>. Libération des chambres à 12h00.</li>
</ul>

<p style="margin:8px 0 4px; font-style:italic; font-size:13px;"><strong>2. Accès à l'hôtel (avec chambres) – Selon disponibilité</strong></p>
<ul style="margin:0 0 14px; padding-left:20px; font-size:13px; color:#374151;">
  <li style="margin-bottom:4px;">Check-in à partir de 09h00 (confirmé la veille) : <strong>900 dh/chambre</strong>.</li>
  <li style="margin-bottom:4px;">Check-in à partir de 11h00 (confirmé la veille) : <strong>500 dh/chambre</strong>.</li>
  <li style="margin-bottom:4px;">Check-out tardif jusqu'à 15h00 (confirmé le jour du départ) : <strong>500 dh/chambre</strong>.</li>
  <li style="margin-bottom:4px;">Check-out tardif jusqu'à 18h00 (confirmé le jour du départ) : <strong>900 dh/chambre</strong>.</li>
</ul>

<p style="margin:0 0 16px; font-size:14px;"><strong>Conditions groupes&nbsp;:</strong> Minimum 11 chambres. En dessous de ce nombre, la demande est traitée comme une réservation individuelle.</p>

<h2 style="font-size:15px; font-weight:700; color:#1e293b; border-bottom:2px solid #f59e0b; padding-bottom:6px; margin:24px 0 12px;">Conditions de paiement et option pour confirmation</h2>

{{ schedule_detail }}

<div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:14px 18px; margin:0 0 16px; font-size:14px; color:#78350f;">
  <strong>50% du montant global</strong> devront être réglés au plus tard le <strong>{{ date_limite_paiement }} avant 12h00</strong> pour confirmation du groupe et blocage des chambres. Le reliquat est à régler <strong>7 jours avant la date d'arrivée</strong>.
</div>

<div style="background:#fef2f2; border-left:4px solid #dc2626; border-radius:0 6px 6px 0; padding:12px 16px; margin:0 0 20px; font-size:14px; color:#991b1b; font-weight:600;">
  ⚠️ Ceci est une confirmation de disponibilité, pas une confirmation de réservation.
</div>

<h2 style="font-size:15px; font-weight:700; color:#1e293b; border-bottom:2px solid #f59e0b; padding-bottom:6px; margin:24px 0 12px;">Conditions d'annulation et No-show</h2>

<p style="font-size:14px; margin:0 0 20px;">Veuillez noter que <strong>50% du montant global du séjour</strong> seront facturés pour tout no-show total ou partiel et pour toute annulation, avec un minimum d'une nuit.</p>

<p style="margin:0 0 6px; font-size:14px;"><strong>Modes de paiement acceptés :</strong></p>
<ul style="margin:0 0 8px; padding-left:20px; font-size:14px;">
  <li>Virement normal ou instantané</li>
  <li>Chèque certifié</li>
  <li>Versement</li>
</ul>

<p style="font-size:13px; color:#6b7280; margin:0 0 8px; font-style:italic;">L'hôtel peut refuser tout paiement qui ne respecte pas les délais et conditions. Le règlement intérieur de l'hôtel s'applique à l'ensemble des réservations. Tout séjour entraîne l'acceptation des conditions générales de vente, du règlement intérieur et des conditions particulières.</p>

<p style="font-size:12px; color:#9ca3af; margin:0 0 24px;">Tout litige pouvant naître de l'interprétation et/ou de l'exécution des présentes conditions est soumis au droit Marocain et relève de la compétence exclusive des tribunaux de commerce de Marrakech.</p>

<p style="font-size:14px; margin:0 0 24px;">Nous restons à votre entière disposition pour tout complément d'information et vous invitons à visiter notre site <a href="https://{{ site_web }}" style="color:#f59e0b;">{{ site_web }}</a> pour plus de détails sur l'hôtel.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #e5e7eb; padding-top:20px; margin-top:8px; font-size:14px; color:#374151;">
  <tr>
    <td style="padding-top:16px;">
      <p style="margin:0 0 8px; font-style:italic; color:#6b7280;">Best regards / Cordialement,</p>
      <p style="margin:0 0 2px; font-weight:700; font-size:15px;">{{ commercial_nom }}</p>
      <p style="margin:0 0 2px; color:#6b7280;">{{ commercial_titre }}</p>
      <p style="margin:0 0 2px; color:#6b7280;">{{ commercial_tel }}</p>
      <p style="margin:0 0 16px;"><a href="https://{{ site_web }}" style="color:#f59e0b;">{{ site_web }}</a></p>
      <p style="font-size:11px; color:#d1d5db; margin:0;">🌿 Please consider the environment before printing this email. Pensez à l'environnement avant d'imprimer cet e-mail.</p>
    </td>
  </tr>
</table>
HTML,
            ],

            // ─── Refus réservation ────────────────────────────────────────────
            [
                'key'         => 'reservation_refused',
                'name'        => 'Refus de réservation',
                'description' => 'Envoyé au client quand sa demande de réservation est refusée.',
                'subject'     => 'Concernant votre demande de réservation — {{ reference }}',
                'placeholders' => [
                    ['key' => 'contact_name', 'label' => 'Nom du contact'],
                    ['key' => 'reference',    'label' => 'Référence réservation'],
                    ['key' => 'reason',       'label' => 'Motif du refus'],
                    ['key' => 'contact_email','label' => 'Email contact Magic Hotels'],
                ],
                'html_body' => <<<'HTML'
<h1>Concernant votre demande de réservation</h1>

<p>Bonjour <strong>{{ contact_name }}</strong>,</p>

<p>Nous avons étudié votre demande de réservation groupe (réf. <strong>{{ reference }}</strong>) et sommes dans l'obligation de ne pas pouvoir y donner suite à ce stade.</p>

<div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:14px 18px; margin:16px 0; font-size:14px; color:#991b1b;">
  <strong>Motif communiqué :</strong> {{ reason }}
</div>

<div style="background:#fef9ee; border-left:4px solid #f59e0b; border-radius:0 6px 6px 0; padding:12px 16px; margin:16px 0; font-size:14px; color:#78350f;">
  Nous nous excusons pour les désagréments occasionnés.
</div>

<p>N'hésitez pas à nous recontacter pour explorer d'autres dates ou options disponibles. Notre équipe sera ravie de vous accompagner.</p>

<p>Pour toute question, contactez notre équipe à <a href="mailto:{{ contact_email }}" style="color:#f59e0b;">{{ contact_email }}</a>.</p>

<p>Cordialement,<br>L'équipe Magic Hotels</p>
HTML,
            ],

            // ─── Modification acceptée ────────────────────────────────────────
            [
                'key'         => 'modification_accepted',
                'name'        => 'Modification acceptée',
                'description' => 'Envoyé au client quand sa demande de modification est acceptée.',
                'subject'     => '✅ Modification acceptée — Réservation {{ reference }}',
                'placeholders' => [
                    ['key' => 'contact_name',  'label' => 'Nom du contact'],
                    ['key' => 'reference',     'label' => 'Référence réservation'],
                    ['key' => 'check_in',      'label' => 'Nouvelle date d\'arrivée'],
                    ['key' => 'check_out',     'label' => 'Nouvelle date de départ'],
                    ['key' => 'total',         'label' => 'Nouveau total (MAD)'],
                    ['key' => 'payment_url',   'label' => 'Lien de paiement'],
                    ['key' => 'contact_email', 'label' => 'Email contact Magic Hotels'],
                ],
                'html_body' => <<<'HTML'
<h1>Modification acceptée ✓</h1>

<p>Bonjour <strong>{{ contact_name }}</strong>,</p>

<p>Votre demande de modification de la réservation <strong>{{ reference }}</strong> a été acceptée et le prix a été recalculé.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; margin:16px 0;">
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8; width:45%;">Arrivée</td>
    <td style="padding:10px 16px; font-weight:600; color:#1e293b;">{{ check_in }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8;">Départ</td>
    <td style="padding:10px 16px; font-weight:600; color:#1e293b;">{{ check_out }}</td>
  </tr>
  <tr>
    <td style="padding:10px 16px; color:#94a3b8; font-weight:600;">Nouveau total</td>
    <td style="padding:10px 16px; font-weight:700; color:#f59e0b; font-size:16px;">{{ total }} MAD</td>
  </tr>
</table>

<p>Un nouveau paiement est nécessaire pour finaliser votre réservation.</p>

<div style="text-align:center; margin:24px 0;">
  <a href="{{ payment_url }}" style="display:inline-block; background:#f59e0b; color:#fff; font-weight:600; padding:12px 28px; border-radius:8px; text-decoration:none; font-size:15px;">💳 Procéder au paiement →</a>
</div>

<p>Pour toute question, contactez notre équipe à <a href="mailto:{{ contact_email }}" style="color:#f59e0b;">{{ contact_email }}</a>.</p>

<p>Cordialement,<br>L'équipe Magic Hotels</p>
HTML,
            ],

            // ─── Modification refusée ─────────────────────────────────────────
            [
                'key'         => 'modification_refused',
                'name'        => 'Modification refusée',
                'description' => 'Envoyé au client quand sa demande de modification est refusée.',
                'subject'     => 'Demande de modification — Réservation {{ reference }}',
                'placeholders' => [
                    ['key' => 'contact_name',  'label' => 'Nom du contact'],
                    ['key' => 'reference',     'label' => 'Référence réservation'],
                    ['key' => 'reason',        'label' => 'Motif du refus'],
                    ['key' => 'contact_email', 'label' => 'Email contact Magic Hotels'],
                ],
                'html_body' => <<<'HTML'
<h1>Demande de modification</h1>

<p>Bonjour <strong>{{ contact_name }}</strong>,</p>

<p>Votre demande de modification de la réservation <strong>{{ reference }}</strong> n'a malheureusement pas pu être traitée.</p>

<div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:14px 18px; margin:16px 0; font-size:14px; color:#991b1b;">
  <strong>Motif :</strong> {{ reason }}
</div>

<p>Votre réservation reste active dans son état précédent.</p>

<p>Pour toute question, contactez notre équipe à <a href="mailto:{{ contact_email }}" style="color:#f59e0b;">{{ contact_email }}</a>.</p>

<p>Cordialement,<br>L'équipe Magic Hotels</p>
HTML,
            ],

            // ─── Confirmation de paiement ─────────────────────────────────────
            [
                'key'         => 'payment_confirmed',
                'name'        => 'Confirmation de paiement',
                'description' => 'Envoyé après réception et validation du paiement.',
                'subject'     => '✅ Paiement confirmé — Réservation {{ reference }}',
                'placeholders' => [
                    ['key' => 'contact_name',  'label' => 'Nom du contact'],
                    ['key' => 'reference',     'label' => 'Référence réservation'],
                    ['key' => 'hotel_name',    'label' => 'Nom de l\'hôtel'],
                    ['key' => 'check_in',      'label' => 'Date d\'arrivée'],
                    ['key' => 'check_out',     'label' => 'Date de départ'],
                    ['key' => 'amount_paid',   'label' => 'Montant payé (MAD)'],
                    ['key' => 'contact_email', 'label' => 'Email contact Magic Hotels'],
                ],
                'html_body' => <<<'HTML'
<h1>Paiement confirmé ! ✅</h1>

<p>Bonjour <strong>{{ contact_name }}</strong>,</p>

<p>Nous avons bien reçu votre paiement pour la réservation <strong>{{ reference }}</strong>. Votre réservation est désormais confirmée.</p>

<div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:14px 18px; margin:16px 0; font-size:14px; color:#166534;">
  ✅ Réservation <strong>{{ reference }}</strong> — Confirmée et payée
</div>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; margin:16px 0;">
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8; width:45%;">Hôtel</td>
    <td style="padding:10px 16px; font-weight:600; color:#1e293b;">{{ hotel_name }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8;">Séjour</td>
    <td style="padding:10px 16px; font-weight:600; color:#1e293b;">{{ check_in }} → {{ check_out }}</td>
  </tr>
  <tr>
    <td style="padding:10px 16px; color:#94a3b8; font-weight:600;">Montant réglé</td>
    <td style="padding:10px 16px; font-weight:700; color:#16a34a; font-size:16px;">{{ amount_paid }} MAD</td>
  </tr>
</table>

<p>Notre équipe vous contactera si des informations complémentaires sont nécessaires avant votre arrivée. À très bientôt à Magic Hotels !</p>

<p>Pour toute question, contactez notre équipe à <a href="mailto:{{ contact_email }}" style="color:#f59e0b;">{{ contact_email }}</a>.</p>
HTML,
            ],


            // ─── [ADMIN] Nouvelle réservation ────────────────────────────────────
            [
                'key'         => 'admin_new_reservation',
                'name'        => '[Admin] Nouvelle demande de réservation',
                'description' => 'Envoyé à l\'équipe admin quand une agence soumet une nouvelle réservation.',
                'subject'     => '🔔 Nouvelle demande #{{ reference }} — {{ agency_name }}',
                'placeholders' => [
                    ['key' => 'reference',      'label' => 'Référence réservation'],
                    ['key' => 'agency_name',    'label' => 'Nom de l\'agence'],
                    ['key' => 'contact_name',   'label' => 'Nom du contact'],
                    ['key' => 'contact_email',  'label' => 'Email du contact'],
                    ['key' => 'hotel_name',     'label' => 'Nom de l\'hôtel'],
                    ['key' => 'check_in',       'label' => 'Date d\'arrivée'],
                    ['key' => 'check_out',      'label' => 'Date de départ'],
                    ['key' => 'total_persons',  'label' => 'Nombre de personnes'],
                    ['key' => 'admin_url',      'label' => 'Lien vers la fiche admin'],
                ],
                'html_body' => <<<'HTML'
<h1>🔔 Nouvelle demande de réservation</h1>

<p>Une nouvelle demande groupe vient d'être soumise :</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; margin:16px 0;">
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8; width:40%;">Référence</td>
    <td style="padding:10px 16px; font-weight:700; color:#1e293b;">{{ reference }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8;">Agence</td>
    <td style="padding:10px 16px; font-weight:600; color:#1e293b;">{{ agency_name }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8;">Contact</td>
    <td style="padding:10px 16px; color:#1e293b;">{{ contact_name }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8;">Email</td>
    <td style="padding:10px 16px; color:#1e293b;">{{ contact_email }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8;">Hôtel</td>
    <td style="padding:10px 16px; color:#1e293b;">{{ hotel_name }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8;">Arrivée</td>
    <td style="padding:10px 16px; color:#1e293b;">{{ check_in }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8;">Départ</td>
    <td style="padding:10px 16px; color:#1e293b;">{{ check_out }}</td>
  </tr>
  <tr>
    <td style="padding:10px 16px; color:#94a3b8;">Personnes</td>
    <td style="padding:10px 16px; color:#1e293b;">{{ total_persons }}</td>
  </tr>
</table>

<div style="text-align:center; margin:24px 0;">
  <a href="{{ admin_url }}" style="background:#f59e0b;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;">Traiter la demande →</a>
</div>
HTML,
            ],

            // ─── [ADMIN] Annulation ───────────────────────────────────────────────
            [
                'key'         => 'admin_cancellation',
                'name'        => '[Admin] Annulation de réservation',
                'description' => 'Envoyé à l\'équipe admin quand une agence annule sa réservation.',
                'subject'     => '❌ Annulation — #{{ reference }}',
                'placeholders' => [
                    ['key' => 'reference',   'label' => 'Référence réservation'],
                    ['key' => 'agency_name', 'label' => 'Nom de l\'agence'],
                    ['key' => 'admin_url',   'label' => 'Lien vers la fiche admin'],
                ],
                'html_body' => <<<'HTML'
<h1>❌ Annulation de réservation</h1>

<p>La réservation <strong>{{ reference }}</strong> ({{ agency_name }}) a été annulée.</p>

<div style="text-align:center; margin:24px 0;">
  <a href="{{ admin_url }}" style="background:#f59e0b;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;">Voir la fiche →</a>
</div>
HTML,
            ],

            // ─── [ADMIN] Demande de modification ────────────────────────────────
            [
                'key'         => 'admin_modification_request',
                'name'        => '[Admin] Demande de modification',
                'description' => 'Envoyé à l\'équipe admin quand une agence soumet une demande de modification.',
                'subject'     => '🔄 Modification demandée #{{ reference }}',
                'placeholders' => [
                    ['key' => 'reference',   'label' => 'Référence réservation'],
                    ['key' => 'agency_name', 'label' => 'Nom de l\'agence'],
                    ['key' => 'admin_url',   'label' => 'Lien vers la fiche admin'],
                ],
                'html_body' => <<<'HTML'
<h1>🔄 Modification demandée</h1>

<p>L'agence <strong>{{ agency_name }}</strong> a soumis une demande de modification pour la réservation <strong>{{ reference }}</strong>.</p>

<div style="text-align:center; margin:24px 0;">
  <a href="{{ admin_url }}" style="background:#f59e0b;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;">Traiter la modification →</a>
</div>
HTML,
            ],

            // ─── Facture Proforma ─────────────────────────────────────────────
            [
                'key'         => 'proforma_invoice',
                'name'        => 'Facture Proforma',
                'description' => 'Email envoyé avec la facture proforma en pièce jointe PDF.',
                'subject'     => 'Facture Proforma – {{ reference }} – {{ hotel_name }}',
                'placeholders' => [
                    ['key' => 'contact_name',     'label' => 'Nom du contact'],
                    ['key' => 'reference',         'label' => 'Référence réservation'],
                    ['key' => 'hotel_name',        'label' => 'Nom de l\'hôtel'],
                    ['key' => 'check_in',          'label' => 'Date d\'arrivée'],
                    ['key' => 'check_out',         'label' => 'Date de départ'],
                    ['key' => 'nights',            'label' => 'Durée (ex: 4 nuits)'],
                    ['key' => 'total',             'label' => 'Total TTC (MAD)'],
                    ['key' => 'payment_deadline',  'label' => 'Date limite de paiement'],
                    ['key' => 'hotel_phone',       'label' => 'Téléphone hôtel'],
                    ['key' => 'hotel_email',       'label' => 'Email hôtel'],
                    ['key' => 'hotel_address',     'label' => 'Adresse hôtel'],
                ],
                'html_body' => <<<'HTML'
<h1>Facture Proforma</h1>

<p>Bonjour <strong>{{ contact_name }}</strong>,</p>

<p>Veuillez trouver ci-joint la <strong>facture proforma</strong> pour votre réservation auprès de <strong>{{ hotel_name }}</strong>.</p>

<div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:14px 18px; margin:16px 0; font-size:14px; color:#166534; text-align:center;">
  <div style="font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">Référence de réservation</div>
  <div style="font-size:22px; font-weight:bold; color:#166534; font-family:monospace;">{{ reference }}</div>
</div>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; margin:16px 0;">
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8; width:45%;">Hôtel</td>
    <td style="padding:10px 16px; font-weight:600; color:#1e293b;">{{ hotel_name }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8;">Arrivée</td>
    <td style="padding:10px 16px; font-weight:600; color:#1e293b;">{{ check_in }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8;">Départ</td>
    <td style="padding:10px 16px; font-weight:600; color:#1e293b;">{{ check_out }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8;">Durée</td>
    <td style="padding:10px 16px; font-weight:600; color:#1e293b;">{{ nights }}</td>
  </tr>
  <tr>
    <td style="padding:10px 16px; color:#94a3b8; font-weight:600;">Total TTC</td>
    <td style="padding:10px 16px; font-weight:700; color:#f59e0b; font-size:16px;">{{ total }}</td>
  </tr>
</table>

<div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:12px 16px; margin:16px 0; font-size:13px; color:#166534;">
  📎 La facture proforma détaillée (chambres, suppléments, remises, échéancier) est jointe à cet email au format <strong>PDF</strong>.
</div>

{{ payment_deadline }}

<p>Pour toute question, n'hésitez pas à nous contacter.</p>

<p>Cordialement,<br>
<strong>{{ hotel_name }}</strong><br>
{{ hotel_phone }}<br>
{{ hotel_email }}
</p>
HTML,
            ],

            // ─── [ADMIN] Nouvelle agence ─────────────────────────────────────────
            [
                'key'         => 'admin_new_agency',
                'name'        => '[Admin] Nouvelle demande de partenariat agence',
                'description' => 'Envoyé à l\'équipe admin quand une agence soumet une demande de partenariat.',
                'subject'     => '🏢 Nouvelle demande de partenariat — {{ agency_name }}',
                'placeholders' => [
                    ['key' => 'agency_name',    'label' => 'Nom de l\'agence'],
                    ['key' => 'contact_name',   'label' => 'Nom du contact'],
                    ['key' => 'contact_email',  'label' => 'Email du contact'],
                    ['key' => 'phone',          'label' => 'Téléphone'],
                    ['key' => 'city',           'label' => 'Ville'],
                    ['key' => 'country',        'label' => 'Pays'],
                    ['key' => 'message',        'label' => 'Message de l\'agence'],
                    ['key' => 'admin_url',      'label' => 'Lien vers la fiche admin'],
                ],
                'html_body' => <<<'HTML'
<h1>🏢 Nouvelle demande de partenariat agence</h1>

<p>Une nouvelle agence vient de soumettre une demande de partenariat.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; margin:16px 0;">
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8; width:40%;">Agence</td>
    <td style="padding:10px 16px; font-weight:700; color:#1e293b;">{{ agency_name }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8;">Contact</td>
    <td style="padding:10px 16px; color:#1e293b;">{{ contact_name }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8;">Email</td>
    <td style="padding:10px 16px; color:#1e293b;">{{ contact_email }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8;">Téléphone</td>
    <td style="padding:10px 16px; color:#1e293b;">{{ phone }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f1f5f9;">
    <td style="padding:10px 16px; color:#94a3b8;">Ville</td>
    <td style="padding:10px 16px; color:#1e293b;">{{ city }}</td>
  </tr>
  <tr>
    <td style="padding:10px 16px; color:#94a3b8;">Pays</td>
    <td style="padding:10px 16px; color:#1e293b;">{{ country }}</td>
  </tr>
</table>

{{ message }}

<div style="text-align:center; margin:24px 0;">
  <a href="{{ admin_url }}" style="background:#1d4ed8;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;">Voir la demande dans l'admin →</a>
</div>
HTML,
            ],

        ];

        foreach ($templates as $tpl) {
            EmailTemplate::updateOrCreate(['key' => $tpl['key']], $tpl);
        }
    }
}
