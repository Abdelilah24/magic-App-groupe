@extends('admin.layouts.app')
@section('title', $hotel->name)
@section('page-title', $hotel->name)
@section('page-subtitle', $hotel->city . '  ' . $hotel->stars_label)
@php use Illuminate\Support\Facades\Storage; @endphp

@section('header-actions')
    <a href="{{ route('admin.hotels.edit', $hotel) }}" class="bg-blue-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-blue-700">Modifier</a>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6"> <div class="lg:col-span-2">

    {{-- Infos générales --}}
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-4">
        <div class="flex items-start gap-5 mb-4">
            {{-- Logo --}}
            @if($hotel->logo)
            <img src="{{ Storage::url($hotel->logo) }}" alt="Logo {{ $hotel->name }}"
                 class="w-20 h-20 object-contain rounded-xl border border-gray-200 bg-gray-50 p-1.5 shrink-0">
            @else
            <div class="w-20 h-20 rounded-xl border border-dashed border-gray-300 bg-gray-50 flex items-center justify-center shrink-0">
                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            @endif
            <div class="flex-1">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><span class="text-gray-500">Adresse :</span> {{ $hotel->address ?? '—' }}</div>
                    <div><span class="text-gray-500">Ville :</span> {{ $hotel->city ?? '—' }}</div>
                    <div><span class="text-gray-500">Pays :</span> {{ $hotel->country ?? '—' }}</div>
                    <div><span class="text-gray-500">Email :</span> {{ $hotel->email ?? '—' }}</div>
                    <div><span class="text-gray-500">Téléphone :</span> {{ $hotel->phone ?? '—' }}</div>
                    <div><span class="text-gray-500">Statut :</span>
                        <span class="px-2 py-0.5 rounded-full text-xs {{ $hotel->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $hotel->is_active ? 'Actif' : 'Inactif' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIB --}}
        @if($hotel->bank_name || $hotel->bank_rib || $hotel->bank_iban)
        <div class="border-t border-gray-100 pt-4 mt-2">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                Coordonnées bancaires
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @if($hotel->bank_name)
                <div class="flex flex-col bg-gray-50 rounded-lg px-3 py-2.5">
                    <span class="text-xs text-gray-400 mb-0.5">Banque</span>
                    <span class="text-sm font-medium text-gray-800">{{ $hotel->bank_name }}</span>
                </div>
                @endif
                @if($hotel->bank_rib)
                <div class="flex flex-col bg-gray-50 rounded-lg px-3 py-2.5">
                    <span class="text-xs text-gray-400 mb-0.5">RIB</span>
                    <span class="text-sm font-mono font-medium text-gray-800 tracking-wide">{{ $hotel->bank_rib }}</span>
                </div>
                @endif
                @if($hotel->bank_iban)
                <div class="flex flex-col bg-gray-50 rounded-lg px-3 py-2.5">
                    <span class="text-xs text-gray-400 mb-0.5">IBAN</span>
                    <span class="text-sm font-mono font-medium text-gray-800 tracking-wide">{{ $hotel->bank_iban }}</span>
                </div>
                @endif
                @if($hotel->bank_swift)
                <div class="flex flex-col bg-gray-50 rounded-lg px-3 py-2.5">
                    <span class="text-xs text-gray-400 mb-0.5">SWIFT / BIC</span>
                    <span class="text-sm font-mono font-medium text-gray-800 uppercase tracking-widest">{{ $hotel->bank_swift }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div> <div class="bg-white border border-gray-200 rounded-xl p-6"> <div class="flex justify-between items-center mb-4"> <h2 class="font-semibold">Types de chambres</h2> <a href="{{ route('admin.room-types.create', ['hotel_id' => $hotel->id]) }}" class="text-sm text-amber-600 hover:underline">+ Ajouter</a> </div> @foreach($hotel->roomTypes as $rt)
            <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0 text-sm"> <div> <p class="font-medium">{{ $rt->name }}</p> <p class="text-xs text-gray-400">Capacité : {{ $rt->capacity }} | Stock : {{ $rt->total_rooms }}</p> </div> <a href="{{ route('admin.room-prices.index', ['hotel_id' => $hotel->id]) }}" class="text-amber-600 text-xs hover:underline">Tarifs </a> </div> @endforeach
        </div> </div> <div> <div class="bg-white border border-gray-200 rounded-xl p-5"> <h3 class="font-semibold mb-3">Dernières réservations</h3> @foreach($hotel->reservations as $res)
            <div class="py-2 border-b border-gray-50 last:border-0 text-sm"> <p class="font-mono text-xs text-amber-600">{{ $res->reference }}</p> <p class="text-gray-700">{{ $res->agency_name }}</p> </div> @endforeach
        </div> </div>
</div>
@endsection
