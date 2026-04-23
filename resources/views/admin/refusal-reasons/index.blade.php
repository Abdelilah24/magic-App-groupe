@extends('admin.layouts.app')

@section('title', 'Motifs de refus')
@section('page-title', 'Motifs de refus')
@section('page-subtitle', 'Gérer les raisons prédéfinies proposées lors du refus d\'une réservation')

@section('header-actions')
    <button @click="$dispatch('open-create-reason')"
            x-data
            class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold text-sm px-4 py-2 rounded-lg transition-colors shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nouveau motif
    </button>
@endsection

@section('content')
<div
    @open-create-reason.window="openCreate = true"
    x-data="{
        openCreate: false,
        openEdit:   false,
        openDelete: false,
        editId:     null,
        editLabel:  '',
        editOrder:  0,
        editActive: true,
        deleteId:   null,
        deleteLabel: '',

        startEdit(id, label, order, active) {
            this.editId     = id;
            this.editLabel  = label;
            this.editOrder  = order;
            this.editActive = active;
            this.openEdit   = true;
        },
        startDelete(id, label) {
            this.deleteId    = id;
            this.deleteLabel = label;
            this.openDelete  = true;
        }
    }"
>

{{-- ===================== LISTE ===================== --}}
<div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
            <h2 class="font-bold text-gray-900">Motifs configurés</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ $reasons->count() }} motif(s) — triés par ordre d'affichage</p>
        </div>
    </div>

    @if($reasons->isEmpty())
        <div class="px-6 py-12 text-center">
            <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-sm text-gray-500">Aucun motif configuré. Commencez par en créer un.</p>
        </div>
    @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-3 text-left">Ordre</th>
                    <th class="px-6 py-3 text-left">Libellé</th>
                    <th class="px-6 py-3 text-center">Statut</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($reasons as $reason)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 text-gray-500 font-mono text-xs">{{ $reason->sort_order }}</td>
                    <td class="px-6 py-4">
                        <span class="font-medium text-gray-900">{{ $reason->label }}</span>
                        @if($reason->isOther())
                            <span class="ml-2 text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium">Saisie libre</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($reason->is_active)
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-2.5 py-0.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Actif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-gray-500 bg-gray-100 border border-gray-200 rounded-full px-2.5 py-0.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactif
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button
                                @click="startEdit({{ $reason->id }}, {{ json_encode($reason->label) }}, {{ $reason->sort_order }}, {{ $reason->is_active ? 'true' : 'false' }})"
                                class="text-xs font-medium text-blue-600 hover:text-blue-800 px-2.5 py-1.5 rounded-lg hover:bg-blue-50 transition-colors">
                                Modifier
                            </button>
                            @if(! $reason->isOther())
                            <button
                                @click="startDelete({{ $reason->id }}, {{ json_encode($reason->label) }})"
                                class="text-xs font-medium text-red-600 hover:text-red-800 px-2.5 py-1.5 rounded-lg hover:bg-red-50 transition-colors">
                                Supprimer
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- ===================== MODAL CRÉER ===================== --}}
<div x-show="openCreate" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="openCreate = false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-bold text-gray-900">Nouveau motif de refus</h3>
            <button @click="openCreate = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form action="{{ route('admin.refusal-reasons.store') }}" method="POST" class="px-6 py-5 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Libellé <span class="text-red-500">*</span></label>
                <input type="text" name="label" required maxlength="200" autofocus
                       placeholder="Ex : Disponibilité insuffisante…"
                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Ordre d'affichage</label>
                    <input type="number" name="sort_order" value="0" min="0"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent">
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" checked
                               class="w-4 h-4 rounded accent-amber-500">
                        <span class="text-sm font-medium text-gray-700">Actif</span>
                    </label>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                <button type="button" @click="openCreate = false"
                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 rounded-lg hover:bg-gray-100 transition-colors">
                    Annuler
                </button>
                <button type="submit"
                        class="px-5 py-2 text-sm font-semibold bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors shadow-sm">
                    Créer le motif
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===================== MODAL MODIFIER ===================== --}}
<div x-show="openEdit" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="openEdit = false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-bold text-gray-900">Modifier le motif</h3>
            <button @click="openEdit = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form :action="`{{ url('admin/refusal-reasons') }}/${editId}`" method="POST" class="px-6 py-5 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Libellé <span class="text-red-500">*</span></label>
                <input type="text" name="label" required maxlength="200"
                       x-model="editLabel"
                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Ordre d'affichage</label>
                    <input type="number" name="sort_order" min="0"
                           x-model="editOrder"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent">
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               :checked="editActive"
                               @change="editActive = $event.target.checked"
                               class="w-4 h-4 rounded accent-amber-500">
                        <span class="text-sm font-medium text-gray-700">Actif</span>
                    </label>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                <button type="button" @click="openEdit = false"
                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 rounded-lg hover:bg-gray-100 transition-colors">
                    Annuler
                </button>
                <button type="submit"
                        class="px-5 py-2 text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors shadow-sm">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===================== MODAL SUPPRIMER ===================== --}}
<div x-show="openDelete" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="openDelete = false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-gray-900">Supprimer ce motif ?</h3>
                <p class="text-sm text-gray-500 mt-0.5" x-text="`« ${deleteLabel} »`"></p>
            </div>
        </div>
        <p class="text-sm text-gray-600 mb-5">Cette action est irréversible. Le motif sera définitivement supprimé.</p>
        <div class="flex gap-3 justify-end">
            <button @click="openDelete = false"
                    class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 rounded-lg hover:bg-gray-100 transition-colors">
                Annuler
            </button>
            <form :action="`{{ url('admin/refusal-reasons') }}/${deleteId}`" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-5 py-2 text-sm font-semibold bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors shadow-sm">
                    Supprimer
                </button>
            </form>
        </div>
    </div>
</div>

</div>

<style>[x-cloak]{display:none!important}</style>
@endsection
