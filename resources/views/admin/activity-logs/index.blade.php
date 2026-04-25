@extends('admin.layouts.app')

@section('title', 'Journal des modifications')
@section('page-title', 'Journal des modifications')
@section('page-subtitle', 'Toutes les actions effectuées dans l\'application')

@section('header-actions')
    <form method="POST" action="{{ route('admin.activity-logs.purge') }}"
          onsubmit="return confirm('Purger tout le journal ? Cette action est irréversible.')">
        @csrf @method('DELETE')
        <button type="submit"
                class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Purger le journal
        </button>
    </form>
@endsection

@section('content')
<div class="space-y-4">

    {{-- ── Filtres ──────────────────────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('admin.activity-logs.index') }}"
          class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">

            {{-- Recherche texte --}}
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">Recherche</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Description, section…"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>

            {{-- Section --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Section</label>
                <select name="section" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                    <option value="">Toutes</option>
                    @foreach($sections as $s)
                        <option value="{{ $s }}" @selected(request('section') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Événement --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Action</label>
                <select name="event" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                    <option value="">Toutes</option>
                    <option value="created" @selected(request('event') === 'created')>Création</option>
                    <option value="updated" @selected(request('event') === 'updated')>Modification</option>
                    <option value="deleted" @selected(request('event') === 'deleted')>Suppression</option>
                </select>
            </div>

            {{-- Utilisateur --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Auteur</label>
                <select name="user_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                    <option value="">Tous</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Période --}}
            <div class="lg:col-span-2 grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Du</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Au</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
            </div>

        </div>
        <div class="flex gap-2 mt-3">
            <button type="submit"
                    class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition">
                Filtrer
            </button>
            <a href="{{ route('admin.activity-logs.index') }}"
               class="px-4 py-2 border border-gray-200 text-sm text-gray-600 rounded-lg hover:bg-gray-50 transition">
                Réinitialiser
            </a>
        </div>
    </form>

    {{-- ── Résultats ────────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
            <p class="text-sm text-gray-500">
                <span class="font-semibold text-gray-800">{{ number_format($logs->total()) }}</span> entrée(s)
            </p>
            <p class="text-xs text-gray-400">Page {{ $logs->currentPage() }} / {{ $logs->lastPage() }}</p>
        </div>

        @if($logs->isEmpty())
            <div class="py-16 text-center text-gray-400">
                <svg class="w-10 h-10 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Aucune entrée dans le journal.
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left w-40">Date & heure</th>
                        <th class="px-4 py-3 text-left w-28">Action</th>
                        <th class="px-4 py-3 text-left w-44">Section</th>
                        <th class="px-4 py-3 text-left">Détail</th>
                        <th class="px-4 py-3 text-left w-36">Auteur</th>
                        <th class="px-4 py-3 text-left w-28">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($logs as $log)
                    @php
                        $eventColor = match($log->event) {
                            'created' => 'bg-green-100 text-green-700',
                            'updated' => 'bg-blue-100 text-blue-700',
                            'deleted' => 'bg-red-100 text-red-700',
                            default   => 'bg-gray-100 text-gray-600',
                        };
                        $sectionColor = match($log->section) {
                            'Réservations'             => 'bg-amber-50 text-amber-700 border-amber-200',
                            'Agences'                  => 'bg-purple-50 text-purple-700 border-purple-200',
                            'Hôtels'                   => 'bg-sky-50 text-sky-700 border-sky-200',
                            'Types de chambres'        => 'bg-teal-50 text-teal-700 border-teal-200',
                            'Tableau tarifaire'        => 'bg-orange-50 text-orange-700 border-orange-200',
                            'Grilles tarifaires'       => 'bg-orange-50 text-orange-700 border-orange-200',
                            'Configurations d\'occupation' => 'bg-lime-50 text-lime-700 border-lime-200',
                            'Suppléments'              => 'bg-pink-50 text-pink-700 border-pink-200',
                            'Services supplémentaires' => 'bg-pink-50 text-pink-700 border-pink-200',
                            'Modèles d\'e-mails'       => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                            'Modèles PDF'              => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                            'Motifs de refus'          => 'bg-rose-50 text-rose-700 border-rose-200',
                            'Calendrier'               => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                            'Rôles & Permissions'      => 'bg-slate-100 text-slate-700 border-slate-200',
                            'Utilisateurs'             => 'bg-slate-100 text-slate-700 border-slate-200',
                            'Mon profil'               => 'bg-gray-100 text-gray-700 border-gray-200',
                            default                    => 'bg-gray-100 text-gray-600 border-gray-200',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">

                        {{-- Date --}}
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                            <p class="font-medium text-gray-700">{{ $log->created_at->format('d/m/Y') }}</p>
                            <p class="text-xs text-gray-400">{{ $log->created_at->format('H:i:s') }}</p>
                        </td>

                        {{-- Action --}}
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $eventColor }}">
                                {{ $log->event_label }}
                            </span>
                        </td>

                        {{-- Section --}}
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium border {{ $sectionColor }}">
                                {{ $log->section }}
                            </span>
                        </td>

                        {{-- Détail --}}
                        <td class="px-4 py-3">
                            <p class="text-gray-800 font-medium">{{ $log->description }}</p>
                            @if($log->properties && isset($log->properties['changed']))
                            <p class="text-xs text-gray-400 mt-0.5">
                                Champs modifiés : {{ implode(', ', $log->properties['changed']) }}
                            </p>
                            @endif
                        </td>

                        {{-- Auteur --}}
                        <td class="px-4 py-3">
                            @if($log->user)
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-amber-400 flex items-center justify-center text-xs font-bold text-white shrink-0">
                                        {{ strtoupper(substr($log->user->name, 0, 1)) }}
                                    </div>
                                    <span class="text-gray-700 font-medium truncate">{{ $log->user->name }}</span>
                                </div>
                            @else
                                <span class="text-gray-400 text-xs italic">Système</span>
                            @endif
                        </td>

                        {{-- IP --}}
                        <td class="px-4 py-3 text-xs text-gray-400 font-mono">
                            {{ $log->ip_address ?? '—' }}
                        </td>

                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Pagination --}}
            @if($logs->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $logs->links() }}
            </div>
            @endif
        @endif
    </div>

</div>
@endsection
