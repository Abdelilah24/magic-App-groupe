@extends('admin.layouts.app')
@section('title', 'Grilles tarifaires')
@section('page-title', 'Grilles tarifaires')

@section('header-actions')
    <a href="{{ route('admin.room-prices.table', ['hotel_id' => $hotelId]) }}"
       class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2 rounded-lg">
        📊 Tableau tarifaire
    </a>
@endsection

@section('content')
<div class="space-y-6">

    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-5 py-3 text-sm font-medium flex items-center gap-2">
        ✅ {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-5 py-3 text-sm font-medium">
        ⚠ {{ session('error') }}
    </div>
    @endif

    {{-- Sélecteur hôtel --}}
    <form method="GET" action="{{ route('admin.tariff-grids.index') }}" class="flex items-center gap-2">
        <select name="hotel_id" onchange="this.form.submit()"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-amber-400 focus:outline-none">
            @foreach($hotels as $h)
            <option value="{{ $h->id }}" {{ $hotelId == $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
            @endforeach
        </select>
    </form>

    @if($hotelId && $grids->isEmpty())
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
        <p class="text-amber-800 font-semibold mb-3">Aucune grille tarifaire configurée pour cet hôtel.</p>
        <form method="POST" action="{{ route('admin.tariff-grids.init-defaults') }}">
            @csrf
            <input type="hidden" name="hotel_id" value="{{ $hotelId }}">
            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-5 py-2 rounded-lg">
                ⚡ Initialiser les grilles par défaut
            </button>
        </form>
    </div>
    @elseif($grids->isNotEmpty())

    {{-- ── Tableau des grilles ──────────────────────────────────────────── --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700">
                {{ $grids->count() }} grille(s) tarifaire(s)
                <span class="ml-2 text-xs font-normal text-gray-400">— Les grilles calculées se basent sur la grille NRF de base.</span>
            </h2>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs text-gray-500 uppercase tracking-wide">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold">Code</th>
                    <th class="px-5 py-3 text-left font-semibold">Nom</th>
                    <th class="px-5 py-3 text-left font-semibold">Formule</th>
                    <th class="px-5 py-3 text-left font-semibold">Arrondi</th>
                    <th class="px-5 py-3 text-center font-semibold">Exemple (base 1 000 MAD)</th>
                    <th class="px-5 py-3 text-right font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($grids as $grid)
                @php
                    // Calcul d'exemple avec 1000 MAD de base
                    $allGridsKeyed = $grids->keyBy('id');
                    $example = $grid->calculatePrice(1000, $allGridsKeyed->all());
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-3">
                        <span class="font-mono text-xs font-bold bg-amber-100 text-amber-700 px-2 py-0.5 rounded">
                            {{ $grid->code }}
                        </span>
                    </td>
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $grid->name }}</td>
                    <td class="px-5 py-3 text-gray-500 text-xs">
                        @if($grid->is_base)
                            <span class="bg-emerald-100 text-emerald-700 font-semibold px-2 py-0.5 rounded">Saisie manuelle</span>
                        @else
                            <span class="font-mono">{{ $grid->formulaLabel() }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-xs text-gray-500">
                        @php $roundLabels = ['round'=>'Arrondi', 'ceil'=>'Vers le haut', 'floor'=>'Vers le bas', 'none'=>'Aucun'] @endphp
                        {{ $roundLabels[$grid->rounding] ?? $grid->rounding }}
                    </td>
                    <td class="px-5 py-3 text-center font-semibold text-amber-700">
                        {{ number_format($example, 2, ',', ' ') }} MAD
                    </td>
                    <td class="px-5 py-3 text-right">
                        <button onclick="openEdit({{ $grid->id }})"
                            class="text-xs text-amber-600 hover:text-amber-800 font-medium border border-amber-200 hover:border-amber-400 px-3 py-1 rounded-lg transition-colors">
                            ✏️ Modifier
                        </button>
                        @if(!$grid->is_base)
                        <form method="POST" action="{{ route('admin.tariff-grids.destroy', $grid) }}"
                              class="inline" onsubmit="return confirm('Supprimer cette grille ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-gray-400 hover:text-red-500 ml-1 px-2 py-1 rounded-lg transition-colors">×</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ── Modales d'édition ─────────────────────────────────────────────── --}}
    @foreach($grids as $grid)
    <div id="edit-modal-{{ $grid->id }}" class="fixed inset-0 z-50 hidden bg-black/40 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">Modifier : {{ $grid->name }}</h3>
                <button onclick="closeEdit({{ $grid->id }})" class="text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
            </div>
            <form method="POST" action="{{ route('admin.tariff-grids.update', $grid) }}" class="p-6 space-y-4">
                @csrf @method('PUT')

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nom</label>
                    <input type="text" name="name" value="{{ old('name', $grid->name) }}" required
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                </div>

                @if(!$grid->is_base)
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Grille de référence</label>
                    <select name="base_grid_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        @foreach($grids->where('id', '!=', $grid->id)->where('is_base', false)->prepend($grids->firstWhere('is_base', true)) as $g)
                        @if($g)
                        <option value="{{ $g->id }}" {{ $grid->base_grid_id == $g->id ? 'selected' : '' }}>
                            {{ $g->code }} — {{ $g->name }}
                        </option>
                        @endif
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Opérateur</label>
                        <select name="operator" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                            <option value="divide"           {{ $grid->operator === 'divide'           ? 'selected' : '' }}>÷ Diviser par</option>
                            <option value="multiply"         {{ $grid->operator === 'multiply'         ? 'selected' : '' }}>× Multiplier par</option>
                            <option value="subtract_percent" {{ $grid->operator === 'subtract_percent' ? 'selected' : '' }}>− Soustraire %</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Valeur</label>
                        <input type="number" name="operator_value" step="0.0001" min="0.0001"
                            value="{{ old('operator_value', $grid->operator_value) }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                    </div>
                </div>
                @endif

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Arrondi</label>
                    <select name="rounding" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        <option value="round" {{ $grid->rounding === 'round' ? 'selected' : '' }}>Arrondi standard (0,5 → 1)</option>
                        <option value="ceil"  {{ $grid->rounding === 'ceil'  ? 'selected' : '' }}>Arrondi au-dessus</option>
                        <option value="floor" {{ $grid->rounding === 'floor' ? 'selected' : '' }}>Arrondi en-dessous</option>
                        <option value="none"  {{ $grid->rounding === 'none'  ? 'selected' : '' }}>Sans arrondi</option>
                    </select>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                        class="flex-1 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                        Enregistrer
                    </button>
                    <button type="button" onclick="closeEdit({{ $grid->id }})"
                        class="text-gray-400 text-sm px-4 py-2 hover:text-gray-600">Annuler</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach

    {{-- ── Ajouter une grille personnalisée ──────────────────────────────── --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5" x-data="{ open: false }">
        <button @click="open = !open" class="flex items-center gap-2 text-sm font-semibold text-amber-700 hover:text-amber-900">
            <span class="text-lg leading-none">+</span> Ajouter une grille personnalisée
        </button>
        <div x-show="open" x-transition class="mt-4 border-t pt-4">
            <form method="POST" action="{{ route('admin.tariff-grids.store') }}" class="grid grid-cols-2 gap-4">
                @csrf
                <input type="hidden" name="hotel_id" value="{{ $hotelId }}">

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nom *</label>
                    <input type="text" name="name" required placeholder="Ex: Agence partenaire"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Code * <span class="font-normal text-gray-400">(lettres/tirets)</span></label>
                    <input type="text" name="code" required placeholder="EX: AGENCE_PARTENAIRE"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none uppercase">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Basée sur *</label>
                    <select name="base_grid_id" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        @foreach($grids as $g)
                        <option value="{{ $g->id }}">{{ $g->code }} — {{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Arrondi *</label>
                    <select name="rounding" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        <option value="round">Arrondi standard</option>
                        <option value="ceil">Vers le haut</option>
                        <option value="floor">Vers le bas</option>
                        <option value="none">Sans arrondi</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Opérateur *</label>
                    <select name="operator" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        <option value="divide">÷ Diviser par</option>
                        <option value="multiply">× Multiplier par</option>
                        <option value="subtract_percent">− Soustraire %</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Valeur *</label>
                    <input type="number" name="operator_value" step="0.0001" min="0.0001" required placeholder="Ex: 1.1"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                </div>
                <div class="col-span-2">
                    <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-5 py-2 rounded-lg">
                        Créer la grille
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
function openEdit(id) {
    document.getElementById('edit-modal-' + id).classList.remove('hidden');
    document.getElementById('edit-modal-' + id).classList.add('flex');
}
function closeEdit(id) {
    document.getElementById('edit-modal-' + id).classList.add('hidden');
    document.getElementById('edit-modal-' + id).classList.remove('flex');
}
// Fermer en cliquant outside
document.querySelectorAll('[id^="edit-modal-"]').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) closeEdit(this.id.replace('edit-modal-', ''));
    });
});
</script>
@endpush
