@extends('admin.layouts.app')
@section('title', 'Liens sécurisés')
@section('page-title', 'Liens sécurisés')
@section('page-subtitle', 'Gérez les accès des agences partenaires')

@section('header-actions')
    <a href="{{ route('admin.secure-links.create') }}"
       class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium px-4 py-2 rounded-lg">
        + Nouveau lien
    </a>
@endsection

@section('content')
<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
    <table class="min-w-full divide-y divide-gray-100">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Agence</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hôtel</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiration</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisations</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Créé le</th>
                <th></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            @forelse($links as $link)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <p class="text-sm font-medium text-gray-900">{{ $link->agency_name }}</p>
                    <p class="text-xs text-gray-400">{{ $link->contact_name }}</p>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ $link->agency_email }}</td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ $link->hotel?->name ?? 'Tous hôtels' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700">
                    {{ $link->expires_at ? $link->expires_at->format('d/m/Y') : '∞ Illimité' }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ $link->uses_count }} / {{ $link->max_uses }}</td>
                <td class="px-4 py-3">
                    @php
                        $statusClasses = [
                            'Actif'     => 'bg-green-100 text-green-700',
                            'Expiré'    => 'bg-gray-100 text-gray-500',
                            'Utilisé'   => 'bg-blue-100 text-blue-700',
                            'Désactivé' => 'bg-red-100 text-red-600',
                        ];
                    @endphp
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClasses[$link->status_label] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $link->status_label }}
                    </span>
                </td>
                <td class="px-4 py-3 text-xs text-gray-400">{{ $link->created_at->format('d/m/Y') }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.secure-links.show', $link) }}"
                       class="text-amber-600 hover:underline text-xs font-medium">Gérer →</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="py-16 text-center text-gray-400">Aucun lien créé.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100">{{ $links->links() }}</div>
</div>
@endsection
