<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<title>Facture Proforma {{ $reservation->reference }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 10px;
        color: #000;
        background: #fff;
        line-height: 1.4;
    }
    .page { padding: 22px 30px; }

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

    /* Pied de page */
    .footer { text-align: center; font-size: 8px; color: #555; border-top: 1px solid #ccc; padding-top: 6px; margin-top: 8px; }
</style>
</head>
<body>
<div class="page">
@php
    $hotel     = $reservation->hotel;
    $rooms     = $reservation->rooms;
    // S'assurer que les relations sont bien chargées sur chaque chambre
    $rooms->loadMissing(['roomType', 'occupancyConfig']);
    $supp      = $reservation->supplements;
    $schedules = $reservation->paymentSchedules->sortBy('installment_number');
    $sejours   = $reservation->sejours;

    $nights   = (int) $reservation->nights;
    $checkIn  = $reservation->check_in;
    $checkOut = $reservation->check_out;

    $months = [1=>'Jan',2=>'Fév',3=>'Mar',4=>'Avr',5=>'Mai',6=>'Juin',
               7=>'Juil',8=>'Août',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Déc'];

    // Totaux financiers
    $roomsSubtotal        = $rooms->sum(fn($r) => (float) $r->total_price);
    $discountAgencyPct    = (float) ($reservation->discount_percent ?? 0);
    $discountPromo        = (float) ($reservation->promo_discount_amount ?? 0);
    $suppTotal            = (float) ($reservation->supplement_total ?? 0);
    $extrasCollection     = $reservation->extras ?? collect();
    $extrasTotal          = (float) $extrasCollection->sum('total_price');
    $taxeTotal            = (float) ($reservation->taxe_total ?? 0);

    // Sous-total chambres après promo intégrée dans les prix/nuit
    // (la promo est déjà reflétée dans les lignes du tableau → on l'absorbe ici)
    $roomsSubtotalNet     = $roomsSubtotal - $discountPromo;
    $discountAgencyAmount = $discountAgencyPct > 0 ? $roomsSubtotalNet * $discountAgencyPct / 100 : 0;

    // Total calculé depuis les composantes pour éviter les écarts avec total_price
    $grandTotal           = $roomsSubtotalNet - $discountAgencyAmount + $suppTotal + $extrasTotal + $taxeTotal;

    // Résumé voyageurs
    $totalAdults   = $rooms->sum(fn($r) => ($r->adults   ?? 0) * max(1, $r->quantity ?? 1));
    $totalChildren = $rooms->sum(fn($r) => ($r->children ?? 0) * max(1, $r->quantity ?? 1));
    $totalPax      = $totalAdults + $totalChildren;

    // Résumé séjours
    $nbSejours    = $sejours->count();
    $totalNights  = $sejours->sum(fn($s) => (int) $s['nights']);
@endphp

{{-- EN-TÊTE --}}
<table class="header">
    <tr>
        <td colspan="2" style="text-align:center; padding-bottom:10px; font-size:20px; font-weight:bold; letter-spacing:2px; text-transform:uppercase;">
            Facture proforma
        </td>
    </tr>
    <tr>
        <td style="width:65%">
            @if($hotel->logo)
                <img src="{{ storage_path('app/public/' . $hotel->logo) }}" alt="{{ $hotel->name }}" style="max-height:42px; max-width:116px; margin-bottom:4px;"><br>
            @endif
            <div class="hotel-name">{{ $hotel->name }}</div>
            <div class="hotel-sub">
                @if($hotel->address){{ $hotel->address }} — @endif
                @if($hotel->city){{ $hotel->city }}@endif
                @if($hotel->phone) | Tél : {{ $hotel->phone }}@endif
                @if($hotel->email) | {{ $hotel->email }}@endif
            </div>
        </td>
        <td style="width:35%; text-align:right; vertical-align:top;">
            <div class="doc-info">
                <strong>Réf : {{ $reservation->reference }}</strong><br>
                Émise le {{ now()->format('d/m/Y') }}
                @if($reservation->payment_deadline)
                    <br>Échéance : <strong>{{ $reservation->payment_deadline->format('d/m/Y') }}</strong>
                @endif
            </div>
        </td>
    </tr>
</table>

<hr class="sep">

{{-- CLIENT & RÉSERVATION --}}
<table class="blocks">
    <tr>
        <td class="block">
            <div class="block-title">Client</div>
            @if($reservation->agency)
                <div class="row"><span class="val">{{ $reservation->agency->name }}</span></div>
            @endif
            <div class="row"><span class="val">{{ $reservation->contact_name }}</span></div>
            @if($reservation->email)
                <div class="row"><span class="lbl">Email : </span>{{ $reservation->email }}</div>
            @endif
            @if($reservation->phone)
                <div class="row"><span class="lbl">Tél : </span>{{ $reservation->phone }}</div>
            @endif
            @if($reservation->agency?->city)
                <div class="row" style="font-size:9px; color:#333; margin-top:2px;">
                    @if($reservation->agency->address){{ $reservation->agency->address }}, @endif
                    {{ $reservation->agency->city }}
                    @if($reservation->agency->country) — {{ $reservation->agency->country }}@endif
                </div>
            @endif
        </td>
        <td class="block-gap"></td>
        <td class="block">
            <div class="block-title">Réservation</div>
            <div class="row"><span class="lbl">Hôtel : </span><span class="val">{{ $hotel->name }}</span></div>
            <div class="row">
                <span class="lbl">Séjours : </span>
                <span class="val">{{ $nbSejours }} séjour{{ $nbSejours > 1 ? 's' : '' }} · {{ $totalNights }} nuit{{ $totalNights > 1 ? 's' : '' }} au total</span>
            </div>
            <div class="row">
                <span class="lbl">Personnes : </span>
                <span class="val">
                    {{ $totalPax }}
                    ({{ $totalAdults }} adulte{{ $totalAdults > 1 ? 's' : '' }}
                    @if($totalChildren)
                        ; {{ $totalChildren }} enfant{{ $totalChildren > 1 ? 's' : '' }}
                    @endif)
                </span>
            </div>
        </td>
    </tr>
</table>

{{-- TABLEAU CHAMBRES PAR SÉJOUR --}}
<div class="section">Détail des chambres</div>
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
        @forelse($sejours as $sejour)
        @php
            $sNights   = (int) $sejour['nights'];
            $sIn       = $sejour['check_in'];
            $sOut      = $sejour['check_out'];
            $sRooms    = $sejour['rooms'];
            $rowCount  = $sRooms->count();
            $sejourStr = $sIn->format('d') . ' ' . $months[$sIn->month]
                       . ' → '
                       . $sOut->format('d') . ' ' . $months[$sOut->month] . ' ' . $sOut->format('Y');
        @endphp
        @php
            // Taux de promo applicable pour ce séjour (basé sur le nb de nuits)
            $sPromoRate = ($discountPromo > 0) ? (float) $hotel->getPromoRate($sNights) : 0.0;
        @endphp
        @foreach($sRooms as $room)
        @php
            $qty    = max(1, (int) ($room->quantity ?? 1));
            $oLabel = $room->occupancy_config_label ?? $room->occupancyConfig?->label ?? '';
            $occupation = $oLabel ?: '—';

            $priceOriginal = (float) $room->price_per_night;
            $priceReduced  = $sPromoRate > 0
                ? round($priceOriginal * (1 - $sPromoRate / 100), 2)
                : $priceOriginal;
            $totalReduced  = $sPromoRate > 0
                ? round($priceReduced * $qty * $sNights, 2)
                : (float) $room->total_price;
        @endphp
        <tr>
            @if($loop->first)
            <td class="sejour-cell" rowspan="{{ $rowCount }}">
                {{ $sejourStr }}<br>
                <span style="font-size:8px; font-weight:normal;">{{ $sNights }} nuit{{ $sNights > 1 ? 's' : '' }}</span>
                @if($sPromoRate > 0)
                <br><span style="font-size:7.5px; color:#c00; font-weight:bold;">Promo −{{ number_format($sPromoRate, 0) }}%</span>
                @endif
            </td>
            @endif
            <td>{{ $occupation ?: '—' }}</td>
            <td class="r">{{ $qty }}</td>
            <td class="r">{{ $sNights }}</td>
            <td class="r">
                @if($sPromoRate > 0)
                    <span style="text-decoration:line-through; color:#999; font-size:8px;">{{ number_format($priceOriginal, 2, ',', ' ') }}</span><br>
                    <strong>{{ number_format($priceReduced, 2, ',', ' ') }} MAD</strong>
                @else
                    {{ number_format($priceOriginal, 2, ',', ' ') }} MAD
                @endif
            </td>
            <td class="r"><strong>{{ number_format($totalReduced, 2, ',', ' ') }} MAD</strong></td>
        </tr>
        @endforeach
        @empty
        <tr><td colspan="6" style="text-align:center; padding:8px; color:#555;">Aucune chambre</td></tr>
        @endforelse
    </tbody>
</table>

{{-- SUPPLÉMENTS --}}
@if($supp->isNotEmpty())
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
        @foreach($supp as $s)
        <tr>
            <td><strong>{{ $s->supplement?->title ?? 'Supplément' }}</strong></td>
            <td class="r">{{ $s->adults_count > 0 ? $s->adults_count : '—' }}</td>
            <td class="r">{{ $s->adults_count > 0 ? number_format((float)$s->unit_price_adult, 2, ',', ' ') . ' MAD' : '—' }}</td>
            <td class="r">{{ ($s->children_count ?? 0) > 0 ? $s->children_count : '—' }}</td>
            <td class="r">{{ ($s->children_count ?? 0) > 0 ? number_format((float)($s->unit_price_child ?? 0), 2, ',', ' ') . ' MAD' : '—' }}</td>
            <td class="r"><strong>{{ number_format((float)$s->total_price, 2, ',', ' ') }} MAD</strong></td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- SERVICES EXTRAS --}}
