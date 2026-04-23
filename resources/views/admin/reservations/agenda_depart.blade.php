@extends('admin.layouts.app')
@section('title', 'Agenda des départs')
@section('page-title', 'Agenda des départs')

@section('header-actions')
    <div class="flex items-center gap-2"> <a href="{{ route('admin.reservations.agenda', array_merge(['month' => $month, 'hotel_id' => $hotelId], ['statuses' => $selectedStatuses])) }}"
           class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg flex items-center gap-1.5"> Arrivées
        </a> <a href="{{ route('admin.reservations.index') }}"
           class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg"> Liste des réservations
        </a> </div>
@endsection

@php
use Carbon\Carbon;

$prevMonth = Carbon::parse($month)->subMonth()->format('Y-m');
$nextMonth = Carbon::parse($month)->addMonth()->format('Y-m');

$firstDay   = $startOfMonth->copy()->startOfWeek(Carbon::MONDAY);
$lastDay    = $endOfMonth->copy()->endOfWeek(Carbon::SUNDAY);
$calDays    = [];
$cursor     = $firstDay->copy();
while ($cursor->lte($lastDay)) {
    $calDays[] = $cursor->copy();
    $cursor->addDay();
}

$statusColors = [
    'pending'              => ['bg' => 'bg-amber-100',   'text' => 'text-amber-800',   'dot' => 'bg-amber-400',   'label' => 'En attente'],
    'accepted'             => ['bg' => 'bg-blue-100',    'text' => 'text-blue-800',    'dot' => 'bg-blue-400',    'label' => 'Accepté'],
    'waiting_payment'      => ['bg' => 'bg-orange-100',  'text' => 'text-orange-800',  'dot' => 'bg-orange-400',  'label' => 'Attente paiement'],
    'partially_paid'       => ['bg' => 'bg-purple-100',  'text' => 'text-purple-800',  'dot' => 'bg-purple-400',  'label' => 'Partiellement payé'],
    'paid'                 => ['bg' => 'bg-green-100',   'text' => 'text-green-800',   'dot' => 'bg-green-400',   'label' => 'Payé'],
    'confirmed'            => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800', 'dot' => 'bg-emerald-500', 'label' => 'Confirmé'],
    'modification_pending' => ['bg' => 'bg-indigo-100',  'text' => 'text-indigo-800',  'dot' => 'bg-indigo-400',  'label' => 'Modification'],
    'draft'                => ['bg' => 'bg-gray-100',    'text' => 'text-gray-600',    'dot' => 'bg-gray-400',    'label' => 'Brouillon'],
];
@endphp

