<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PdfTemplate;

class PdfTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $placeholders = [
            ['key' => 'titre',                 'label' => 'Titre du document'],
            ['key' => 'reference',             'label' => 'Référence de la réservation'],
            ['key' => 'date_emission',         'label' => "Date d'émission"],
            ['key' => 'echeance_ligne',        'label' => 'Ligne échéance (vide si aucune)'],
            ['key' => 'hotel_logo',            'label' => "Logo de l'hôtel (balise img)"],
            ['key' => 'hotel_nom',             'label' => "Nom de l'hôtel"],
            ['key' => 'hotel_info',            'label' => "Adresse et contacts de l'hôtel"],
            ['key' => 'client_nom',            'label' => 'Nom agence / client'],
            ['key' => 'client_contact',        'label' => 'Nom du contact'],
            ['key' => 'client_email',          'label' => 'Email'],
            ['key' => 'client_telephone',      'label' => 'Téléphone'],
            ['key' => 'client_adresse',        'label' => 'Adresse du client'],
            ['key' => 'reservation_hotel',     'label' => 'Hôtel (bloc réservation)'],
            ['key' => 'reservation_sejours',   'label' => 'Résumé séjours'],
            ['key' => 'reservation_personnes', 'label' => 'Résumé personnes'],
            ['key' => 'tableau_chambres',      'label' => 'Tableau détail des chambres'],
            ['key' => 'tableau_supplements',   'label' => 'Tableau des suppléments (vide si aucun)'],
            ['key' => 'tableau_extras',        'label' => 'Tableau des services extras (vide si aucun)'],
            ['key' => 'tableau_totaux',        'label' => 'Bloc totaux'],
            ['key' => 'tableau_echeancier',    'label' => 'Tableau échéancier (vide si aucun)'],
            ['key' => 'coordonnees_bancaires', 'label' => 'Coordonnées bancaires (vide si aucun)'],
            ['key' => 'conditions_hotel',      'label' => "Conditions générales de l'hôtel"],
            ['key' => 'logo_principal',        'label' => 'Logo principal (bas de chaque page)'],
            ['key' => 'pied_de_page',          'label' => 'Pied de page'],
        ];

        // ── CSS (colonne séparée — jamais édité par TinyMCE) ──────────────────
        $css = <<<'CSS'
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 10px;
    color: #000;
    background: #fff;
    line-height: 1.4;
}
.page { padding: 22px 30px 55px; }

