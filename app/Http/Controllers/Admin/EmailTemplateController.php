<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $templates = EmailTemplate::orderBy('name')->get();
        return view('admin.email-templates.index', compact('templates'));
    }

    public function edit(EmailTemplate $emailTemplate)
    {
        return view('admin.email-templates.edit', ['template' => $emailTemplate]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $data = $request->validate([
            'subject'   => 'required|string|max:255',
            'html_body' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $emailTemplate->update([
            'subject'   => $this->decodeMceProtected($data['subject']),
            'html_body' => $this->decodeMceProtected($data['html_body']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.email-templates.edit', $emailTemplate)
            ->with('success', "Template « {$emailTemplate->name} » mis à jour.");
    }

    /**
     * Aperçu du template avec des données fictives.
     */
    public function preview(EmailTemplate $emailTemplate)
    {
        $sampleData = $this->getSampleData($emailTemplate->key);
        $body       = $emailTemplate->renderBody($sampleData);

        $html = $this->buildPreviewHtml($body);
        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * TinyMCE encode les {{ variable }} en <!--mce:protected URL-encoded-->.
     * Gère aussi la forme entité-encodée &lt;!--mce:protected...--&gt;
     * On restaure les {{ variable }} avant toute sauvegarde en DB.
     */
    private function decodeMceProtected(string $html): string
    {
        // Forme normale : <!--mce:protected %7B%7B...%7D%7D-->
        $html = preg_replace_callback(
            '/<!--mce:protected (.*?)-->/',
            fn($m) => urldecode($m[1]),
            $html
        );

        // Forme entité-encodée : &lt;!--mce:protected ...--&gt;
        $html = preg_replace_callback(
            '/&lt;!--mce:protected (.*?)--&gt;/',
            fn($m) => urldecode($m[1]),
            $html
        );

        return $html;
    }

    private function buildPreviewHtml(string $body): string
    {
        // Utilise le même layout que les vrais emails (emails.layout)
        $layoutPath = resource_path('views/emails/layout.blade.php');
        $layout     = file_get_contents($layoutPath);

        // Extraire les styles du layout
        preg_match('/<style>(.*?)<\/style>/s', $layout, $styleMatch);
        $styles = $styleMatch[1] ?? '';

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
{$styles}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <span class="brand">✦ Magic Hotels</span>
    <p style="margin:4px 0 0; font-size:13px; color:#94a3b8;">Portail Réservations Groupes</p>
  </div>
  <div class="body">
    {$body}
  </div>
  <div class="footer">
    <p>Magic Hotels — <a href="https://www.magichotels.ma" style="color:#94a3b8;">www.magichotels.ma</a></p>
    <p>Aperçu généré automatiquement — données fictives.</p>
  </div>
</div>
</body>
</html>
HTML;
    }

    private function getSampleData(string $key): array
    {
        $chambresDetailHtml = '
<ul style="margin:0 0 12px; padding-left:20px; font-size:14px; color:#374151;">
  <li style="margin-bottom:6px;">40 × Chambre SGL/DBL Standard — <strong>1 958,40 MAD/nuit</strong>, All Inclusive, hors taxes de séjour.</li>
  <li style="margin-bottom:6px;">40 × Chambre Triple Standard — <strong>2 643,84 MAD/nuit</strong>, All Inclusive, hors taxes de séjour.</li>
  <li style="margin-bottom:6px;">40 × Chambre Familiale (4+0) Standard — <strong>3 329,28 MAD/nuit</strong>, All Inclusive, hors taxes de séjour.</li>
</ul>';

        $base = [
            'contact_name'          => 'Jean Dupont',
            'agency_name'           => 'Voyages Prestige',
            'email'                 => 'contact@voyages-prestige.ma',
            'password'              => 'TMP4x9kRm',
            'login_url'             => url('/espace-agence/connexion'),
            'portal_url'            => url('/espace-agence'),
            'tariff_status'         => 'Agence de voyages',
            'approval_date'         => now()->format('d/m/Y'),
            'contact_email'         => config('magic.contact_email', 'reservations@magichotels.ma'),
            'reason'                => 'Dossier incomplet — veuillez soumettre un nouveau dossier.',
            'reference'             => 'MH-2026-00042',
            'hotel_name'            => 'Hotel Aqua Mirage Marrakech',
            'total_persons'         => '86',
            'sejours_label'         => '2 séjours · 10 nuits au total',
            'sejours_detail'        => '
<h3 style="font-size:14px; color:#92400e; margin:20px 0 8px;">Séjour 1 — 10/04/2026 → 19/04/2026 (9 nuits)</h3>
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-size:13px; margin-bottom:8px;">
  <thead><tr style="background:#fef3c7; color:#92400e;">
    <th style="text-align:left; padding:6px 8px; border:1px solid #fde68a;">Chambre / Occupation</th>
    <th style="text-align:center; padding:6px 8px; border:1px solid #fde68a;">Personnes/ch.</th>
    <th style="text-align:right; padding:6px 8px; border:1px solid #fde68a;">Montant</th>
  </tr></thead>
  <tbody>
    <tr>
      <td style="padding:6px 8px; border:1px solid #f3f4f6; color:#374151;"><strong>2</strong> × SGL/DBL Standard <span style="color:#9ca3af; font-size:11px;">× 9 nuits</span></td>
      <td style="padding:6px 8px; border:1px solid #f3f4f6; text-align:center; color:#6b7280; font-size:12px;">1 ad.</td>
      <td style="padding:6px 8px; border:1px solid #f3f4f6; text-align:right; font-weight:600; color:#374151;">33 390 MAD</td>
    </tr>
    <tr style="background:#eff6ff;">
      <td colspan="2" style="padding:5px 8px; border:1px solid #dbeafe; color:#1d4ed8; font-size:12px;">Taxe de séjour (2 adulte(s) × 9 nuit(s) × 19,80 DHS)</td>
      <td style="padding:5px 8px; border:1px solid #dbeafe; text-align:right; font-weight:600; color:#1d4ed8;">356 MAD</td>
    </tr>
    <tr style="background:#fef9ee;">
      <td colspan="2" style="padding:6px 8px; border:1px solid #fde68a; font-weight:700; color:#92400e;">Sous-total séjour 1</td>
      <td style="padding:6px 8px; border:1px solid #fde68a; text-align:right; font-weight:700; color:#92400e;">33 746 MAD</td>
    </tr>
  </tbody>
</table>
<h3 style="font-size:14px; color:#92400e; margin:20px 0 8px;">Séjour 2 — 23/04/2026 → 24/04/2026 (1 nuit)</h3>
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-size:13px; margin-bottom:8px;">
  <thead><tr style="background:#fef3c7; color:#92400e;">
    <th style="text-align:left; padding:6px 8px; border:1px solid #fde68a;">Chambre / Occupation</th>
    <th style="text-align:center; padding:6px 8px; border:1px solid #fde68a;">Personnes/ch.</th>
    <th style="text-align:right; padding:6px 8px; border:1px solid #fde68a;">Montant</th>
  </tr></thead>
  <tbody>
    <tr>
      <td style="padding:6px 8px; border:1px solid #f3f4f6; color:#374151;"><strong>1</strong> × SGL/DBL Standard <span style="color:#9ca3af; font-size:11px;">× 1 nuit</span></td>
      <td style="padding:6px 8px; border:1px solid #f3f4f6; text-align:center; color:#6b7280; font-size:12px;">2 ad. · 1 enf.</td>
      <td style="padding:6px 8px; border:1px solid #f3f4f6; text-align:right; font-weight:600; color:#374151;">1 855 MAD</td>
    </tr>
    <tr style="background:#eff6ff;">
      <td colspan="2" style="padding:5px 8px; border:1px solid #dbeafe; color:#1d4ed8; font-size:12px;">Taxe de séjour (1 adulte(s) × 1 nuit(s) × 19,80 DHS)</td>
      <td style="padding:5px 8px; border:1px solid #dbeafe; text-align:right; font-weight:600; color:#1d4ed8;">20 MAD</td>
    </tr>
    <tr style="background:#fef9ee;">
      <td colspan="2" style="padding:6px 8px; border:1px solid #fde68a; font-weight:700; color:#92400e;">Sous-total séjour 2</td>
      <td style="padding:6px 8px; border:1px solid #fde68a; text-align:right; font-weight:700; color:#92400e;">1 875 MAD</td>
    </tr>
  </tbody>
</table>',
            'financial_recap'       => '
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-size:13px; margin-top:12px;">
  <tr><td style="padding:5px 8px; color:#374151;">Hébergement (chambres)</td><td style="padding:5px 8px; text-align:right; color:#374151;">35 245 MAD</td></tr>
  <tr><td style="padding:5px 8px; color:#1d4ed8;">Taxe de séjour</td><td style="padding:5px 8px; text-align:right; color:#1d4ed8;">376 MAD</td></tr>
  <tr><td style="padding:5px 8px; color:#7c3aed;">Diner 7 (optionnel)</td><td style="padding:5px 8px; text-align:right; color:#7c3aed;">200 MAD</td></tr>
  <tr><td style="padding:5px 8px; color:#d97706;">Soirée nouvel année (obligatoire)</td><td style="padding:5px 8px; text-align:right; color:#d97706;">400 MAD</td></tr>
  <tr style="background:#fef3c7; border-top:2px solid #fbbf24;">
    <td style="padding:8px; font-weight:700; font-size:14px; color:#92400e;">TOTAL ESTIMÉ</td>
    <td style="padding:8px; text-align:right; font-weight:700; font-size:14px; color:#d97706;">36 221,00 MAD</td>
  </tr>
</table>
<p style="font-size:11px; color:#9ca3af; margin-top:4px;">* Prix indicatif, confirmé après validation par notre équipe.</p>',
            'special_requests'      => '',
            'check_in'              => '20/06/2026',
            'check_out'             => '25/06/2026',
            'nights'                => '5',
            'total'                 => '8 750',
            'amount_paid'           => '8 750',
            'payment_url'           => url('/pay/sample-token'),
            'expires_at'            => now()->addDays(7)->format('d/m/Y'),
            // modification_accepted / modification_refused
            'payment_url'           => url('/pay/sample-token'),
            // reservation_quote
            'client_civilite'       => 'M.',
            'client_nom'            => 'Amine Benali',
            'hotel_nom'             => 'AQUA MIRAGE Marrakech',
            'dates'                 => 'Du 24 au 26 Avril 2026, soit 2 nuits',
            'nombre_chambres'       => '15 chambres standards',
            'nombre_personnes'      => 'entre 31 à 46 personnes',
            'regime'                => 'All Inclusive, l\'unique régime proposé par l\'hôtel',
            'chambres_detail'       => $chambresDetailHtml,
            'taxe_sejour'           => '19,80',
            'date_limite_paiement'  => '26/03/2026 avant 12:00',
            'schedule_detail'       => '
<table width="100%" cellpadding="0" cellspacing="0"
  style="border-collapse:collapse; background:#fffbeb; border:1px solid #fde68a; border-radius:8px; font-size:14px; margin:0 0 16px; overflow:hidden;">
  <thead>
    <tr style="background:#fef3c7;">
      <th style="padding:8px 16px; text-align:left; color:#92400e; font-size:12px; text-transform:uppercase; letter-spacing:0.05em;">Libellé</th>
      <th style="padding:8px 16px; text-align:left; color:#92400e; font-size:12px; text-transform:uppercase; letter-spacing:0.05em;">Date limite</th>
      <th style="padding:8px 16px; text-align:left; color:#92400e; font-size:12px; text-transform:uppercase; letter-spacing:0.05em;">Montant</th>
    </tr>
  </thead>
  <tbody>
    <tr style="border-bottom:1px solid #fde68a;">
      <td style="padding:10px 16px; color:#78350f; font-weight:600;">Acompte 50%</td>
      <td style="padding:10px 16px; color:#374151; font-size:14px;">📅 26/03/2026 avant 12:00</td>
      <td style="padding:10px 16px; font-weight:700; color:#b45309; white-space:nowrap;">4 375,00 MAD <span style="color:#9ca3af; font-size:12px;">(50%)</span></td>
    </tr>
    <tr style="border-bottom:1px solid #fde68a;">
      <td style="padding:10px 16px; color:#78350f; font-weight:600;">Solde</td>
      <td style="padding:10px 16px; color:#374151; font-size:14px;">📅 13/06/2026</td>
      <td style="padding:10px 16px; font-weight:700; color:#b45309; white-space:nowrap;">4 375,00 MAD <span style="color:#9ca3af; font-size:12px;">(50%)</span></td>
    </tr>
  </tbody>
</table>',
            'commercial_nom'        => 'Amine ZGHAOUI',
            'commercial_titre'      => 'Director of Sales & Marketing – Morocco',
            'commercial_tel'        => '+212 6 14 09 75 82',
            'site_web'              => 'www.magichotels.ma',
        ];

        // ── Templates admin ──────────────────────────────────────────────────
        $base['admin_url']      = url('/admin/reservations/56');
        $base['agency_name']    = $base['agency_name']    ?? 'Agence Horizon Voyages';
        $base['total_persons']  = $base['total_persons']  ?? '42';
        // admin_new_agency specific
        $base['phone']          = '+212 6 55 44 33 22';
        $base['city']           = 'Casablanca';
        $base['country']        = 'Maroc';
        $base['message']        = '<p><strong>Message :</strong> Nous souhaitons établir un partenariat pour des groupes réguliers.</p>';

        return $base;
    }
}
