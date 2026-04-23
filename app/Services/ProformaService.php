<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Reservation;

/**
 * Construit les données nécessaires au rendu du PDF « Facture Proforma ».
 * Extrait de ReservationController pour être réutilisable depuis les Mailables.
 */
class ProformaService
{
    /**
     * Prépare le tableau de variables à injecter dans le PdfTemplate ou la vue Blade.
     */
    public function buildData(Reservation $reservation): array
    {
        // 'sejours' est un accesseur (getSejoursAttribute), pas une relation —
        // il se calcule à partir de 'rooms', donc on charge 'rooms' ici.
        $reservation->loadMissing([
            'hotel',
            'rooms.roomType',
            'rooms.occupancyConfig',
            'supplements.supplement',
            'paymentSchedules',
            'agency',
            'extras',
        ]);

        $hotel     = $reservation->hotel;
        $rooms     = $reservation->rooms;
        $supp      = $reservation->supplements;
        $extras    = $reservation->extras;
        $schedules = $reservation->paymentSchedules->sortBy('installment_number');
        $sejours   = $reservation->sejours;

        $months = [
            1 => 'Jan', 2 => 'Fév', 3 => 'Mar', 4 => 'Avr',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juil', 8 => 'Août',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc',
        ];

        // ── Totaux financiers ──────────────────────────────────────────────────
        $roomsSubtotal        = $rooms->sum(fn($r) => (float) $r->total_price);
        $discountAgencyPct    = (float) ($reservation->discount_percent ?? 0);
        $discountPromo        = (float) ($reservation->promo_discount_amount ?? 0);
        $suppTotal            = (float) ($reservation->supplement_total ?? 0);
        $extrasTotal          = (float) $extras->sum('total_price');
        $taxeTotal            = (float) ($reservation->taxe_total ?? 0);

        $roomsSubtotalNet     = $roomsSubtotal - $discountPromo;
        $discountAgencyAmount = $discountAgencyPct > 0 ? $roomsSubtotalNet * $discountAgencyPct / 100 : 0;
        $grandTotal           = $roomsSubtotalNet - $discountAgencyAmount + $suppTotal + $extrasTotal + $taxeTotal;

        // ── Résumé voyageurs ───────────────────────────────────────────────────
        $totalAdults   = $rooms->sum(fn($r) => ($r->adults   ?? 0) * max(1, $r->quantity ?? 1));
        $totalChildren = $rooms->sum(fn($r) => ($r->children ?? 0) * max(1, $r->quantity ?? 1));
        $totalPax      = $totalAdults + $totalChildren;

        $nbSejours   = $sejours->count();
        $totalNights = $sejours->sum(fn($s) => (int) $s['nights']);

        // ── Blocs simples ──────────────────────────────────────────────────────

        // Hotel logo
        $hotelLogo = '';
        if ($hotel->logo) {
            $hotelLogo = '<img src="' . storage_path('app/public/' . $hotel->logo) . '" alt="' . htmlspecialchars($hotel->name) . '" style="max-height:42px; max-width:116px; margin-bottom:4px;"><br>';
        }

        // Hotel info
        $hotelInfoParts = [];
        if ($hotel->address) $hotelInfoParts[] = $hotel->address;
        if ($hotel->city)    $hotelInfoParts[] = $hotel->city;
        $hotelInfo = implode(' — ', $hotelInfoParts);
        if ($hotel->phone) $hotelInfo .= ' | Tél : ' . $hotel->phone;
        if ($hotel->email) $hotelInfo .= ' | ' . $hotel->email;

        // Échéance ligne
        $echeanceLigne = '';
        if ($reservation->payment_deadline) {
            $echeanceLigne = '<br>Échéance : <strong>' . $reservation->payment_deadline->format('d/m/Y') . '</strong>';
        }

        // Client blocks
        $clientNom = '';
        if ($reservation->agency) {
            $clientNom = '<div class="row"><span class="val">' . htmlspecialchars($reservation->agency->name) . '</span></div>';
        }
        $clientContact = '<div class="row"><span class="val">' . htmlspecialchars((string) $reservation->contact_name) . '</span></div>';
        $clientEmail   = $reservation->email
            ? '<div class="row"><span class="lbl">Email : </span>' . htmlspecialchars($reservation->email) . '</div>'
            : '';
        $clientTel     = $reservation->phone
            ? '<div class="row"><span class="lbl">Tél : </span>' . htmlspecialchars($reservation->phone) . '</div>'
            : '';
        $clientAdresse = '';
        if ($reservation->agency?->city) {
            $parts = [];
            if ($reservation->agency->address) $parts[] = $reservation->agency->address;
            $parts[] = $reservation->agency->city;
            if ($reservation->agency->country) $parts[] = '— ' . $reservation->agency->country;
            $clientAdresse = '<div class="row" style="font-size:9px; color:#333; margin-top:2px;">' . htmlspecialchars(implode(', ', $parts)) . '</div>';
        }

        // Reservation blocks
        $reservationHotel     = '<div class="row"><span class="lbl">Hôtel : </span><span class="val">' . htmlspecialchars($hotel->name) . '</span></div>';
        $reservationSejours   = '<div class="row"><span class="lbl">Séjours : </span><span class="val">'
            . $nbSejours . ' séjour' . ($nbSejours > 1 ? 's' : '') . ' · '
            . $totalNights . ' nuit' . ($totalNights > 1 ? 's' : '') . ' au total'
            . '</span></div>';
        $reservationPersonnes = '<div class="row"><span class="lbl">Personnes : </span><span class="val">'
            . $totalPax . ' (' . $totalAdults . ' adulte' . ($totalAdults > 1 ? 's' : '')
            . ($totalChildren ? '; ' . $totalChildren . ' enfant' . ($totalChildren > 1 ? 's' : '') : '')
            . ')</span></div>';

        // ── Tableau chambres ───────────────────────────────────────────────────
        $tableauChambres = '<table class="tbl"><thead><tr>'
            . '<th style="width:18%">Séjour</th>'
            . '<th style="width:30%">Occupation</th>'
            . '<th class="r" style="width:8%">Qté</th>'
            . '<th class="r" style="width:8%">Nuits</th>'
            . '<th class="r" style="width:18%">Prix / nuit</th>'
            . '<th class="r" style="width:18%">Sous-total</th>'
            . '</tr></thead><tbody>';

        if ($sejours->isEmpty()) {
            $tableauChambres .= '<tr><td colspan="6" style="text-align:center; padding:8px; color:#555;">Aucune chambre</td></tr>';
        } else {
            foreach ($sejours as $sejour) {
                $sNights   = (int) $sejour['nights'];
                $sIn       = $sejour['check_in'];
                $sOut      = $sejour['check_out'];
                $sRooms    = $sejour['rooms'];
                $rowCount  = $sRooms->count();
                $sejourStr = $sIn->format('d') . ' ' . $months[$sIn->month]
                    . ' → '
                    . $sOut->format('d') . ' ' . $months[$sOut->month] . ' ' . $sOut->format('Y');

                $sPromoRate = ($discountPromo > 0) ? (float) $hotel->getPromoRate($sNights) : 0.0;

                $isFirst = true;
                foreach ($sRooms as $room) {
                    $qty           = max(1, (int) ($room->quantity ?? 1));
                    $oLabel        = $room->occupancy_config_label ?? $room->occupancyConfig?->label ?? '';
                    $occupation    = $oLabel ?: '—';

                    $priceOriginal = (float) $room->price_per_night;
                    $priceReduced  = $sPromoRate > 0
                        ? round($priceOriginal * (1 - $sPromoRate / 100), 2)
                        : $priceOriginal;
                    $totalReduced  = $sPromoRate > 0
                        ? round($priceReduced * $qty * $sNights, 2)
                        : (float) $room->total_price;

                    $tableauChambres .= '<tr>';

                    if ($isFirst) {
                        $tableauChambres .= '<td class="sejour-cell" rowspan="' . $rowCount . '">'
                            . htmlspecialchars($sejourStr) . '<br>'
                            . '<span style="font-size:8px; font-weight:normal;">' . $sNights . ' nuit' . ($sNights > 1 ? 's' : '') . '</span>';
                        if ($sPromoRate > 0) {
                            $tableauChambres .= '<br><span style="font-size:7.5px; color:#c00; font-weight:bold;">Promo −' . number_format($sPromoRate, 0) . '%</span>';
                        }
                        $tableauChambres .= '</td>';
                        $isFirst = false;
                    }

                    $priceCell = $sPromoRate > 0
                        ? '<span style="text-decoration:line-through; color:#999; font-size:8px;">' . number_format($priceOriginal, 2, ',', ' ') . '</span><br>'
                          . '<strong>' . number_format($priceReduced, 2, ',', ' ') . ' MAD</strong>'
                        : number_format($priceOriginal, 2, ',', ' ') . ' MAD';

                    $tableauChambres .= '<td>' . htmlspecialchars($occupation) . '</td>'
                        . '<td class="r">' . $qty . '</td>'
                        . '<td class="r">' . $sNights . '</td>'
                        . '<td class="r">' . $priceCell . '</td>'
                        . '<td class="r"><strong>' . number_format($totalReduced, 2, ',', ' ') . ' MAD</strong></td>'
                        . '</tr>';
                }
            }
        }

        $tableauChambres .= '</tbody></table>';

        // ── Tableau suppléments ────────────────────────────────────────────────
        $tableauSupplements = '';
        if ($supp->isNotEmpty()) {
            $tableauSupplements = '<div class="section">Suppléments</div>'
                . '<table class="tbl2"><thead><tr>'
                . '<th>Désignation</th>'
                . '<th class="r">Nb adultes</th>'
                . '<th class="r">Tarif adulte</th>'
                . '<th class="r">Nb enfants</th>'
                . '<th class="r">Tarif enfant</th>'
                . '<th class="r">Total</th>'
                . '</tr></thead><tbody>';

            foreach ($supp as $s) {
                $tableauSupplements .= '<tr>'
                    . '<td><strong>' . htmlspecialchars($s->supplement?->title ?? 'Supplément') . '</strong></td>'
                    . '<td class="r">' . ($s->adults_count > 0 ? $s->adults_count : '—') . '</td>'
                    . '<td class="r">' . ($s->adults_count > 0 ? number_format((float) $s->unit_price_adult, 2, ',', ' ') . ' MAD' : '—') . '</td>'
                    . '<td class="r">' . (($s->children_count ?? 0) > 0 ? $s->children_count : '—') . '</td>'
                    . '<td class="r">' . (($s->children_count ?? 0) > 0 ? number_format((float) ($s->unit_price_child ?? 0), 2, ',', ' ') . ' MAD' : '—') . '</td>'
                    . '<td class="r"><strong>' . number_format((float) $s->total_price, 2, ',', ' ') . ' MAD</strong></td>'
                    . '</tr>';
            }

            $tableauSupplements .= '</tbody></table>';
        }

        // ── Tableau services extras ────────────────────────────────────────────
        $tableauExtras = '';
        if ($extras->isNotEmpty()) {
            $tableauExtras = '<div class="section">Services Extras</div>'
                . '<table class="tbl2"><thead><tr>'
                . '<th>Désignation</th>'
                . '<th>Description</th>'
                . '<th class="r">Qté</th>'
                . '<th class="r">Prix unitaire</th>'
                . '<th class="r">Total</th>'
                . '</tr></thead><tbody>';

            foreach ($extras as $e) {
                $tableauExtras .= '<tr>'
                    . '<td><strong>' . htmlspecialchars($e->name) . '</strong></td>'
                    . '<td>' . htmlspecialchars((string) $e->description) . '</td>'
                    . '<td class="r">' . (int) $e->quantity . '</td>'
                    . '<td class="r">' . number_format((float) $e->unit_price, 2, ',', ' ') . ' MAD</td>'
                    . '<td class="r"><strong>' . number_format((float) $e->total_price, 2, ',', ' ') . ' MAD</strong></td>'
                    . '</tr>';
            }

            $tableauExtras .= '</tbody></table>';
        }

        // ── Tableau totaux ─────────────────────────────────────────────────────
        $totRows = '<tr><td>Sous-total chambres</td><td>' . number_format($roomsSubtotalNet, 2, ',', ' ') . ' MAD</td></tr>';
        if ($discountAgencyPct > 0) {
            $totRows .= '<tr><td>Remise agence (' . $discountAgencyPct . '%)</td><td>- ' . number_format($discountAgencyAmount, 2, ',', ' ') . ' MAD</td></tr>';
        }
        if ($suppTotal > 0) {
            $totRows .= '<tr><td>Suppléments</td><td>+ ' . number_format($suppTotal, 2, ',', ' ') . ' MAD</td></tr>';
        }
        if ($extrasTotal > 0) {
            $totRows .= '<tr><td>Services extras</td><td>+ ' . number_format($extrasTotal, 2, ',', ' ') . ' MAD</td></tr>';
        }
        if ($taxeTotal > 0) {
            $totRows .= '<tr><td>Taxe de séjour</td><td>+ ' . number_format($taxeTotal, 2, ',', ' ') . ' MAD</td></tr>';
        }
        $totRows .= '<tr class="tot-grand"><td>TOTAL TTC</td><td>' . number_format($grandTotal, 2, ',', ' ') . ' MAD</td></tr>';

        $tableauTotaux = '<table class="totals-wrap">'
            . '<tr><td class="totals-spacer"></td>'
            . '<td class="totals-box"><table class="tot-row">' . $totRows . '</table></td>'
            . '</tr></table>';

        // ── Tableau échéancier ─────────────────────────────────────────────────
        $tableauEcheancier = '';
        if ($schedules->isNotEmpty()) {
            $tableauEcheancier = '<div class="section">Échéancier de paiement</div>'
                . '<table class="sch-tbl"><thead><tr>'
                . '<th>Libellé</th><th>Date limite</th>'
                . '<th class="r">Montant</th><th class="r">%</th><th class="r">Statut</th>'
                . '</tr></thead><tbody>';

            foreach ($schedules as $sch) {
                $label       = $sch->label ?: ('Échéance #' . $sch->installment_number);
                $pct         = $grandTotal > 0 ? round($sch->amount / $grandTotal * 100) : 0;
                $statusLabel = match($sch->status ?? 'pending') {
                    'paid'    => 'Payé',
                    'overdue' => 'En retard',
                    default   => 'En attente',
                };

                $tableauEcheancier .= '<tr>'
                    . '<td>' . htmlspecialchars($label) . '</td>'
                    . '<td>' . $sch->due_date->format('d/m/Y') . '</td>'
                    . '<td class="r"><strong>' . number_format((float) $sch->amount, 2, ',', ' ') . ' MAD</strong></td>'
                    . '<td class="r">' . $pct . '%</td>'
                    . '<td class="r">' . $statusLabel . '</td>'
                    . '</tr>';
            }

            $tableauEcheancier .= '</tbody></table>';
        }

        // ── Coordonnées bancaires ──────────────────────────────────────────────
        $coordonneesBancaires = '';
        if ($hotel->bank_rib || $hotel->bank_iban || $hotel->bank_name) {
            $coordonneesBancaires = '<div class="rib"><span class="rib-title">Coordonnées bancaires — </span>';
            if ($hotel->bank_name)  $coordonneesBancaires .= '<span class="rib-lbl">Banque : </span><span class="rib-val">' . htmlspecialchars($hotel->bank_name) . '</span> ';
            if ($hotel->bank_swift) $coordonneesBancaires .= '<span class="rib-lbl">SWIFT : </span><span class="rib-val">' . htmlspecialchars(strtoupper($hotel->bank_swift)) . '</span> ';
            if ($hotel->bank_rib)   $coordonneesBancaires .= '<span class="rib-lbl">RIB : </span><span class="rib-val">' . htmlspecialchars($hotel->bank_rib) . '</span> ';
            if ($hotel->bank_iban)  $coordonneesBancaires .= '<span class="rib-lbl">IBAN : </span><span class="rib-val">' . htmlspecialchars($hotel->bank_iban) . '</span>';
            $coordonneesBancaires .= '</div>';
        }

        // ── Logo principal ─────────────────────────────────────────────────────
        $appLogo       = AppSetting::get(AppSetting::KEY_APP_LOGO);
        $logoPrincipal = '';
        if ($appLogo) {
            $logoPath = storage_path('app/public/' . $appLogo);
            if (file_exists($logoPath)) {
                $logoPrincipal = '<img src="' . $logoPath . '" alt="Logo">';
            }
        }

        // ── Pied de page ───────────────────────────────────────────────────────
        $piedDePage = $hotel->name;
        if ($hotel->address) $piedDePage .= ' — ' . $hotel->address;
        if ($hotel->city)    $piedDePage .= ' — ' . $hotel->city;
        if ($hotel->phone)   $piedDePage .= ' — Tél : ' . $hotel->phone;
        $piedDePage .= ' | Réf. ' . $reservation->reference . ' — Document émis le ' . now()->format('d/m/Y');

        return [
            'titre'                 => 'FACTURE PROFORMA',
            'reference'             => $reservation->reference,
            'date_emission'         => now()->format('d/m/Y'),
            'echeance_ligne'        => $echeanceLigne,
            'hotel_logo'            => $hotelLogo,
            'hotel_nom'             => htmlspecialchars($hotel->name),
            'hotel_info'            => htmlspecialchars($hotelInfo),
            'client_nom'            => $clientNom,
            'client_contact'        => $clientContact,
            'client_email'          => $clientEmail,
            'client_telephone'      => $clientTel,
            'client_adresse'        => $clientAdresse,
            'reservation_hotel'     => $reservationHotel,
            'reservation_sejours'   => $reservationSejours,
            'reservation_personnes' => $reservationPersonnes,
            'tableau_chambres'      => $tableauChambres,
            'tableau_supplements'   => $tableauSupplements,
            'tableau_extras'        => $tableauExtras,
            'tableau_totaux'        => $tableauTotaux,
            'tableau_echeancier'    => $tableauEcheancier,
            'coordonnees_bancaires' => $coordonneesBancaires,
            'conditions_hotel'      => $this->buildConditionsHotel(),
            'logo_principal'        => $logoPrincipal,
            'pied_de_page'          => htmlspecialchars($piedDePage),
        ];
    }

