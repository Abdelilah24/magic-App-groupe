<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-50">
<head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Espace Agence  {{ $agency->name }}</title> <script src="https://cdn.tailwindcss.com"></script> <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script> <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"> <meta name="csrf-token" content="{{ csrf_token() }}"> <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-full"> {{--  Header  --}}
<header class="bg-slate-900 text-white sticky top-0 z-30"> <div class="max-w-6xl mx-auto px-6 py-3 flex items-center justify-between"> <div class="flex items-center gap-4"> <span class="text-amber-400 text-lg font-bold"> Magic Hotels</span> <span class="hidden sm:block text-slate-500 text-sm">|</span> <span class="hidden sm:block text-slate-300 text-sm font-medium">{{ $agency->name }}</span> </div> <div class="flex items-center gap-4"> <a href="{{ route('agency.portal.profile') }}"
               class="text-slate-400 hover:text-white text-sm flex items-center gap-1.5"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/> </svg> Mon profil
            </a> <form action="{{ route('agency.logout') }}" method="POST"> @csrf
                <button class="text-slate-400 hover:text-white text-sm flex items-center gap-1.5"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/> </svg> Déconnexion
                </button> </form> </div> </div>
</header> {{--  Nav  --}}
<nav class="bg-white border-b border-gray-200"> <div class="max-w-6xl mx-auto px-6"> <div class="flex gap-6 -mb-px text-sm"> <a href="{{ route('agency.portal.dashboard') }}"
               class="py-3 px-1 border-b-2 border-amber-500 text-amber-600 font-semibold"> Tableau de bord
            </a> <a href="{{ route('agency.portal.profile') }}"
               class="py-3 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium"> Mon profil
            </a> </div> </div>
