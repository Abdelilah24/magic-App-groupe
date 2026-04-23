@extends('emails.layout')

@section('body')
<h1>Votre demande a bien été reçue </h1> <p>Bonjour <strong>{{ $reservation->contact_name }}</strong>,</p>
<p>Nous avons bien reçu votre demande de réservation groupe. Notre équipe va l'étudier et vous répondra dans les plus brefs délais.</p> {{-- Infos principales --}}
<div class="info-box"> <div class="info-row"> <span class="label">Référence</span> <span class="value"><strong>{{ $reservation->reference }}</strong></span> </div> <div class="info-row"> <span class="label">Agence</span> <span class="value">{{ $reservation->agency_name }}</span> </div> <div class="info-row"> <span class="label">Hôtel</span> <span class="value">{{ $reservation->hotel->name }}</span> </div> <div class="info-row"> <span class="label">Personnes</span> <span class="value">{{ $reservation->total_persons }}</span> </div>
</div> {{-- Détail par séjour --}}
@php
    $sejours         = $reservation->sejours;
    $taxeRate        = (float) ($reservation->hotel->taxe_sejour ?? 19.80);
    $taxeTotalGlobal = 0;
    $roomsTotalGlobal = 0;
@endphp

@foreach($sejours as $i => $sejour)
@php
    $sejourNights     = $sejour['nights'];
    $sejourAdults     = $sejour['rooms']->sum(fn($r) => ($r->adults ?? 0) * ($r->quantity ?? 1));
    $sejourRoomsTotal = $sejour['rooms']->sum('total_price');
    $sejourTaxe       = round($sejourAdults * $sejourNights * $taxeRate); // round per séjour
    $taxeTotalGlobal  += $sejourTaxe;
    $roomsTotalGlobal += $sejourRoomsTotal;
@endphp

<h3 style="font-size:14px; color:#92400e; margin: 20px 0 8px;"> @if($sejours->count() > 1)
 Séjour {{ $i + 1 }} 
    @endif
    {{ $sejour['check_in']->format('d/m/Y') }}  {{ $sejour['check_out']->format('d/m/Y') }}
    ({{ $sejourNights }} nuit{{ $sejourNights > 1 ? 's' : '' }})
</h3> <table style="width:100%; border-collapse:collapse; font-size:13px; margin-bottom:8px;"> <thead> <tr style="background:#fef3c7; color:#92400e;"> <th style="text-align:left; padding:6px 8px; border:1px solid #fde68a;">Chambre / Occupation</th> <th style="text-align:center; padding:6px 8px; border:1px solid #fde68a;">Personnes/ch.</th> <th style="text-align:right; padding:6px 8px; border:1px solid #fde68a;">Montant</th> </tr> </thead> <tbody> @foreach($sejour['rooms'] as $room)
        <tr> <td style="padding:6px 8px; border:1px solid #f3f4f6; color:#374151;"> <strong>{{ $room->quantity }}</strong> × {{ $room->occupancy_config_label ?? $room->roomType->name }}
                <span style="color:#9ca3af; font-size:11px;"> × {{ $sejourNights }} nuit{{ $sejourNights > 1 ? 's' : '' }}</span> </td> <td style="padding:6px 8px; border:1px solid #f3f4f6; text-align:center; color:#6b7280; font-size:12px;"> {{ $room->adults ?? 0 }} ad.@if($room->children) · {{ $room->children }} enf.@endif@if($room->babies) · {{ $room->babies }} bébé@endif
            </td> <td style="padding:6px 8px; border:1px solid #f3f4f6; text-align:right; font-weight:600; color:#374151;"> {{ $room->total_price ? number_format($room->total_price, 0, ',', ' ') . ' MAD' : '' }}
            </td> </tr> @endforeach
        @if($sejourTaxe > 0)
        <tr style="background:#eff6ff;"> <td colspan="2" style="padding:5px 8px; border:1px solid #dbeafe; color:#1d4ed8; font-size:12px;"> Taxe de séjour ({{ $sejourAdults }} adulte(s) × {{ $sejourNights }} nuit(s) × {{ number_format($taxeRate, 2, ',', ' ') }} DHS)
            </td> <td style="padding:5px 8px; border:1px solid #dbeafe; text-align:right; font-weight:600; color:#1d4ed8;"> {{ number_format($sejourTaxe, 0, ',', ' ') }} MAD
            </td> </tr> @endif
        @if($sejours->count() > 1)
        <tr style="background:#fef9ee;"> <td colspan="2" style="padding:6px 8px; border:1px solid #fde68a; font-weight:700; color:#92400e;"> Sous-total séjour {{ $i + 1 }}
            </td> <td style="padding:6px 8px; border:1px solid #fde68a; text-align:right; font-weight:700; color:#92400e;"> {{ number_format($sejourRoomsTotal + $sejourTaxe, 0, ',', ' ') }} MAD
            </td> </tr> @endif
    </tbody>
</table>
@endforeach

{{-- Récapitulatif financier --}}
@if($reservation->total_price)
<table style="width:100%; border-collapse:collapse; font-size:13px; margin-top:12px;"> <tr> <td style="padding:5px 8px; color:#374151;">Hébergement (chambres)</td> <td style="padding:5px 8px; text-align:right; color:#374151;">{{ number_format($roomsTotalGlobal, 0, ',', ' ') }} MAD</td> </tr> @if($taxeTotalGlobal > 0)
    <tr> <td style="padding:5px 8px; color:#1d4ed8;">Taxe de séjour</td> <td style="padding:5px 8px; text-align:right; color:#1d4ed8;">{{ number_format($taxeTotalGlobal, 0, ',', ' ') }} MAD</td> </tr> @endif
    @foreach($reservation->supplements as $rs)
    <tr> <td style="padding:5px 8px; color:{{ $rs->is_mandatory ? '#d97706' : '#7c3aed' }};"> {{ $rs->supplement->title }} ({{ $rs->is_mandatory ? 'obligatoire' : 'optionnel' }})
        </td> <td style="padding:5px 8px; text-align:right; color:{{ $rs->is_mandatory ? '#d97706' : '#7c3aed' }};"> {{ number_format($rs->total_price, 0, ',', ' ') }} MAD
        </td> </tr> @endforeach
    <tr style="background:#fef3c7; border-top:2px solid #fbbf24;"> <td style="padding:8px; font-weight:700; font-size:14px; color:#92400e;">TOTAL ESTIMÉ</td> <td style="padding:8px; text-align:right; font-weight:700; font-size:14px; color:#d97706;"> {{ number_format($reservation->total_price, 2, ',', ' ') }} MAD
        </td> </tr>
</table>
<p style="font-size:11px; color:#9ca3af; margin-top:4px;">* Prix indicatif, confirmé après validation par notre équipe.</p>
@endif

@if($reservation->special_requests)
<div style="margin-top:16px; padding:10px 12px; background:#f9fafb; border-radius:6px; border-left:3px solid #d1d5db;"> <p style="font-size:12px; color:#6b7280; margin:0 0 4px;">Demandes spéciales :</p> <p style="font-size:13px; color:#374151; margin:0;">{{ $reservation->special_requests }}</p>
</div>
@endif

<p style="margin-top:20px;">Vous pouvez suivre l'état de votre demande en temps réel via le bouton ci-dessous.</p> @if($reservation->secureLink)
<div style="text-align:center; margin: 24px 0;"> <a href="{{ route('client.reservation.show', [$reservation->secureLink->token, $reservation]) }}" class="btn"> Suivre ma demande 
    </a>
</div>
@endif

<p>Merci de votre confiance.</p>
@endsection
