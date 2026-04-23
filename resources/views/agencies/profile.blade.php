<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil — {{ $agency->name }} — Magic Hotels</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; } [x-cloak]{display:none!important}</style>
</head>
<body class="min-h-full bg-gray-50">

{{-- ── Header ─────────────────────────────────────────────────────────────── --}}
<header class="bg-slate-900 text-white sticky top-0 z-30 shadow-lg">
    <div class="max-w-5xl mx-auto px-6 py-3.5 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('agency.portal.dashboard') }}" class="text-amber-400 text-lg font-bold hover:text-amber-300 transition-colors">
                Magic Hotels
            </a>
            <span class="hidden sm:block text-slate-600 text-lg font-light">|</span>
            <span class="hidden sm:block text-slate-300 text-sm font-medium">{{ $agency->name }}</span>
        </div>
        <form action="{{ route('agency.logout') }}" method="POST">
            @csrf
            <button class="flex items-center gap-2 text-slate-400 hover:text-white text-sm transition-colors px-3 py-1.5 rounded-lg hover:bg-slate-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Déconnexion
            </button>
        </form>
    </div>
</header>

{{-- ── Nav ─────────────────────────────────────────────────────────────────── --}}
<nav class="bg-white border-b border-gray-200 shadow-sm">
    <div class="max-w-5xl mx-auto px-6">
        <div class="flex gap-1 text-sm">
            <a href="{{ route('agency.portal.dashboard') }}"
               class="py-3.5 px-4 border-b-2 border-transparent text-gray-500 hover:text-gray-800 hover:border-gray-300 font-medium transition-colors">
                Tableau de bord
            </a>
            <a href="{{ route('agency.portal.profile') }}"
               class="py-3.5 px-4 border-b-2 border-amber-500 text-amber-600 font-semibold">
                Mon profil
            </a>
        </div>
    </div>
</nav>