@if($extrasCollection->isNotEmpty())
<div class="section">Services Extras</div>
<table class="tbl2">
    <thead>
        <tr>
            <th>Désignation</th>
            <th>Description</th>
            <th class="r">Qté</th>
            <th class="r">Prix unitaire</th>
            <th class="r">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($extrasCollection as $e)
        <tr>
            <td><strong>{{ $e->name }}</strong></td>
            <td>{{ $e->description ?? '' }}</td>
            <td class="r">{{ (int) $e->quantity }}</td>
            <td class="r">{{ number_format((float) $e->unit_price, 2, ',', ' ') }} MAD</td>
            <td class="r"><strong>{{ number_format((float) $e->total_price, 2, ',', ' ') }} MAD</strong></td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- TOTAUX --}}
<table class="totals-wrap">
    <tr>
        <td class="totals-spacer"></td>
        <td class="totals-box">
            <table class="tot-row">
                <tr>
                    <td>Sous-total chambres</td>
                    <td>{{ number_format($roomsSubtotalNet, 2, ',', ' ') }} MAD</td>
                </tr>
                @if($discountAgencyPct > 0)
                <tr>
                    <td>Remise agence ({{ $discountAgencyPct }}%)</td>
                    <td>- {{ number_format($discountAgencyAmount, 2, ',', ' ') }} MAD</td>
                </tr>
                @endif
                @if($suppTotal > 0)
                <tr>
                    <td>Suppléments</td>
                    <td>+ {{ number_format($suppTotal, 2, ',', ' ') }} MAD</td>
                </tr>
                @endif
                @if($extrasTotal > 0)
                <tr>
                    <td>Services extras</td>
                    <td>+ {{ number_format($extrasTotal, 2, ',', ' ') }} MAD</td>
                </tr>
                @endif
                @if($taxeTotal > 0)
                <tr>
                    <td>Taxe de séjour</td>
                    <td>+ {{ number_format($taxeTotal, 2, ',', ' ') }} MAD</td>
                </tr>
                @endif
                <tr class="tot-grand">
                    <td>TOTAL TTC</td>
                    <td>{{ number_format($grandTotal, 2, ',', ' ') }} MAD</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ÉCHÉANCIER --}}
