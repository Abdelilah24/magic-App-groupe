<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PdfTemplate;
use Illuminate\Http\Request;

class PdfTemplateController extends Controller
{
    public function index()
    {
        $templates = PdfTemplate::orderBy('name')->get();
        return view('admin.pdf-templates.index', compact('templates'));
    }

    public function edit(PdfTemplate $pdfTemplate)
    {
        return view('admin.pdf-templates.edit', ['template' => $pdfTemplate]);
    }

    public function update(Request $request, PdfTemplate $pdfTemplate)
    {
        $data = $request->validate([
            'html_body' => 'required|string',
            'css'       => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $pdfTemplate->update([
            'html_body' => $this->decodeMceProtected($data['html_body']),
            'css'       => $data['css'] ?? '',
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.pdf-templates.edit', $pdfTemplate)
            ->with('success', "Template PDF « {$pdfTemplate->name} » mis à jour.");
    }

    /**
     * Aperçu du template avec des données fictives.
     */
    public function preview(PdfTemplate $pdfTemplate)
    {
        $sampleData = $this->getSampleData();
        $html       = $pdfTemplate->renderBody($sampleData);

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

    /**
     * Données fictives pour l'aperçu du template proforma.
     */
    private function getSampleData(): array
    {
        $tableauChambres = '
<table class="tbl">
    <thead>
        <tr>
            <th style="width:18%">Séjour</th>
            <th style="width:30%">Occupation</th>
            <th class="r" style="width:8%">Qté</th>
            <th class="r" style="width:8%">Nuits</th>
            <th class="r" style="width:18%">Prix / nuit</th>
            <th class="r" style="width:18%">Sous-total</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="sejour-cell" rowspan="2">
                10 Avr → 15 Avr 2026<br>
                <span style="font-size:8px; font-weight:normal;">5 nuits</span>
            </td>
            <td>2 adultes (DBL Standard)</td>
            <td class="r">10</td>
            <td class="r">5</td>
            <td class="r">1 200,00 MAD</td>
            <td class="r"><strong>60 000,00 MAD</strong></td>
        </tr>
        <tr>
            <td>3 adultes (Triple Standard)</td>
            <td class="r">5</td>
            <td class="r">5</td>
            <td class="r">1 600,00 MAD</td>
            <td class="r"><strong>40 000,00 MAD</strong></td>
        </tr>
    </tbody>
</table>';

        $tableauTotaux = '
<table class="totals-wrap">
    <tr>
        <td class="totals-spacer"></td>
        <td class="totals-box">
            <table class="tot-row">
                <tr>
                    <td>Sous-total chambres</td>
                    <td>100 000,00 MAD</td>
                </tr>
                <tr>
                    <td>Remise agence (10%)</td>
                    <td>- 10 000,00 MAD</td>
                </tr>
                <tr>
                    <td>Suppléments</td>
                    <td>+ 2 500,00 MAD</td>
                </tr>
                <tr>
                    <td>Taxe de séjour</td>
                    <td>+ 990,00 MAD</td>
                </tr>
                <tr class="tot-grand">
                    <td>TOTAL TTC</td>
                    <td>93 490,00 MAD</td>
                </tr>
            </table>
        </td>
    </tr>
</table>';

        $tableauSupplements = '
<div class="section">Suppléments</div>
<table class="tbl2">
    <thead>
        <tr>
            <th>Désignation</th>
            <th class="r">Nb adultes</th>
            <th class="r">Tarif adulte</th>
            <th class="r">Nb enfants</th>
            <th class="r">Tarif enfant</th>
            <th class="r">Total</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Dîner de gala</strong></td>
            <td class="r">45</td>
            <td class="r">50,00 MAD</td>
            <td class="r">—</td>
            <td class="r">—</td>
            <td class="r"><strong>2 500,00 MAD</strong></td>
        </tr>
    </tbody>
</table>';

        $tableauEcheancier = '
<div class="section">Échéancier de paiement</div>
<table class="sch-tbl">
    <thead>
        <tr>
            <th>Libellé</th>
            <th>Date limite</th>
            <th class="r">Montant</th>
            <th class="r">%</th>
            <th class="r">Statut</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Acompte 50%</td>
            <td>01/05/2026</td>
            <td class="r"><strong>46 745,00 MAD</strong></td>
            <td class="r">50%</td>
            <td class="r">En attente</td>
        </tr>
        <tr>
            <td>Solde</td>
            <td>01/06/2026</td>
            <td class="r"><strong>46 745,00 MAD</strong></td>
            <td class="r">50%</td>
            <td class="r">En attente</td>
        </tr>
    </tbody>
</table>';

        $coordonneesBancaires = '
<div class="rib">
    <span class="rib-title">Coordonnées bancaires — </span>
    <span class="rib-lbl">Banque : </span><span class="rib-val">Banque Populaire</span>
    <span class="rib-lbl">SWIFT : </span><span class="rib-val">BCPPMAMCXXX</span>
    <span class="rib-lbl">RIB : </span><span class="rib-val">181 810 21234 56789 01234 56 78</span>
    <span class="rib-lbl">IBAN : </span><span class="rib-val">MA64 1818 1021 2345 6789 0123 4567 8</span>
</div>';

        return [
            'titre'                 => 'FACTURE PROFORMA',
            'reference'             => 'MH-2026-00042',
            'date_emission'         => now()->format('d/m/Y'),
            'echeance_ligne'        => '<br>Échéance : <strong>30/04/2026</strong>',
            'hotel_logo'            => '',
            'hotel_nom'             => 'Hotel Aqua Mirage Marrakech',
            'hotel_info'            => 'Route de Casablanca, Km 5 — Marrakech | Tél : +212 5 24 XX XX XX | reservations@aquamirage.ma',
            'client_nom'            => '<div class="row"><span class="val">Agence Horizon Voyages</span></div>',
            'client_contact'        => '<div class="row"><span class="val">Jean Dupont</span></div>',
            'client_email'          => '<div class="row"><span class="lbl">Email : </span>jean.dupont@horizon-voyages.ma</div>',
            'client_telephone'      => '<div class="row"><span class="lbl">Tél : </span>+212 6 55 44 33 22</div>',
            'client_adresse'        => '<div class="row" style="font-size:9px; color:#333; margin-top:2px;">123 Rue Mohammed V, Casablanca — Maroc</div>',
            'reservation_hotel'     => '<div class="row"><span class="lbl">Hôtel : </span><span class="val">Hotel Aqua Mirage Marrakech</span></div>',
            'reservation_sejours'   => '<div class="row"><span class="lbl">Séjours : </span><span class="val">1 séjour · 5 nuits au total</span></div>',
            'reservation_personnes' => '<div class="row"><span class="lbl">Personnes : </span><span class="val">45 (45 adultes)</span></div>',
            'tableau_chambres'      => $tableauChambres,
            'tableau_supplements'   => $tableauSupplements,
            'tableau_totaux'        => $tableauTotaux,
            'tableau_echeancier'    => $tableauEcheancier,
            'coordonnees_bancaires' => $coordonneesBancaires,
            'conditions_hotel'      => '',
            'logo_principal'        => '',
            'pied_de_page'          => 'Hotel Aqua Mirage Marrakech — Route de Casablanca, Km 5 — Marrakech | Réf. MH-2026-00042 — Document émis le ' . now()->format('d/m/Y'),
        ];
    }
}
