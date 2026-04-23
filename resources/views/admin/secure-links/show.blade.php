@extends('admin.layouts.app')
@section('title', 'Lien — ' . $secureLink->agency_name)
@section('page-title', $secureLink->agency_name)
@section('page-subtitle', 'Lien sécurisé')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">

        {{-- URL --}}
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h2 class="text-base font-semibold mb-3">URL sécurisée</h2>
            <div class="flex gap-2">
                <input type="text" readonly value="{{ $secureLink->url }}"
                    class="flex-1 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 font-mono">
                <button onclick="navigator.clipboard.writeText('{{ $secureLink->url }}')"
                    class="bg-amber-500 text-white text-sm px-4 py-2 rounded-lg hover:bg-amber-600">Copier</button>
            </div>
        </div>

        {{-- Infos --}}
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h2 class="text-base font-semibold mb-4">Informations</h2>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div><span class="text-gray-500">Agence :</span> <strong>{{ $secureLink->agency_name }}</strong></div>
                <div><span class="text-gray-500">Email :</span> {{ $secureLink->agency_email }}</div>
                <div><span class="text-gray-500">Contact :</span> {{ $secureLink->contact_name ?? '—' }}</div>
                <div><span class="text-gray-500">Téléphone :</span> {{ $secureLink->contact_phone ?? '—' }}</div>
                <div><span class="text-gray-500">Hôtel :</span> {{ $secureLink->hotel?->name ?? 'Tous' }}</div>
                <div><span class="text-gray-500">Statut :</span> <strong>{{ $secureLink->status_label }}</strong></div>
                <div><span class="text-gray-500">Expiration :</span> {{ $secureLink->expires_at?->format('d/m/Y') ?? 'Illimité' }}</div>
                <div><span class="text-gray-500">Utilisations :</span> {{ $secureLink->uses_count }} / {{ $secureLink->max_uses }}</div>
                <div><span class="text-gray-500">Créé par :</span> {{ $secureLink->creator?->name ?? '—' }}</div>
                <div><span class="text-gray-500">Créé le :</span> {{ $secureLink->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        {{-- Réservations associées --}}
        @if($secureLink->reservations->isNotEmpty())
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h2 class="text-base font-semibold mb-4">Réservations via ce lien</h2>
            @foreach($secureLink->reservations as $res)
            <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                    <p class="text-sm font-medium font-mono">{{ $res->reference }}</p>
                    <p class="text-xs text-gray-400">{{ $res->check_in->format('d/m/Y') }} → {{ $res->check_out->format('d/m/Y') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    @include('admin.partials.status-badge', ['status' => $res->status, 'label' => $res->status_label])
                    <a href="{{ route('admin.reservations.show', $res) }}" class="text-amber-600 text-xs hover:underline">Voir →</a>
                </div>
            </div>
            @endforeach
        </div>
        @endif

    </div>

    {{-- Actions --}}
    <div class="space-y-4">
        <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
            <h3 class="font-semibold text-gray-900">Actions</h3>

            <form action="{{ route('admin.secure-links.send-email', $secureLink) }}" method="POST">
                @csrf
                <button class="w-full bg-amber-500 hover:bg-amber-600 text-white font-medium py-2 rounded-lg text-sm">
                    📧 Renvoyer l'email d'invitation
                </button>
            </form>

            <form action="{{ route('admin.secure-links.regenerate', $secureLink) }}" method="POST">
                @csrf @method('PATCH')
                <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-lg text-sm">
                    🔄 Régénérer le token
                </button>
            </form>

            @if($secureLink->is_active)
            <form action="{{ route('admin.secure-links.revoke', $secureLink) }}" method="POST">
                @csrf @method('PATCH')
                <button class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 rounded-lg text-sm"
                    onclick="return confirm('Révoquer ce lien ?')">
                    ✗ Révoquer le lien
                </button>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