@section('content')
<div class="space-y-4"> {{--  Barre de navigation  --}}
    <div class="flex flex-wrap items-center gap-3"> {{-- Filtre hôtel --}}
        <form method="GET" action="{{ route('admin.reservations.agenda-depart') }}" id="hotelForm" class="flex items-center gap-2"> <input type="hidden" name="month" value="{{ $month }}"> @foreach($selectedStatuses as $s)
            <input type="hidden" name="statuses[]" value="{{ $s }}"> @endforeach
            <select name="hotel_id" onchange="this.form.submit()"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-red-400 focus:outline-none"> <option value="">Tous les hôtels</option> @foreach($hotels as $h)
                <option value="{{ $h->id }}" {{ $hotelId == $h->id ? 'selected' : '' }}>{{ $h->name }}</option> @endforeach
            </select> </form> {{-- Navigation mois --}}
        <div class="flex items-center gap-1 bg-white border border-gray-200 rounded-xl overflow-hidden"> <a href="{{ route('admin.reservations.agenda-depart', array_merge(['month' => $prevMonth, 'hotel_id' => $hotelId], ['statuses' => $selectedStatuses])) }}"
               class="px-3 py-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800 transition-colors text-sm font-bold"></a> <span class="px-4 py-2 text-sm font-semibold text-gray-800 capitalize min-w-[160px] text-center"> {{ $startOfMonth->translatedFormat('F Y') }}
            </span> <a href="{{ route('admin.reservations.agenda-depart', array_merge(['month' => $nextMonth, 'hotel_id' => $hotelId], ['statuses' => $selectedStatuses])) }}"
               class="px-3 py-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800 transition-colors text-sm font-bold"></a> </div> {{-- Aujourd'hui --}}
        <a href="{{ route('admin.reservations.agenda-depart', array_merge(['month' => now()->format('Y-m'), 'hotel_id' => $hotelId], ['statuses' => $selectedStatuses])) }}"
           class="text-sm text-red-600 hover:text-red-800 border border-red-200 bg-red-50 px-3 py-2 rounded-lg transition-colors"> Aujourd'hui
        </a> {{-- Stats --}}
        <div class="ml-auto flex items-center gap-3 text-xs text-gray-500"> @if($todayDeparts > 0)
            <span class="flex items-center gap-1.5 bg-red-50 border border-red-200 text-red-700 font-semibold px-3 py-1.5 rounded-lg"> <span class="w-2 h-2 bg-red-400 rounded-full animate-pulse"></span> {{ $todayDeparts }} départ(s) aujourd'hui
            </span> @endif
            <span class="bg-gray-50 border border-gray-200 px-3 py-1.5 rounded-lg"> {{ $totalDeparts }} départ(s) ce mois
            </span> </div> </div> {{--  Filtre statuts  --}}
    <form method="GET" action="{{ route('admin.reservations.agenda-depart') }}" id="statusForm"> <input type="hidden" name="month" value="{{ $month }}"> <input type="hidden" name="hotel_id" value="{{ $hotelId }}"> <div class="flex flex-wrap gap-2 items-center"> <span class="text-xs text-gray-500 font-medium">Filtrer :</span> @foreach($statusColors as $status => $colors)
            @php $count = $statusCounts[$status] ?? 0; $isSelected = in_array($status, $selectedStatuses); @endphp
            <label class="cursor-pointer select-none"> <input type="checkbox" name="statuses[]" value="{{ $status }}"
                       {{ $isSelected ? 'checked' : '' }}
                       onchange="document.getElementById('statusForm').submit()"
                       class="sr-only"> <span class="flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-full border-2 transition-all
                             {{ $isSelected
                                 ? $colors['bg'] . ' ' . $colors['text'] . ' border-transparent font-semibold'
                                 : 'bg-white text-gray-400 border-gray-200 hover:border-gray-300' }}"> <span class="w-2 h-2 rounded-full {{ $isSelected ? $colors['dot'] : 'bg-gray-300' }}"></span> {{ $colors['label'] }}
                    @if($count > 0)
                    <span class="font-bold">{{ $count }}</span> @endif
                </span> </label> @endforeach
        </div> </form> {{--  Calendrier  --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden"> {{-- Jours de la semaine --}}
        <div class="grid grid-cols-7 border-b border-gray-200"> @foreach(['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $i => $jourLabel)
            <div class="py-2.5 text-center text-xs font-semibold uppercase tracking-wide
                        {{ $i >= 5 ? 'text-gray-400' : 'text-gray-500' }}
                        {{ $i < 6 ? 'border-r border-gray-100' : '' }}"> {{ $jourLabel }}
            </div> @endforeach
        </div> {{-- Grille des jours --}}
        <div class="grid grid-cols-7"> @foreach($calDays as $idx => $day)
            @php
                $dateKey    = $day->format('Y-m-d');
                $isToday    = $day->isToday();
                $isCurrentM = $day->month === $startOfMonth->month;
                $isWeekend  = $day->isWeekend();
                $departs    = $byDate->get($dateKey, collect());
                $count      = $departs->count();
                $isLastCol  = ($idx % 7) === 6;
            @endphp

            <div class="min-h-[110px] p-2 border-b border-gray-100 flex flex-col gap-1
                        {{ !$isLastCol ? 'border-r border-gray-100' : '' }}
                        {{ $isToday ? 'bg-red-50' : ($isWeekend && $isCurrentM ? 'bg-gray-50/50' : '') }}
                        {{ !$isCurrentM ? 'bg-gray-50/70 opacity-50' : '' }}"> {{-- Numéro du jour --}}
                <div class="flex items-center justify-between mb-0.5"> <span class="text-xs font-bold leading-none
                                 {{ $isToday
                                     ? 'w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center'
                                     : ($isCurrentM ? 'text-gray-700' : 'text-gray-400') }}"> {{ $day->day }}
                    </span> @if($count > 0)
                    <span class="text-[10px] font-bold {{ $count >= 3 ? 'text-red-500' : 'text-gray-400' }}"> {{ $count }}
                    </span> @endif
                </div> {{-- Départs --}}
                @foreach($departs->take(3) as $res)
                @php
                    $sc = $statusColors[$res->status] ?? $statusColors['draft'];
                    // Séjour correspondant à cette date de départ
                    $sejoursOnDate = $res->rooms->filter(
                        fn($r) => $r->check_out && $r->check_out->format('Y-m-d') === $dateKey
                    );
                    $sejourCheckIn = $sejoursOnDate->first()?->check_in;
                    $sejourNights  = $sejourCheckIn
                        ? $sejourCheckIn->diffInDays(\Carbon\Carbon::parse($dateKey))
                        : null;
                    // Numéro du séjour si multi-séjour
                    $allSejourDates = $res->rooms->filter(fn($r) => $r->check_out !== null)
                        ->map(fn($r) => $r->check_out->format('Y-m-d'))->unique()->sort()->values();
                    $sejourIdx = $allSejourDates->search($dateKey);
                    $isMulti   = $allSejourDates->count() > 1;
                @endphp
                <a href="{{ route('admin.reservations.show', $res) }}"
                   class="flex items-center gap-0.5 text-[10px] leading-tight rounded px-1.5 py-1 font-medium transition-opacity hover:opacity-80
                          {{ $sc['bg'] }} {{ $sc['text'] }}"
                   title="{{ $res->agency_name ?: $res->contact_name }}  {{ $res->hotel->name ?? '' }}{{ $sejourNights ? '  '.$sejourNights.' nuit'.($sejourNights>1?'s':'') : '' }}  {{ $sc['label'] }}"> <span class="inline-block w-1.5 h-1.5 rounded-full {{ $sc['dot'] }} mr-0.5 shrink-0 align-middle"></span> <span class="truncate">{{ Str::limit($res->agency_name ?: $res->contact_name, $isMulti ? 13 : 18) }}</span> @if($isMulti)
                    <span class="shrink-0 ml-0.5 opacity-70">·S{{ $sejourIdx + 1 }}</span> @endif
                </a> @endforeach

                {{-- +N si plus de 3 → popup --}}
                @if($count > 3)
                @php
                    $popupData = $departs->map(function($res) use ($statusColors, $dateKey) {
                        $sc = $statusColors[$res->status] ?? $statusColors['draft'];
                        $allDates  = $res->rooms->filter(fn($r) => $r->check_out !== null)
                            ->map(fn($r) => $r->check_out->format('Y-m-d'))->unique()->sort()->values();
                        $sejourIdx = $allDates->search($dateKey);
                        $isMulti   = $allDates->count() > 1;
                        $sejoursOnDate = $res->rooms->filter(fn($r) => $r->check_out && $r->check_out->format('Y-m-d') === $dateKey);
                        $sejourNights  = $sejoursOnDate->first()?->check_in
                            ? $sejoursOnDate->first()->check_in->diffInDays(\Carbon\Carbon::parse($dateKey))
                            : null;
                        return [
                            'name'   => $res->agency_name ?: $res->contact_name,
                            'hotel'  => $res->hotel->name ?? '',
                            'ref'    => $res->reference,
                            'label'  => $sc['label'],
                            'dot'    => $sc['dot'],
                            'bg'     => $sc['bg'],
                            'text'   => $sc['text'],
                            'sejour' => $isMulti ? ($sejourIdx + 1) : null,
                            'nights' => $sejourNights,
                            'url'    => route('admin.reservations.show', $res),
                        ];
                    })->values()->toArray();
                @endphp
                <button type="button"
                        onclick="openDayPopup('{{ $dateKey }}', this)"
                        data-reservations="{{ json_encode($popupData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
                        class="text-[10px] text-red-500 hover:text-red-700 font-semibold text-center w-full cursor-pointer hover:underline">
                    +{{ $count - 3 }} autres
                </button>
                @endif
            </div> @endforeach
        </div> </div> {{--  Départs aujourd'hui  --}}
    @php $todayKey = now()->format('Y-m-d'); $todayList = $byDate->get($todayKey, collect()); @endphp
    @if($todayList->isNotEmpty() && $startOfMonth->month === now()->month)
    <div class="bg-red-50 border border-red-200 rounded-2xl p-4"> <h3 class="text-sm font-bold text-red-800 mb-3 flex items-center gap-2"> <span class="w-2 h-2 bg-red-400 rounded-full animate-pulse"></span> Départs aujourd'hui ({{ $todayList->count() }})
        </h3> <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2"> @foreach($todayList as $res)
            @php
                $sc = $statusColors[$res->status] ?? $statusColors['draft'];
                $todayRooms = $res->rooms->filter(fn($r) => $r->check_out && $r->check_out->isToday());
                $todaySejour = $todayRooms->first();
                $todayNights = $todaySejour?->check_in
                    ? $todaySejour->check_in->diffInDays(now())
                    : $res->nights;
                $allDates2 = $res->rooms->filter(fn($r) => $r->check_out !== null)
                    ->map(fn($r) => $r->check_out->format('Y-m-d'))->unique()->sort()->values();
                $sejourIdx2 = $allDates2->search(now()->format('Y-m-d'));
                $isMulti2   = $allDates2->count() > 1;
            @endphp
            <a href="{{ route('admin.reservations.show', $res) }}"
               class="flex items-center gap-3 bg-white border border-red-100 rounded-xl px-4 py-3 hover:border-red-300 hover:shadow-sm transition-all"> <span class="w-3 h-3 rounded-full shrink-0 {{ $sc['dot'] }}"></span> <div class="min-w-0 flex-1"> <div class="text-sm font-semibold text-gray-800 truncate flex items-center gap-1.5"> {{ $res->agency_name ?: $res->contact_name }}
                        @if($isMulti2)
                        <span class="text-[10px] font-bold bg-red-100 text-red-700 px-1.5 py-0.5 rounded-full shrink-0">Séj. {{ $sejourIdx2 + 1 }}</span> @endif
                    </div> <div class="text-xs text-gray-400 truncate"> {{ $res->hotel->name ?? '' }} · {{ $res->reference }}
                        @if($todayNights) · {{ $todayNights }} nuit{{ $todayNights > 1 ? 's' : '' }} @endif
                    </div> </div> <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full shrink-0 {{ $sc['bg'] }} {{ $sc['text'] }}"> {{ $sc['label'] }}
                </span> </a> @endforeach
        </div> </div> @endif

</div>

{{-- ── Popup "+N autres" ──────────────────────────────────────────────── --}}
<div id="day-popup-overlay"
     onclick="closeDayPopup()"
     class="hidden fixed inset-0 bg-black/40 z-40 backdrop-blur-sm"></div>

<div id="day-popup"
     class="hidden fixed z-50 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2
            bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[80vh] flex flex-col overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 shrink-0">
        <h3 id="day-popup-title" class="font-bold text-gray-900 text-sm"></h3>
        <button onclick="closeDayPopup()"
                class="text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
    </div>
    <div id="day-popup-list" class="overflow-y-auto flex-1 p-4 space-y-2"></div>
</div>

<script>
const MONTHS_FR_D = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];

function openDayPopup(dateKey, btn) {
    const data = JSON.parse(btn.getAttribute('data-reservations'));
    const [y, m, d] = dateKey.split('-');
    const title = parseInt(d) + ' ' + MONTHS_FR_D[parseInt(m) - 1] + ' ' + y
                + ' \u2014 ' + data.length + ' d\u00e9part' + (data.length > 1 ? 's' : '');

    document.getElementById('day-popup-title').textContent = title;
    document.getElementById('day-popup-list').innerHTML = data.map(r => `
        <a href="${r.url}" class="flex items-center gap-3 border border-gray-100 rounded-xl px-4 py-3 hover:border-red-200 hover:bg-red-50 transition-all">
            <span class="w-2.5 h-2.5 rounded-full shrink-0 ${r.dot}"></span>
            <div class="flex-1 min-w-0">
                <div class="text-sm font-semibold text-gray-800 truncate flex items-center gap-1.5">
                    ${esc(r.name)}
                    ${r.sejour ? '<span class="text-[10px] font-bold bg-red-100 text-red-700 px-1.5 py-0.5 rounded-full shrink-0">S\u00e9j.\u00a0' + r.sejour + '</span>' : ''}
                </div>
                <div class="text-xs text-gray-400 truncate">
                    ${esc(r.hotel)} &middot; ${esc(r.ref)}${r.nights ? ' &middot; ' + r.nights + ' nuit' + (r.nights > 1 ? 's' : '') : ''}
                </div>
            </div>
            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full shrink-0 ${r.bg} ${r.text}">${esc(r.label)}</span>
        </a>
    `).join('');

    document.getElementById('day-popup-overlay').classList.remove('hidden');
    document.getElementById('day-popup').classList.remove('hidden');
}

function closeDayPopup() {
    document.getElementById('day-popup-overlay').classList.add('hidden');
    document.getElementById('day-popup').classList.add('hidden');
}

function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDayPopup(); });
</script>

@endsection
