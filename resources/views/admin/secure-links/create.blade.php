@extends('admin.layouts.app')
@section('title', 'Nouveau lien agence')
@section('page-title', 'Créer un lien sécurisé')
@section('page-subtitle', 'Générez un lien d\'accès pour une agence partenaire')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <form action="{{ route('admin.secure-links.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'agence *</label>
                    <input type="text" name="agency_name" required value="{{ old('agency_name') }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
                        placeholder="Agence Voyage Maroc">
                    @error('agency_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email agence *</label>
                    <input type="email" name="agency_email" required value="{{ old('agency_email') }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                    @error('agency_email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom du contact</label>
                    <input type="text" name="contact_name" value="{{ old('contact_name') }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone contact</label>
                    <input type="text" name="contact_phone" value="{{ old('contact_phone') }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hôtel (optionnel)</label>
                    <select name="hotel_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        <option value="">— Tous les hôtels —</option>
                        @foreach($hotels as $hotel)
                        <option value="{{ $hotel->id }}" {{ old('hotel_id') == $hotel->id ? 'selected' : '' }}>{{ $hotel->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expiration (jours)</label>
                    <input type="number" name="expires_in_days" value="{{ old('expires_in_days', 30) }}" min="1"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
                        placeholder="30">
                    <p class="text-xs text-gray-400 mt-1">Laisser vide pour illimité</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Utilisations max</label>
                    <input type="number" name="max_uses" value="{{ old('max_uses', 1) }}" min="1"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes internes</label>
                    <textarea name="notes" rows="2"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"></textarea>
                </div>

                <div class="col-span-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="send_email" value="1" checked class="rounded border-gray-300 text-amber-500">
                        <span class="text-sm text-gray-700">Envoyer l'email d'invitation automatiquement</span>
                    </label>
                </div>
            </div>

            <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100">
                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-6 py-2 rounded-lg text-sm">
                    Générer le lien
                </button>
                <a href="{{ route('admin.secure-links.index') }}" class="text-gray-500 hover:text-gray-700 text-sm px-4 py-2">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
