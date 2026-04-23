<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>@yield('title', 'Admin')  Magic Hotels</title> <script src="https://cdn.tailwindcss.com"></script> <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script> <link rel="preconnect" href="https://fonts.googleapis.com"> <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    {{-- Flatpickr --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        /* Intégration visuelle Flatpickr dans le style admin */
        .flatpickr-alt-input {
            background: white !important;
        }
        .flatpickr-calendar {
            font-family: 'Inter', sans-serif;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px -5px rgb(0 0 0 / .1), 0 4px 6px -2px rgb(0 0 0 / .05);
            border: 1px solid #e5e7eb;
        }
        .flatpickr-day.selected, .flatpickr-day.selected:hover {
            background: #f59e0b;
            border-color: #f59e0b;
        }
        .flatpickr-day.today { border-color: #f59e0b; }
        .flatpickr-months .flatpickr-month,
        .flatpickr-weekdays,
        span.flatpickr-weekday {
            background: #1e293b;
            color: white;
        }
        .flatpickr-months .flatpickr-prev-month,
        .flatpickr-months .flatpickr-next-month { fill: white; }
        .flatpickr-months .flatpickr-prev-month:hover svg,
        .flatpickr-months .flatpickr-next-month:hover svg { fill: #f59e0b; }
        .numInputWrapper span.arrowUp:after { border-bottom-color: white; }
        .numInputWrapper span.arrowDown:after { border-top-color: white; }
    </style>
</head>
<body class="h-full"> <div class="min-h-full flex" x-data="{ sidebarOpen: false }"> {{-- Sidebar --}}
    <aside class="hidden lg:flex lg:flex-col lg:w-64 bg-slate-900 text-white"> {{-- Logo --}}
        <div class="flex h-16 items-center px-6 border-b border-slate-700">
            <span class="text-xl font-bold text-amber-400">Magic Hotels</span>
        </div> <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
@php
    $_u = auth()->user();
    $_canReservations = $_u->hasPermission('reservations.view');
    $_canAgencies     = $_u->hasPermission('agencies.view');
    $_canHotels       = $_u->hasPermission('hotels.view');
    $_canPricing      = $_u->hasPermission('pricing.manage');
    $_canOccupancy    = $_u->hasPermission('occupancy.manage');
    $_canSupplements  = $_u->hasPermission('supplements.manage');
    $_canExtraServices= $_u->hasPermission('extra_services.manage');
    $_canTemplates    = $_u->hasPermission('templates.manage');
    $_canRefusal      = $_u->hasPermission('refusal_reasons.manage');
    $_canCalendar     = $_u->hasPermission('calendar.manage');
    $_showTarif   = $_canHotels || $_canPricing || $_canOccupancy;
    $_showServices= $_canSupplements || $_canExtraServices;
    $_showConfig  = $_canTemplates || $_canRefusal || $_canCalendar;
    // users.manage : inclus dans hasPermission (super_admin → true automatiquement)
@endphp

            {{-- ── PRINCIPAL ────────────────────────────────────────────── --}}
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-amber-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Tableau de bord
            </a>

            @if($_canReservations)
            <a href="{{ route('admin.reservations.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.reservations.index') || request()->routeIs('admin.reservations.show') ? 'bg-amber-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Réservations
            </a>

            {{-- Dropdown Agendas --}}
            <div x-data="{ open: {{ request()->routeIs('admin.reservations.agenda*') ? 'true' : 'false' }} }">
                <button @click="open = !open"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.reservations.agenda*') ? 'text-amber-400 bg-slate-800' : 'text-slate-300 hover:bg-slate-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 12h6m-6 4h6"/></svg>
                    <span class="flex-1 text-left">Agendas</span>
                    <svg class="w-4 h-4 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : 'rotate-0'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1" class="mt-1 ml-4 pl-3 border-l-2 border-slate-700 space-y-0.5">
                    <a href="{{ route('admin.reservations.agenda') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.reservations.agenda') && !request()->routeIs('admin.reservations.agenda-depart') ? 'bg-amber-500 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Agenda arrivées
                    </a>
                    <a href="{{ route('admin.reservations.agenda-depart') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.reservations.agenda-depart') ? 'bg-amber-500 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Agenda départs
                    </a>
                </div>
            </div>
            @endif

            @if($_canAgencies)
            <a href="{{ route('admin.agencies.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.agencies*') ? 'bg-amber-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Agences
                @php $pendingAgencies = \App\Models\Agency::pending()->count() @endphp
                @if($pendingAgencies > 0)
                    <span class="ml-auto bg-orange-500 text-white text-xs rounded-full px-2 py-0.5">{{ $pendingAgencies }}</span>
                @endif
            </a>
            @endif

            {{-- ── TARIFICATION ─────────────────────────────────────────── --}}
            @if($_showTarif)
            <div class="pt-5 pb-1">
                <p class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Tarification</p>
            </div>

            @if($_canHotels)
            <a href="{{ route('admin.hotels.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.hotels*') ? 'bg-amber-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Hôtels
            </a>

            <a href="{{ route('admin.room-types.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.room-types*') ? 'bg-amber-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Types de chambres
            </a>
            @endif

            @if($_canPricing)
            <a href="{{ route('admin.room-prices.table', ['hotel_id' => 1]) }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.room-prices*') ? 'bg-amber-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Tarifs calendrier
            </a>

            <a href="{{ route('admin.tariff-grids.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.tariff-grids*') ? 'bg-amber-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Grilles tarifaires
            </a>
            @endif

            @if($_canOccupancy)
            <a href="{{ route('admin.occupancy-configs.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.occupancy-configs*') ? 'bg-amber-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Configs occupation
            </a>
            @endif
            @endif

            {{-- ── SERVICES ─────────────────────────────────────────────── --}}
            @if($_showServices)
            <div class="pt-5 pb-1">
                <p class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Services</p>
            </div>

            @if($_canSupplements)
            <a href="{{ route('admin.supplements.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.supplements*') ? 'bg-amber-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                Suppléments / Événements
            </a>
            @endif

            @if($_canExtraServices)
            <a href="{{ route('admin.extra-services.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.extra-services*') ? 'bg-amber-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Services Extras
            </a>
            @endif
            @endif

            {{-- ── CONFIGURATION ────────────────────────────────────────── --}}
            @if($_showConfig)
            <div class="pt-5 pb-1">
                <p class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Configuration</p>
            </div>

            @if($_canTemplates)
            <a href="{{ route('admin.email-templates.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.email-templates*') ? 'bg-amber-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Templates emails
            </a>

            <a href="{{ route('admin.pdf-templates.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.pdf-templates*') ? 'bg-amber-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Templates PDF
            </a>
            @endif

            @if($_canRefusal)
            <a href="{{ route('admin.refusal-reasons.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.refusal-reasons*') ? 'bg-amber-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                Motifs de refus
            </a>
            @endif

            @if($_canCalendar)
            {{-- Dropdown Vacances & Jours fériés --}}
            <div x-data="{ open: {{ request()->routeIs('admin.calendar*') ? 'true' : 'false' }} }">
                <button @click="open = !open"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.calendar*') ? 'text-amber-400 bg-slate-800' : 'text-slate-300 hover:bg-slate-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="flex-1 text-left">Vacances & Jours fériés</span>
                    <svg class="w-4 h-4 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : 'rotate-0'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1" class="mt-1 ml-4 pl-3 border-l-2 border-slate-700 space-y-0.5">
                    <a href="{{ route('admin.calendar.index') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.calendar.index') ? 'bg-amber-500 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Calendrier
                    </a>
                    <a href="{{ route('admin.calendar.list') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.calendar.list') ? 'bg-amber-500 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        Liste des événements
                    </a>
                </div>
            </div>
            @endif
            @endif

            {{-- ── ADMINISTRATION ───────────────────────────────────────── --}}
            @if($_u->isSuperAdmin() || $_u->hasPermission('users.manage'))
            <div class="pt-5 pb-1">
                <p class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Administration</p>
            </div>

            @if($_u->isSuperAdmin())
            <a href="{{ route('admin.roles.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.roles*') ? 'bg-amber-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Rôles & Permissions
            </a>
            @endif

            @if($_u->hasPermission('users.manage'))
            <a href="{{ route('admin.users.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.users*') ? 'bg-amber-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                Utilisateurs
            </a>
            @endif
            @endif

        </nav>
        {{-- ── Zone utilisateur ─────────────────────────────────────── --}}
        <div class="px-4 py-4 border-t border-slate-700 space-y-1">

            {{-- Lien Mon profil --}}
            <a href="{{ route('admin.profile.edit') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors
                      {{ request()->routeIs('admin.profile*') ? 'bg-amber-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                <div class="w-7 h-7 rounded-full bg-amber-400 flex items-center justify-center text-slate-900 font-bold text-xs shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate leading-tight">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-400 truncate leading-tight">{{ auth()->user()->email }}</p>
                </div>
                <svg class="w-4 h-4 shrink-0 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </a>

            {{-- Bouton Déconnexion --}}
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white transition-colors">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Se déconnecter
                </button>
            </form>
        </div>
        </aside> {{-- Main --}}
    <div class="flex-1 flex flex-col min-w-0"> {{-- Top bar --}}
        <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between"> <div> <h1 class="text-xl font-semibold text-gray-900">@yield('page-title', 'Tableau de bord')</h1> @hasSection('page-subtitle')
                    <p class="text-sm text-gray-500 mt-0.5">@yield('page-subtitle')</p> @endif
            </div> <div class="flex items-center gap-3"> @yield('header-actions')
            </div> </header> {{-- Alerts --}}
        <div class="px-6 pt-4"> @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm flex items-center gap-2"> <svg class="w-5 h-5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> {{ session('success') }}
                </div> @endif
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm flex items-center gap-2 mt-2"> <svg class="w-5 h-5 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg> {{ session('error') }}
                </div> @endif
        </div> {{-- Content --}}
        <main class="flex-1 px-6 py-6"> @yield('content')
        </main> </div>
</div>

{{-- Flatpickr JS --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/fr.js"></script>
<script>
(function () {
    // Appliquer la locale française globalement
    flatpickr.localize(flatpickr.l10ns.fr);

    function makeCfg(el) {
        const cfg = {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            allowInput: false,
            disableMobile: true,
            onReady: function (dates, dateStr, instance) {
                if (instance.altInput) {
                    instance.altInput.placeholder =
                        instance.element.placeholder || 'jj/mm/aaaa';
                }
            },
            onChange: function (dates, dateStr, instance) {
                instance.element.dispatchEvent(new Event('input',  { bubbles: true }));
                instance.element.dispatchEvent(new Event('change', { bubbles: true }));
            },
        };
        if (el.min) cfg.minDate = el.min;
        if (el.max) cfg.maxDate = el.max;
        return cfg;
    }

    window.initDatePickers = function (root) {
        (root || document).querySelectorAll("input[type='date']").forEach(function (el) {
            if (!el._flatpickr) flatpickr(el, makeCfg(el));
        });
    };

    var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            m.addedNodes.forEach(function (node) {
                if (node.nodeType !== 1) return;
                if (node.matches && node.matches("input[type='date']") && !node._flatpickr) {
                    flatpickr(node, makeCfg(node));
                } else {
                    window.initDatePickers(node);
                }
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        observer.observe(document.body, { childList: true, subtree: true });
        window.initDatePickers();
    });

    // Après Alpine : polling jusqu'à ce que tous les champs soient initialisés
    document.addEventListener('alpine:initialized', function () {
        var attempts = 0;
        var interval = setInterval(function () {
            window.initDatePickers();
            attempts++;
            if (attempts >= 20) clearInterval(interval); // 20 × 150ms = 3 sec
        }, 150);
    });
})();
</script>

@stack('scripts')
</body>
</html>