@if($schedules->isNotEmpty())
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
        @foreach($schedules as $sch)
        @php
            $label = $sch->label ?: ('Échéance #' . $sch->installment_number);
            $pct   = $grandTotal > 0 ? round($sch->amount / $grandTotal * 100) : 0;
            $statusLabel = match($sch->status ?? 'pending') {
                'paid'    => 'Payé',
                'overdue' => 'En retard',
                default   => 'En attente',
            };
        @endphp
        <tr>
            <td>{{ $label }}</td>
            <td>{{ $sch->due_date->format('d/m/Y') }}</td>
            <td class="r"><strong>{{ number_format((float)$sch->amount, 2, ',', ' ') }} MAD</strong></td>
            <td class="r">{{ $pct }}%</td>
            <td class="r">{{ $statusLabel }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- COORDONNÉES BANCAIRES --}}
@if($hotel->bank_rib || $hotel->bank_iban || $hotel->bank_name)
<div class="rib">
    <span class="rib-title">Coordonnées bancaires — </span>
    @if($hotel->bank_name)<span class="rib-lbl">Banque : </span><span class="rib-val">{{ $hotel->bank_name }}</span>@endif
    @if($hotel->bank_swift)  <span class="rib-lbl">SWIFT : </span><span class="rib-val">{{ strtoupper($hotel->bank_swift) }}</span>@endif
    @if($hotel->bank_rib)  <span class="rib-lbl">RIB : </span><span class="rib-val">{{ $hotel->bank_rib }}</span>@endif
    @if($hotel->bank_iban)  <span class="rib-lbl">IBAN : </span><span class="rib-val">{{ $hotel->bank_iban }}</span>@endif
</div>
@endif

{{-- PIED DE PAGE --}}
<div class="footer">
    {{ $hotel->name }}
    @if($hotel->address) — {{ $hotel->address }}@endif
    @if($hotel->city) — {{ $hotel->city }}@endif
    @if($hotel->phone) — Tél : {{ $hotel->phone }}@endif
    | Réf. {{ $reservation->reference }} — Document émis le {{ now()->format('d/m/Y') }}
</div>

</div>
</body>
</html>