</nav> <main class="max-w-6xl mx-auto px-6 py-8 space-y-8" x-data="portalReservationForm()"> {{-- Flash --}}
    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm flex items-center gap-2"> <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> {{ session('success') }}
    </div> @endif

    {{--  Bienvenue + stats  --}}
    <div> <div class="flex items-center justify-between mb-4"> <div> <h1 class="text-xl font-bold text-gray-900">Bonjour, {{ $agency->contact_name ?? $agency->name }} </h1> <p class="text-sm text-gray-500 mt-0.5">Bienvenue dans votre espace partenaire Magic Hotels.</p> </div> <div class="flex items-center gap-3"> <span class="text-xs px-3 py-1.5 rounded-full font-medium
                    @if($agency->status === 'approved') bg-emerald-100 text-emerald-700
                    @elseif($agency->status === 'pending') bg-yellow-100 text-yellow-700
                    @else bg-red-100 text-red-700 @endif"> {{ $agency->status_label }}
                </span> @if($agency->status === 'approved')
                <button type="button"
                    @click="open = true; $nextTick(() => document.getElementById('nouvelle-demande')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                    class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-colors shadow-sm"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/> </svg> Nouvelle demande
                </button> @endif
            </div> </div> {{-- Cartes stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4"> <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex"> <div class="w-1.5 bg-gray-400 shrink-0"></div> <div class="px-4 py-4 flex-1"> <div class="flex items-center justify-between mb-2"> <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">Total</p> <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center"> <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg> </div> </div> <p class="text-3xl font-bold text-gray-900">{{ $stats['total'] }}</p> <p class="text-xs text-gray-400 mt-1">demande{{ $stats['total'] > 1 ? 's' : '' }}</p> </div> </div> <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex"> <div class="w-1.5 bg-blue-500 shrink-0"></div> <div class="px-4 py-4 flex-1"> <div class="flex items-center justify-between mb-2"> <p class="text-xs text-blue-500 font-semibold uppercase tracking-wide">En cours</p> <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center"> <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> </div> </div> <p class="text-3xl font-bold text-blue-600">{{ $stats['active'] }}</p> <p class="text-xs text-gray-400 mt-1">active{{ $stats['active'] > 1 ? 's' : '' }}</p> </div> </div> <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex"> <div class="w-1.5 bg-emerald-500 shrink-0"></div> <div class="px-4 py-4 flex-1"> <div class="flex items-center justify-between mb-2"> <p class="text-xs text-emerald-600 font-semibold uppercase tracking-wide">Confirmées</p> <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center"> <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> </div> </div> <p class="text-3xl font-bold text-emerald-600">{{ $stats['confirmed'] }}</p> <p class="text-xs text-gray-400 mt-1">payées / confirmées</p> </div> </div> <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex"> <div class="w-1.5 bg-amber-500 shrink-0"></div> <div class="px-4 py-4 flex-1"> <div class="flex items-center justify-between mb-2"> <p class="text-xs text-amber-600 font-semibold uppercase tracking-wide">Total payé</p> <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center"> <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> </div> </div> <p class="text-2xl font-bold text-amber-600">{{ number_format($stats['total_paid'], 0, ',', ' ') }}</p> <p class="text-xs text-gray-400 mt-1">MAD encaissés</p> </div> </div> </div> </div> {{--  Demandes  --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden"> <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between"> <div class="flex items-center gap-3"> <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center"> <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/> </svg> </div> <div> <h2 class="font-bold text-gray-900">Mes demandes</h2> <p class="text-xs text-gray-400">{{ $stats['total'] }} demande{{ $stats['total'] > 1 ? 's' : '' }} au total</p> </div> </div> <div class="flex items-center gap-2 text-xs"> @if($stats['pending_pay'] > 0)
                <span class="bg-orange-100 text-orange-700 px-3 py-1.5 rounded-full font-semibold flex items-center gap-1"> <span class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-pulse inline-block"></span> {{ $stats['pending_pay'] }} paiement(s) en attente
                </span> @endif
            </div> </div> @if($reservations->isEmpty())
        <div class="px-6 py-16 text-center"> <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/> </svg> <p class="text-gray-500 text-sm font-medium">Aucune demande pour le moment.</p> <p class="text-gray-400 text-xs mt-1">Contactez-nous pour soumettre votre première demande.</p> </div> @else
        {{-- Table des demandes --}}
        <div class="overflow-x-auto"> <table class="min-w-full divide-y divide-gray-100"> <thead class="bg-gray-50"> <tr> <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Référence</th> <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wide">Chambres</th> <th class="px-5 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wide">Prix total</th> <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wide">Paiement</th> <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wide">Statut</th> <th class="px-5 py-3"></th> </tr> </thead> <tbody class="divide-y divide-gray-100 bg-white"> @foreach($reservations as $res)
            @php
                $_taxeRate = 0;
                try { $_taxeRate = (float)($res->hotel->taxe_sejour ?? 0); } catch(\Exception $e) {}
                if ((float)($res->taxe_total ?? 0) > 0) {
                    $_resTaxe = (float)$res->taxe_total;
                } elseif ($_taxeRate > 0 && $res->rooms->isNotEmpty()) {
                    $_resTaxe = 0;
                    $_stayGroups = $res->rooms->groupBy(fn($r) => ($r->check_in?->format('Y-m-d') ?? 'g') . '_' .
                        ($r->check_out?->format('Y-m-d') ?? 'g'));
                    foreach ($_stayGroups as $_sRooms) {
                        $_f = $_sRooms->first();
                        $_n = ($_f->check_in && $_f->check_out) ? $_f->check_in->diffInDays($_f->check_out) : (int)$res->nights;
                        $_a = $_sRooms->sum(fn($r) => ($r->adults ?? 0) * max(1, $r->quantity ?? 1));
                        $_resTaxe += round($_a * $_n * $_taxeRate, 2);
                    }
                } else {
                    $_resTaxe = 0;
                }
                $totalPrice  = round(($res->total_price ?? 0) + $_resTaxe, 2);
                $amountPaid  = $res->payments->where('status','completed')->sum('amount');
                $pendingPay  = $res->payments->where('status','pending')->sum('amount');
                $remaining   = max(0, $totalPrice - $amountPaid);
                $pct         = $totalPrice > 0 ? min(100, round($amountPaid / $totalPrice * 100)) : 0;
                $hasPending  = $pendingPay > 0;
                $nbChambres  = $res->rooms->sum('quantity');
                $statusColors = [
                    'draft'               => 'bg-gray-100 text-gray-600',
                    'pending'             => 'bg-yellow-100 text-yellow-700',
                    'accepted'            => 'bg-blue-100 text-blue-700',
                    'refused'             => 'bg-red-100 text-red-700',
                    'waiting_payment'     => 'bg-orange-100 text-orange-700',
                    'partially_paid'      => 'bg-indigo-100 text-indigo-700',
                    'paid'                => 'bg-teal-100 text-teal-700',
                    'confirmed'           => 'bg-emerald-100 text-emerald-700',
                    'modification_pending'=> 'bg-purple-100 text-purple-700',
                    'cancelled'           => 'bg-gray-100 text-gray-400',
                ];
                $statusBar = [
                    'confirmed'           => 'bg-emerald-500',
                    'waiting_payment'     => 'bg-orange-500',
                    'partially_paid'      => 'bg-indigo-500',
                    'accepted'            => 'bg-blue-500',
                    'refused'             => 'bg-red-400',
                    'cancelled'           => 'bg-red-400',
                    'modification_pending'=> 'bg-purple-500',
                    'pending'             => 'bg-yellow-400',
                    'draft'               => 'bg-gray-300',
                ];
                $_ciPortal  = $res->check_in instanceof \Carbon\Carbon ? $res->check_in : \Carbon\Carbon::parse($res->check_in);
                $_daysLeft  = (int) now()->startOfDay()->diffInDays($_ciPortal->copy()->startOfDay(), false);
                $canEdit    = in_array($res->status, ['draft','pending','accepted','waiting_payment','partially_paid','modification_pending'])
                              && $_daysLeft > 7;
                $blockedBy7 = in_array($res->status, ['draft','pending','accepted','waiting_payment','partially_paid','modification_pending'])
                              && $_daysLeft <= 7 && $_daysLeft >= 0;
                // Groupes séjours pour badge multi-séjour
                $resStayGroups = $res->rooms->groupBy(fn($r) => ($r->check_in?->format('Y-m-d') ?? 'x') . '_' .
                    ($r->check_out?->format('Y-m-d') ?? 'x'));
                $nbSejours = $resStayGroups->count();
            @endphp
            <tr class="hover:bg-amber-50/40 transition-colors group"> {{-- Référence + date création --}}
                <td class="px-5 py-4"> <div class="flex items-center gap-2"> <div class="w-1 h-8 rounded-full {{ $statusBar[$res->status] ?? 'bg-gray-300' }} shrink-0"></div> <div> <a href="{{ route('agency.portal.show-reservation', $res) }}"
                               class="font-mono text-sm font-bold text-gray-900 hover:text-amber-600 transition-colors"> {{ $res->reference }}
                            </a> <p class="text-xs text-gray-400 mt-0.5">{{ $res->created_at->format('d/m/Y') }}</p> </div> </div> </td> {{-- Nb chambres --}}
                <td class="px-5 py-4 text-center"> <span class="inline-flex items-center gap-1 text-sm font-bold text-gray-800"> <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg> {{ $nbChambres }}
                    </span> <p class="text-xs text-gray-400 mt-0.5">chambre{{ $nbChambres > 1 ? 's' : '' }}</p> </td> {{-- Prix total --}}
                <td class="px-5 py-4 text-right"> @if($totalPrice > 0)
                    <p class="text-sm font-extrabold text-gray-900">{{ number_format($totalPrice, 2, ',', ' ') }} <span class="text-xs font-normal text-gray-400">MAD</span></p> @if($hasPending)
                    <p class="text-xs text-amber-600 mt-0.5"> {{ number_format($pendingPay, 0, ',', ' ') }} en attente</p> @endif
                    @else
                    <span class="text-gray-300"></span> @endif
                </td> {{-- Barre paiement --}}
                <td class="px-5 py-4 w-32"> @if($totalPrice > 0)
                    <div class="flex items-center gap-2"> <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden"> <div class="h-2 rounded-full {{ $pct >= 100 ? 'bg-emerald-500' : 'bg-amber-400' }}" style="width:{{ $pct }}%"></div> </div> <span class="text-xs font-bold {{ $pct >= 100 ? 'text-emerald-600' : 'text-gray-500' }} shrink-0">{{ $pct }}%</span> </div> <p class="text-xs text-gray-400 mt-0.5 text-center"> @if($pct >= 100)  Soldé
                        @elseif($remaining > 0) Reste {{ number_format($remaining, 0, ',', ' ') }} MAD
                        @endif
                    </p> @else
                    <span class="text-gray-300 text-xs"></span> @endif
                </td> {{-- Statut --}}
                <td class="px-5 py-4 text-center"> <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $statusColors[$res->status] ?? 'bg-gray-100 text-gray-600' }}"> {{ $res->status_label }}
                    </span> </td> {{-- Actions --}}
                <td class="px-5 py-4"> <div class="flex items-center gap-1.5 justify-end flex-wrap">
                        <a href="{{ route('agency.portal.show-reservation', $res) }}"
                           class="text-xs font-semibold bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 px-2.5 py-1.5 rounded-lg inline-flex items-center gap-1 transition-colors"> <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg> Voir
                        </a>
                        @if($canEdit)
                        <a href="{{ route('agency.portal.edit-reservation', $res) }}"
                           class="text-xs font-semibold bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 px-2.5 py-1.5 rounded-lg inline-flex items-center gap-1 transition-colors"> <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.772-8.772z"/></svg> Modifier
                        </a>
                        @elseif($blockedBy7)
                        <span title="Modification impossible : arrivée dans {{ $_daysLeft }} jour{{ $_daysLeft > 1 ? 's' : '' }} (délai &lt; 7 j)"
                              class="text-xs font-semibold bg-orange-50 text-orange-500 border border-orange-200 px-2.5 py-1.5 rounded-lg inline-flex items-center gap-1 cursor-default">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Modif. verrouillée
                        </span>
                        @endif
                    </div> </td> </tr> @endforeach
            </tbody> </table> </div> @endif
    </div> {{--  Formulaire de demande  --}}
    @if($agency->status === 'approved')
    <div id="nouvelle-demande" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden"> {{-- En-tête --}}
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between"> <div class="flex items-center gap-2"> <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/> </svg> <h2 class="font-semibold text-gray-900 text-sm">Nouvelle demande</h2> </div> <button @click="open = !open" type="button"
                class="text-sm font-medium bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg transition-colors flex items-center gap-1.5"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/> </svg> <span x-text="open ? 'Fermer' : 'Nouvelle demande'"></span> </button> </div> <div x-show="open" x-transition class="px-5 py-5"> <form action="{{ route('agency.portal.store-reservation') }}" method="POST" class="space-y-5"> @csrf
                <input type="hidden" name="hotel_id"       :value="selectedHotelId"> <input type="hidden" name="check_in"       :value="overallCheckIn"> <input type="hidden" name="check_out"      :value="overallCheckOut"> <input type="hidden" name="total_persons"  :value="totalPersons"> {{-- Sélection de l'hôtel --}}
                <div> <label class="block text-xs font-medium text-gray-700 mb-1">Hôtel *</label> <select x-model="selectedHotelId" required @change="onHotelChange()"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none bg-white"> <option value=""> Choisir un hôtel </option> @foreach($hotels as $hotel)
                        <option value="{{ $hotel->id }}">{{ $hotel->name }}</option> @endforeach
                    </select> </div> {{--  SÉJOURS  --}}
                <template x-for="(stay, stayIdx) in stays" :key="stayIdx"> <div class="bg-gray-50 border border-gray-200 rounded-xl overflow-hidden"> {{-- En-tête séjour --}}
                        <div class="flex items-center justify-between px-4 py-2.5 bg-amber-50 border-b border-amber-100"> <span class="text-sm font-semibold text-amber-900"> Séjour <span x-text="stayIdx + 1"></span> <span x-show="nightsFor(stayIdx) > 0" class="font-normal text-amber-700"> <span x-text="nightsFor(stayIdx)"></span> nuit(s)
                                    · <span x-text="personsForStay(stayIdx)"></span> pers.
                                </span> </span> <button type="button" x-show="stays.length > 1" @click="removeStay(stayIdx)"
                                class="text-xs text-red-400 hover:text-red-600 flex items-center gap-1 px-2 py-1 rounded hover:bg-red-50"> <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg> Supprimer
                            </button> </div> <div class="p-4 space-y-3"> {{-- Dates --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3"> <div> <label class="block text-xs font-medium text-gray-600 mb-1">Date d'arrivée *</label> <input type="date" :name="`stays[${stayIdx}][check_in]`" required
                                        x-model="stay.check_in" min="{{ date('Y-m-d') }}"
                                        @change="calculatePriceForStay(stayIdx)"
                                        x-init="$nextTick(() => { if (!$el._flatpickr && window.initDatePickers) window.initDatePickers($el.parentElement); })"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none bg-white"> </div> <div> <label class="block text-xs font-medium text-gray-600 mb-1">Date de départ *</label> <input type="date" :name="`stays[${stayIdx}][check_out]`" required
                                        x-model="stay.check_out"
                                        :min="stay.check_in || '{{ date('Y-m-d', strtotime('+1 day')) }}'"
                                        @change="calculatePriceForStay(stayIdx)"
                                        x-init="$nextTick(() => { if (!$el._flatpickr && window.initDatePickers) window.initDatePickers($el.parentElement); })"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none bg-white"> </div> </div> {{-- En-têtes colonnes --}}
                            <div class="hidden sm:flex gap-2 text-xs font-medium text-gray-400 px-1 items-end"> <div class="flex-1">Chambre &amp; Occupation</div> <div class="w-14 text-center">Qté</div> <div class="w-20 text-center leading-tight">Adult.<br><span class="text-[10px] font-normal text-gray-300">12 ans+</span></div> <div class="w-20 text-center leading-tight">Enf.<br><span class="text-[10px] font-normal text-gray-300">2–11 ans</span></div> <div class="w-20 text-center leading-tight">Bébés<br><span class="text-[10px] font-normal text-gray-300">0–1 an</span></div> <div class="w-8"></div> </div> {{-- Lignes chambres --}}
                            <template x-for="(room, roomIdx) in stay.rooms" :key="roomIdx"> <div class="space-y-1.5 pb-2 border-b border-gray-100 last:border-0 last:pb-0"> <input type="hidden" :name="`stays[${stayIdx}][rooms][${roomIdx}][room_type_id]`" :value="room.room_type_id"> <input type="hidden" :name="`stays[${stayIdx}][rooms][${roomIdx}][occupancy_config_id]`" :value="room.occupancy_config_id || ''"> <div class="flex flex-wrap sm:flex-nowrap gap-2 items-center"> {{-- Combo chambre + occupation (options injectées via JS) --}}
                                        <div class="w-full sm:flex-1"> <select required
                                                :value="room.room_type_id && room.occupancy_config_id
                                                        ? room.room_type_id + '|' + room.occupancy_config_id
                                                        : ''"
                                                @change="selectRoomConfig(stayIdx, roomIdx, $event.target.value)"
                                                x-effect="populateRoomSelect($el, room)"
                                                class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none bg-white"
                                                :class="!room.occupancy_config_id ? 'border-amber-300' : 'border-gray-200'"> <option value=""> Chambre &amp; occupation </option> </select> </div> {{-- Quantité --}}
                                        <div class="w-14 shrink-0"> <input type="number" :name="`stays[${stayIdx}][rooms][${roomIdx}][quantity]`" required
                                                x-model.number="room.quantity" min="1"
                                                @change="recalculateAllStays()"
                                                class="w-full border border-gray-200 rounded-lg px-2 py-2 text-sm text-center focus:ring-2 focus:ring-amber-400 focus:outline-none"> </div> {{-- Adultes --}}
                                        <div class="w-20 shrink-0"> <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-amber-400"> <span class="px-1.5 text-gray-400 text-xs bg-gray-50 py-2 border-r border-gray-200 shrink-0">A</span> <input type="number" :name="`stays[${stayIdx}][rooms][${roomIdx}][adults]`"
                                                    x-model.number="room.adults"
                                                    :min="getConfigById(room.occupancy_config_id)?.min_adults ?? 0"
                                                    :max="getConfigById(room.occupancy_config_id)?.max_adults ?? 20"
                                                    @change="clampPersons(stayIdx, roomIdx); calculatePriceForStay(stayIdx)"
                                                    class="flex-1 px-1 py-2 text-sm text-center focus:outline-none w-0 min-w-0"> </div> </div> {{-- Enfants --}}
                                        <div class="w-20 shrink-0"> <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-amber-400"> <span class="px-1.5 text-gray-400 text-xs bg-gray-50 py-2 border-r border-gray-200 shrink-0">E</span> <input type="number" :name="`stays[${stayIdx}][rooms][${roomIdx}][children]`"
                                                    x-model.number="room.children"
                                                    :min="getConfigById(room.occupancy_config_id)?.min_children ?? 0"
                                                    :max="getConfigById(room.occupancy_config_id)?.max_children ?? 10"
                                                    @change="clampPersons(stayIdx, roomIdx); calculatePriceForStay(stayIdx)"
                                                    class="flex-1 px-1 py-2 text-sm text-center focus:outline-none w-0 min-w-0"> </div> </div> {{-- Bébés --}}
                                        <div class="w-20 shrink-0"> <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-amber-400"> <span class="px-1.5 text-gray-400 text-xs bg-gray-50 py-2 border-r border-gray-200 shrink-0">B</span> <input type="number" :name="`stays[${stayIdx}][rooms][${roomIdx}][babies]`"
                                                    x-model.number="room.babies" min="0"
                                                    :max="getConfigById(room.occupancy_config_id)?.max_babies ?? 5"
                                                    @change="clampPersons(stayIdx, roomIdx); calculatePriceForStay(stayIdx)"
                                                    class="flex-1 px-1 py-2 text-sm text-center focus:outline-none w-0 min-w-0"> </div> </div> {{-- Supprimer chambre --}}
                                        <div class="w-8 shrink-0 flex justify-end"> <button type="button" x-show="stay.rooms.length > 1"
                                                @click="removeRoom(stayIdx, roomIdx)"
                                                class="p-1.5 text-red-300 hover:text-red-500 hover:bg-red-50 rounded-lg"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> </button> </div> </div> {{-- Plage occupation + prix calculé --}}
                                    <div class="flex items-center justify-between pl-1 min-h-[1.25rem]"> <template x-if="getConfigById(room.occupancy_config_id)"> <p class="text-xs text-gray-400"> Plage : <span x-text="getConfigById(room.occupancy_config_id)?.min_adults"></span><span x-text="getConfigById(room.occupancy_config_id)?.max_adults"></span> adulte(s)
                                            </p> </template> <template x-if="!getConfigById(room.occupancy_config_id)"><span></span></template> {{-- Prix calculé pour cette ligne chambre --}}
                                        <template x-if="getRoomLinePrice(stayIdx, room) !== null"> <span class="text-xs font-semibold text-amber-700 whitespace-nowrap"
                                                x-text="`${formatTaxe(getRoomLinePrice(stayIdx, room))} MAD`"></span> </template> </div> {{-- Lit bébé --}}
                                    <div x-show="portalCapacityFor(room.room_type_id).babyBed" x-transition> <label class="inline-flex items-center gap-2 cursor-pointer p-2 bg-blue-50 border border-blue-100 rounded-lg"> <input type="checkbox" :name="`stays[${stayIdx}][rooms][${roomIdx}][baby_bed]`"
                                                value="1" x-model="room.baby_bed"
                                                class="rounded border-gray-300 text-amber-500"> <span class="text-xs text-gray-700 font-medium"> Demander un lit bébé</span> </label> </div> {{-- Avertissement capacité --}}
                                    <div x-show="hasCapacityErrorFor(stayIdx, roomIdx)" x-transition> <div class="flex items-start gap-2 rounded-lg px-3 py-2 text-xs border bg-red-50 border-red-200 text-red-700"> <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/> </svg> <span>Capacité dépassée  max <strong x-text="portalCapacityFor(room.room_type_id).max"></strong> pers. par chambre.</span> </div> </div> </div> </template> {{-- Ajouter chambre --}}
                            <button type="button" @click="addRoom(stayIdx)"
                                class="text-xs text-amber-600 hover:text-amber-700 font-medium flex items-center gap-1 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 rounded-lg"> <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Ajouter un type de chambre
                            </button> {{-- Prix estimé pour ce séjour --}}
                            <template x-if="priceResults[stayIdx]"> <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mt-2"> <template x-for="line in priceResults[stayIdx].breakdown"> <div class="flex justify-between text-xs py-0.5 text-amber-800"> <span> <span x-text="`${line.quantity} × ${line.occupancy_label || line.room_type_name} × ${priceResults[stayIdx].nights} nuits`"></span> <template x-if="line.unit_price_raw && priceResults[stayIdx].nights > 0">
                                        <span class="ml-1">
                                            <span
                                                :class="promoForStay(stayIdx) ? 'text-gray-400 line-through text-xs' : 'text-amber-500'"
                                                x-text="promoForStay(stayIdx)
                                                    ? formatTaxe(line.unit_price_raw / priceResults[stayIdx].nights) + ' MAD'
                                                    : '(' + formatTaxe(line.unit_price_raw / priceResults[stayIdx].nights) + ' MAD / chambre par nuit)'">
                                            </span>
                                            <span
                                                x-show="promoForStay(stayIdx) !== null"
                                                class="text-emerald-600 font-semibold ml-1"
                                                x-text="promoForStay(stayIdx)
                                                    ? '→ ' + formatTaxe(line.unit_price_raw / priceResults[stayIdx].nights * (1 - promoForStay(stayIdx).rate / 100)) + ' MAD / chambre par nuit'
                                                    : ''">
                                            </span>
                                        </span>
                                    </template> </span> <span class="font-semibold" x-text="promoForStay(stayIdx) ? `${formatTaxe(line.line_total * (1 - promoForStay(stayIdx).rate / 100))} MAD` : `${formatTaxe(line.line_total)} MAD`"></span> </div> </template> <template x-if="priceResults[stayIdx].taxe_sejour_total > 0"> <div class="flex justify-between text-xs py-0.5 text-blue-700"> <span x-text="` Taxe de séjour (${priceResults[stayIdx].taxe_sejour_adults} adulte(s) × ${priceResults[stayIdx].nights} nuit(s) × ${formatTaxe(priceResults[stayIdx].taxe_sejour_rate)} DHS)`"></span> <span class="font-semibold" x-text="`${formatTaxe(priceResults[stayIdx].taxe_sejour_total)} MAD`"></span> </div> </template> <template x-if="promoForStay(stayIdx)"> <div class="flex items-center gap-1.5 mt-1 px-2 py-1.5 bg-emerald-50 border border-emerald-100 rounded-lg"> <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> <span class="text-xs text-emerald-700 font-medium" x-text="`Réduction long séjour de ${promoForStay(stayIdx).rate}% déjà appliquée sur le prix par chambre par nuit.`"></span> </div> </template> <div class="flex justify-between text-sm font-bold mt-2 pt-2 border-t border-amber-200 text-amber-900"> <span>Sous-total séjour <span x-text="stayIdx + 1"></span></span> <span x-text="`${formatTaxe(priceResults[stayIdx].total * (1 - (promoForStay(stayIdx)?.rate || 0) / 100) + priceResults[stayIdx].taxe_sejour_total)} MAD`"></span> </div> </div> </template> </div> </div> </template> {{-- Ajouter un séjour --}}
                <div class="flex justify-center"> <button type="button" @click="addStay()"
                        class="inline-flex items-center gap-2 text-sm font-semibold text-amber-600 hover:text-amber-700 bg-white border-2 border-dashed border-amber-300 hover:border-amber-400 px-6 py-3 rounded-xl w-full justify-center"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Ajouter un autre séjour (autre date)
                    </button> </div> {{-- Grand total --}}
                <div x-show="grandTotal > 0" class="bg-amber-50 border border-amber-200 rounded-xl p-4 space-y-2"> <div class="flex items-center justify-between flex-wrap gap-1"> <p class="text-sm font-semibold text-amber-900">RÉCAPITULATIF ESTIMÉ</p> <p class="text-xs text-amber-600"> <span x-text="totalRoomsCount"></span> chb. · <span x-text="stays.length"></span> séjour(s) · <span x-text="totalPersons"></span> pers. (<span x-text="totalAdults"></span> adulte<span x-show="totalAdults !== 1">s</span><template x-if="totalChildren > 0"><span> · <span x-text="totalChildren"></span> enfant<span x-show="totalChildren !== 1">s</span></span></template>) </p> </div> <div class="space-y-1 pt-2 border-t border-amber-200"> <div class="flex justify-between text-sm text-amber-800"> <span>Hébergement <template x-if="promoInfo"><span class="text-xs text-emerald-600 font-medium ml-1">(promo incluse)</span></template></span> <span class="font-medium" x-text="`${formatTaxe(grandTotal - (promoInfo ? promoInfo.discount : 0))} MAD`"></span> </div> <template x-if="taxeTotalGlobal > 0"> <div class="flex justify-between text-sm text-blue-700"> <span> Taxe de séjour</span> <span class="font-medium" x-text="`${formatTaxe(taxeTotalGlobal)} MAD`"></span> </div> </template> </div> {{-- Suppléments obligatoires --}}
                    <template x-if="mandatorySupplements.length > 0"> <div class="pt-2 border-t border-amber-200"> <p class="text-xs font-semibold text-orange-600 mb-2"> Suppléments inclus (obligatoires) :</p> <template x-for="sup in mandatorySupplements" :key="sup.id"> <div class="flex items-start justify-between gap-3 py-1.5 px-2 rounded-lg bg-orange-50 border border-orange-100 mb-1"> <div> <span class="text-sm font-medium text-orange-800"> <span x-text="sup.title"></span></span> <div class="text-xs text-orange-400 mt-0.5 space-x-2"> <template x-if="sup.adults > 0 && sup.price_adult > 0"> <span x-text="`${sup.adults} adulte(s) × ${formatPrice(sup.price_adult)} MAD`"></span> </template> <template x-if="sup.children > 0 && sup.price_child > 0"> <span x-text="`· ${sup.children} enfant(s) × ${formatPrice(sup.price_child)} MAD`"></span> </template> <template x-if="sup.babies > 0 && sup.price_baby > 0"> <span x-text="`· ${sup.babies} bébé(s) × ${formatPrice(sup.price_baby)} MAD`"></span> </template> </div> </div> <span class="text-sm font-semibold text-orange-700 shrink-0 whitespace-nowrap" x-text="`${formatPrice(sup.total)} MAD`"></span> </div> </template> </div> </template> {{-- Suppléments optionnels --}}
                    <template x-if="optionalSupplements.length > 0"> <div class="pt-2 border-t border-amber-200"> <p class="text-xs font-semibold text-gray-700 mb-2">Suppléments optionnels :</p> <template x-for="sup in optionalSupplements" :key="sup.id"> <label class="flex items-center justify-between gap-3 cursor-pointer py-1.5 hover:bg-amber-50 rounded-lg px-1 -mx-1"> <span class="flex items-center gap-2"> <input type="checkbox" :value="sup.id" :name="`selected_supplements[]`"
                                            x-model="selectedOptionalSupplements"
                                            class="rounded border-gray-300 text-amber-500 shrink-0"> <span class="text-sm text-gray-700"> <span x-text="sup.title"></span></span> </span> <span class="text-sm font-semibold text-gray-800" x-text="`${formatPrice(sup.total)} MAD`"></span> </label> </template> </div> </template> <div class="flex items-center justify-between pt-3 border-t border-amber-300"> <p class="text-base font-bold text-amber-900">TOTAL ESTIMÉ</p> <p class="text-2xl font-bold text-amber-700" x-text="`${formatTaxe(grandTotalWithExtras)} MAD`"></p> </div> <p class="text-xs text-amber-500">* Prix indicatif, confirmé après validation.</p> </div> {{-- Options flexibles --}}
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4"> <h3 class="text-xs font-semibold text-gray-700 mb-3">Options de flexibilité</h3> <div class="space-y-2"> <label class="flex items-start gap-3 cursor-pointer"> <input type="checkbox" name="flexible_dates" value="1"
                                class="mt-0.5 rounded border-gray-300 text-amber-500 focus:ring-amber-400"> <div> <p class="text-sm font-medium text-gray-800"> Dates flexibles</p> <p class="text-xs text-gray-500 mt-0.5">Accepter des dates alternatives si les dates demandées ne sont pas disponibles.</p> </div> </label> <label class="flex items-start gap-3 cursor-pointer"> <input type="checkbox" name="flexible_hotel" value="1"
                                class="mt-0.5 rounded border-gray-300 text-amber-500 focus:ring-amber-400"> <div> <p class="text-sm font-medium text-gray-800"> Hôtel flexible</p> <p class="text-xs text-gray-500 mt-0.5">Accepter un hôtel de niveau équivalent si cet hôtel n'est pas disponible.</p> </div> </label> </div> </div> {{-- Erreur capacité --}}
                <div x-show="hasCapacityError" x-transition
                    class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex items-center gap-2 text-sm text-red-700"> <svg class="w-5 h-5 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/> </svg> <span>Veuillez corriger les erreurs de capacité avant de soumettre.</span> </div> {{-- Personne responsable --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Nom du responsable <span class="text-red-500">*</span></label>
                        <input type="text" name="contact_name" required maxlength="100"
                               value="{{ old('contact_name', $agency->contact_name) }}"
                               placeholder="Nom et prénom"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Téléphone du responsable</label>
                        <input type="text" name="phone" maxlength="30"
                               value="{{ old('phone', $agency->phone) }}"
                               placeholder="+212 6XX XXX XXX"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                    </div>
                </div>
                {{-- Demandes spéciales --}}
                <div> <label class="block text-xs font-medium text-gray-700 mb-1">Demandes spéciales</label> <textarea name="special_requests" rows="2"
                        placeholder="Transfert aéroport, régime alimentaire, chambres communicantes..."
                        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">{{ old('special_requests') }}</textarea> </div> {{-- Alerte minimum 11 chambres --}}
                <div x-show="minRoomsBlocked" x-transition
                     class="bg-red-50 border border-red-200 rounded-xl px-5 py-4 flex items-start gap-3"> <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/> </svg> <div> <p class="text-sm font-bold text-red-800">Demande non disponible via ce portail</p> <p class="text-sm text-red-700 mt-1"> Les demandes de moins de 11 chambres doivent être effectuées directement via notre site web.
                        </p> <a href="{{ $agency->hotel?->website ?? 'https://magic-emails.eureka-digital.ma' }}"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 mt-2 text-sm font-semibold text-red-700 underline hover:text-red-900"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg> Réserver sur notre site web 
                        </a> </div> </div> {{-- Soumettre --}}
                <div class="flex justify-end"> <button type="submit"
                        :disabled="hasCapacityError || minRoomsBlocked"
                        :class="(hasCapacityError || minRoomsBlocked)
                            ? 'inline-flex items-center gap-2 bg-gray-300 text-gray-500 cursor-not-allowed font-semibold px-8 py-3 rounded-xl text-sm'
                            : 'inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold px-8 py-3 rounded-xl text-sm transition shadow-sm hover:shadow'"> <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg> Soumettre ma demande
                    </button> </div> </form> </div> </div> @endif

    <div class="text-center text-xs text-gray-400 pb-4"> © {{ date('Y') }} Magic Hotels  <a href="mailto:{{ config('magic.contact_email') }}" class="text-amber-600 hover:underline">{{ config('magic.contact_email') }}</a> </div> </main> <script>
// Données par hôtel (injectées côté serveur)
const roomTypeCapacityByHotel = @json($roomTypeCapacityByHotel);
const roomTypeConfigsByHotelData = @json($roomTypeConfigsByHotel);

// Données promo long séjour par hôtel
@php
$_promoByHotel = [];
foreach ($hotels as $_h) {
    $_promoByHotel[$_h->id] = [
        'enabled'      => (bool)  $_h->promo_long_stay_enabled,
        'tier1_nights' => (int)   ($_h->promo_tier1_nights ?? 0),
        'tier1_rate'   => (float) ($_h->promo_tier1_rate   ?? 0),
        'tier2_nights' => (int)   ($_h->promo_tier2_nights ?? 0),
        'tier2_rate'   => (float) ($_h->promo_tier2_rate   ?? 0),
    ];
}
@endphp
const promoByHotel = @json($_promoByHotel);

// Slug du statut de l'agence (pour la règle des 11 chambres min)
const agencyStatusSlug = '{{ $agency->agencyStatus?->slug ?? '' }}';

function portalReservationForm() {
    return {
        open: false,
        selectedHotelId: '',
        stays: [{
            check_in: '',
            check_out: '',
            rooms: [{ room_type_id: '', quantity: 1, adults: 1, children: 0, babies: 0, baby_bed: false, occupancy_config_id: null }]
        }],
        priceResults: [null],
        selectedOptionalSupplements: [],

        onHotelChange() {
            // Reset chambres et prix quand l'hôtel change
            this.stays = [{
                check_in: '',
                check_out: '',
                rooms: [{ room_type_id: '', quantity: 1, adults: 1, children: 0, babies: 0, baby_bed: false, occupancy_config_id: null }]
            }];
            this.priceResults = [null];
        },

        // Peuple dynamiquement les <option> d'un <select> de chambre selon l'hôtel sélectionné
        populateRoomSelect(selectEl, room) {
            const hid = this.selectedHotelId;
            // roomTypeConfigsByHotelData[hid] est un tableau ordonné :
            // [ { room_type_id: X, configs: [...] }, ... ]
            const roomTypeList = roomTypeConfigsByHotelData[hid] || [];
            const currentVal = room.room_type_id && room.occupancy_config_id
                ? String(room.room_type_id) + '|' + String(room.occupancy_config_id) : '';

            // Tout réinitialiser via innerHTML pour éviter les optgroups fantômes
            selectEl.innerHTML = '<option value="">\u2014 Chambre &amp; occupation \u2014</option>';

            // Reconstruire les optgroups dans l'ordre du tableau (= ordre created_at ASC)
            for (const rtData of roomTypeList) {
                const rtId   = rtData.room_type_id;
                const cfgList = rtData.configs;
                if (!cfgList || !cfgList.length) continue;
                const group = document.createElement('optgroup');
                group.label = cfgList[0]?.room_type_name || ('Chambre #' + rtId);
                for (const cfg of cfgList) {
                    const opt = document.createElement('option');
                    opt.value = rtId + '|' + (cfg.id ?? '');
                    opt.textContent = cfg.label;
                    if (opt.value === currentVal) opt.selected = true;
                    group.appendChild(opt);
                }
                selectEl.appendChild(group);
            }
        },

        get totalPersons() {
            const total = this.stays.reduce((sum, stay) => {
                return sum + stay.rooms.reduce((rs, r) => {
                    const qty = parseInt(r.quantity) || 1;
                    return rs + ((parseInt(r.adults)||0) + (parseInt(r.children)||0) + (parseInt(r.babies)||0)) * qty;
                }, 0);
            }, 0);
            return total || 1;
        },

        get totalAdults() {
            return this.stays.reduce((sum, stay) => {
                return sum + stay.rooms.reduce((rs, r) => {
                    return rs + (parseInt(r.adults) || 0) * (parseInt(r.quantity) || 1);
                }, 0);
            }, 0);
        },

        get totalChildren() {
            return this.stays.reduce((sum, stay) => {
                return sum + stay.rooms.reduce((rs, r) => {
                    return rs + ((parseInt(r.children) || 0) + (parseInt(r.babies) || 0)) * (parseInt(r.quantity) || 1);
                }, 0);
            }, 0);
        },

        get grandTotal() {
            return this.priceResults.reduce((sum, r) => sum + (r?.total || 0), 0);
        },

        // Règle : si pas agence-de-voyages ET total chambres < 11  bloquer
        get totalRoomsCount() {
            return this.stays.reduce((sum, s) => sum + s.rooms.filter(r => r.room_type_id && r.quantity > 0)
                             .reduce((rs, r) => rs + (parseInt(r.quantity) || 1), 0), 0);
        },

        get minRoomsBlocked() {
            if (agencyStatusSlug === 'agence-de-voyages') return false;
            return this.totalRoomsCount > 0 && this.totalRoomsCount < 11;
        },

        promoForStay(stayIdx) {
            if (!this.promoInfo) return null;
            return this.promoInfo.details.find(d => d.idx === stayIdx + 1) || null;
        },

        get taxeTotalGlobal() {
            return this.priceResults.reduce((sum, r) => sum + (r?.taxe_sejour_total || 0), 0);
        },

        get allApplicableSupplements() {
            const merged = new Map();
            for (const r of this.priceResults.filter(Boolean)) {
                for (const s of (r.supplements || [])) {
                    if (!merged.has(s.id)) {
                        merged.set(s.id, { ...s });
                    } else {
                        const m = merged.get(s.id);
                        m.adults   = (m.adults   || 0) + (s.adults   || 0);
                        m.children = (m.children || 0) + (s.children || 0);
                        m.babies   = (m.babies   || 0) + (s.babies   || 0);
                        m.total = Math.round(m.adults * (m.price_adult || 0) + m.children * (m.price_child || 0) + m.babies * (m.price_baby || 0));
                    }
                }
            }
            return Array.from(merged.values());
        },

        get mandatorySupplements() {
            return this.allApplicableSupplements.filter(s => s.is_mandatory);
        },

        get optionalSupplements() {
            return this.allApplicableSupplements.filter(s => !s.is_mandatory);
        },

        get mandatorySupplementTotal() {
            return this.mandatorySupplements.reduce((sum, s) => sum + (s.total || 0), 0);
        },

        get selectedOptionalTotal() {
            return this.optionalSupplements
                .filter(s => this.selectedOptionalSupplements.some(sel => sel == s.id))
                .reduce((sum, s) => sum + (s.total || 0), 0);
        },

        get totalNights() {
            return this.stays.reduce((sum, s, idx) => sum + (this.nightsFor ? this.nightsFor(idx) : 0), 0);
        },

        get promoInfo() {
            const promo = promoByHotel[this.selectedHotelId];
            if (!promo || !promo.enabled) return null;

            let totalDiscount = 0;
            const stayDetails = [];

            for (let idx = 0; idx < this.stays.length; idx++) {
                const nights    = this.nightsFor(idx);
                const stayTotal = this.priceResults[idx]?.total || 0;
                if (!stayTotal || nights <= 0) continue;

                let rate = 0;
                if (promo.tier2_nights > 0 && nights >= promo.tier2_nights) {
                    rate = promo.tier2_rate;
                } else if (promo.tier1_nights > 0 && nights >= promo.tier1_nights) {
                    rate = promo.tier1_rate;
                }
                if (!rate) continue;

                const discount = Math.round(stayTotal * rate / 100 * 100) / 100;
                totalDiscount += discount;
                stayDetails.push({ idx: idx + 1, nights, rate, discount });
            }

            if (totalDiscount <= 0) return null;

            // Libellé : groupé si tous les taux sont identiques, sinon détaillé par séjour
            const rates = [...new Set(stayDetails.map(s => s.rate))];
            let label;
            if (rates.length === 1) {
                const totalNights = stayDetails.reduce((s, d) => s + d.nights, 0);
                label = `Promo long séjour (${rates[0]}% sur ${totalNights} nuits)`;
            } else {
                label = 'Promo long séjour (' + stayDetails.map(s => `Séjour ${s.idx} : ${s.rate}% sur ${s.nights} nuits`).join(' · ') + ')';
            }

            return { discount: Math.round(totalDiscount * 100) / 100, label, details: stayDetails };
        },

        get grandTotalWithExtras() {
            const base = this.grandTotal + this.taxeTotalGlobal + this.mandatorySupplementTotal + this.selectedOptionalTotal;
            const promoDiscount = this.promoInfo ? this.promoInfo.discount : 0;
            return Math.max(0, base - promoDiscount);
        },

        get overallCheckIn() {
            const dates = this.stays.map(s => s.check_in).filter(Boolean);
            return dates.length ? dates.reduce((a, b) => a < b ? a : b) : '';
        },

        get overallCheckOut() {
            const dates = this.stays.map(s => s.check_out).filter(Boolean);
            return dates.length ? dates.reduce((a, b) => a > b ? a : b) : '';
        },

        get hasCapacityError() {
            return this.stays.some((stay, si) => stay.rooms.some((room, ri) => this.hasCapacityErrorFor(si, ri))
            );
        },

        nightsFor(stayIdx) {
            const s = this.stays[stayIdx];
            if (!s.check_in || !s.check_out) return 0;
            return Math.max(0, Math.round((new Date(s.check_out) - new Date(s.check_in)) / 86400000));
        },

        personsForStay(stayIdx) {
            return this.stays[stayIdx].rooms.reduce((sum, r) => {
                const qty = parseInt(r.quantity) || 1;
                return sum + ((parseInt(r.adults)||0) + (parseInt(r.children)||0) + (parseInt(r.babies)||0)) * qty;
            }, 0);
        },

        portalCapacityFor(roomTypeId) {
            if (!this.selectedHotelId || !roomTypeCapacityByHotel[this.selectedHotelId]) return { min: 1, max: 999 };
            return roomTypeCapacityByHotel[this.selectedHotelId][roomTypeId] || { min: 1, max: 999 };
        },

        // Retourne le prix calculé (line_total) pour une ligne chambre donnée
        getRoomLinePrice(stayIdx, room) {
            const result = this.priceResults[stayIdx];
            if (!result || !room.room_type_id) return null;
            const line = (result.breakdown || []).find(l => l.room_type_id == room.room_type_id &&
                (room.occupancy_config_id ? l.occupancy_config_id == room.occupancy_config_id : true)
            );
            return line ? line.line_total : null;
        },

        getConfigById(cfgId) {
            if (!cfgId || !this.selectedHotelId) return null;
            const roomTypeList = roomTypeConfigsByHotelData[this.selectedHotelId] || [];
            const id = parseInt(cfgId);
            for (const rtData of roomTypeList) {
                const found = (rtData.configs || []).find(c => c.id === id);
                if (found) return found;
            }
            return null;
        },

        selectRoomConfig(stayIdx, roomIdx, value) {
            const room = this.stays[stayIdx]?.rooms[roomIdx];
            if (!room) return;
            if (!value || !value.includes('|')) {
                room.room_type_id = '';
                room.occupancy_config_id = null;
                this.calculatePriceForStay(stayIdx);
                return;
            }
            const parts = value.split('|');
            room.room_type_id        = parseInt(parts[0]);
            room.occupancy_config_id = parts[1] ? parseInt(parts[1]) : null;
            if (room.occupancy_config_id) {
                const cfg = this.getConfigById(room.occupancy_config_id);
                if (cfg) {
                    room.adults   = Math.max(1, cfg.min_adults || 0);
                    room.children = cfg.min_children || 0;
                    room.babies   = 0;
                }
            }
            this.calculatePriceForStay(stayIdx);
        },

        clampPersons(stayIdx, roomIdx) {
            const room = this.stays[stayIdx]?.rooms[roomIdx];
            if (!room) return;
            const cfg = this.getConfigById(room.occupancy_config_id);
            if (!cfg) return;
            const clamp = (v, min, max) => Math.min(Math.max(parseInt(v) || 0, min), max);
            room.adults   = clamp(room.adults,   cfg.min_adults   ?? 0, cfg.max_adults   ?? 99);
            room.children = clamp(room.children, cfg.min_children ?? 0, cfg.max_children ?? 99);
            room.babies   = clamp(room.babies,   0,                     cfg.max_babies   ?? 99);
        },

        isOverCapacity(stayIdx, roomIdx) {
            const room = this.stays[stayIdx]?.rooms[roomIdx];
            if (!room || !room.room_type_id) return false;
            const cap = this.portalCapacityFor(room.room_type_id);
            const persons = (parseInt(room.adults)||0) + (parseInt(room.children)||0) + (parseInt(room.babies)||0);
            return persons > cap.max;
        },

        hasCapacityErrorFor(stayIdx, roomIdx) {
            return this.isOverCapacity(stayIdx, roomIdx);
        },

        addStay() {
            this.stays.push({ check_in: '', check_out: '',
                rooms: [{ room_type_id: '', quantity: 1, adults: 1, children: 0, babies: 0, baby_bed: false, occupancy_config_id: null }]
            });
            this.priceResults.push(null);
        },

        removeStay(idx) {
            this.stays.splice(idx, 1);
            this.priceResults.splice(idx, 1);
            this.$nextTick(() => this.recalculateAllStays());
        },

        addRoom(stayIdx) {
            this.stays[stayIdx].rooms.push({ room_type_id: '', quantity: 1, adults: 1, children: 0, babies: 0, baby_bed: false, occupancy_config_id: null });
        },

        removeRoom(stayIdx, roomIdx) {
            this.stays[stayIdx].rooms.splice(roomIdx, 1);
            this.recalculateAllStays();
        },

        async recalculateAllStays() {
            await Promise.all(this.stays.map((_, idx) => this.calculatePriceForStay(idx)));
        },

        async calculatePriceForStay(stayIdx) {
            const stay = this.stays[stayIdx];
            if (!this.selectedHotelId || !stay.check_in || !stay.check_out || this.nightsFor(stayIdx) <= 0) {
                this.priceResults[stayIdx] = null;
                this.priceResults = [...this.priceResults];
                return;
            }
            const validRooms = stay.rooms.filter(r => r.room_type_id && r.quantity > 0);
            if (!validRooms.length) return;

            const totalRooms = this.stays.reduce((sum, s) => sum + s.rooms.filter(r => r.room_type_id && r.quantity > 0)
                             .reduce((s2, r) => s2 + (parseInt(r.quantity) || 1), 0), 0);

            try {
                const resp = await fetch('{{ route('client.calculate-price') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        hotel_id:    this.selectedHotelId,
                        check_in:    stay.check_in,
                        check_out:   stay.check_out,
                        rooms:       validRooms.map(r => ({
                            room_type_id:        r.room_type_id,
                            quantity:            r.quantity,
                            adults:              r.adults   || 0,
                            children:            r.children || 0,
                            babies:              r.babies   || 0,
                            occupancy_config_id: r.occupancy_config_id || null,
                        })),
                        total_rooms: totalRooms,
                    })
                });
                const data = await resp.json();
                if (data.success) {
                    this.priceResults[stayIdx] = data;
                    this.priceResults = [...this.priceResults];
                }
            } catch(e) { console.error('[calculatePriceForStay]', e); }
        },

        // Remplace l'espace fine insécable (U+202F) par une espace normale
        _fmt(str) { return str.replace(/[\u202F\u00A0]/g, ' '); },

        formatPrice(n) {
            return this._fmt(new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Math.round(n)));
        },

        // Valeur exacte sans arrondi (pour la taxe de séjour et prix chambre)
        formatTaxe(n) {
            return this._fmt(new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n));
        },
    };
}
</script>
</body>
</html>
