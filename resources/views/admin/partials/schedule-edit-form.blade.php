{{--
    Formulaire d'édition d'une échéance (partial partagé pending + post-acceptation).
    Variables attendues : $reservation, $sch, $totalPrice, $firstCheckIn, $schedOthers
    Doit être inclus dans un élément avec x-data="scheduleEditFormXX()"
--}}

{{-- La définition de la fonction Alpine est poussée dans @stack('scripts') (bas de page, scope global)
     pour être disponible avant qu'Alpine n'évalue l'attribut x-data. --}}
@push('scripts')
<script>
function scheduleEditForm{{ $sch->id }}() {
    return {
        pct:               {{ $totalPrice > 0 ? round($sch->amount / $totalPrice * 100, 1) : 0 }},
        amt:               {{ $sch->amount }},
        label:             @js($sch->label ?? ''),
        dueDate:           '{{ $sch->due_date->format('Y-m-d') }}',
        dueTime:           '{{ $sch->due_time ? \Illuminate\Support\Carbon::parse($sch->due_time)->format('H:i') : '12:00' }}',
        daysBeforeCheckIn: null,
        totalPrice:        {{ $totalPrice }},
        othersScheduled:   {{ $schedOthers }},

        get totalCoveredPct() {
            if (!this.totalPrice) return 0;
            return Math.round((this.othersScheduled + (parseFloat(this.amt) || 0)) / this.totalPrice * 100);
        },
        get remainingAfter() {
            return Math.max(0, this.totalPrice - this.othersScheduled - (parseFloat(this.amt) || 0));
        },
        get overBudget() {
            if (!this.totalPrice) return false;
            return (this.othersScheduled + (parseFloat(this.amt) || 0)) > this.totalPrice + 0.01;
        },
        calcDueDateFromDays() {
            const days = parseInt(this.daysBeforeCheckIn);
            if (!days || days <= 0) return;
            const ref = new Date('{{ $firstCheckIn?->format('Y-m-d') ?? '' }}T00:00:00');
            ref.setDate(ref.getDate() - days);
            this.dueDate = ref.toISOString().split('T')[0];
        },
        applyDays(d) { this.daysBeforeCheckIn = d; this.calcDueDateFromDays(); },
        setPct(p) {
            this.pct = p;
            if (this.totalPrice > 0) {
                this.amt = Math.round((p / 100) * this.totalPrice * 100) / 100;
                if (!this.label) this.label = 'Acompte ' + p + '%';
            }
        },
        setRemaining() {
            const rem = Math.max(0, this.totalPrice - this.othersScheduled);
            this.amt  = Math.round(rem * 100) / 100;
            if (this.totalPrice > 0) this.pct = Math.round(rem / this.totalPrice * 10000) / 100;
            if (!this.label) this.label = 'Solde';
        },
        updateFromPct() {
            const p = parseFloat(this.pct);
            if (!isNaN(p) && this.totalPrice > 0) this.amt = Math.round((p / 100) * this.totalPrice * 100) / 100;
        },
        updateFromAmt() {
            const a = parseFloat(this.amt);
            if (!isNaN(a) && this.totalPrice > 0) this.pct = Math.round(a / this.totalPrice * 10000) / 100;
        },
        fmtAmt(v) {
            return (Math.round(v * 100) / 100).toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        },
    };
}
</script>
@endpush

<form action="{{ route('admin.reservations.schedules.update', [$reservation, $sch]) }}" method="POST">
    @csrf @method('PATCH')

    {{-- Libellé --}}
    <div class="mb-2">
        <label class="block text-xs text-gray-500 mb-1">Libellé</label>
        <input type="text" name="label" x-model="label" value="{{ $sch->label ?? '' }}" placeholder="ex: Acompte 1re tranche"
               class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-blue-400 focus:outline-none">
    </div>

    {{-- Délai avant le 1er check-in --}}
    @if($firstCheckIn)
    <div class="mb-2 p-2.5 bg-blue-50 border border-blue-100 rounded-lg">
        <p class="text-xs text-blue-700 font-medium mb-1.5">
            <svg class="w-3.5 h-3.5 inline-block mr-0.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Délai avant le 1<sup>er</sup> check-in ({{ $firstCheckIn->format('d/m/Y') }})
        </p>
        <div class="flex flex-wrap gap-1 items-center">
            <input type="number" min="1" max="365"
                   x-model.number="daysBeforeCheckIn" @input="calcDueDateFromDays()"
                   placeholder="nb jours"
                   class="w-20 border border-blue-200 rounded-lg px-2 py-1 text-xs focus:ring-2 focus:ring-blue-400 focus:outline-none">
            @foreach([7, 14, 30, 60] as $d)
            <button type="button" @click="applyDays({{ $d }})"
                :class="daysBeforeCheckIn === {{ $d }} ? 'bg-blue-600 text-white' : 'bg-white text-blue-600 border border-blue-200 hover:bg-blue-100'"
                class="text-xs font-semibold px-2 py-1 rounded-md transition">{{ $d }}j</button>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Date + Heure --}}
    <div class="grid grid-cols-2 gap-2 mb-2">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Date limite *</label>
            <input type="date" name="due_date" required x-model="dueDate"
                   class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-blue-400 focus:outline-none">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Heure limite</label>
            <input type="time" name="due_time" x-model="dueTime"
                   class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-blue-400 focus:outline-none">
        </div>
    </div>

    {{-- Boutons preset % --}}
    @if($totalPrice > 0)
    <div class="flex gap-1 flex-wrap mb-2">
        <span class="text-xs text-gray-400 self-center mr-0.5">Rapide :</span>
        @foreach([25, 30, 50, 70, 100] as $p)
        <button type="button" @click="setPct({{ $p }})"
            :class="Math.abs(pct - {{ $p }}) < 0.1 ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-blue-50 hover:text-blue-700'"
            class="text-xs font-semibold px-2 py-1 rounded-md transition">{{ $p }}%</button>
        @endforeach
        <button type="button" @click="setRemaining()"
            class="text-xs font-semibold px-2 py-1 rounded-md bg-blue-50 text-blue-600 hover:bg-blue-100 transition ml-1">Reste</button>
    </div>
    @endif

    {{-- Pourcentage ↔ MAD --}}
    <div class="grid grid-cols-2 gap-2 mb-2">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Pourcentage</label>
            <div class="relative">
                <input type="number" min="0" max="100" step="0.1"
                       x-model="pct" @input="updateFromPct()"
                       :disabled="{{ $totalPrice > 0 ? 'false' : 'true' }}"
                       class="w-full border border-gray-200 rounded-lg pl-2 pr-6 py-1.5 text-xs focus:ring-2 focus:ring-blue-400 focus:outline-none disabled:bg-gray-50 disabled:text-gray-400">
                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">%</span>
            </div>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Montant (MAD) *</label>
            <input type="number" name="amount" min="0.01" step="0.01" required
                   x-model="amt" @input="updateFromAmt()" value="{{ $sch->amount }}"
                   class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-blue-400 focus:outline-none">
        </div>
    </div>

    {{-- Barre de progression --}}
    @if($totalPrice > 0)
    <div class="mb-3">
        <div class="flex justify-between text-xs mb-1">
            <span class="text-gray-400">Déjà planifié</span>
            <span class="font-semibold" :class="overBudget ? 'text-red-600' : 'text-gray-600'"
                  x-text="fmtAmt(othersScheduled + (parseFloat(amt)||0)) + ' / ' + fmtAmt(totalPrice) + ' MAD'"></span>
        </div>
        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-2 rounded-full transition-all"
                 :class="overBudget ? 'bg-red-400' : 'bg-blue-400'"
                 :style="'width:' + Math.min(100, totalCoveredPct) + '%'"></div>
        </div>
        <p x-show="overBudget" class="text-xs text-red-600 mt-0.5">Dépasse le total de la réservation</p>
        <p x-show="!overBudget && remainingAfter > 0" class="text-xs text-gray-400 mt-0.5"
           x-text="'Reste : ' + fmtAmt(remainingAfter) + ' MAD · ' + totalCoveredPct + '% planifié'"></p>
        <p x-show="!overBudget && remainingAfter <= 0 && (parseFloat(amt)||0) > 0"
           class="text-xs text-green-600 font-medium mt-0.5">Total entièrement couvert</p>
    </div>
    @endif

    <div class="flex gap-2">
        <button type="submit" :disabled="!(parseFloat(amt) > 0) || overBudget"
                class="text-xs bg-blue-600 hover:bg-blue-700 disabled:bg-gray-200 disabled:cursor-not-allowed text-white px-4 py-1.5 rounded font-medium">
            Enregistrer
        </button>
        <button type="button" onclick="toggleScheduleEdit({{ $sch->id }})"
                class="text-xs text-gray-400 hover:text-gray-600">Annuler</button>
    </div>
</form>
