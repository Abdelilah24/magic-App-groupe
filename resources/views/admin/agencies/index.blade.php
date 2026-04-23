@extends('admin.layouts.app')
@section('title', 'Agences')
@section('page-title', 'Agences partenaires')
@section('page-subtitle', 'Gérez les agences de voyage inscrites')

@section('content')

{{-- Filtres statut --}}
<div class="flex flex-wrap gap-2 mb-5">
    <a href="{{ route('admin.agencies.index') }}"
       class="px-3 py-1.5 rounded-lg text-sm font-medium border {{ !request('status') ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300' }}">
        Toutes <span class="ml-1 text-xs opacity-70">({{ $counts['all'] }})</span>
    </a>
    <a href="{{ route('admin.agencies.index', ['status' => 'pending']) }}"
       class="px-3 py-1.5 rounded-lg text-sm font-medium border {{ request('status') === 'pending' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-gray-600 border-gray-200' }}">
        En attente
        @if($counts['pending'] > 0)
            <span class="ml-1 bg-red-500 text-white text-xs rounded-full px-1.5">{{ $counts['pending'] }}</span>
        @endif
    </a>
    <a href="{{ route('admin.agencies.index', ['status' => 'approved']) }}"
       class="px-3 py-1.5 rounded-lg text-sm font-medium border {{ request('status') === 'approved' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-gray-600 border-gray-200' }}">
        Approuvées <span class="ml-1 text-xs opacity-70">({{ $counts['approved'] }})</span>
    </a>
    <a href="{{ route('admin.agencies.index', ['status' => 'rejected']) }}"
       class="px-3 py-1.5 rounded-lg text-sm font-medium border {{ request('status') === 'rejected' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-gray-600 border-gray-200' }}">
        Rejetées
    </a>
</div>

{{-- Recherche --}}
<form method="GET" class="mb-4 flex gap-2">
    @if(request('status')) <input type="hidden" name="status" value="{{ request('status') }}"> @endif
    <input type="text" name="search" value="{{ request('search') }}"
           placeholder="Nom, email, contact..."
           class="flex-1 border border-gray-200 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
    <button class="bg-amber-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-amber-600">Rechercher</button>
</form>

{{-- Tableau --}}
<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
    <table class="min-w-full divide-y divide-gray-100">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Agence</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ville</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Réservations</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inscrite le</th>
                <th></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            @forelse($agencies as $agency)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <p class="text-sm font-medium text-gray-900">{{ $agency->name }}</p>
                    <p class="text-xs text-gray-400">{{ $agency->email }}</p>
                </td>
                <td class="px-4 py-3">
                    <p class="text-sm text-gray-700">{{ $agency->contact_name }}</p>
                    <p class="text-xs text-gray-400">{{ $agency->phone }}</p>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ $agency->city ?? '—' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ $agency->reservations_count }}</td>
                <td class="px-4 py-3">
                    @php
                        $colors = ['pending' => 'bg-yellow-100 text-yellow-800', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700'];
                    @endphp
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colors[$agency->status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $agency->status_label }}
                    </span>
                </td>
                <td class="px-4 py-3 text-xs text-gray-400">{{ $agency->created_at->format('d/m/Y') }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.agencies.show', $agency) }}"
                       class="text-amber-600 hover:underline text-xs font-medium">Voir →</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="py-16 text-center text-gray-400">
                    @if(request('status') === 'pending')
                        ✅ Aucune agence en attente d'approbation.
                    @else
                        Aucune agence enregistrée.
                    @endif
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100">{{ $agencies->links() }}</div>
</div>

{{-- Lien d'inscription à partager --}}
<div class="mt-6 bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center justify-between">
    <div>
        <p class="text-sm font-medium text-amber-900">🔗 Lien d'inscription pour les agences</p>
        <p class="text-xs text-amber-600 mt-0.5">Partagez ce lien aux agences pour qu'elles puissent s'inscrire.</p>
    </div>
    <div class="flex items-center gap-2">
        <code class="text-xs bg-white border border-amber-200 rounded px-3 py-1.5 text-amber-800">{{ route('agency.register') }}</code>
        <button onclick="navigator.clipboard.writeText('{{ route('agency.register') }}')"
            class="bg-amber-500 text-white text-xs px-3 py-1.5 rounded-lg hover:bg-amber-600">Copier</button>
    </div>
</div>

@endsection