{{-- ── Main ─────────────────────────────────────────────────────────────────── --}}
<main class="max-w-5xl mx-auto px-6 py-8">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3.5 text-sm mb-6 shadow-sm">
        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3.5 text-sm mb-6 shadow-sm">
        <svg class="w-5 h-5 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        {{ session('error') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ── Colonne gauche : identité ─────────────────────────────────── --}}
        <div class="lg:col-span-1 space-y-4">

            {{-- Carte identité --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 text-center">
                <div class="w-20 h-20 bg-gradient-to-br from-amber-400 to-amber-500 rounded-2xl flex items-center justify-center text-white font-bold text-3xl mx-auto mb-4 shadow-md">
                    {{ strtoupper(substr($agency->name, 0, 1)) }}
                </div>
                <p class="font-bold text-gray-900 text-lg leading-tight">{{ $agency->name }}</p>
                <p class="text-sm text-gray-500 mt-1">{{ $agency->email }}</p>
                <div class="mt-3">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full
                        @if($agency->status === 'approved') bg-emerald-100 text-emerald-700 border border-emerald-200
                        @elseif($agency->status === 'pending') bg-amber-100 text-amber-700 border border-amber-200
                        @else bg-red-100 text-red-700 border border-red-200 @endif">
                        <span class="w-1.5 h-1.5 rounded-full
                            @if($agency->status === 'approved') bg-emerald-500
                            @elseif($agency->status === 'pending') bg-amber-500
                            @else bg-red-500 @endif"></span>
                        {{ $agency->status_label }}
                    </span>
                </div>
                @if($agency->approved_at)
                <p class="text-xs text-gray-400 mt-3">
                    Partenaire depuis {{ $agency->approved_at->format('d/m/Y') }}
                </p>
                @endif
            </div>

            {{-- Carte infos connexion --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    Connexion
                </h3>
                <div class="space-y-3">
                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl">
                        <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Identifiant</p>
                            <p class="text-sm font-medium text-gray-800 break-all">{{ $agency->email }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl">
                        <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Mot de passe</p>
                            <p class="text-sm text-gray-500">••••••••</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Colonne droite : formulaires ──────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- ═══ Formulaire profil ══════════════════════════════════════ --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="font-semibold text-gray-900">Modifier mon profil</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Informations de contact de votre agence</p>
                        </div>
                    </div>
                </div>

                @if(! empty($agency->pending_changes))
                <div class="mx-6 mt-5 flex items-start gap-3 bg-amber-50 border border-amber-300 rounded-xl px-4 py-3.5 text-sm text-amber-800">
                    <svg class="w-5 h-5 mt-0.5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="font-semibold">Demande de modification en cours d'examen</p>
                        <p class="text-amber-700 mt-0.5 text-xs">Vos modifications sont en attente d'approbation par nos garants. Le formulaire sera déverrouillé une fois la décision prise.</p>
                    </div>
                </div>
                @endif

                <form action="{{ route('agency.portal.profile.update') }}" method="POST" class="p-6">
                    @csrf
                    @method('PATCH')

                    @if(! empty($agency->pending_changes))
                    <fieldset disabled class="space-y-5 opacity-50 cursor-not-allowed select-none">
                    @else
                    <fieldset class="space-y-5">
                    @endif

                        {{-- Nom agence (lecture seule) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Nom de l'agence
                            </label>
                            <div class="relative">
                                <input type="text" value="{{ $agency->name }}" disabled
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-400 cursor-not-allowed pr-10">
                                <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1.5 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Le nom officiel ne peut être modifié que par Magic Hotels.
                            </p>
                        </div>

                        {{-- Nom du contact --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Nom du contact <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="contact_name" required
                                   value="{{ old('contact_name', $agency->contact_name) }}"
                                   placeholder="Prénom et nom du responsable"
                                   class="w-full border @error('contact_name') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:border-amber-400 focus:outline-none transition-colors">
                            @error('contact_name')
                                <p class="text-red-600 text-xs mt-1.5 flex items-center gap-1">
                                    <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Téléphone --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Téléphone</label>
                            <input type="tel" name="phone"
                                   value="{{ old('phone', $agency->phone) }}"
                                   placeholder="+212 6XX XXX XXX"
                                   class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:border-amber-400 focus:outline-none transition-colors">
                        </div>

                        {{-- Adresse --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Adresse</label>
                            <input type="text" name="address"
                                   value="{{ old('address', $agency->address) }}"
                                   placeholder="N° et rue"
                                   class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:border-amber-400 focus:outline-none transition-colors">
                        </div>

                        {{-- Ville + Pays --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Ville</label>
                                <input type="text" name="city"
                                       value="{{ old('city', $agency->city) }}"
                                       placeholder="Casablanca"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:border-amber-400 focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Pays</label>
                                <select name="country"
                                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:border-amber-400 focus:outline-none transition-colors bg-white">
                                    <option value="">— Sélectionner —</option>
                                    {{-- Pays fréquents en premier --}}
                                    <option value="Maroc"       {{ old('country', $agency->country) === 'Maroc'       ? 'selected' : '' }}>Maroc</option>
                                    <option value="Algérie"     {{ old('country', $agency->country) === 'Algérie'     ? 'selected' : '' }}>Algérie</option>
                                    <option value="Tunisie"     {{ old('country', $agency->country) === 'Tunisie'     ? 'selected' : '' }}>Tunisie</option>
                                    <option value="France"      {{ old('country', $agency->country) === 'France'      ? 'selected' : '' }}>France</option>
                                    <option value="Belgique"    {{ old('country', $agency->country) === 'Belgique'    ? 'selected' : '' }}>Belgique</option>
                                    <option value="Espagne"     {{ old('country', $agency->country) === 'Espagne'     ? 'selected' : '' }}>Espagne</option>
                                    <option disabled>──────────────</option>
                                    <option value="Allemagne"   {{ old('country', $agency->country) === 'Allemagne'   ? 'selected' : '' }}>Allemagne</option>
                                    <option value="Arabie Saoudite" {{ old('country', $agency->country) === 'Arabie Saoudite' ? 'selected' : '' }}>Arabie Saoudite</option>
                                    <option value="Bahreïn"     {{ old('country', $agency->country) === 'Bahreïn'     ? 'selected' : '' }}>Bahreïn</option>
                                    <option value="Canada"      {{ old('country', $agency->country) === 'Canada'      ? 'selected' : '' }}>Canada</option>
                                    <option value="Égypte"      {{ old('country', $agency->country) === 'Égypte'      ? 'selected' : '' }}>Égypte</option>
                                    <option value="Émirats arabes unis" {{ old('country', $agency->country) === 'Émirats arabes unis' ? 'selected' : '' }}>Émirats arabes unis</option>
                                    <option value="Italie"      {{ old('country', $agency->country) === 'Italie'      ? 'selected' : '' }}>Italie</option>
                                    <option value="Jordanie"    {{ old('country', $agency->country) === 'Jordanie'    ? 'selected' : '' }}>Jordanie</option>
                                    <option value="Koweït"      {{ old('country', $agency->country) === 'Koweït'      ? 'selected' : '' }}>Koweït</option>
                                    <option value="Liban"       {{ old('country', $agency->country) === 'Liban'       ? 'selected' : '' }}>Liban</option>
                                    <option value="Libye"       {{ old('country', $agency->country) === 'Libye'       ? 'selected' : '' }}>Libye</option>
                                    <option value="Mauritanie"  {{ old('country', $agency->country) === 'Mauritanie'  ? 'selected' : '' }}>Mauritanie</option>
                                    <option value="Pays-Bas"    {{ old('country', $agency->country) === 'Pays-Bas'    ? 'selected' : '' }}>Pays-Bas</option>
                                    <option value="Portugal"    {{ old('country', $agency->country) === 'Portugal'    ? 'selected' : '' }}>Portugal</option>
                                    <option value="Qatar"       {{ old('country', $agency->country) === 'Qatar'       ? 'selected' : '' }}>Qatar</option>
                                    <option value="Royaume-Uni" {{ old('country', $agency->country) === 'Royaume-Uni' ? 'selected' : '' }}>Royaume-Uni</option>
                                    <option value="Sénégal"     {{ old('country', $agency->country) === 'Sénégal'     ? 'selected' : '' }}>Sénégal</option>
                                    <option value="Suisse"      {{ old('country', $agency->country) === 'Suisse'      ? 'selected' : '' }}>Suisse</option>
                                    <option value="Turquie"     {{ old('country', $agency->country) === 'Turquie'     ? 'selected' : '' }}>Turquie</option>
                                    <option value="Autre"       {{ old('country', $agency->country) === 'Autre'       ? 'selected' : '' }}>Autre</option>
                                </select>
                            </div>
                        </div>

                        {{-- Site web --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Site web</label>
                            <input type="url" name="website"
                                   value="{{ old('website', $agency->website) }}"
                                   placeholder="https://www.votre-agence.ma"
                                   class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:border-amber-400 focus:outline-none transition-colors">
                        </div>

                    </fieldset>

                    {{-- Bandeau info approbation --}}
                    @if(empty($agency->pending_changes))
                    <div class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 mt-5 text-sm text-amber-800">
                        <svg class="w-4 h-4 mt-0.5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Votre demande sera examinée par nos garants. Les informations ne seront modifiées qu'après approbation.</span>
                    </div>

                    <div class="flex items-center gap-3 pt-5 mt-3 border-t border-gray-100">
                        <button type="submit"
                                class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2.5 px-6 rounded-xl text-sm transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            Demande de modification
                        </button>
                        <a href="{{ route('agency.portal.dashboard') }}"
                           class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2.5 rounded-xl border border-gray-200 hover:border-gray-300 transition-colors">
                            Annuler
                        </a>
                    </div>
                    @endif
                </form>
            </div>

            {{-- ═══ Changer le mot de passe ════════════════════════════════ --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden"
                 x-data="{ showCurrent: false, showNew: false, showConfirm: false }">

                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="font-semibold text-gray-900">Changer le mot de passe</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Minimum 8 caractères</p>
                        </div>
                    </div>
                </div>

                {{-- Erreurs --}}
                @if($errors->any() && $errors->has('current_password') || $errors->has('password'))
                <div class="mx-6 mt-5 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700 space-y-1">
                    @foreach($errors->all() as $error)
                        <p class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            {{ $error }}
                        </p>
                    @endforeach
                </div>
                @endif

                <form action="{{ route('agency.portal.profile.password') }}" method="POST" class="p-6">
                    @csrf
                    @method('PATCH')

                    <div class="space-y-5">

                        {{-- Mot de passe actuel --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Mot de passe actuel <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input :type="showCurrent ? 'text' : 'password'"
                                       name="current_password" required autocomplete="current-password"
                                       placeholder="Votre mot de passe actuel"
                                       class="w-full border @error('current_password') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-xl px-4 py-2.5 pr-11 text-sm focus:ring-2 focus:ring-amber-400 focus:border-amber-400 focus:outline-none transition-colors">
                                <button type="button" @click="showCurrent = !showCurrent"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                                    <svg x-show="!showCurrent" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="showCurrent" x-cloak class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                </button>
                            </div>
                            @error('current_password')
                                <p class="text-red-600 text-xs mt-1.5 flex items-center gap-1">
                                    <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Nouveau + Confirmation --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Nouveau mot de passe <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="showNew ? 'text' : 'password'"
                                           name="password" required autocomplete="new-password"
                                           placeholder="8 caractères minimum"
                                           class="w-full border @error('password') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-xl px-4 py-2.5 pr-11 text-sm focus:ring-2 focus:ring-amber-400 focus:border-amber-400 focus:outline-none transition-colors">
                                    <button type="button" @click="showNew = !showNew"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                                        <svg x-show="!showNew" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <svg x-show="showNew" x-cloak class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                        </svg>
                                    </button>
                                </div>
                                @error('password')
                                    <p class="text-red-600 text-xs mt-1.5 flex items-center gap-1">
                                        <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Confirmer le mot de passe <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="showConfirm ? 'text' : 'password'"
                                           name="password_confirmation" required autocomplete="new-password"
                                           placeholder="Répétez le nouveau mot de passe"
                                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 pr-11 text-sm focus:ring-2 focus:ring-amber-400 focus:border-amber-400 focus:outline-none transition-colors">
                                    <button type="button" @click="showConfirm = !showConfirm"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                                        <svg x-show="!showConfirm" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <svg x-show="showConfirm" x-cloak class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="pt-5 mt-5 border-t border-gray-100">
                        <button type="submit"
                                class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2.5 px-6 rounded-xl text-sm transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Mettre à jour le mot de passe
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</main>

{{-- ── Footer ──────────────────────────────────────────────────────────────── --}}
<div class="max-w-5xl mx-auto px-6 pb-8 text-center text-xs text-gray-400">
    © {{ date('Y') }} Magic Hotels —
    <a href="mailto:{{ config('magic.contact_email') }}" class="text-amber-600 hover:underline">
        {{ config('magic.contact_email') }}
    </a>
</div>

</body>
</html>