    /**
     * Bloc HTML statique des conditions générales de l'hôtel.
     */
    public function buildConditionsHotel(): string
    {
        return <<<'HTML'
<div class="conditions">
<div class="conditions-title">Conditions de l&#39;hôtel</div>

<div class="conditions-sub">Horaires d&#39;accès :</div>
<ul>
  <li><strong>Check-In :</strong> À partir de 15h00. Accès à l&#39;hôtel strictement interdit pour toute arrivée avant cette heure-ci.</li>
  <li><strong>Check-Out :</strong> À midi. Les clients sont priés de libérer les chambres à 12h00 au plus tard. Ils peuvent laisser les bagages à la réception, prendre leur déjeuner et quitter l&#39;hôtel à 15h00 au plus tard.</li>
</ul>

<div class="conditions-sub">Conditions enfant :</div>
<ul>
  <li>Est considérée <strong>bébé</strong> toute personne ayant moins de 24 mois le jour du départ.</li>
  <li>Est considérée <strong>enfant</strong> toute personne ayant moins de 12 ans le jour du départ.</li>
  <li>Est considérée <strong>adulte</strong> toute personne ayant 12 ans et plus le jour du départ.</li>
  <li>Un changement de catégorie de chambre pourra être appliqué selon le nombre et l&#39;âge des clients, ainsi que la disponibilité des chambres, moyennant un supplément.</li>
</ul>

<div class="conditions-sub">Supplément arrivée anticipée et départ tardif :</div>
<ol>
  <li style="font-weight:bold; margin-bottom:2px;">Accès à l&#39;hôtel (sans chambres)
    <ul>
      <li>Accès à partir de 09h00 : <strong>300 dh/adulte — 150 dh/enfant.</strong> Attribution des chambres à 15h00.</li>
      <li>Accès à partir de 11h00 : <strong>150 dh/adulte — 75 dh/enfant.</strong> Attribution des chambres à 15h00.</li>
      <li>Départ tardif jusqu&#39;à 18h00 : <strong>150 dh/adulte — 75 dh/enfant.</strong> Libération des chambres à 12h00.</li>
      <li>Départ tardif jusqu&#39;à 20h00 : <strong>300 dh/adulte — 150 dh/enfant.</strong> Libération des chambres à 12h00.</li>
    </ul>
  </li>
  <li style="font-weight:bold; margin-bottom:2px;">Accès à l&#39;hôtel (avec chambres) – Selon disponibilité
    <ul>
      <li>Check-in à partir de 09h00 (confirmé la veille) : <strong>900 dh/chambre.</strong></li>
      <li>Check-in à partir de 11h00 (confirmé la veille) : <strong>500 dh/chambre.</strong></li>
      <li>Check-out tardif jusqu&#39;à 15h00 (confirmé le jour du départ) : <strong>500 dh/chambre.</strong></li>
      <li>Check-out tardif jusqu&#39;à 18h00 (confirmé le jour du départ) : <strong>900 dh/chambre.</strong></li>
    </ul>
  </li>
</ol>

<div class="conditions-sub">Conditions groupes :</div>
<p class="conditions-body">Minimum 11 chambres. En dessous de ce nombre, la demande est traitée comme une réservation individuelle.</p>

<div class="conditions-sub">Conditions d&#39;annulation et No-show</div>
<p class="conditions-body">Veuillez noter que 50% du montant global du séjour seront facturés pour tout no-show total ou partiel et pour toute annulation, avec un minimum d&#39;une nuit.</p>

<div class="conditions-sub">Modes de paiement acceptés :</div>
<ul>
  <li>Virement normal ou instantané</li>
  <li>Chèque certifié</li>
  <li>Versement</li>
</ul>

<p class="conditions-body" style="margin-top:5px;">
L&#39;hôtel peut refuser tout paiement qui ne respecte pas les délais et conditions. Le règlement intérieur de l&#39;hôtel s&#39;applique à l&#39;ensemble des réservations. Tout séjour entraîne l&#39;acceptation des conditions générales de vente, du règlement intérieur et des conditions particulières.
</p>
<p class="conditions-body" style="margin-top:3px;">
Tout litige pouvant naître de l&#39;interprétation et/ou de l&#39;exécution des présentes conditions est soumis au droit Marocain et relève de la compétence exclusive des tribunaux de commerce de Marrakech.
</p>
<p class="conditions-body" style="margin-top:3px;">
Nous restons à votre entière disposition pour tout complément d&#39;information et vous invitons à visiter notre site <strong>www.magichotels.ma</strong> pour plus de détails sur l&#39;hôtel.
</p>

<div class="contact-block">
  <strong>Best regards / Cordialement,</strong><br>
  <strong>Amine ZGHAOUI</strong><br>
  Director of Sales &amp; Marketing – Morocco<br>
  +212 6 14 09 75 82<br>
  www.magichotels.ma
  <div class="env-note">🌿 Please consider the environment before printing this email. Pensez à l&#39;environnement avant d&#39;imprimer cet e-mail.</div>
</div>
</div>
HTML;
    }
}
