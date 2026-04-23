@extends('admin.layouts.app')
@section('title', 'Nouvel hôtel')
@section('page-title', 'Ajouter un hôtel')

@section('content')
<div class="max-w-2xl"> <div class="bg-white border border-gray-200 rounded-xl p-6"> <form action="{{ route('admin.hotels.store') }}" method="POST" enctype="multipart/form-data"> @csrf
            <div class="grid grid-cols-2 gap-4"> <div class="col-span-2"> <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'hôtel *</label> <input type="text" name="name" required value="{{ old('name') }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div> <div> <label class="block text-sm font-medium text-gray-700 mb-1">Ville</label> <input type="text" name="city" value="{{ old('city') }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> <div> <label class="block text-sm font-medium text-gray-700 mb-1">Pays</label> <input type="text" name="country" value="{{ old('country', 'Maroc') }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> <div> <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label> <input type="text" name="phone" value="{{ old('phone') }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> <div> <label class="block text-sm font-medium text-gray-700 mb-1">Email</label> <input type="email" name="email" value="{{ old('email') }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> <div> <label class="block text-sm font-medium text-gray-700 mb-1">Nombre d'étoiles</label> <select name="stars" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> @for($i=0; $i<=5; $i++)
                        <option value="{{ $i }}" {{ old('stars', 0) == $i ? 'selected' : '' }}>{{ $i }} étoile(s)</option> @endfor
                    </select> </div> <div> <label class="flex items-center gap-2 mt-6 cursor-pointer"> <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-amber-500"> <span class="text-sm text-gray-700">Actif</span> </label> </div> <div class="col-span-2"> <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label> <input type="text" name="address" value="{{ old('address') }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> <div class="col-span-2"> <label class="block text-sm font-medium text-gray-700 mb-1">Description</label> <textarea name="description" rows="3"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">{{ old('description') }}</textarea> </div>

                {{-- Logo --}}
                <div class="col-span-2 pt-2">
                    <div class="border-t border-gray-100 pt-4 mb-4">
                        <p class="text-sm font-semibold text-gray-700">Logo de l'hôtel</p>
                        <p class="text-xs text-gray-400 mt-0.5">JPG, PNG, WebP ou SVG — max 2 Mo</p>
                    </div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fichier logo</label>
                    <input type="file" name="logo" accept="image/*"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-500
                                  file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                  file:text-xs file:font-medium file:bg-amber-50 file:text-amber-700
                                  hover:file:bg-amber-100 focus:outline-none">
                    @error('logo')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- RIB / Coordonnées bancaires --}}
                <div class="col-span-2 pt-2">
                    <div class="border-t border-gray-100 pt-4 mb-4">
                        <p class="text-sm font-semibold text-gray-700">Coordonnées bancaires (RIB)</p>
                        <p class="text-xs text-gray-400 mt-0.5">Utilisées sur les devis et factures envoyés aux clients</p>
                    </div>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom de la banque</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name') }}"
                           placeholder="Ex : Attijariwafa Bank"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Numéro RIB</label>
                    <input type="text" name="bank_rib" value="{{ old('bank_rib') }}"
                           placeholder="Ex : 007 780 0000123456789012 34"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-amber-400 focus:outline-none">
                    @error('bank_rib')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IBAN <span class="text-gray-400 font-normal">(optionnel)</span></label>
                    <input type="text" name="bank_iban" value="{{ old('bank_iban') }}"
                           placeholder="Ex : MA64007780000123456789"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-amber-400 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code SWIFT / BIC <span class="text-gray-400 font-normal">(optionnel)</span></label>
                    <input type="text" name="bank_swift" value="{{ old('bank_swift') }}"
                           placeholder="Ex : BCMAMAMC"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono uppercase focus:ring-2 focus:ring-amber-400 focus:outline-none">
                </div>

                </div> <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100"> <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-6 py-2 rounded-lg text-sm">Créer l'hôtel</button> <a href="{{ route('admin.hotels.index') }}" class="text-gray-500 text-sm px-4 py-2">Annuler</a> </div> </form> </div>
</div>
@endsection
