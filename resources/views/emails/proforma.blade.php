<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Facture Proforma {{ $reservation->reference }}</title>
<style>
    body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #374151; background: #f9fafb; margin: 0; padding: 0; }
    .wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; }
    .header { background: #1d4ed8; padding: 28px 32px; text-align: center; }
    .header h1 { color: #ffffff; font-size: 20px; margin: 0 0 4px; }
    .header p  { color: #bfdbfe; font-size: 13px; margin: 0; }
    .body { padding: 28px 32px; }
    .body p { margin: 0 0 14px; line-height: 1.6; }
    .ref-box {
        background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px;
        padding: 14px 18px; margin: 18px 0; text-align: center;
    }
    .ref-box .ref-label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; }
    .ref-box .ref-value { font-size: 22px; font-weight: bold; color: #1d4ed8; font-family: monospace; }
    .info-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
    .info-label { color: #6b7280; }
    .info-value { font-weight: 600; color: #111827; }
    .total-row { background: #1d4ed8; color: #ffffff; padding: 12px 18px; border-radius: 8px; text-align: center; margin: 18px 0; }
    .total-row .total-label { font-size: 12px; opacity: 0.8; }
    .total-row .total-value { font-size: 24px; font-weight: bold; color: #fbbf24; }
    .attachment-note {
        background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;
        padding: 12px 16px; margin: 18px 0; font-size: 13px; color: #166534;
    }
    .footer { background: #f3f4f6; padding: 20px 32px; text-align: center; font-size: 11px; color: #9ca3af; }
    .footer a { color: #f59e0b; text-decoration: none; }
</style>
</head>
<body>
<div class="wrapper">

    <div class="header">
        <h1>Facture Proforma</h1>
        <p>{{ $hotel->name }}</p>
    </div>

    <div class="body">
        <p>Bonjour <strong>{{ $reservation->contact_name }}</strong>,</p>

        <p>Veuillez trouver ci-joint la <strong>facture proforma</strong> pour votre réservation auprès de <strong>{{ $hotel->name }}</strong>.</p>

        <div class="ref-box">
            <div class="ref-label">Référence de réservation</div>
            <div class="ref-value">{{ $reservation->reference }}</div>
        </div>

        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin: 16px 0;">
            <tr>
                <td style="padding:7px 0; border-bottom:1px solid #f3f4f6; font-size:13px; color:#6b7280;">Hôtel</td>
                <td style="padding:7px 0; border-bottom:1px solid #f3f4f6; font-size:13px; font-weight:600; color:#111827; text-align:right;">{{ $hotel->name }}</td>
            </tr>
            @php
                $months = [1=>'Janvier',2=>'Février',3=>'Mars',4=>'Avril',5=>'Mai',6=>'Juin',7=>'Juillet',8=>'Août',9=>'Septembre',10=>'Octobre',11=>'Novembre',12=>'Décembre'];
                $ci = $reservation->check_in;
                $co = $reservation->check_out;
                $nights = (int) $reservation->nights;
            @endphp
            <tr>
                <td style="padding:7px 0; border-bottom:1px solid #f3f4f6; font-size:13px; color:#6b7280;">Arrivée</td>
                <td style="padding:7px 0; border-bottom:1px solid #f3f4f6; font-size:13px; font-weight:600; color:#111827; text-align:right;">{{ $ci->format('d') }} {{ $months[$ci->month] }} {{ $ci->format('Y') }}</td>
            </tr>
            <tr>
                <td style="padding:7px 0; border-bottom:1px solid #f3f4f6; font-size:13px; color:#6b7280;">Départ</td>
                <td style="padding:7px 0; border-bottom:1px solid #f3f4f6; font-size:13px; font-weight:600; color:#111827; text-align:right;">{{ $co->format('d') }} {{ $months[$co->month] }} {{ $co->format('Y') }}</td>
            </tr>
            <tr>
                <td style="padding:7px 0; font-size:13px; color:#6b7280;">Durée</td>
                <td style="padding:7px 0; font-size:13px; font-weight:600; color:#111827; text-align:right;">{{ $nights }} nuit{{ $nights > 1 ? 's' : '' }}</td>
            </tr>
        </table>

        <div class="total-row">
            <div class="total-label">TOTAL TTC</div>
            <div class="total-value">{{ number_format((float)$reservation->total_price, 2, ',', ' ') }} MAD</div>
        </div>

        <div class="attachment-note">
            📎 La facture proforma détaillée (chambres, suppléments, remises, échéancier) est jointe à cet email au format <strong>PDF</strong>.
        </div>

        @if($reservation->payment_deadline)
        <p style="color:#dc2626; font-size:13px;">
            ⚠️ <strong>Date limite de paiement :</strong> {{ $reservation->payment_deadline->format('d/m/Y') }}
        </p>
        @endif

        <p>Pour toute question, n'hésitez pas à nous contacter.</p>

        <p>Cordialement,<br>
        <strong>{{ $hotel->name }}</strong>
        @if($hotel->phone)<br>{{ $hotel->phone }}@endif
        @if($hotel->email)<br>{{ $hotel->email }}@endif
        </p>
    </div>

    <div class="footer">
        {{ $hotel->name }}
        @if($hotel->address) — {{ $hotel->address }}@endif
        @if($hotel->city), {{ $hotel->city }}@endif
        @if($hotel->country), {{ $hotel->country }}@endif
        <br>
        Ce document est une facture proforma et ne constitue pas une facture définitive.
    </div>

</div>
</body>
</html>
