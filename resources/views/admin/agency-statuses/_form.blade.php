@php $s = $status ?? null; @endphp

<div class="space-y-4">

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nom du statut *</label>
        <input type="text" name="name" required
               value="{{ old('name', $s?->name) }}"
               placeholder="Ex : Agence de voyages, Individu, Comité d'entreprise..."
               class="w-full border @error('name') border-red-300 @else border-gray-200 @enderror rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Remise (%) *</label>
        <div class="flex items-center gap-3">
            <input type="number" name="discount_percent" required min="0" max="100" step="0.01"
                   value="{{ old('discount_percent', $s?->discount_percent ?? 0) }}"
                   class="w-32 border @error('discount_percent') border-red-300 @else border-gray-200 @enderror rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
                   id="discountInput">
            <span class="text-gray-500 text-sm">% de remise sur le tarif de base</span>
        </div>
        <p class="text-xs text-gray-400 mt-1">0 = tarif plein · 10 = −10 % · 100 = gratuit</p>
        <div class="mt-2 p-2 bg-amber-50 border border-amber-100 rounded-lg text-xs text-amber-700" id="formulaPreview">
            Formule : Tarif × <span id="multiplier">1.00</span> — Ex : 1 000 MAD → <span id="example">1 000 MAD</span>
        </div>
        @error('discount_percent')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Description (optionnel)</label>
        <textarea name="description" rows="2"
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
            placeholder="Ex : Agences de voyages inscrites au registre du tourisme">{{ old('description', $s?->description) }}</textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
        <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $s?->sort_order ?? 0) }}"
               class="w-24 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
    </div>

    <div class="flex items-center gap-6">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-amber-500"
                {{ old('is_active', $s ? ($s->is_active ? '1' : '') : '1') ? 'checked' : '' }}>
            <span class="text-sm text-gray-700">Actif (visible à l'inscription)</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="is_default" value="1" class="rounded border-gray-300 text-amber-500"
                {{ old('is_default', $s?->is_default) ? 'checked' : '' }}>
            <span class="text-sm text-gray-700">Statut par défaut</span>
        </label>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('discountInput');
    const multiplierEl = document.getElementById('multiplier');
    const exampleEl = document.getElementById('example');

    function update() {
        const pct = parseFloat(input.value) || 0;
        const mult = 1 - pct / 100;
        const ex   = Math.round(1000 * mult);
        multiplierEl.textContent = mult.toFixed(2);
        exampleEl.textContent = ex.toLocaleString('fr-FR') + ' MAD';
    }

    input.addEventListener('input', update);
    update();
});
</script>