/* En-tête */
.header { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
.header td { vertical-align: top; }
.hotel-name { font-size: 13px; font-weight: bold; }
.hotel-sub { font-size: 9px; color: #333; margin-top: 2px; line-height: 1.5; }
.doc-info { text-align: right; font-size: 9px; color: #333; }
.doc-info strong { font-size: 11px; color: #000; }

/* Séparateur */
.sep { border: none; border-top: 1px solid #000; margin: 8px 0; }

/* Blocs info 2 colonnes */
.blocks { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
.block { width: 48%; vertical-align: top; border: 1px solid #000; padding: 6px 9px; }
.block-gap { width: 4%; }
.block-title { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #000; padding-bottom: 3px; margin-bottom: 5px; }
.row { margin-bottom: 2px; }
.lbl { color: #555; }
.val { font-weight: bold; }

/* Titre de section */
.section { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.8px; border-bottom: 1px solid #000; padding-bottom: 3px; margin-bottom: 5px; }

/* Tableau chambres par séjour */
.tbl { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 10px; }
.tbl thead th {
    padding: 5px 7px; text-align: left;
    font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4px;
    border: 1px solid #000; background: #f0f0f0;
}
.tbl thead th.r { text-align: right; }
.tbl tbody td { padding: 5px 7px; border: 1px solid #ccc; vertical-align: middle; }
.tbl tbody td.r { text-align: right; }
.tbl tbody td.sejour-cell {
    font-size: 9px; font-weight: bold; text-align: center;
    border: 1px solid #000; vertical-align: middle;
    line-height: 1.6;
}

/* Tableau suppléments */
.tbl2 { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 10px; }
.tbl2 thead th {
    padding: 5px 7px; text-align: left;
    font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4px;
    border: 1px solid #000; background: #f0f0f0;
}
.tbl2 thead th.r { text-align: right; }
.tbl2 tbody td { padding: 5px 7px; border: 1px solid #ccc; }
.tbl2 tbody td.r { text-align: right; }

/* Totaux — table-based (DomPDF ne supporte pas float:right) */
.totals-wrap { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
.totals-spacer { width: 55%; }
.totals-box { width: 45%; border: 1px solid #000; vertical-align: top; }
.tot-row { width: 100%; border-collapse: collapse; }
.tot-row td { padding: 4px 9px; border-bottom: 1px solid #ddd; font-size: 10px; }
.tot-row td:last-child { text-align: right; font-weight: bold; }
.tot-row tr:last-child td { border-bottom: none; }
.tot-grand td { border-top: 2px solid #000 !important; font-size: 11px; font-weight: bold; }

/* Échéancier */
.sch-tbl { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 10px; }
.sch-tbl thead th {
    padding: 5px 7px; text-align: left;
    font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4px;
    border: 1px solid #000; background: #f0f0f0;
}
.sch-tbl thead th.r { text-align: right; }
.sch-tbl tbody td { padding: 5px 7px; border: 1px solid #ccc; }
.sch-tbl tbody td.r { text-align: right; }

/* RIB */
.rib { border: 1px solid #000; padding: 7px 10px; margin-bottom: 10px; }
.rib-title { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 5px; }
.rib-tbl { width: 100%; border-collapse: collapse; }
.rib-tbl td { padding: 2px 0; font-size: 9.5px; }
.rib-lbl { color: #555; width: 70px; }
.rib-val { font-weight: bold; font-family: DejaVu Sans Mono, monospace; }

/* Pied de page — fixé en bas, répété sur chaque page par DomPDF */
.page-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 4px 30px 6px;
    border-top: 1px solid #ccc;
    text-align: center;
    background: #fff;
}
.page-footer img {
    display: block;
    margin: 0 auto 2px;
    max-height: 28px;
    max-width: 110px;
}
.footer-text { font-size: 8px; color: #555; }

/* Conditions de l'hôtel */
.conditions { border: 1px solid #ccc; border-top: 3px solid #000; padding: 10px 12px; margin-top: 14px; margin-bottom: 10px; page-break-inside: avoid; }
.conditions-title { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 8px; }
.conditions-sub { font-size: 8.5px; font-weight: bold; margin-top: 7px; margin-bottom: 3px; color: #111; }
.conditions p, .conditions-body { font-size: 8px; color: #333; line-height: 1.5; margin-bottom: 2px; }
.conditions ul { margin: 2px 0 4px 14px; padding: 0; }
.conditions ul li { font-size: 8px; color: #333; line-height: 1.5; list-style-type: disc; }
.conditions ol { margin: 2px 0 4px 14px; padding: 0; }
.conditions ol li { font-size: 8px; color: #333; line-height: 1.5; }
.conditions .contact-block { margin-top: 8px; border-top: 1px solid #e5e5e5; padding-top: 6px; font-size: 8px; color: #444; }
.conditions .contact-block strong { color: #000; }
.conditions .env-note { font-size: 7.5px; color: #888; font-style: italic; margin-top: 4px; }
CSS;

        // ── Corps HTML (body uniquement — TinyMCE-safe) ───────────────────────
        $htmlBody = <<<'HTML'
<div class="page-footer">
    {{ logo_principal }}
    <div class="footer-text">{{ pied_de_page }}</div>
</div>

<div class="page">

<table class="header">
    <tr>
        <td colspan="2" style="text-align:center; padding-bottom:10px; font-size:20px; font-weight:bold; letter-spacing:2px; text-transform:uppercase;">{{ titre }}</td>
    </tr>
    <tr>
        <td style="width:65%">
            {{ hotel_logo }}
            <div class="hotel-name">{{ hotel_nom }}</div>
            <div class="hotel-sub">{{ hotel_info }}</div>
        </td>
        <td style="width:35%; text-align:right; vertical-align:top;">
            <div class="doc-info">
                <strong>Réf : {{ reference }}</strong><br>
                Émise le {{ date_emission }}
                {{ echeance_ligne }}
            </div>
        </td>
    </tr>
</table>

<hr class="sep">

<table class="blocks">
    <tr>
        <td class="block">
            <div class="block-title">Client</div>
            {{ client_nom }}{{ client_contact }}{{ client_email }}{{ client_telephone }}{{ client_adresse }}
        </td>
        <td class="block-gap"></td>
        <td class="block">
            <div class="block-title">Réservation</div>
            {{ reservation_hotel }}{{ reservation_sejours }}{{ reservation_personnes }}
        </td>
    </tr>
</table>

<div class="section">Détail des chambres</div>
{{ tableau_chambres }}
{{ tableau_supplements }}
{{ tableau_extras }}
{{ tableau_totaux }}
{{ tableau_echeancier }}
{{ coordonnees_bancaires }}

<div class="conditions">
<div class="conditions-title">Conditions de l'hôtel</div>

<div class="conditions-sub">Horaires d'accès :</div>
<ul>
  <li><strong>Check-In :</strong> À partir de 15h00. Accès à l'hôtel strictement interdit pour toute arrivée avant cette heure-ci.</li>
  <li><strong>Check-Out :</strong> À midi. Les clients sont priés de libérer les chambres à 12h00 au plus tard. Ils peuvent laisser les bagages à la réception, prendre leur déjeuner et quitter l'hôtel à 15h00 au plus tard.</li>
</ul>

<div class="conditions-sub">Conditions enfant :</div>
<ul>
  <li>Est considérée <strong>bébé</strong> toute personne ayant moins de 24 mois le jour du départ.</li>
  <li>Est considérée <strong>enfant</strong> toute personne ayant moins de 12 ans le jour du départ.</li>
  <li>Est considérée <strong>adulte</strong> toute personne ayant 12 ans et plus le jour du départ.</li>
  <li>Un changement de catégorie de chambre pourra être appliqué selon le nombre et l'âge des clients, ainsi que la disponibilité des chambres, moyennant un supplément.</li>
</ul>

<div class="conditions-sub">Supplément arrivée anticipée et départ tardif :</div>
<ol>
  <li style="font-weight:bold; margin-bottom:2px;">Accès à l'hôtel (sans chambres)
    <ul>
      <li>Accès à partir de 09h00 : <strong>300 dh/adulte — 150 dh/enfant.</strong> Attribution des chambres à 15h00.</li>
      <li>Accès à partir de 11h00 : <strong>150 dh/adulte — 75 dh/enfant.</strong> Attribution des chambres à 15h00.</li>
      <li>Départ tardif jusqu'à 18h00 : <strong>150 dh/adulte — 75 dh/enfant.</strong> Libération des chambres à 12h00.</li>
      <li>Départ tardif jusqu'à 20h00 : <strong>300 dh/adulte — 150 dh/enfant.</strong> Libération des chambres à 12h00.</li>
    </ul>
  </li>
  <li style="font-weight:bold; margin-bottom:2px;">Accès à l'hôtel (avec chambres) – Selon disponibilité
    <ul>
      <li>Check-in à partir de 09h00 (confirmé la veille) : <strong>900 dh/chambre.</strong></li>
      <li>Check-in à partir de 11h00 (confirmé la veille) : <strong>500 dh/chambre.</strong></li>
      <li>Check-out tardif jusqu'à 15h00 (confirmé le jour du départ) : <strong>500 dh/chambre.</strong></li>
      <li>Check-out tardif jusqu'à 18h00 (confirmé le jour du départ) : <strong>900 dh/chambre.</strong></li>
    </ul>
  </li>
</ol>

<div class="conditions-sub">Conditions groupes :</div>
<p class="conditions-body">Minimum 11 chambres. En dessous de ce nombre, la demande est traitée comme une réservation individuelle.</p>

<div class="conditions-sub">Conditions d'annulation et No-show</div>
<p class="conditions-body">Veuillez noter que 50% du montant global du séjour seront facturés pour tout no-show total ou partiel et pour toute annulation, avec un minimum d'une nuit.</p>

<div class="conditions-sub">Modes de paiement acceptés :</div>
<ul>
  <li>Virement normal ou instantané</li>
  <li>Chèque certifié</li>
  <li>Versement</li>
</ul>

<p class="conditions-body" style="margin-top:5px;">
L'hôtel peut refuser tout paiement qui ne respecte pas les délais et conditions. Le règlement intérieur de l'hôtel s'applique à l'ensemble des réservations. Tout séjour entraîne l'acceptation des conditions générales de vente, du règlement intérieur et des conditions particulières.
</p>
<p class="conditions-body" style="margin-top:3px;">
Tout litige pouvant naître de l'interprétation et/ou de l'exécution des présentes conditions est soumis au droit Marocain et relève de la compétence exclusive des tribunaux de commerce de Marrakech.
</p>
<p class="conditions-body" style="margin-top:3px;">
Nous restons à votre entière disposition pour tout complément d'information et vous invitons à visiter notre site <strong>www.magichotels.ma</strong> pour plus de détails sur l'hôtel.
</p>

<div class="contact-block">
  <strong>Best regards / Cordialement,</strong><br>
  <strong>Amine ZGHAOUI</strong><br>
  Director of Sales &amp; Marketing – Morocco<br>
  +212 6 14 09 75 82<br>
  www.magichotels.ma
  <div class="env-note">🌿 Please consider the environment before printing this email. Pensez à l'environnement avant d'imprimer cet e-mail.</div>
</div>
</div>


</div>
HTML;

        PdfTemplate::updateOrCreate(
            ['key' => 'proforma'],
            [
                'name'         => 'Facture Proforma',
                'description'  => 'Template PDF de la facture proforma envoyée aux clients/agences.',
                'html_body'    => $htmlBody,
                'css'          => $css,
                'placeholders' => $placeholders,
                'is_active'    => true,
            ]
        );
    }
}
