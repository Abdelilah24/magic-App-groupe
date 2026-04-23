@extends('layouts.client')
@section('title', 'Fiche de police  ' . $reservation->reference)
@section('main-class', 'w-full max-w-full')

@section('content')
<div class="space-y-6 px-4"> {{-- En-tête --}}
    <div class="bg-white border border-gray-200 rounded-xl p-6"> <div class="flex items-start gap-4"> <div class="text-4xl"></div> <div> <h1 class="text-xl font-bold text-gray-900">Fiche de police  Enregistrement des voyageurs</h1> <p class="text-gray-500 text-sm mt-1"> Réservation <strong class="font-mono text-amber-600">{{ $reservation->reference }}</strong> · {{ $reservation->hotel->name }}
                    · Arrivée le {{ $reservation->check_in->format('d/m/Y') }}
                </p> <p class="text-xs text-gray-400 mt-2"> Conformément à la réglementation marocaine, veuillez renseigner les informations de chaque voyageur.
 Ces données sont transmises aux autorités locales dans les 24h suivant votre arrivée.
                </p> </div> </div> {{-- Progression --}}
        @php
            $filledCount = $existing->filter(fn($g) => $g->isComplete())->count();
            $totalCount  = count($slots);
            $pct         = $totalCount > 0 ? round($filledCount / $totalCount * 100) : 0;
        @endphp
        <div class="mt-4"> <div class="flex justify-between text-xs text-gray-500 mb-1"> <span>{{ $filledCount }} / {{ $totalCount }} fiche(s) complète(s)</span> <span>{{ $pct }}%</span> </div> <div class="w-full bg-gray-100 rounded-full h-2"> <div class="h-2 rounded-full transition-all {{ $pct === 100 ? 'bg-emerald-500' : 'bg-amber-400' }}"
                     style="width: {{ $pct }}%"></div> </div> </div> </div> @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-5 py-3 text-sm font-medium"> {{ session('success') }}
    </div> @endif

    {{-- Erreurs validation soumission finale --}}
    @if($errors->has('guests'))
    <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-5 py-3 text-sm">
        <p class="font-semibold mb-1">Veuillez corriger les erreurs suivantes :</p>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->get('guests') as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Formulaire --}}
    <form id="guest-police-form" method="POST" novalidate
          action="{{ $formAction ?? route('client.reservation.guests.save', ['token' => $token, 'reservation' => $reservation]) }}"> @csrf
        <input type="hidden" id="guest-action" name="_action" value="draft">

        <div class="space-y-6"> @foreach($groupedSlots as $sejour)

        {{--  En-tête Séjour  --}}
        @if(count($groupedSlots) > 1)
        <div class="flex items-center gap-3"> <div class="h-px flex-1 bg-gray-200"></div> <span class="text-xs font-bold text-gray-400 uppercase tracking-widest"> Séjour {{ $sejour['sejour_index'] }}
            </span> <div class="h-px flex-1 bg-gray-200"></div> </div> @endif

        <div class="space-y-5"> @foreach($sejour['rooms'] as $roomGroup)

        {{--  Bloc chambre  --}}
        <div class="rounded-2xl border border-gray-200 overflow-hidden shadow-sm"> {{-- Header chambre --}}
            @php
                $_room  = $roomGroup['room'];
                $_code  = $_room->occupancy_config_label
                       ?? $_room->occupancyConfig?->code
                       ?? null;
                $_occ   = $_room->occupancyConfig?->occupancy_description ?? null;

                // Fallback sur les valeurs brutes de la chambre
                if (!$_occ) {
                    $_parts = [];
                    if ($_room->adults)   $_parts[] = $_room->adults   . ' adulte'  . ($_room->adults   > 1 ? 's' : '');
                    if ($_room->children) $_parts[] = $_room->children . ' enfant'  . ($_room->children > 1 ? 's' : '');
                    if ($_room->babies)   $_parts[] = $_room->babies   . ' bébé'    . ($_room->babies   > 1 ? 's' : '');
                    $_occ = implode(' + ', $_parts);
                }
                $_nights = $sejour['check_in']->diffInDays($sejour['check_out']);
            @endphp
            <div class="bg-gray-50 border-b border-gray-200 px-5 py-3"> <div class="flex items-center gap-2 flex-wrap"> <span class="text-base"></span> @if($_code)
                    <span class="font-bold text-gray-800 text-sm font-mono">{{ $_code }}</span> <span class="text-gray-300"></span> @endif
                    @if($_occ)
                    <span class="text-sm font-medium text-gray-700">{{ $_occ }}</span> @endif
                    @if($roomGroup['room_num'] > 1 || count($roomGroup['slots']) > 0)
                    <span class="text-xs bg-gray-200 text-gray-500 px-2 py-0.5 rounded-full">Chambre {{ $roomGroup['room_num'] }}</span> @endif
                </div> <div class="mt-1 text-xs text-gray-400"> {{ $sejour['check_in']->format('d/m/Y') }}  {{ $sejour['check_out']->format('d/m/Y') }}
                    · {{ $_nights }} nuit{{ $_nights > 1 ? 's' : '' }}
                </div> </div> {{--  Tableau une ligne par voyageur  --}}
            <div class="overflow-x-auto"> @php
                $inp = 'style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff; outline:none;"';
                $sel = 'style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff; outline:none;"';
                $th  = 'style="padding:8px 10px; font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; background:#f9fafb; border-bottom:2px solid #e5e7eb; text-align:left;"';
                $td  = 'style="padding:6px 8px; border-bottom:1px solid #f3f4f6; vertical-align:top;"';
                $tdl = 'style="padding:6px 12px; border-bottom:1px solid #f3f4f6; vertical-align:middle; white-space:nowrap; font-weight:600; font-size:13px; position:sticky; left:0; background:#fff; z-index:1;"';
            @endphp
            <table style="border-collapse:collapse; min-width:100%"> <thead> <tr> <th {!! $th !!} style="padding:8px 12px; font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.04em; background:#f9fafb; border-bottom:2px solid #e5e7eb; text-align:left; position:sticky; left:0; z-index:2; white-space:nowrap;">Voyageur</th> <th {!! $th !!} style="min-width:90px;">Titre</th> <th {!! $th !!}>Nom <span style="color:#ef4444">*</span></th> <th {!! $th !!}>Prénom <span style="color:#ef4444">*</span></th> <th {!! $th !!}>Date naiss. <span style="color:#ef4444">*</span></th> @if($showAge ?? true)<th {!! $th !!}>Âge</th>@endif <th {!! $th !!}>Lieu naiss.</th> <th {!! $th !!}>Pays naiss.</th> <th {!! $th !!}>Nationalité <span style="color:#ef4444">*</span></th> <th {!! $th !!}>Type doc <span style="color:#ef4444">*</span></th> <th {!! $th !!}>N° document <span style="color:#ef4444">*</span></th> <th {!! $th !!}>Exp. doc</th> <th {!! $th !!}>Pays émission</th> <th {!! $th !!}>Adresse</th> <th {!! $th !!}>Ville</th> <th {!! $th !!}>Code postal</th> <th {!! $th !!}>Pays résid.</th> <th {!! $th !!}>Profession</th> <th {!! $th !!}>N° entrée Maroc</th> </tr> </thead> <tbody> @foreach($roomGroup['slots'] as $slot)
                @php
                    $g    = $existing->get($slot['index']);
                    $pre  = "guests[{$slot['index']}]";
                    $isOk = $g?->isComplete();
                    $icon = $slot['type'] === 'adult' ? '' : ($slot['type'] === 'child' ? '' : '');
                    $rowBg = $isOk ? '#f0fdf4' : ($g ? '#fffbeb' : '#ffffff');
                    $rowStyle = "background:{$rowBg}";
                @endphp
                <tr style="{{ $rowStyle }}"> <td {!! $tdl !!} style="padding:6px 12px; border-bottom:1px solid #f3f4f6; vertical-align:middle; white-space:nowrap; font-weight:600; font-size:13px; position:sticky; left:0; background:{{ $rowBg }}; z-index:1;"> <input type="hidden" name="{{ $pre }}[type]" value="{{ $slot['type'] }}"> <span style="display:inline-flex; align-items:center; gap:6px;"> <span>{{ $icon }}</span> <span>{{ $slot['label'] }}</span> @if($isOk)
                                <span style="display:inline-block; width:14px; height:14px; background:#10b981; border-radius:50%; color:white; font-size:9px; font-weight:700; text-align:center; line-height:14px;"></span> @elseif($g)
                                <span style="display:inline-block; width:14px; height:14px; background:#f59e0b; border-radius:50%; color:white; font-size:9px; font-weight:700; text-align:center; line-height:14px;">!</span> @endif
                        </span> </td> {{-- Civilité --}}
                    <td {!! $td !!}> <select name="{{ $pre }}[civilite]" {!! $sel !!}> <option value="" disabled selected>Titre</option> @foreach(['M.' => 'M.', 'Mme' => 'Mme', 'Mlle' => 'Mlle'] as $val => $lbl)
                            <option value="{{ $val }}" {{ ($g?->civilite ?? '') === $val ? 'selected' : '' }}>{{ $lbl }}</option> @endforeach
                        </select> </td> {{-- Nom --}}
                    <td {!! $td !!}> <input type="text" name="{{ $pre }}[nom]"
                               value="{{ old("{$pre}.nom", $g?->nom ?? '') }}"
                               placeholder="NOM" required
                               style="width:110px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff; text-transform:uppercase;"> </td> {{-- Prénom --}}
                    <td {!! $td !!}> <input type="text" name="{{ $pre }}[prenom]"
                               value="{{ old("{$pre}.prenom", $g?->prenom ?? '') }}"
                               placeholder="Prénom" required
                               style="width:110px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff;"> </td> {{-- Date naissance --}}
                    <td {!! $td !!}> <input type="date" name="{{ $pre }}[date_naissance]"
                               value="{{ old("{$pre}.date_naissance", $g?->date_naissance?->format('Y-m-d') ?? '') }}"
                               required
                               data-age-target="age-{{ $slot['index'] }}"
                               oninput="calcAge(this)"
                               style="width:130px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff;"> </td> {{-- Âge calculé (admin uniquement) --}}
                    @if($showAge ?? true)
                    <td {!! $td !!}>
                        <span id="age-{{ $slot['index'] }}"
                              style="display:inline-block; width:45px; text-align:center; font-size:12px; font-weight:600; color:#374151; padding:5px 4px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px;">
                            @if($g?->date_naissance){{ $g->date_naissance->age }}@else—@endif
                        </span>
                    </td>
                    @endif
                    {{-- Lieu naissance --}}
                    <td {!! $td !!}> <input type="text" name="{{ $pre }}[lieu_naissance]"
                               value="{{ old("{$pre}.lieu_naissance", $g?->lieu_naissance ?? '') }}"
                               placeholder="Ville"
                               style="width:100px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff;"> </td> {{-- Pays naissance --}}
                    <td {!! $td !!}> <select name="{{ $pre }}[pays_naissance]"
                                style="width:130px; border:1px solid #d1d5db; border-radius:6px; padding:5px 6px; font-size:12px; background:#fff;"> <option value=""></option> @foreach($countries as $code => $lbl)
                            <option value="{{ $code }}" {{ ($g?->pays_naissance ?? '') === $code ? 'selected' : '' }}>{{ $lbl }}</option> @endforeach
                        </select> </td> {{-- Nationalité --}}
                    <td {!! $td !!}> <select name="{{ $pre }}[nationalite]"
                                required
                                style="width:130px; border:1px solid #d1d5db; border-radius:6px; padding:5px 6px; font-size:12px; background:#fff;"> <option value=""></option> @foreach($countries as $code => $lbl)
                            <option value="{{ $code }}" {{ ($g?->nationalite ?? '') === $code ? 'selected' : '' }}>{{ $lbl }}</option> @endforeach
                        </select> </td> {{-- Type document --}}
                    <td {!! $td !!}> <select name="{{ $pre }}[type_document]"
                                required
                                style="width:130px; border:1px solid #d1d5db; border-radius:6px; padding:5px 6px; font-size:12px; background:#fff;"> <option value=""></option> <option value="passeport"    {{ ($g?->type_document ?? '') === 'passeport'    ? 'selected' : '' }}>Passeport</option> <option value="cni"          {{ ($g?->type_document ?? '') === 'cni'          ? 'selected' : '' }}>CNI</option> <option value="titre_sejour" {{ ($g?->type_document ?? '') === 'titre_sejour' ? 'selected' : '' }}>Titre séjour</option> <option value="autre"        {{ ($g?->type_document ?? '') === 'autre'        ? 'selected' : '' }}>Autre</option> </select> </td> {{-- N° document --}}
                    <td {!! $td !!}> <input type="text" name="{{ $pre }}[numero_document]"
                               value="{{ old("{$pre}.numero_document", $g?->numero_document ?? '') }}"
                               placeholder="AB123456" required
                               style="width:110px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff; text-transform:uppercase; font-family:monospace;"> </td> {{-- Expiration doc --}}
                    <td {!! $td !!}> <input type="date" name="{{ $pre }}[date_expiration_document]"
                               value="{{ old("{$pre}.date_expiration_document", $g?->date_expiration_document?->format('Y-m-d') ?? '') }}"
                               style="width:130px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff;"> </td> {{-- Pays émission --}}
                    <td {!! $td !!}> <select name="{{ $pre }}[pays_emission_document]"
                                style="width:130px; border:1px solid #d1d5db; border-radius:6px; padding:5px 6px; font-size:12px; background:#fff;"> <option value=""></option> @foreach($countries as $code => $lbl)
                            <option value="{{ $code }}" {{ ($g?->pays_emission_document ?? '') === $code ? 'selected' : '' }}>{{ $lbl }}</option> @endforeach
                        </select> </td> {{-- Adresse --}}
                    <td {!! $td !!}> <input type="text" name="{{ $pre }}[adresse]"
                               value="{{ old("{$pre}.adresse", $g?->adresse ?? '') }}"
                               placeholder="N° et rue"
                               style="width:150px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff;"> </td> {{-- Ville --}}
                    <td {!! $td !!}> <input type="text" name="{{ $pre }}[ville]"
                               value="{{ old("{$pre}.ville", $g?->ville ?? '') }}"
                               placeholder="Ville"
                               style="width:100px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff;"> </td> {{-- Code postal --}}
                    <td {!! $td !!}> <input type="text" name="{{ $pre }}[code_postal]"
                               value="{{ old("{$pre}.code_postal", $g?->code_postal ?? '') }}"
                               placeholder="20000"
                               style="width:75px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff;"> </td> {{-- Pays résidence --}}
                    <td {!! $td !!}> <select name="{{ $pre }}[pays_residence]"
                                style="width:130px; border:1px solid #d1d5db; border-radius:6px; padding:5px 6px; font-size:12px; background:#fff;"> <option value=""></option> @foreach($countries as $code => $lbl)
                            <option value="{{ $code }}" {{ ($g?->pays_residence ?? '') === $code ? 'selected' : '' }}>{{ $lbl }}</option> @endforeach
                        </select> </td> {{-- Profession --}}
                    <td {!! $td !!}> <input type="text" name="{{ $pre }}[profession]"
                               value="{{ old("{$pre}.profession", $g?->profession ?? '') }}"
                               placeholder="Ingénieur"
                               style="width:120px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff;"> </td> {{-- N° entrée Maroc --}}
                    <td {!! $td !!}> <input type="text" name="{{ $pre }}[numero_entree_maroc]"
                               value="{{ old("{$pre}.numero_entree_maroc", $g?->numero_entree_maroc ?? '') }}"
                               placeholder="EM-123456"
                               style="width:120px; border:1px solid #d1d5db; border-radius:6px; padding:5px 8px; font-size:12px; background:#fff; text-transform:uppercase; font-family:monospace;"> </td> </tr> @endforeach
                </tbody> </table> </div>{{-- fin overflow-x-auto --}}
        </div>{{-- fin bloc chambre --}}

        @endforeach
        </div>{{-- fin rooms séjour --}}

        @endforeach
        </div>{{-- fin groupedSlots --}}

        {{-- Boutons --}}
        <div class="mt-6 bg-white border border-gray-200 rounded-2xl px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm sticky bottom-4 z-10">
            <div class="flex items-center gap-3">
                <a href="{{ $backUrl ?? route('client.reservation.show', ['token' => $token, 'reservation' => $reservation]) }}"
                   class="text-sm text-gray-500 hover:text-gray-700">← Retour</a>
                @if(isset($autosaveAction))
                <span id="autosave-indicator" class="text-xs text-gray-400 flex items-center gap-1.5">
                    <span id="autosave-dot" class="inline-block w-2 h-2 rounded-full bg-gray-300"></span>
                    <span id="autosave-text">Autosave actif</span>
                </span>
                @endif
            </div>
            <div class="flex items-center gap-3 flex-wrap justify-end">
                <button type="button" onclick="guestFormDraft()"
                        class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 font-semibold px-5 py-2.5 rounded-xl text-sm transition-colors shadow-sm">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Enregistrer comme brouillon
                </button>
                <button type="button" onclick="guestFormSubmit()"
                        class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Soumettre les fiches
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function calcAge(input) {
    const targetId = input.getAttribute('data-age-target');
    const span = document.getElementById(targetId);
    if (!span) return;
    if (!input.value) { span.textContent = '—'; return; }
    const birth = new Date(input.value);
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
    span.textContent = age >= 0 ? age : '—';
}
</script>
<script>
(function () {
    const form       = document.getElementById('guest-police-form');
    const actionInp  = document.getElementById('guest-action');
    const dotEl      = document.getElementById('autosave-dot');
    const textEl     = document.getElementById('autosave-text');
    const autosaveUrl = @isset($autosaveAction) '{{ $autosaveAction }}' @else null @endisset;

    let isDirty   = false;
    let lastSaved = null;
    let saving    = false;

    // Détecter les modifications
    form.addEventListener('input',  () => { isDirty = true; });
    form.addEventListener('change', () => { isDirty = true; });

    // --- Autosave ---
    async function autosave() {
        if (!isDirty || saving || !autosaveUrl) return;
        isDirty = false;
        saving  = true;
        if (dotEl) { dotEl.className = 'inline-block w-2 h-2 rounded-full bg-amber-400 animate-pulse'; }
        if (textEl) textEl.textContent = 'Sauvegarde…';

        try {
            const fd = new FormData(form);
            fd.set('_action', 'draft');
            const res  = await fetch(autosaveUrl, {
                method: 'POST',
                body: fd,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.ok) {
                lastSaved = new Date();
                if (dotEl) dotEl.className = 'inline-block w-2 h-2 rounded-full bg-emerald-400';
                if (textEl) textEl.textContent = 'Sauvegardé à l\'instant';
            }
        } catch (e) {
            if (dotEl) dotEl.className = 'inline-block w-2 h-2 rounded-full bg-red-400';
            if (textEl) textEl.textContent = 'Erreur sauvegarde';
        } finally {
            saving = false;
        }
    }

    // Rafraîchir l'affichage du temps
    function refreshSaveLabel() {
        if (!lastSaved || !textEl) return;
        const sec = Math.round((Date.now() - lastSaved) / 1000);
        if (sec < 10)  textEl.textContent = 'Sauvegardé à l\'instant';
        else if (sec < 60) textEl.textContent = `Sauvegardé il y a ${sec}s`;
        else textEl.textContent = `Sauvegardé il y a ${Math.round(sec/60)} min`;
    }

    if (autosaveUrl) {
        setInterval(autosave,         5000);
        setInterval(refreshSaveLabel, 10000);
    }

    // --- Brouillon ---
    window.guestFormDraft = function () {
        actionInp.value = 'draft';
        form.submit();
    };

    // --- Soumission finale avec validation JS ---
    window.guestFormSubmit = function () {
        const rows = form.querySelectorAll('tbody tr');
        const requiredNames = ['nom', 'prenom', 'date_naissance', 'nationalite', 'numero_document', 'type_document'];
        let errorCount = 0;
        let firstError = null;

        rows.forEach(row => {
            // Vérifier si la ligne a au moins un champ rempli (si tout vide, on ignore)
            const allInputs = row.querySelectorAll('input, select');
            const hasAnyValue = Array.from(allInputs).some(el => {
                const n = el.name || '';
                return n.endsWith('[type]') ? false : el.value.trim() !== '';
            });
            if (!hasAnyValue) return;

            requiredNames.forEach(field => {
                const el = row.querySelector(`[name$="[${field}]"]`);
                if (el && !el.value.trim()) {
                    el.style.borderColor = '#ef4444';
                    el.style.boxShadow   = '0 0 0 2px #fee2e2';
                    errorCount++;
                    if (!firstError) firstError = el;
                } else if (el) {
                    el.style.borderColor = '#d1d5db';
                    el.style.boxShadow   = '';
                }
            });
        });

        if (errorCount > 0) {
            if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            // Toast d'erreur
            showToast(`${errorCount} champ(s) obligatoire(s) manquant(s). Complétez ou enregistrez comme brouillon.`, 'error');
            return;
        }

        actionInp.value = 'submit';
        form.submit();
    };

    // --- Mini toast ---
    function showToast(msg, type) {
        let t = document.getElementById('guest-toast');
        if (!t) {
            t = document.createElement('div');
            t.id = 'guest-toast';
            t.style.cssText = 'position:fixed;bottom:90px;left:50%;transform:translateX(-50%);z-index:9999;padding:12px 20px;border-radius:12px;font-size:13px;font-weight:600;box-shadow:0 4px 20px rgba(0,0,0,.15);transition:opacity .3s;max-width:420px;text-align:center;';
            document.body.appendChild(t);
        }
        t.textContent = msg;
        t.style.background = type === 'error' ? '#fef2f2' : '#f0fdf4';
        t.style.color      = type === 'error' ? '#b91c1c' : '#15803d';
        t.style.border     = type === 'error' ? '1px solid #fecaca' : '1px solid #bbf7d0';
        t.style.opacity    = '1';
        clearTimeout(t._timer);
        t._timer = setTimeout(() => { t.style.opacity = '0'; }, 4000);
    }
})();
</script>
@endpush
@endsection
