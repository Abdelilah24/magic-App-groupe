@extends('layouts.client')
@section('title', 'Fiche de police  ' . $reservation->reference)
@section('main-class', 'w-full max-w-full')

@section('content')
<div class="space-y-6 px-4"> {{-- En-tête --}}
    <div class="bg-white border border-gray-200 rounded-xl p-6"> <div class="flex items-start gap-4"> <div class="text-4xl"></div> <div class="flex-1"> <h1 class="text-xl font-bold text-gray-900">Fiche de police  Enregistrement des voyageurs</h1> <p class="text-gray-500 text-sm mt-1"> Réservation <strong class="font-mono text-amber-600">{{ $reservation->reference }}</strong> · {{ $reservation->hotel->name }}
                    · Arrivée le {{ $reservation->check_in->format('d/m/Y') }}
                </p> <p class="text-xs text-gray-400 mt-2"> Conformément à la réglementation marocaine, veuillez renseigner les informations de chaque voyageur.
 Ces données sont transmises aux autorités locales dans les 24h suivant votre arrivée.
                </p> </div> </div> {{-- Progression globale --}}
        <div class="mt-4"> <div class="flex justify-between text-xs text-gray-500 mb-1"> <span>{{ $filledCount }} / {{ $totalSlots }} fiche(s) complète(s)</span> <span>{{ $totalSlots > 0 ? round($filledCount / $totalSlots * 100) : 0 }}%</span> </div> <div class="w-full bg-gray-100 rounded-full h-2"> <div class="h-2 rounded-full {{ $filledCount === $totalSlots && $totalSlots > 0 ? 'bg-emerald-500' : 'bg-amber-400' }}"
                     style="width: {{ $totalSlots > 0 ? min(100, round($filledCount/$totalSlots*100)) : 0 }}%"></div> </div> </div> {{-- Navigation pagination --}}
        @if($totalPages > 1)
        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between text-sm"> <span class="text-gray-500 text-xs">Page {{ $page }} / {{ $totalPages }}  {{ $totalSlots }} voyageurs au total</span> <div class="flex gap-2"> @if($page > 1)
                <a href="?page={{ $page - 1 }}" class="px-3 py-1.5 border border-gray-200 rounded-lg hover:bg-gray-50 text-xs font-medium text-gray-600"> Précédente</a> @endif
                @for($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++)
                <a href="?page={{ $p }}" class="px-3 py-1.5 border rounded-lg text-xs font-semibold transition-colors
                    {{ $p === $page ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-gray-600 border-gray-200 hover:border-amber-300' }}">{{ $p }}</a> @endfor
                @if($page < $totalPages)
                <a href="?page={{ $page + 1 }}" class="px-3 py-1.5 border border-gray-200 rounded-lg hover:bg-gray-50 text-xs font-medium text-gray-600">Suivante </a> @endif
            </div> </div> @endif
    </div> @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-5 py-3 text-sm font-medium"> {{ session('success') }}
    </div> @endif

    {{-- Formulaire --}}
    <form method="POST" action="{{ $formAction }}?page={{ $page }}"> @csrf

        <div class="space-y-6"> @foreach($groupedSlots as $sejour)

        {{-- Séjour header --}}
        @if(count($groupedSlots) > 1 || $page > 1)
        <div class="flex items-center gap-3"> <div class="h-px flex-1 bg-gray-200"></div> <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Séjour {{ $sejour['sejour_index'] }}</span> <div class="h-px flex-1 bg-gray-200"></div> </div> @endif

        <div class="space-y-5"> @foreach($sejour['rooms'] as $roomGroup)

        <div class="rounded-2xl border border-gray-200 overflow-hidden shadow-sm"> {{-- Header chambre --}}
            @php
                $_room  = $roomGroup['room'];
                $_parts = [];
                if ($_room->adults)   $_parts[] = $_room->adults   . ' adulte'  . ($_room->adults   > 1 ? 's' : '');
                if ($_room->children) $_parts[] = $_room->children . ' enfant'  . ($_room->children > 1 ? 's' : '');
                if ($_room->babies)   $_parts[] = $_room->babies   . ' bébé'    . ($_room->babies   > 1 ? 's' : '');
                $_occ    = implode(' · ', $_parts);
                $_nights = $sejour['check_in']->diffInDays($sejour['check_out']);
                $_name   = $_room->roomType->name ?? 'Chambre';
            @endphp
            <div class="bg-gray-50 border-b border-gray-200 px-5 py-3"> <div class="flex items-center gap-2 flex-wrap"> <span class="text-base"></span> <span class="font-bold text-gray-800 text-sm">{{ $_name }}</span> @if($_occ)
                    <span class="text-gray-300"></span> <span class="text-sm text-gray-700">{{ $_occ }}</span> @endif
                    <span class="text-xs bg-gray-200 text-gray-500 px-2 py-0.5 rounded-full">Chambre {{ $roomGroup['room_num'] }}</span> </div> <div class="mt-1 text-xs text-gray-400"> {{ $sejour['check_in']->format('d/m/Y') }}  {{ $sejour['check_out']->format('d/m/Y') }}
                    · {{ $_nights }} nuit{{ $_nights > 1 ? 's' : '' }}
                </div> </div> {{-- Tableau voyageurs --}}
            <div class="overflow-x-auto"> @php
                $th  = 'style="padding:8px 10px; font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; background:#f9fafb; border-bottom:2px solid #e5e7eb; text-align:left;"';
                $td  = 'style="padding:6px 8px; border-bottom:1px solid #f3f4f6; vertical-align:top;"';
                $tdl = 'style="padding:6px 12px; border-bottom:1px solid #f3f4f6; vertical-align:middle; white-space:nowrap; font-weight:600; font-size:13px; position:sticky; left:0; z-index:1;"';
            @endphp
            <table style="border-collapse:collapse; min-width:100%"> <thead> <tr> <th {!! $th !!} style="padding:8px 12px; font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.04em; background:#f9fafb; border-bottom:2px solid #e5e7eb; text-align:left; position:sticky; left:0; z-index:2; white-space:nowrap;">Voyageur</th> <th {!! $th !!}>Civ.</th> <th {!! $th !!}>Nom <span style="color:#ef4444">*</span></th> <th {!! $th !!}>Prénom <span style="color:#ef4444">*</span></th> <th {!! $th !!}>Date naiss. <span style="color:#ef4444">*</span></th> <th {!! $th !!}>Lieu naiss.</th> <th {!! $th !!}>Pays naiss.</th> <th {!! $th !!}>Nationalité <span style="color:#ef4444">*</span></th> <th {!! $th !!}>Type doc <span style="color:#ef4444">*</span></th> <th {!! $th !!}>N° document <span style="color:#ef4444">*</span></th> <th {!! $th !!}>Exp. doc</th> <th {!! $th !!}>Pays émission</th> <th {!! $th !!}>Adresse</th> <th {!! $th !!}>Ville</th> <th {!! $th !!}>Code postal</th> <th {!! $th !!}>Pays résid.</th> <th {!! $th !!}>Profession</th> </tr> </thead> <tbody> @foreach($roomGroup['slots'] as $slot)
                @php
                    $g      = $existing->get($slot['index']);
                    $pre    = "guests[{$slot['index']}]";
                    $isOk   = $g?->isComplete();
                    $icon   = $slot['type'] === 'adult' ? '' : ($slot['type'] === 'child' ? '' : '');
                    $rowBg  = $isOk ? '#f0fdf4' : ($g ? '#fffbeb' : '#ffffff');
                @endphp
                <tr style="background:{{ $rowBg }}"> <td style="padding:6px 12px; border-bottom:1px solid #f3f4f6; vertical-align:middle; white-space:nowrap; font-weight:600; font-size:13px; position:sticky; left:0; background:{{ $rowBg }}; z-index:1;"> <input type="hidden" name="{{ $pre }}[type]" value="{{ $slot['type'] }}"> <span style="display:inline-flex; align-items:center; gap:6px;"> <span>{{ $icon }}</span> <span>{{ $slot['label'] }}</span> @if($isOk)
                                <span style="display:inline-block; width:14px; height:14px; background:#10b981; border-radius:50%; color:white; font-size:9px; font-weight:700; text-align:center; line-height:14px;"></span> @elseif($g)
                                <span style="display:inline-block; width:14px; height:14px; background:#f59e0b; border-radius:50%; color:white; font-size:9px; font-weight:700; text-align:center; line-height:14px;">!</span> @endif
                        </span> </td> <td {!! $td !!}> <select name="{{ $pre }}[civilite]" style="width:80px; border:1px solid #d1d5db; border-radius:6px; padding:5px 4px; font-size:12px; background:#fff;"> <option value=""></option> @foreach(['M.'=>'M.','Mme'=>'Mme','Mlle'=>'Mlle','Autre'=>'Autre'] as $val=>$lbl)
                            <option value="{{ $val }}" {{ ($g?->civilite ?? '') === $val ? 'selected' : '' }}>{{ $lbl }}</option> @endforeach
                        </select> </td> <td {!! $td !!}><input type="text" name="{{ $pre }}[nom]" value="{{ old("{$pre}.nom", $g?->nom ?? '') }}" placeholder="NOM" required style="width:110px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff; text-transform:uppercase;"></td> <td {!! $td !!}><input type="text" name="{{ $pre }}[prenom]" value="{{ old("{$pre}.prenom", $g?->prenom ?? '') }}" placeholder="Prénom" required style="width:110px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff;"></td> <td {!! $td !!}><input type="date" name="{{ $pre }}[date_naissance]" value="{{ old("{$pre}.date_naissance", $g?->date_naissance?->format('Y-m-d') ?? '') }}" required style="width:130px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff;"></td> <td {!! $td !!}><input type="text" name="{{ $pre }}[lieu_naissance]" value="{{ old("{$pre}.lieu_naissance", $g?->lieu_naissance ?? '') }}" placeholder="Ville" style="width:100px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff;"></td> <td {!! $td !!}> <select name="{{ $pre }}[pays_naissance]" style="width:130px; border:1px solid #d1d5db; border-radius:6px; padding:5px 6px; font-size:12px; background:#fff;"> <option value=""></option> @foreach($countries as $code=>$lbl)<option value="{{ $code }}" {{ ($g?->pays_naissance ?? '') === $code ? 'selected' : '' }}>{{ $lbl }}</option>@endforeach
                        </select> </td> <td {!! $td !!}> <select name="{{ $pre }}[nationalite]" required style="width:130px; border:1px solid #d1d5db; border-radius:6px; padding:5px 6px; font-size:12px; background:#fff;"> <option value=""></option> @foreach($countries as $code=>$lbl)<option value="{{ $code }}" {{ ($g?->nationalite ?? '') === $code ? 'selected' : '' }}>{{ $lbl }}</option>@endforeach
                        </select> </td> <td {!! $td !!}> <select name="{{ $pre }}[type_document]" required style="width:130px; border:1px solid #d1d5db; border-radius:6px; padding:5px 6px; font-size:12px; background:#fff;"> <option value=""></option> <option value="passeport"    {{ ($g?->type_document ?? '') === 'passeport'    ? 'selected' : '' }}>Passeport</option> <option value="cni"          {{ ($g?->type_document ?? '') === 'cni'          ? 'selected' : '' }}>CNI</option> <option value="titre_sejour" {{ ($g?->type_document ?? '') === 'titre_sejour' ? 'selected' : '' }}>Titre séjour</option> <option value="autre"        {{ ($g?->type_document ?? '') === 'autre'        ? 'selected' : '' }}>Autre</option> </select> </td> <td {!! $td !!}><input type="text" name="{{ $pre }}[numero_document]" value="{{ old("{$pre}.numero_document", $g?->numero_document ?? '') }}" placeholder="AB123456" required style="width:110px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff; text-transform:uppercase; font-family:monospace;"></td> <td {!! $td !!}><input type="date" name="{{ $pre }}[date_expiration_document]" value="{{ old("{$pre}.date_expiration_document", $g?->date_expiration_document?->format('Y-m-d') ?? '') }}" style="width:130px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff;"></td> <td {!! $td !!}> <select name="{{ $pre }}[pays_emission_document]" style="width:130px; border:1px solid #d1d5db; border-radius:6px; padding:5px 6px; font-size:12px; background:#fff;"> <option value=""></option> @foreach($countries as $code=>$lbl)<option value="{{ $code }}" {{ ($g?->pays_emission_document ?? '') === $code ? 'selected' : '' }}>{{ $lbl }}</option>@endforeach
                        </select> </td> <td {!! $td !!}><input type="text" name="{{ $pre }}[adresse]" value="{{ old("{$pre}.adresse", $g?->adresse ?? '') }}" placeholder="N° et rue" style="width:150px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff;"></td> <td {!! $td !!}><input type="text" name="{{ $pre }}[ville]" value="{{ old("{$pre}.ville", $g?->ville ?? '') }}" placeholder="Ville" style="width:100px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff;"></td> <td {!! $td !!}><input type="text" name="{{ $pre }}[code_postal]" value="{{ old("{$pre}.code_postal", $g?->code_postal ?? '') }}" placeholder="20000" style="width:75px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff;"></td> <td {!! $td !!}> <select name="{{ $pre }}[pays_residence]" style="width:130px; border:1px solid #d1d5db; border-radius:6px; padding:5px 6px; font-size:12px; background:#fff;"> <option value=""></option> @foreach($countries as $code=>$lbl)<option value="{{ $code }}" {{ ($g?->pays_residence ?? '') === $code ? 'selected' : '' }}>{{ $lbl }}</option>@endforeach
                        </select> </td> <td {!! $td !!}><input type="text" name="{{ $pre }}[profession]" value="{{ old("{$pre}.profession", $g?->profession ?? '') }}" placeholder="Ingénieur" style="width:120px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff;"></td> </tr> @endforeach
                </tbody> </table> </div> </div> @endforeach
        </div> @endforeach
        </div> {{-- Boutons --}}
        <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-3"> <a href="{{ $backUrl }}" class="text-sm text-gray-500 hover:text-gray-700"> Retour à la réservation</a> <div class="flex gap-3"> @if($totalPages > 1 && $page < $totalPages)
                <button type="submit" name="_next" value="1"
                        class="bg-gray-700 hover:bg-gray-800 text-white font-semibold px-6 py-3 rounded-xl text-sm transition-colors shadow-sm"> Enregistrer et continuer  (page {{ $page + 1 }}/{{ $totalPages }})
                </button> @else
                <button type="submit"
                        class="bg-amber-500 hover:bg-amber-600 text-white font-semibold px-8 py-3 rounded-xl shadow-sm text-sm transition-colors"> Enregistrer les fiches
                </button> @endif
            </div> </div> </form> </div>
@endsection
