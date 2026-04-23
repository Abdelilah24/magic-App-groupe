<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ProformaInvoiceMail;
use App\Mail\ReservationQuoteMail;
use App\Services\ProformaService;
use App\Models\Hotel;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\RefusalReason;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\ReservationSupplement;
use App\Models\RoomType;
use App\Models\StatusHistory;
use App\Services\PricingService;
use App\Services\NotificationService;
use App\Services\ReservationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationService  $reservationService,
        private readonly PricingService      $pricingService,
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request)
    {
        $query = Reservation::with(['hotel', 'rooms.roomType', 'rooms.occupancyConfig'])
            ->where('status', '!=', Reservation::STATUS_DRAFT)
            ->orderByDesc('created_at');

        // Filtre statut
        if ($status = $request->query('status')) {
            $query->byStatus($status);
        }

        // Recherche : référence, agence, email, téléphone
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference',   'like', "%{$search}%")
                  ->orWhere('agency_name', 'like', "%{$search}%")
                  ->orWhere('email',        'like', "%{$search}%")
                  ->orWhere('phone',        'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%");
            });
        }

        // Filtre hôtel
        if ($hotelId = $request->query('hotel_id')) {
            $query->where('hotel_id', $hotelId);
        }

        // Filtre code tarifaire
        if ($tariffCode = $request->query('tariff_code')) {
            $query->where('tariff_code', $tariffCode);
        }

        // Filtre date d'arrivée (check_in)
        if ($checkInFrom = $request->query('check_in_from')) {
            $query->whereDate('check_in', '>=', $checkInFrom);
        }
        if ($checkInTo = $request->query('check_in_to')) {
            $query->whereDate('check_in', '<=', $checkInTo);
        }

        // Filtre date de création
        if ($createdFrom = $request->query('created_from')) {
            $query->whereDate('created_at', '>=', $createdFrom);
        }
        if ($createdTo = $request->query('created_to')) {
            $query->whereDate('created_at', '<=', $createdTo);
        }

        // Tri
        $sort = $request->query('sort', 'created_at');
        $dir  = $request->query('dir', 'desc');
        $allowedSorts = ['created_at', 'check_in', 'total_price', 'reference'];
        if (in_array($sort, $allowedSorts)) {
            $query->reorder($sort, $dir === 'asc' ? 'asc' : 'desc');
        }

        $reservations = $query->paginate(20)->withQueryString();

        $statusCounts = collect(Reservation::STATUSES)
            ->filter(fn ($s) => $s !== Reservation::STATUS_DRAFT)
            ->mapWithKeys(fn ($s) => [$s => Reservation::byStatus($s)->count()]);

        $hotels      = Hotel::orderBy('name')->get(['id', 'name']);
        $tariffCodes = Reservation::distinct()->pluck('tariff_code')->filter()->sort()->values();
        $hasFilters  = $request->hasAny(['search', 'hotel_id', 'tariff_code', 'check_in_from', 'check_in_to', 'created_from', 'created_to']);

        // Requête AJAX : retourner uniquement le partial du tableau
        if ($request->ajax()) {
            return view('admin.reservations.partials.table', compact(
                'reservations', 'hasFilters', 'sort', 'dir'
            ))->render();
        }

        return view('admin.reservations.index', compact(
            'reservations', 'statusCounts', 'hotels', 'tariffCodes', 'hasFilters', 'sort', 'dir'
        ));
    }

    public function agenda(Request $request)
    {
        $month   = $request->query('month', now()->format('Y-m'));
        $hotelId = $request->query('hotel_id');

        try {
            $startOfMonth = \Carbon\Carbon::parse($month)->startOfMonth();
            $endOfMonth   = \Carbon\Carbon::parse($month)->endOfMonth();
        } catch (\Exception $e) {
            $startOfMonth = now()->startOfMonth();
            $endOfMonth   = now()->endOfMonth();
            $month        = now()->format('Y-m');
        }

        // Statuts sélectionnés — défaut : paid + confirmed + partially_paid
        $defaultStatuses = [
            Reservation::STATUS_PAID,
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_PARTIALLY_PAID,
        ];
        $selectedStatuses = $request->query('statuses')
            ? array_filter((array) $request->query('statuses'))
            : $defaultStatuses;

        // Chercher les réservations qui ont au moins un séjour (room) avec check_in dans le mois
        // OU dont le check_in global est dans le mois (rétrocompat.)
        $query = Reservation::with(['hotel', 'rooms'])
            ->whereIn('status', $selectedStatuses)
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('check_in', [$startOfMonth, $endOfMonth])
                  ->orWhereHas('rooms', fn ($rq) =>
                        $rq->whereBetween('check_in', [$startOfMonth, $endOfMonth])
                  );
            })
            ->orderBy('check_in');

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        $reservations = $query->get();

        // ── Grouper par date d'arrivée de chaque SÉJOUR ───────────────────────
        // Une réservation multi-séjour peut apparaître sur plusieurs dates.
        // $byDate : ['Y-m-d' => Collection of Reservation]
        $byDate = collect();

        foreach ($reservations as $reservation) {
            // Dates de début uniques des séjours (check_in des rooms) dans le mois
            $sejourDates = $reservation->rooms
                ->filter(fn ($r) => $r->check_in !== null
                    && $r->check_in->gte($startOfMonth)
                    && $r->check_in->lte($endOfMonth))
                ->map(fn ($r) => $r->check_in->format('Y-m-d'))
                ->unique()
                ->values();

            // Fallback : si aucune room n'a de check_in individuel, utiliser check_in global
            if ($sejourDates->isEmpty()) {
                $globalCi = $reservation->check_in;
                if ($globalCi->gte($startOfMonth) && $globalCi->lte($endOfMonth)) {
                    $sejourDates = collect([$globalCi->format('Y-m-d')]);
                }
            }

            foreach ($sejourDates as $dateKey) {
                if (! $byDate->has($dateKey)) {
                    $byDate->put($dateKey, collect());
                }
                $byDate->get($dateKey)->push($reservation);
            }
        }

        $hotels = Hotel::active()->orderBy('name')->get();

        // Comptage par statut (basé sur les séjours du mois)
        $statusCounts = collect(Reservation::STATUSES)
            ->diff([Reservation::STATUS_REFUSED, Reservation::STATUS_CANCELLED])
            ->mapWithKeys(fn ($s) => [
                $s => Reservation::whereIn('status', [$s])
                        ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                            $q->whereBetween('check_in', [$startOfMonth, $endOfMonth])
                              ->orWhereHas('rooms', fn ($rq) =>
                                    $rq->whereBetween('check_in', [$startOfMonth, $endOfMonth])
                              );
                        })
                        ->when($hotelId, fn ($q) => $q->where('hotel_id', $hotelId))
                        ->count()
            ]);

        // Stats : nombre total de séjours-arrivées dans le mois
        $totalArrivals = $byDate->sum(fn ($list) => $list->count());
        $todayKey      = now()->format('Y-m-d');
        $todayArrivals = $byDate->has($todayKey) ? $byDate->get($todayKey)->count() : 0;

        return view('admin.reservations.agenda', compact(
            'byDate', 'month', 'startOfMonth', 'endOfMonth',
            'hotels', 'hotelId', 'totalArrivals', 'todayArrivals',
            'selectedStatuses', 'statusCounts'
        ));
    }

    /**
     * Agenda des départs — groupé par date de check_out de chaque séjour.
     */
    public function agendaDepart(Request $request)
    {
        $month   = $request->query('month', now()->format('Y-m'));
        $hotelId = $request->query('hotel_id');

        try {
            $startOfMonth = \Carbon\Carbon::parse($month)->startOfMonth();
            $endOfMonth   = \Carbon\Carbon::parse($month)->endOfMonth();
        } catch (\Exception $e) {
            $startOfMonth = now()->startOfMonth();
            $endOfMonth   = now()->endOfMonth();
            $month        = now()->format('Y-m');
        }

        $defaultStatuses = [
            Reservation::STATUS_PAID,
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_PARTIALLY_PAID,
        ];
        $selectedStatuses = $request->query('statuses')
            ? array_filter((array) $request->query('statuses'))
            : $defaultStatuses;

        // Chercher les réservations dont au moins un séjour part dans le mois
        $query = Reservation::with(['hotel', 'rooms'])
            ->whereIn('status', $selectedStatuses)
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('check_out', [$startOfMonth, $endOfMonth])
                  ->orWhereHas('rooms', fn ($rq) =>
                        $rq->whereBetween('check_out', [$startOfMonth, $endOfMonth])
                  );
            })
            ->orderBy('check_out');

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        $reservations = $query->get();

        // ── Grouper par date de départ de chaque SÉJOUR ──────────────────────
        $byDate = collect();

        foreach ($reservations as $reservation) {
            $sejourDates = $reservation->rooms
                ->filter(fn ($r) => $r->check_out !== null
                    && $r->check_out->gte($startOfMonth)
                    && $r->check_out->lte($endOfMonth))
                ->map(fn ($r) => $r->check_out->format('Y-m-d'))
                ->unique()
                ->values();

            // Fallback : check_out global
            if ($sejourDates->isEmpty()) {
                $globalCo = $reservation->check_out;
                if ($globalCo->gte($startOfMonth) && $globalCo->lte($endOfMonth)) {
                    $sejourDates = collect([$globalCo->format('Y-m-d')]);
                }
            }

            foreach ($sejourDates as $dateKey) {
                if (! $byDate->has($dateKey)) {
                    $byDate->put($dateKey, collect());
                }
                $byDate->get($dateKey)->push($reservation);
            }
        }

        $hotels = Hotel::active()->orderBy('name')->get();

        $statusCounts = collect(Reservation::STATUSES)
            ->diff([Reservation::STATUS_REFUSED, Reservation::STATUS_CANCELLED])
            ->mapWithKeys(fn ($s) => [
                $s => Reservation::whereIn('status', [$s])
                        ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                            $q->whereBetween('check_out', [$startOfMonth, $endOfMonth])
                              ->orWhereHas('rooms', fn ($rq) =>
                                    $rq->whereBetween('check_out', [$startOfMonth, $endOfMonth])
                              );
                        })
                        ->when($hotelId, fn ($q) => $q->where('hotel_id', $hotelId))
                        ->count()
            ]);

        $totalDeparts  = $byDate->sum(fn ($list) => $list->count());
        $todayKey      = now()->format('Y-m-d');
        $todayDeparts  = $byDate->has($todayKey) ? $byDate->get($todayKey)->count() : 0;

        return view('admin.reservations.agenda_depart', compact(
            'byDate', 'month', 'startOfMonth', 'endOfMonth',
            'hotels', 'hotelId', 'totalDeparts', 'todayDeparts',
            'selectedStatuses', 'statusCounts'
        ));
    }

    public function markUnread(Reservation $reservation): \Illuminate\Http\RedirectResponse
    {
        $reservation->updateQuietly(['is_read' => false]);

        return redirect()->route('admin.reservations.index')
            ->with('success', 'Réservation marquée comme non lue.');
    }

    public function show(Reservation $reservation)
    {
        // Marquer comme lu sans déclencher les événements du modèle
        if (! $reservation->is_read) {
            $reservation->updateQuietly(['is_read' => true]);
        }

        $reservation->load([
            'hotel', 'rooms.roomType', 'rooms.occupancyConfig', 'payments',
            'statusHistories', 'handler', 'secureLink',
            'paymentSchedules.payment',
            'supplements.supplement',
            'guestRegistrations',
            'logs',
            'extras',
        ]);

        // Pour les réservations en attente : recalculer la promo PAR SÉJOUR
        // (sans muter la DB — affichage cohérent avec le taux actuel et la nouvelle logique par séjour)
        $displayPromoAmount = null;
        $displayPromoRate   = null;
        $displayPromoDetails = [];
        if (in_array($reservation->status, [
            Reservation::STATUS_PENDING,
            Reservation::STATUS_MODIFICATION_PENDING,
        ]) && $reservation->hotel) {
            $hotel      = $reservation->hotel;
            $totalPromo = 0.0;

            // Grouper chambres par séjour
            $stayGroups = $reservation->rooms->groupBy(fn($r) =>
                ($r->check_in?->format('Y-m-d')  ?? $reservation->check_in->format('Y-m-d')) . '_' .
                ($r->check_out?->format('Y-m-d') ?? $reservation->check_out->format('Y-m-d'))
            );

            $stayIdx = 0;
            foreach ($stayGroups as $rooms) {
                $stayIdx++;
                $first    = $rooms->first();
                $checkIn  = $first->check_in  ?? $reservation->check_in;
                $checkOut = $first->check_out ?? $reservation->check_out;
                $nights   = (int) $checkIn->diffInDays($checkOut);
                $rate     = $hotel->getPromoRate($nights);
                if ($rate <= 0) continue;

                $stayTotal   = (float) $rooms->sum(fn($r) => $r->total_price ?? 0);
                $stayPromo   = round($stayTotal * $rate / 100, 2);
                $totalPromo += $stayPromo;
                $displayPromoDetails[] = [
                    'idx'     => $stayIdx,
                    'nights'  => $nights,
                    'rate'    => $rate,
                    'discount'=> $stayPromo,
                ];
            }

            if ($totalPromo > 0) {
                $displayPromoAmount = round($totalPromo, 2);
                // Taux effectif pour affichage condensé
                $roomsSum = (float) $reservation->rooms->sum(fn($r) => $r->total_price ?? 0);
                $displayPromoRate = $roomsSum > 0 ? round($totalPromo / $roomsSum * 100, 2) : null;
            }
        }

        $refusalReasons  = RefusalReason::active()->ordered()->get();
        $extraServices   = \App\Models\ExtraService::active()->orderBy('name')->get();

        return view('admin.reservations.show', compact('reservation', 'displayPromoAmount', 'displayPromoRate', 'displayPromoDetails', 'refusalReasons', 'extraServices'));
    }

    /**
     * Accepter une réservation.
     */
    public function accept(Request $request, Reservation $reservation)
    {
        $request->validate(['notes' => 'nullable|string|max:1000']);

        if (! in_array($reservation->status, [Reservation::STATUS_PENDING])) {
            return back()->with('error', 'Cette réservation ne peut pas être acceptée dans son état actuel.');
        }

        $this->reservationService->accept($reservation, $request->user(), $request->notes);

        return redirect()
            ->route('admin.reservations.show', $reservation)
            ->with('success', "Réservation {$reservation->reference} acceptée. Devis envoyé au client.");
    }

    /**
     * Refuser une réservation.
     */
    public function refuse(Request $request, Reservation $reservation)
    {
        $request->validate([
            'reason_ids'      => 'required|array|min:1',
            'reason_ids.*'    => 'integer|exists:refusal_reasons,id',
            'custom_reason'   => 'nullable|string|max:1000',
            'refusal_type'    => 'required|in:definitive,with_suggestion',
        ]);

        $reason = $this->buildRefusalReason(
            $request->input('reason_ids', []),
            $request->input('custom_reason', '')
        );

        $withSuggestion = $request->input('refusal_type') === 'with_suggestion';

        $this->reservationService->refuse($reservation, $request->user(), $reason, $withSuggestion);

        return redirect()
            ->route('admin.reservations.show', $reservation)
            ->with('success', "Réservation {$reservation->reference} refusée.");
    }

    /**
     * Construit la chaîne de motif de refus à partir des IDs sélectionnés
     * et d'un éventuel motif personnalisé.
     */
    private function buildRefusalReason(array $reasonIds, ?string $customReason = null): string
    {
        $labels = RefusalReason::whereIn('id', $reasonIds)
            ->ordered()
            ->pluck('label')
            ->toArray();

        // Retire "Autre" de la liste des libellés si un texte libre est fourni
        $otherLabel = 'Autre';
        $hasOther   = in_array($otherLabel, $labels);
        $labels     = array_filter($labels, fn($l) => $l !== $otherLabel);

        $parts = array_values($labels);
        if ($hasOther && trim($customReason ?? '') !== '') {
            $parts[] = trim($customReason ?? '');
        } elseif ($hasOther) {
            $parts[] = 'Autre';
        }

        return implode(' — ', $parts);
    }

    /**
     * Enregistrer un paiement partiel ou total (admin).
     */
    public function markPaid(Request $request, Reservation $reservation)
    {
        $maxAmount = max(0, ($reservation->total_price ?? 0) - $reservation->payments->where('status', 'completed')->sum('amount'));

        $request->validate([
            'amount'    => 'required|numeric|min:1|max:' . ($maxAmount ?: $reservation->total_price),
            'method'    => 'required|in:bank_transfer,cash,card,check,other',
            'reference' => 'nullable|string|max:100',
            'notes'     => 'nullable|string|max:500',
            'proof'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if (! in_array($reservation->status, [
            Reservation::STATUS_WAITING_PAYMENT,
            Reservation::STATUS_ACCEPTED,
        ])) {
            return back()->with('error', 'Impossible d\'enregistrer un paiement dans cet état.');
        }

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('payment-proofs', 'public');
        }

        // Enregistrer le paiement
        \App\Models\Payment::create([
            'reservation_id' => $reservation->id,
            'amount'         => $request->amount,
            'currency'       => 'MAD',
            'method'         => $request->method,
            'status'         => 'completed',
            'reference'      => $request->reference,
            'notes'          => $request->notes,
            'proof_path'     => $proofPath,
            'recorded_by'    => $request->user()->id,
            'paid_at'        => now(),
        ]);

        $reservation->refresh();
        $totalPaid   = $reservation->payments->where('status', 'completed')->sum('amount');
        $totalPrice  = $reservation->total_price ?? 0;
        $pctPaid     = $totalPrice > 0 ? $totalPaid / $totalPrice : 0;
        $isFullyPaid = $totalPrice > 0 && $totalPaid >= $totalPrice;
        $pctInt      = round($pctPaid * 100);

        $methods = ['bank_transfer'=>'Virement','cash'=>'Espèces','card'=>'Carte','check'=>'Chèque','other'=>'Autre'];

        if ($isFullyPaid) {
            // Paiement complet → confirmer
            $this->reservationService->markAsPaid($reservation, $request->user(), $request->all());
            $msg = "Paiement complet enregistré (100%). Réservation confirmée.";
            \App\Models\ReservationLog::record($reservation, 'payment_added',
                "Paiement complet enregistré par admin — " . number_format($request->amount, 2, ',', ' ') . " MAD",
                [],
                ['amount' => $request->amount, 'method' => $methods[$request->method] ?? $request->method, 'reference' => $request->reference, 'total_paid' => $totalPaid, 'pct' => 100],
                $request->notes, 'admin', $request->user()->id, $request->user()->name
            );
        } elseif ($pctPaid >= 0.5) {
            // 50% ou plus payé → confirmer
            $prevStatus = $reservation->status;
            $reservation->update(['status' => Reservation::STATUS_CONFIRMED]);
            StatusHistory::create([
                'reservation_id' => $reservation->id,
                'from_status'    => $prevStatus,
                'to_status'      => Reservation::STATUS_CONFIRMED,
                'comment'        => "Seuil de 50% atteint ({$pctInt}% payé) — réservation confirmée.",
                'actor_type'     => 'admin',
                'actor_id'       => $request->user()->id,
                'actor_name'     => $request->user()->name,
            ]);
            $remaining = $totalPrice - $totalPaid;
            $msg = "Paiement de {$request->amount} MAD enregistré ({$pctInt}% payé). Réservation confirmée. Reste : " . number_format($remaining, 0, ',', ' ') . " MAD.";
            \App\Models\ReservationLog::record($reservation, 'payment_added',
                "Paiement enregistré par admin — " . number_format($request->amount, 2, ',', ' ') . " MAD ({$pctInt}%) — Réservation confirmée",
                [],
                ['amount' => $request->amount, 'method' => $methods[$request->method] ?? $request->method, 'reference' => $request->reference, 'total_paid' => $totalPaid, 'remaining' => $remaining, 'pct' => $pctInt],
                $request->notes, 'admin', $request->user()->id, $request->user()->name
            );
        } else {
            // Moins de 50% → paiement partiel
            $reservation->update(['status' => Reservation::STATUS_PARTIALLY_PAID]);
            StatusHistory::create([
                'reservation_id' => $reservation->id,
                'from_status'    => $reservation->getOriginal('status') ?? $reservation->status,
                'to_status'      => Reservation::STATUS_PARTIALLY_PAID,
                'comment'        => "Paiement partiel de {$request->amount} MAD enregistré par admin ({$pctInt}%).",
                'actor_type'     => 'admin',
                'actor_id'       => $request->user()->id,
                'actor_name'     => $request->user()->name,
            ]);
            $remaining = $totalPrice - $totalPaid;
            $msg = "Paiement de {$request->amount} MAD enregistré ({$pctInt}% payé). Reste : " . number_format($remaining, 0, ',', ' ') . " MAD.";
            \App\Models\ReservationLog::record($reservation, 'payment_added',
                "Paiement partiel enregistré par admin — " . number_format($request->amount, 2, ',', ' ') . " MAD ({$pctInt}%)",
                [],
                ['amount' => $request->amount, 'method' => $methods[$request->method] ?? $request->method, 'reference' => $request->reference, 'total_paid' => $totalPaid, 'remaining' => $remaining, 'pct' => $pctInt],
                $request->notes, 'admin', $request->user()->id, $request->user()->name
            );
        }

        return redirect()
            ->route('admin.reservations.show', $reservation)
            ->with('success', $msg);
    }

    /**
     * Valider un paiement soumis par une agence.
     */
    public function validatePayment(Request $request, Reservation $reservation, \App\Models\Payment $payment)
    {
        // Sécurité : ce paiement appartient bien à cette réservation
        abort_if($payment->reservation_id !== $reservation->id, 403);

        $prevStatus = $reservation->status;

        $payment->update([
            'status'      => 'completed',
            'recorded_by' => $request->user()->id,
            'paid_at'     => now(),
        ]);

        $reservation->refresh();
        $totalPaid  = $reservation->payments->where('status','completed')->sum('amount');
        $totalPrice = $reservation->total_price ?? 0;

        $pctPaid = $totalPrice > 0 ? $totalPaid / $totalPrice : 0;
        $pctInt  = (int) round($pctPaid * 100);

        if ($totalPrice > 0 && $totalPaid >= $totalPrice) {
            // Paiement complet (100%)
            $this->reservationService->markAsPaid($reservation, $request->user(), []);
            $msg = 'Paiement validé. Réservation confirmée (100% payé).';
            \App\Models\ReservationLog::record($reservation, 'payment_validated',
                "Paiement de {$payment->amount} MAD validé — Réservation confirmée (100%)",
                ['amount' => $payment->amount, 'method' => $payment->method],
                ['status' => 'confirmed', 'total_paid' => $totalPaid],
                null, 'admin', $request->user()->id, $request->user()->name
            );
        } elseif ($pctPaid >= 0.5 && ! in_array($reservation->status, [Reservation::STATUS_CONFIRMED, Reservation::STATUS_PAID])) {
            // Seuil 50% atteint → confirmer
            $reservation->update(['status' => Reservation::STATUS_CONFIRMED]);
            StatusHistory::create([
                'reservation_id' => $reservation->id,
                'from_status'    => $prevStatus,
                'to_status'      => Reservation::STATUS_CONFIRMED,
                'comment'        => "Seuil de 50% atteint ({$pctInt}% payé) — réservation confirmée.",
                'actor_type'     => 'admin',
                'actor_id'       => $request->user()->id,
                'actor_name'     => $request->user()->name,
            ]);
            $remaining = $totalPrice - $totalPaid;
            $msg = "Paiement de {$payment->amount} MAD validé ({$pctInt}% payé). Réservation confirmée. Reste : " . number_format($remaining, 0, ',', ' ') . ' MAD.';
            \App\Models\ReservationLog::record($reservation, 'payment_validated',
                "Paiement de {$payment->amount} MAD validé ({$pctInt}%) — Réservation confirmée (seuil 50%)",
                ['amount' => $payment->amount, 'method' => $payment->method],
                ['status' => 'confirmed', 'total_paid' => $totalPaid, 'remaining' => $remaining, 'pct' => $pctInt],
                null, 'admin', $request->user()->id, $request->user()->name
            );
        } else {
            // Moins de 50% → paiement partiel
            $reservation->update(['status' => Reservation::STATUS_PARTIALLY_PAID]);
            StatusHistory::create([
                'reservation_id' => $reservation->id,
                'from_status'    => $prevStatus,
                'to_status'      => Reservation::STATUS_PARTIALLY_PAID,
                'comment'        => "Paiement agence de {$payment->amount} MAD validé ({$pctInt}%).",
                'actor_type'     => 'admin',
                'actor_id'       => $request->user()->id,
                'actor_name'     => $request->user()->name,
            ]);
            $remaining = $totalPrice - $totalPaid;
            $msg = "Paiement de {$payment->amount} MAD validé ({$pctInt}% payé). Reste : " . number_format($remaining, 0, ',', ' ') . ' MAD.';
            \App\Models\ReservationLog::record($reservation, 'payment_validated',
                "Paiement de {$payment->amount} MAD validé ({$pctInt}% payé)",
                ['amount' => $payment->amount, 'method' => $payment->method],
                ['total_paid' => $totalPaid, 'remaining' => $remaining, 'pct' => $pctInt],
                null, 'admin', $request->user()->id, $request->user()->name
            );
        }

        return redirect()
            ->route('admin.reservations.show', $reservation)
            ->with('success', $msg);
    }

    /**
     * Accepter une modification client.
     */
    public function acceptModification(Reservation $reservation, Request $request)
    {
        $this->reservationService->acceptModification($reservation, $request->user());

        return redirect()
            ->route('admin.reservations.show', $reservation)
            ->with('success', 'Modification acceptée et prix recalculé.');
    }

    /**
     * Renvoyer le devis (email avec échéancier) au client.
     */
    public function resendQuote(Reservation $reservation)
    {
        abort_if(
            ! in_array($reservation->status, [
                Reservation::STATUS_WAITING_PAYMENT,
                Reservation::STATUS_ACCEPTED,
                Reservation::STATUS_PARTIALLY_PAID,
            ]),
            422,
            'Le devis ne peut être renvoyé que pour les réservations en attente de paiement.'
        );

        $this->notificationService->sendQuote($reservation);

        // Enregistrer dans l'historique : le dernier entry ne sera plus
        // "modification_pending → waiting_payment", donc l'alerte disparaîtra.
        \App\Models\StatusHistory::create([
            'reservation_id' => $reservation->id,
            'from_status'    => $reservation->status,
            'to_status'      => $reservation->status,
            'comment'        => 'Devis renvoyé à ' . $reservation->email,
            'actor_type'     => 'admin',
            'actor_id'       => auth()->id(),
            'actor_name'     => auth()->user()->name ?? 'Admin',
        ]);
        \App\Models\ReservationLog::record($reservation, 'devis_sent',
            "Devis renvoyé à {$reservation->email}",
            [], ['email' => $reservation->email, 'status' => $reservation->status],
            null, 'admin', auth()->id(), auth()->user()->name ?? 'Admin'
        );

        return redirect()
            ->route('admin.reservations.show', $reservation)
            ->with('success', "Devis renvoyé avec succès à {$reservation->email}.");
    }

    /**
     * Refuser une modification client.
     */
    public function refuseModification(Request $request, Reservation $reservation)
    {
        $request->validate([
            'reason_ids'    => 'required|array|min:1',
            'reason_ids.*'  => 'integer|exists:refusal_reasons,id',
            'custom_reason' => 'nullable|string|max:1000',
        ]);

        $reason = $this->buildRefusalReason(
            $request->input('reason_ids', []),
            $request->input('custom_reason', '')
        );

        $this->reservationService->refuseModification($reservation, $request->user(), $reason);

        return redirect()
            ->route('admin.reservations.show', $reservation)
            ->with('success', 'Modification refusée.');
    }

    /**
     * Formulaire d'édition admin d'une réservation.
     */
    public function edit(Reservation $reservation)
    {
        $reservation->load(['hotel', 'rooms.roomType', 'payments', 'secureLink.agency.agencyStatus', 'supplements.supplement', 'extras']);
        $roomTypes = RoomType::where('hotel_id', $reservation->hotel_id)
            ->active()
            ->with('activeOccupancyConfigs')
            ->orderBy('name')
            ->get();

        // Charger les relations nécessaires pour les prix initiaux
        $reservation->loadMissing(['rooms.roomType', 'rooms.occupancyConfig', 'hotel']);

        $extrasTotal = (float) $reservation->extras->sum('total_price');

        // Grouper les chambres par séjour (check_in + check_out)
        $stayGroups = $reservation->rooms
            ->groupBy(fn ($r) =>
                ($r->check_in?->format('Y-m-d')  ?? $reservation->check_in->format('Y-m-d'))
                . '|' .
                ($r->check_out?->format('Y-m-d') ?? $reservation->check_out->format('Y-m-d'))
            )
            ->map(fn ($rooms, $key) => [
                'check_in'  => explode('|', $key)[0],
                'check_out' => explode('|', $key)[1],
                'rooms'     => $rooms->map(fn ($r) => [
                    'room_type_id'        => (string) $r->room_type_id,
                    'occupancy_config_id' => $r->occupancy_config_id ? (string) $r->occupancy_config_id : null,
                    'comboValue'          => $r->room_type_id . '|' . ($r->occupancy_config_id ?? ''),
                    'quantity'            => $r->quantity,
                    'adults'              => $r->adults   ?? 1,
                    'children'            => $r->children ?? 0,
                    'babies'              => $r->babies   ?? 0,
                ])->values()->toArray(),
            ])
            ->values()
            ->toArray();

        // Si aucun séjour (réservation vierge), créer un séjour vide par défaut
        if (empty($stayGroups)) {
            $stayGroups = [[
                'check_in'  => $reservation->check_in->format('Y-m-d'),
                'check_out' => $reservation->check_out->format('Y-m-d'),
                'rooms'     => [['room_type_id' => '', 'occupancy_config_id' => null, 'comboValue' => '', 'quantity' => 1, 'adults' => 1, 'children' => 0, 'babies' => 0]],
            ]];
        }

        return view('admin.reservations.edit', compact('reservation', 'roomTypes', 'stayGroups', 'extrasTotal'));
    }

    /**
     * Enregistrer les modifications admin d'une réservation.
     * Recalcule le prix depuis le calendrier tarifaire.
     */
    public function updateReservation(Request $request, Reservation $reservation)
    {
        $data = $request->validate([
            'total_persons'        => 'required|integer|min:1',
            'special_requests'     => 'nullable|string|max:2000',
            'admin_notes'          => 'nullable|string|max:1000',
            'selected_supplements' => 'nullable|array',
            'selected_supplements.*' => 'integer|exists:supplements,id',
            'stays'                => 'required|array|min:1',
            'stays.*.check_in'      => 'required|date',
            'stays.*.check_out'     => 'required|date|after:stays.*.check_in',
            'stays.*.rooms'                            => 'required|array|min:1',
            'stays.*.rooms.*.room_type_id'             => 'required|exists:room_types,id',
            'stays.*.rooms.*.quantity'                 => 'required|integer|min:1',
            'stays.*.rooms.*.adults'                   => 'nullable|integer|min:0',
            'stays.*.rooms.*.children'                 => 'nullable|integer|min:0',
            'stays.*.rooms.*.babies'                   => 'nullable|integer|min:0',
            'stays.*.rooms.*.occupancy_config_id'      => 'nullable|exists:room_occupancy_configs,id',
        ]);

        DB::transaction(function () use ($reservation, $data, $request) {
            $prevStatus   = $reservation->status;
            $oldSnapshot  = [
                'check_in'         => $reservation->check_in?->format('d/m/Y'),
                'check_out'        => $reservation->check_out?->format('d/m/Y'),
                'total_persons'    => $reservation->total_persons,
                'total_price'      => $reservation->total_price,
                'taxe_total'       => $reservation->taxe_total,
                'supplement_total' => $reservation->supplement_total,
            ];
            $stays        = $data['stays'];

            // Dates globales = bornes des séjours
            $allCheckIns  = array_column($stays, 'check_in');
            $allCheckOuts = array_column($stays, 'check_out');
            $globalCheckIn  = min($allCheckIns);
            $globalCheckOut = max($allCheckOuts);

            // Mettre à jour les champs de base
            $reservation->update([
                'check_in'         => $globalCheckIn,
                'check_out'        => $globalCheckOut,
                'total_persons'    => $data['total_persons'],
                'special_requests' => $data['special_requests'] ?? null,
                'admin_notes'      => $data['admin_notes'] ?? $reservation->admin_notes,
                'handled_by'       => $request->user()->id,
            ]);

            // ── Analyser les changements pour appliquer la bonne stratégie ──────
            $agencyStatusSlug = $reservation->secureLink?->agency?->agencyStatus?->slug;
            $totalRoomsAll    = collect($stays)
                ->flatMap(fn ($s) => $s['rooms'] ?? [])
                ->sum(fn ($r) => max(1, (int) ($r['quantity'] ?? 1)));
            $tariffCode = $this->pricingService->determineTariffCode($agencyStatusSlug, $totalRoomsAll);

            $changes = $this->reservationService->analyzeModificationChanges($reservation, $stays);
            $datesChanged = $changes['dates_changed'];
            $oldPrices    = $changes['old_prices'];

            // ── Reconstruire les lignes chambres ─────────────────────────────
            $reservation->rooms()->delete();
            $totalPrice    = 0.0;
            $totalTaxe     = 0.0;
            $allBreakdowns = [];

            foreach ($stays as $stayIdx => $stay) {
                $stayKey  = $stay['check_in'] . '_' . $stay['check_out'];
                $nights   = (int) \Carbon\Carbon::parse($stay['check_in'])->diffInDays(\Carbon\Carbon::parse($stay['check_out']));

                // Rooms à recalculer (nouvelles ou dates changées)
                $roomsToCalculate = [];
                // Rooms à créer avec prix conservés
                $roomsToPreserve  = [];

                foreach ($stay['rooms'] as $roomIndex => $room) {
                    if ((int) ($room['quantity'] ?? 0) <= 0) continue;
                    $configId = ! empty($room['occupancy_config_id']) ? (int) $room['occupancy_config_id'] : null;
                    // Clé par POSITION : évite la collision si même type+config
                    $posKey = "{$stayKey}:{$roomIndex}";

                    $isNewStay      = $changes['stay_is_new'][$stayIdx]        ?? false;
                    $isDatesChanged = $changes['stay_dates_changed'][$stayIdx]  ?? false;
                    $shouldCalculate = $isNewStay || $isDatesChanged || ! isset($oldPrices[$posKey]);

                    if ($shouldCalculate) {
                        $roomsToCalculate[] = ['input' => $room, 'configId' => $configId];
                    } else {
                        $roomsToPreserve[]  = ['input' => $room, 'configId' => $configId, 'oldPrice' => $oldPrices[$posKey]];
                    }
                }

                // ── Rooms conservées : prix figés ─────────────────────────
                foreach ($roomsToPreserve as $entry) {
                    $room   = $entry['input'];
                    $old    = $entry['oldPrice'];
                    $qty    = (int) $room['quantity'];
                    $ppn    = (float) ($old['price_per_night'] ?? 0);
                    // Si la quantité a changé, le total est proportionnel
                    $total  = $ppn > 0 ? round($ppn * $qty * $nights, 2) : (float) ($old['total_price'] ?? 0);

                    $dbRoom = ReservationRoom::create([
                        'reservation_id'      => $reservation->id,
                        'room_type_id'        => $room['room_type_id'],
                        'quantity'            => $qty,
                        'adults'              => (int) ($room['adults']   ?? 1),
                        'children'            => (int) ($room['children'] ?? 0),
                        'babies'              => (int) ($room['babies']   ?? 0),
                        'occupancy_config_id' => $entry['configId'],
                        'check_in'            => $stay['check_in'],
                        'check_out'           => $stay['check_out'],
                        'price_per_night'     => $ppn ?: null,
                        'total_price'         => $total,
                        'price_detail'        => $old['price_detail'] ?? [],
                    ]);

                    $totalPrice += $total;
                    // Taxe préservée proportionnellement au nombre de nuits
                    try {
                        $taxeRate   = $reservation->hotel->taxe_sejour ?? 0;
                        $adults     = (int) ($room['adults'] ?? 0) * $qty;
                        $totalTaxe += round($adults * $nights * $taxeRate, 2);
                    } catch (\Exception $_e) {}
                }

                // ── Rooms à calculer (nouvelles ou dates changées) ────────
                if (! empty($roomsToCalculate)) {
                    $stayRoomsInput = array_map(fn($e) => [
                        'room_type_id'        => $e['input']['room_type_id'],
                        'quantity'            => (int) $e['input']['quantity'],
                        'adults'              => (int) ($e['input']['adults']   ?? 1),
                        'children'            => (int) ($e['input']['children'] ?? 0),
                        'babies'              => (int) ($e['input']['babies']   ?? 0),
                        'occupancy_config_id' => $e['configId'],
                    ], $roomsToCalculate);

                    // Créer d'abord les lignes DB (sans prix)
                    foreach ($roomsToCalculate as $entry) {
                        $room = $entry['input'];
                        ReservationRoom::create([
                            'reservation_id'      => $reservation->id,
                            'room_type_id'        => $room['room_type_id'],
                            'quantity'            => (int) $room['quantity'],
                            'adults'              => (int) ($room['adults']   ?? 1),
                            'children'            => (int) ($room['children'] ?? 0),
                            'babies'              => (int) ($room['babies']   ?? 0),
                            'occupancy_config_id' => $entry['configId'],
                            'check_in'            => $stay['check_in'],
                            'check_out'           => $stay['check_out'],
                        ]);
                    }

                    try {
                        $calcResult = $this->pricingService->calculate(
                            $reservation->hotel_id,
                            $stay['check_in'],
                            $stay['check_out'],
                            $stayRoomsInput,
                            0.0,
                            $tariffCode
                        );

                        // Appliquer les prix calculés aux nouvelles lignes (index-based)
                        // On récupère uniquement les rooms de CE séjour qui n'ont pas de prix encore
                        $dbNewRooms = $reservation->rooms()
                            ->where('check_in', $stay['check_in'])
                            ->where('check_out', $stay['check_out'])
                            ->whereNull('price_per_night')
                            ->orderBy('id')
                            ->get();

                        foreach (array_values($calcResult['breakdown']) as $i => $line) {
                            $dbRoom = $dbNewRooms[$i] ?? null;
                            if (! $dbRoom) continue;
                            // price_per_night = prix moyen par nuit (unit_price_raw / nights)
                            // évite d'afficher uniquement le tarif de la 1ère nuit pour les séjours à tarif variable
                            $_nightDetail = $line['night_detail'] ?? [];
                            $_nights      = max(1, count($_nightDetail));
                            $_avgPpn      = isset($line['unit_price_raw']) && $_nights > 0
                                ? round($line['unit_price_raw'] / $_nights, 2)
                                : (count($_nightDetail) > 0 ? $_nightDetail[0]['unit_price'] : null);

                            $dbRoom->update([
                                'price_per_night'     => $_avgPpn,
                                'total_price'         => $line['line_total'],
                                'price_detail'        => $_nightDetail,
                                'occupancy_config_id' => $line['occupancy_config_id'] ?? $dbRoom->occupancy_config_id,
                            ]);
                        }

                        $totalPrice    += $calcResult['total'];
                        $totalTaxe     += round($calcResult['taxe_sejour_total'] ?? 0, 2);
                        $allBreakdowns  = array_merge($allBreakdowns, $calcResult['breakdown']);
                    } catch (\Exception $e) {
                        // Prix non disponibles pour ce séjour — on continue
                    }
                }
            }

            // Sauvegarder prix + taxe + tariff (hébergement seul, avant suppléments)
            $reservation->update([
                'total_price'     => round($totalPrice, 2),
                'taxe_total'      => $totalTaxe,
                'tariff_code'     => $tariffCode,
                'price_breakdown' => $allBreakdowns,
                'discount_percent'=> 0,
                'supplement_total'=> 0,
            ]);

            // Appliquer la promo long séjour
            $this->pricingService->applyLongStayPromo($reservation);
            $reservation->refresh();

            // Recalculer et appliquer les suppléments obligatoires
            $reservation->load('rooms');
            $this->pricingService->applyMandatorySupplements($reservation);
            $reservation->refresh();

            // Synchro des suppléments optionnels selon les cases cochées dans le formulaire
            // Supprimer les anciens optionnels, recréer selon selected_supplements[]
            $reservation->load('rooms');
            $rooms = $reservation->rooms;
            $selectedOptionalIds = array_map('intval', $data['selected_supplements'] ?? []);

            // Supprimer tous les anciens suppléments optionnels
            ReservationSupplement::where('reservation_id', $reservation->id)
                ->where('is_mandatory', false)
                ->delete();

            $optionalSupplementTotal = 0.0;

            foreach ($selectedOptionalIds as $supId) {
                $sup = \App\Models\Supplement::find($supId);
                if (! $sup || ! $sup->is_active) continue;

                $supFrom = $sup->date_from; // Carbon ou null
                $supTo   = $sup->date_to;

                $adults = 0; $children = 0; $babies = 0;

                foreach ($rooms as $room) {
                    $roomIn   = $room->check_in  ?? $reservation->check_in;
                    $roomOut  = $room->check_out ?? $reservation->check_out;
                    $lastNight = $roomOut->copy()->subDay();
                    $qty = max(1, (int)($room->quantity ?? 1));

                    $overlaps = (! $supFrom || ! $supTo)
                        || ($supFrom->lte($lastNight) && $supTo->gte($roomIn));

                    if ($overlaps) {
                        $adults   += ($room->adults   ?? 0) * $qty;
                        $children += ($room->children ?? 0) * $qty;
                        $babies   += ($room->babies   ?? 0) * $qty;
                    }
                }

                $newTotal = $adults   * (float)($sup->price_adult ?? 0)
                          + $children * (float)($sup->price_child  ?? 0)
                          + $babies   * (float)($sup->price_baby   ?? 0);

                ReservationSupplement::create([
                    'reservation_id'   => $reservation->id,
                    'supplement_id'    => $sup->id,
                    'is_mandatory'     => false,
                    'adults_count'     => $adults,
                    'children_count'   => $children,
                    'babies_count'     => $babies,
                    'unit_price_adult' => $sup->price_adult,
                    'unit_price_child' => $sup->price_child,
                    'unit_price_baby'  => $sup->price_baby,
                    'total_price'      => round($newTotal, 2),
                ]);

                $optionalSupplementTotal += $newTotal;
            }

            if ($optionalSupplementTotal > 0) {
                $reservation->update([
                    'supplement_total' => round((float)($reservation->supplement_total ?? 0) + $optionalSupplementTotal, 2),
                    'total_price'      => round((float)($reservation->total_price ?? 0) + $optionalSupplementTotal, 2),
                ]);
                $reservation->refresh();
            }

            // Réintégrer les extras dans total_price
            $reservation->loadMissing('extras');
            $extrasSum = (float) $reservation->extras->sum('total_price');
            if ($extrasSum > 0) {
                $reservation->update([
                    'total_price' => round(($reservation->total_price ?? 0) + $extrasSum, 2),
                ]);
                $reservation->refresh();
            }

            // Lire le total final (avec suppléments + extras + remises) pour le reste des traitements
            $finalTotal = $reservation->total_price;

            // Régénérer le token de paiement si paiement encore attendu
            $alreadyPaid = $reservation->payments->where('status', 'completed')->sum('amount');
            $remaining   = max(0, $finalTotal - $alreadyPaid);

            if ($remaining > 0 && ! in_array($prevStatus, [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED, Reservation::STATUS_CANCELLED, Reservation::STATUS_REFUSED])) {
                $newStatus = $alreadyPaid > 0 ? Reservation::STATUS_PARTIALLY_PAID : Reservation::STATUS_WAITING_PAYMENT;
                $reservation->update(['status' => $newStatus]);
                $reservation->generatePaymentToken();
            }

            // Historique
            StatusHistory::create([
                'reservation_id' => $reservation->id,
                'from_status'    => $prevStatus,
                'to_status'      => $reservation->fresh()->status,
                'comment'        => sprintf(
                    'Réservation modifiée par admin. Nouveau total : %s MAD. %d séjour(s), tarif %s.',
                    number_format($finalTotal, 2, ',', ' '),
                    count($stays),
                    $tariffCode
                ),
                'actor_type' => 'admin',
                'actor_id'   => $request->user()->id,
                'actor_name' => $request->user()->name,
                'metadata'   => ['new_total' => $finalTotal, 'tariff_code' => $tariffCode, 'missing_prices' => []],
            ]);
            $newReservation = $reservation->fresh();
            \App\Models\ReservationLog::record($reservation, 'price_recalculated',
                "Réservation modifiée par admin — Nouveau total : " . number_format($finalTotal, 2, ',', ' ') . " MAD",
                $oldSnapshot,
                [
                    'check_in'      => $newReservation->check_in?->format('d/m/Y'),
                    'check_out'     => $newReservation->check_out?->format('d/m/Y'),
                    'total_persons' => $newReservation->total_persons,
                    'total_price'   => $newReservation->total_price,
                    'taxe_total'    => $newReservation->taxe_total,
                    'tariff_code'   => $tariffCode,
                    'nb_stays'      => count($stays),
                ],
                $data['admin_notes'] ?? null, 'admin', $request->user()->id, $request->user()->name
            );
        });

        return redirect()
            ->route('admin.reservations.show', $reservation)
            ->with('success', 'Réservation modifiée et prix recalculé avec succès.');
    }

    /**
     * Changer statut manuellement.
     */
    public function updateStatus(Request $request, Reservation $reservation)
    {
        $request->validate([
            'status'  => 'required|in:' . implode(',', Reservation::STATUSES),
            'comment' => 'nullable|string|max:500',
        ]);

        $prev = $reservation->status;
        $reservation->update(['status' => $request->status]);

        \App\Models\StatusHistory::create([
            'reservation_id' => $reservation->id,
            'from_status'    => $prev,
            'to_status'      => $request->status,
            'comment'        => $request->comment ?? 'Changement manuel par admin.',
            'actor_type'     => 'admin',
            'actor_id'       => $request->user()->id,
            'actor_name'     => $request->user()->name,
        ]);
        \App\Models\ReservationLog::record($reservation, 'status_changed',
            "Statut modifié manuellement : {$prev} → {$request->status}",
            ['status' => $prev], ['status' => $request->status],
            $request->comment, 'admin', $request->user()->id, $request->user()->name
        );

        return redirect()
            ->route('admin.reservations.show', $reservation)
            ->with('success', 'Statut mis à jour.');
    }

    // ─── Prix par nuit d'une chambre ─────────────────────────────────────────

    /**
     * Modifier manuellement le prix par nuit d'une chambre de réservation.
     * Recalcule total_price de la chambre et total_price de la réservation.
     */
    public function updateRoomPrice(Request $request, Reservation $reservation, \App\Models\ReservationRoom $room)
    {
        abort_if($room->reservation_id !== $reservation->id, 403);

        $request->validate([
            'price_per_night' => 'required|numeric|min:0',
        ]);

        $newPpn = round((float) $request->price_per_night, 2);
        $qty    = max(1, (int) ($room->quantity ?? 1));
        $nights = ($room->check_in && $room->check_out)
            ? (int) $room->check_in->diffInDays($room->check_out)
            : max(1, (int) ($reservation->check_in->diffInDays($reservation->check_out)));

        // Sauvegarder le prix original la première fois seulement
        $isFirstOverride = ! $room->price_override;
        if ($isFirstOverride) {
            $room->original_price_per_night = round((float) ($room->price_per_night ?? 0), 2);
            $room->original_total_price     = round((float) ($room->total_price ?? 0), 2);
        }

        $oldResaTotal = round((float) ($reservation->total_price ?? 0), 2);
        $newTotal     = round($newPpn * $qty * $nights, 2);

        $room->price_per_night = $newPpn;
        $room->total_price     = $newTotal;
        $room->price_override  = true;
        // Effacer price_detail (obsolète après un override manuel) :
        // initialPriceResults lira total_price/qty, reflétant le nouveau tarif
        $room->price_detail    = null;
        $room->save();

        // Recalculer la remise long séjour sur les nouveaux totaux par chambre
        $reservation->refresh();
        $newPromoAmount = $this->recalcPromoDiscount($reservation);

        $roomsSum     = (float) $reservation->rooms->sum(fn($r) => $r->total_price ?? 0);
        $suppSum      = (float) ($reservation->supplement_total ?? 0);
        $newResaTotal = round($roomsSum + $suppSum - $newPromoAmount, 2);

        $reservation->update([
            'promo_discount_amount' => $newPromoAmount,
            'total_price'           => $newResaTotal,
        ]);

        return redirect()->route('admin.reservations.show', $reservation)
            ->with('success', 'Prix par nuit mis à jour avec succès.');
    }

    // ─── Validation en lot des tarifs modifiés ───────────────────────────────

    /**
     * Valide et enregistre plusieurs modifications de prix par nuit en une seule fois.
     * Reçoit un JSON {room_id: new_price_per_night, ...}.
     */
    public function batchUpdateRoomPrices(Request $request, Reservation $reservation)
    {
        $request->validate(['changes' => 'required|string']);

        $changes = json_decode($request->changes, true);
        if (! is_array($changes) || empty($changes)) {
            return redirect()->route('admin.reservations.show', $reservation)
                ->with('info', 'Aucune modification à appliquer.');
        }

        $updatedCount = 0;
        foreach ($changes as $roomId => $newPpn) {
            $room = \App\Models\ReservationRoom::find((int) $roomId);
            if (! $room || $room->reservation_id !== $reservation->id) continue;

            $newPpn = round((float) $newPpn, 2);
            $qty    = max(1, (int) ($room->quantity ?? 1));
            $nights = ($room->check_in && $room->check_out)
                ? (int) $room->check_in->diffInDays($room->check_out)
                : max(1, (int) $reservation->check_in->diffInDays($reservation->check_out));

            // Conserver le prix original seulement à la première modification
            if (! $room->price_override) {
                $room->original_price_per_night = round((float) ($room->price_per_night ?? 0), 2);
                $room->original_total_price     = round((float) ($room->total_price     ?? 0), 2);
            }

            $room->price_per_night = $newPpn;
            $room->total_price     = round($newPpn * $qty * $nights, 2);
            $room->price_override  = true;
            // Effacer price_detail (obsolète après un override manuel)
            $room->price_detail    = null;
            $room->save();
            $updatedCount++;
        }

        // Recalculer la remise long séjour sur les nouveaux totaux, puis le total réservation
        $reservation->refresh();
        $newPromoAmount = $this->recalcPromoDiscount($reservation);

        $roomsSum = (float) $reservation->rooms->sum(fn($r) => $r->total_price ?? 0);
        $suppSum  = (float) ($reservation->supplement_total ?? 0);
        $reservation->update([
            'promo_discount_amount' => $newPromoAmount,
            'total_price'           => round($roomsSum + $suppSum - $newPromoAmount, 2),
        ]);

        return redirect()->route('admin.reservations.show', $reservation)
            ->with('success', "$updatedCount tarif(s) mis à jour avec succès.");
    }

    // ─── Date limite de paiement ──────────────────────────────────────────────

    /**
     * Définir / mettre à jour la date limite de paiement.
     */
    public function setDeadline(Request $request, Reservation $reservation)
    {
        $request->validate(['payment_deadline' => 'nullable|date']);

        $reservation->update([
            'payment_deadline' => $request->payment_deadline ?: null,
        ]);

        $msg = $request->payment_deadline
            ? 'Date limite définie : ' . \Carbon\Carbon::parse($request->payment_deadline)->format('d/m/Y')
            : 'Date limite supprimée.';

        return back()->with('success', $msg);
    }

    // ─── Échéancier ───────────────────────────────────────────────────────────

    /**
     * Ajouter une échéance à l'échéancier.
     * Le champ "amount" accepte un montant MAD (ex: 5000) ou un pourcentage (ex: 30%).
     */
    public function storeSchedule(Request $request, Reservation $reservation)
    {
        $request->validate([
            'label'        => 'nullable|string|max:255',
            'due_date'     => 'required|date',
            'due_time'     => 'nullable|date_format:H:i',
            'amount_input' => 'required|string|max:20',
            'notes'        => 'nullable|string|max:500',
        ]);

        $raw = trim($request->input('amount_input'));

        // Détecter si c'est un pourcentage
        if (str_ends_with($raw, '%')) {
            $pct    = (float) rtrim($raw, '%');
            $amount = round(($pct / 100) * ($reservation->total_price ?? 0), 2);
        } else {
            $amount = (float) str_replace([' ', ','], ['', '.'], $raw);
        }

        if ($amount <= 0) {
            return back()->withErrors(['amount_input' => 'Montant invalide.'])->withInput();
        }

        // Vérifier que le montant ne dépasse pas le reste à planifier
        // (ignoré pour les réservations pending dont le total n'est pas encore calculé)
        $totalReservation = round(($reservation->total_price ?? 0) + ($reservation->taxe_total ?? 0), 2);
        if ($reservation->status !== Reservation::STATUS_PENDING && $totalReservation > 0) {
            $alreadyScheduled    = (float) $reservation->paymentSchedules()->sum('amount');
            $remainingToSchedule = max(0, $totalReservation - $alreadyScheduled);
            if ($amount > $remainingToSchedule + 0.01) {
                return back()->withErrors([
                    'amount_input' => 'Le montant (' . number_format($amount, 2, ',', ' ') . ' MAD) dépasse le reste à planifier (' . number_format($remainingToSchedule, 2, ',', ' ') . ' MAD).',
                ])->withInput();
            }
        }

        $lastNumber = $reservation->paymentSchedules()->max('installment_number') ?? 0;

        PaymentSchedule::create([
            'label'              => $request->input('label'),
            'due_date'           => $request->input('due_date'),
            'due_time'           => $request->input('due_time') ?: null,
            'amount'             => $amount,
            'notes'              => $request->input('notes'),
            'reservation_id'     => $reservation->id,
            'installment_number' => $lastNumber + 1,
            'status'             => 'pending',
            'created_by'         => $request->user()->id,
        ]);

        \App\Models\ReservationLog::record($reservation, 'schedule_created',
            "Échéance ajoutée : " . number_format($amount, 2, ',', ' ') . " MAD — Échéance " . ($lastNumber + 1),
            [],
            ['amount' => $amount, 'due_date' => $request->input('due_date'), 'label' => $request->input('label'), 'installment' => $lastNumber + 1],
            $request->input('notes'), 'admin', $request->user()->id, $request->user()->name
        );

        return back()->with('success', 'Échéance ajoutée (' . number_format($amount, 2, ',', ' ') . ' MAD).');
    }

    /**
     * Modifier la date d'une échéance (admin uniquement).
     */
    public function updateSchedule(Request $request, Reservation $reservation, PaymentSchedule $schedule)
    {
        abort_if($schedule->reservation_id !== $reservation->id, 403);
        abort_if($schedule->isPaid(), 403, 'Impossible de modifier une échéance déjà payée.');

        $request->validate([
            'due_date' => 'required|date',
            'due_time' => 'nullable|date_format:H:i',
            'label'    => 'nullable|string|max:100',
            'amount'   => 'required|numeric|min:0.01',
        ]);

        $old = $schedule->only(['due_date', 'due_time', 'label', 'amount']);

        $schedule->update([
            'due_date' => $request->input('due_date'),
            'due_time' => $request->input('due_time') ?: null,
            'label'    => $request->input('label') ?: null,
            'amount'   => (float) $request->input('amount'),
        ]);

        \App\Models\ReservationLog::record($reservation, 'schedule_updated',
            "Échéance #{$schedule->installment_number} modifiée",
            $old,
            $schedule->only(['due_date', 'due_time', 'label', 'amount']),
            null, 'admin', $request->user()->id, $request->user()->name
        );

        return back()->with('success', 'Échéance #' . $schedule->installment_number . ' mise à jour.');
    }

    /**
     * Supprimer une échéance.
     */
    public function destroySchedule(Reservation $reservation, PaymentSchedule $schedule)
    {
        abort_if($schedule->reservation_id !== $reservation->id, 403);

        // Supprimer le paiement lié s'il existe et est en attente
        if ($schedule->payment && $schedule->payment->status === 'pending') {
            $schedule->payment->delete();
        }

        \App\Models\ReservationLog::record($reservation, 'schedule_deleted',
            "Échéance #{$schedule->installment_number} supprimée (" . number_format($schedule->amount, 2, ',', ' ') . " MAD)",
            ['amount' => $schedule->amount, 'due_date' => $schedule->due_date?->format('d/m/Y'), 'label' => $schedule->label],
            [], null, 'admin', auth()->id(), auth()->user()->name ?? 'Admin'
        );

        $schedule->delete();

        return back()->with('success', 'Échéance supprimée.');
    }

    /**
     * Marquer une échéance comme payée (et valider la preuve client si présente).
     */
    public function markSchedulePaid(Request $request, Reservation $reservation, PaymentSchedule $schedule)
    {
        abort_if($schedule->reservation_id !== $reservation->id, 403);

        $request->validate([
            'amount'    => 'nullable|numeric|min:0.01',
            'method'    => 'nullable|string',
            'reference' => 'nullable|string|max:255',
            'proof'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('payment-proofs', 'public');
        }

        $schedule->update(['status' => 'paid']);

        // Valider le paiement client lié si en attente
        if ($schedule->payment && $schedule->payment->status === 'pending') {
            $schedule->payment->update([
                'status'      => 'completed',
                'recorded_by' => $request->user()->id,
                'paid_at'     => now(),
                'proof_path'  => $proofPath ?? $schedule->payment->proof_path,
                'reference'   => $request->input('reference', $schedule->payment->reference),
                'method'      => $request->input('method', $schedule->payment->method),
            ]);
        } else {
            // Créer un paiement enregistré par l'admin
            Payment::create([
                'reservation_id'      => $reservation->id,
                'payment_schedule_id' => $schedule->id,
                'amount'              => $request->input('amount', $schedule->amount),
                'currency'            => 'MAD',
                'method'              => $request->input('method', 'bank_transfer'),
                'reference'           => $request->input('reference'),
                'proof_path'          => $proofPath,
                'status'              => 'completed',
                'notes'               => 'Enregistré par admin.',
                'recorded_by'         => $request->user()->id,
                'paid_at'             => now(),
            ]);
        }

        // Vérifier si toutes les échéances sont payées → confirmer la réservation
        $reservation->load('paymentSchedules');
        $allPaid = $reservation->paymentSchedules->isNotEmpty()
            && $reservation->paymentSchedules->every(fn($s) => $s->status === 'paid');

        $paidAmount = $request->input('amount', $schedule->amount);
        $paidMethod = $request->input('method', 'bank_transfer');
        $methods = ['bank_transfer'=>'Virement','cash'=>'Espèces','card'=>'Carte','check'=>'Chèque','other'=>'Autre'];

        \App\Models\ReservationLog::record($reservation, 'payment_validated',
            "Échéance #{$schedule->installment_number} payée — " . number_format($paidAmount, 2, ',', ' ') . " MAD",
            ['installment' => $schedule->installment_number, 'due_date' => $schedule->due_date?->format('d/m/Y')],
            ['amount' => $paidAmount, 'method' => $methods[$paidMethod] ?? $paidMethod, 'reference' => $request->input('reference')],
            null, 'admin', $request->user()->id, $request->user()->name
        );

        $reservation->refresh();
        $totalPaid  = $reservation->payments()->where('status', 'completed')->sum('amount');
        $totalPrice = $reservation->total_price ?? 0;
        $pct        = $totalPrice > 0 ? $totalPaid / $totalPrice : 0;

        if (! in_array($reservation->status, [Reservation::STATUS_CONFIRMED, Reservation::STATUS_PAID])) {
            if ($pct >= 0.5) {
                // 50% ou plus payé → confirmer
                $prevStatus = $reservation->status;
                $reservation->update(['status' => Reservation::STATUS_CONFIRMED]);
                StatusHistory::create([
                    'reservation_id' => $reservation->id,
                    'from_status'    => $prevStatus,
                    'to_status'      => Reservation::STATUS_CONFIRMED,
                    'comment'        => sprintf(
                        '%s des paiements atteint (%s MAD) — réservation confirmée.',
                        $allPaid ? 'Totalité' : '50%+',
                        number_format($totalPaid, 2, ',', ' ')
                    ),
                    'actor_type'     => 'admin',
                    'actor_id'       => $request->user()->id,
                    'actor_name'     => $request->user()->name,
                ]);
                \App\Models\ReservationLog::record($reservation, 'payment_validated',
                    sprintf("Seuil de 50%% atteint (%d%% payé) — Réservation confirmée", round($pct * 100)),
                    [], ['status' => 'confirmed', 'pct' => round($pct * 100)],
                    null, 'admin', $request->user()->id, $request->user()->name
                );
            } else {
                // Moins de 50% — paiement partiel
                $reservation->update(['status' => Reservation::STATUS_PARTIALLY_PAID]);
            }
        }

        return back()->with('success', "Échéance #{$schedule->installment_number} marquée comme payée.");
    }

    /**
     * Exporter les fiches de police d'une réservation en Excel.
     */
    public function exportGuests(Reservation $reservation)
    {
        $guests = $reservation->guestRegistrations()->orderBy('guest_index')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Fiches de police');

        // En-tête
        $headers = [
            'A' => '#',
            'B' => 'Type',
            'C' => 'Civilité',
            'D' => 'Nom',
            'E' => 'Prénom',
            'F' => 'Date naissance',
            'G' => 'Lieu naissance',
            'H' => 'Pays naissance',
            'I' => 'Nationalité',
            'J' => 'Type document',
            'K' => 'N° document',
            'L' => 'Expiration document',
            'M' => 'Pays émission',
            'N' => 'Adresse',
            'O' => 'Ville',
            'P' => 'Code postal',
            'Q' => 'Pays résidence',
            'R' => 'Profession',
            'S' => 'Statut',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . '1', $label);
        }

        // Style en-tête
        $sheet->getStyle('A1:S1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a5f']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(20);

        // Données
        $row = 2;
        foreach ($guests as $g) {
            $typeLabel = match($g->guest_type) {
                'adult' => 'Adulte',
                'child' => 'Enfant',
                'baby'  => 'Bébé',
                default => $g->guest_type,
            };
            $docLabel = match($g->type_document) {
                'passeport'    => 'Passeport',
                'cni'          => 'CNI',
                'titre_sejour' => 'Titre séjour',
                default        => $g->type_document ?? '',
            };

            $sheet->setCellValue('A' . $row, $g->guest_index);
            $sheet->setCellValue('B' . $row, $typeLabel);
            $sheet->setCellValue('C' . $row, $g->civilite ?? '');
            $sheet->setCellValue('D' . $row, $g->nom ?? '');
            $sheet->setCellValue('E' . $row, $g->prenom ?? '');
            $sheet->setCellValue('F' . $row, $g->date_naissance?->format('d/m/Y') ?? '');
            $sheet->setCellValue('G' . $row, $g->lieu_naissance ?? '');
            $sheet->setCellValue('H' . $row, $g->pays_naissance ?? '');
            $sheet->setCellValue('I' . $row, $g->nationalite ?? '');
            $sheet->setCellValue('J' . $row, $docLabel);
            $sheet->setCellValue('K' . $row, $g->numero_document ?? '');
            $sheet->setCellValue('L' . $row, $g->date_expiration_document?->format('d/m/Y') ?? '');
            $sheet->setCellValue('M' . $row, $g->pays_emission_document ?? '');
            $sheet->setCellValue('N' . $row, $g->adresse ?? '');
            $sheet->setCellValue('O' . $row, $g->ville ?? '');
            $sheet->setCellValue('P' . $row, $g->code_postal ?? '');
            $sheet->setCellValue('Q' . $row, $g->pays_residence ?? '');
            $sheet->setCellValue('R' . $row, $g->profession ?? '');
            $sheet->setCellValue('S' . $row, $g->isComplete() ? 'Complet' : 'Incomplet');

            // Alternance de couleur de fond
            if ($row % 2 === 0) {
                $sheet->getStyle('A' . $row . ':S' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f0f4f8']],
                ]);
            }

            // Colorer statut
            $statusColor = $g->isComplete() ? '16a34a' : 'd97706';
            $sheet->getStyle('S' . $row)->applyFromArray([
                'font' => ['color' => ['rgb' => $statusColor], 'bold' => true],
            ]);

            $row++;
        }

        // Bordures sur tout le tableau
        if ($row > 2) {
            $sheet->getStyle('A1:S' . ($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'd1d5db'],
                    ],
                ],
            ]);
        }

        // Largeurs automatiques
        foreach (range('A', 'S') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Figer la première ligne
        $sheet->freezePane('A2');

        // Générer le fichier
        $filename = 'fiches-police-reservation-' . ($reservation->reference ?? $reservation->id) . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Télécharger la facture proforma en PDF.
     */
    public function proforma(Reservation $reservation)
    {
        $reservation->loadMissing([
            'hotel',
            'rooms.roomType',
            'rooms.occupancyConfig',
            'supplements.supplement',
            'paymentSchedules',
            'agency',
            'extras',
        ]);

        $tpl = \App\Models\PdfTemplate::getByKey('proforma');

        if ($tpl) {
            $data = $this->buildProformaData($reservation);
            $html = $tpl->renderBody($data);
            $pdf  = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        } else {
            $pdf = Pdf::loadView('pdf.proforma', compact('reservation'))->setPaper('a4', 'portrait');
        }

        $filename = 'proforma-' . $reservation->reference . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Construit le tableau de données pour le template PDF proforma.
     * Pré-rend chaque bloc en chaîne HTML à substituer dans le template.
     */
    private function buildProformaData(Reservation $reservation): array
    {
        return app(ProformaService::class)->buildData($reservation);
    }

    /**
     * Recalcule la remise long séjour (promo_discount_amount) en regroupant
     * les chambres par séjour et en appliquant le taux de l'hôtel sur les
     * nouveaux totaux.  Retourne le montant total à déduire (0 si pas de promo).
     */
    private function recalcPromoDiscount(Reservation $reservation): float
    {
        if (! $reservation->hotel) {
            return (float) ($reservation->promo_discount_amount ?? 0);
        }

        $reservation->loadMissing('rooms');

        $total = 0.0;

        $stayGroups = $reservation->rooms->groupBy(fn ($r) =>
            ($r->check_in?->format('Y-m-d')  ?? $reservation->check_in->format('Y-m-d')) . '_' .
            ($r->check_out?->format('Y-m-d') ?? $reservation->check_out->format('Y-m-d'))
        );

        foreach ($stayGroups as $rooms) {
            $first    = $rooms->first();
            $checkIn  = $first->check_in  ?? $reservation->check_in;
            $checkOut = $first->check_out ?? $reservation->check_out;
            $nights   = (int) $checkIn->diffInDays($checkOut);
            $rate     = $reservation->hotel->getPromoRate($nights);

            if ($rate <= 0) continue;

            $stayTotal = (float) $rooms->sum(fn ($r) => $r->total_price ?? 0);
            $total    += round($stayTotal * $rate / 100, 2);
        }

        return round($total, 2);
    }

    /**
     * Envoyer la facture proforma par email.
     * Utilise le template reservation_quote avec le PDF proforma en pièce jointe.
     * sendNow() est obligatoire car ReservationQuoteMail implements ShouldQueue —
     * send() dispatche en file d'attente et l'email ne partirait pas sans worker.
     */
    public function sendProforma(Request $request, Reservation $reservation)
    {
        $reservation->loadMissing(['hotel']);

        $to = $request->input('email') ?: $reservation->email;

        if (! $to) {
            return back()->with('error', 'Aucune adresse email disponible pour cette réservation.');
        }

        // E-mail devis avec facture proforma en pièce jointe (envoi immédiat, hors queue)
        Mail::to($to)->sendNow(new ReservationQuoteMail($reservation));

        return back()->with('success', "Facture proforma envoyée à {$to}.");
    }
}
