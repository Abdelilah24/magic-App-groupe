<div class="grid grid-cols-2 gap-4">

    <div class="col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Hôtel *</label>
        <select name="hotel_id" required
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
            <option value="">— Sélectionner un hôtel —</option>
            @foreach($hotels as $hotel)
            <option value="{{ $hotel->id }}" {{ old('hotel_id', $supplement?->hotel_id) == $hotel->id ? 'selected' : '' }}>
                {{ $hotel->name }}
            </option>
            @endforeach
        </select>
    </div>

    <div class="col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Titre du supplément *</label>
        <input type="text" name="title" required
            value="{{ old('title', $supplement?->title) }}"
            placeholder="ex: Dîner de gala, Soirée Nouvel An, Animation musicale..."
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
    </div>

    <div class="col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <textarea name="description" rows="2"
            placeholder="Détails de l'événement, menu, programme..."
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">{{ old('description', $supplement?->description) }}</textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Date de début *</label>
        <input type="date" name="date_from" required
            value="{{ old('date_from', $supplement?->date_from?->format('Y-m-d')) }}"
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin *</label>
        <input type="date" name="date_to" required
            value="{{ old('date_to', $supplement?->date_to?->format('Y-m-d')) }}"
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
        <p class="text-xs text-gray-400 mt-1">La période est inclusive : si le séjour couvre au moins un jour de cette plage, le supplément s'applique.</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Statut *</label>
        <select name="status" required
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
            <option value="optional"  {{ old('status', $supplement?->status) === 'optional'  ? 'selected' : '' }}>🔵 Optionnel</option>
            <option value="mandatory" {{ old('status', $supplement?->status) === 'mandatory' ? 'selected' : '' }}>🔴 Obligatoire</option>
        </select>
    </div>

    {{-- Prix par personne --}}
    <div class="col-span-2">
        <p class="text-sm font-medium text-gray-700 mb-2">Prix par personne (MAD) *</p>
        <div class="grid grid-cols-3 gap-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">👤 Adulte</label>
                <input type="number" name="price_adult" min="0" step="0.01" required
                    value="{{ old('price_adult', $supplement?->price_adult ?? 0) }}"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">🧒 Enfant</label>
                <input type="number" name="price_child" min="0" step="0.01" required
                    value="{{ old('price_child', $supplement?->price_child ?? 0) }}"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">👶 Bébé</label>
                <input type="number" name="price_baby" min="0" step="0.01" required
                    value="{{ old('price_baby', $supplement?->price_baby ?? 0) }}"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-1">Ces prix sont multipliés par le nombre de personnes de chaque catégorie dans la réservation.</p>
    </div>

    <div class="col-span-2">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="is_active" value="1"
                {{ old('is_active', $supplement?->is_active ?? true) ? 'checked' : '' }}
                class="rounded border-gray-300 text-amber-500">
            <span class="text-sm text-gray-700">Actif</span>
        </label>
    </div>

</div>
