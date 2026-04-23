<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Hotel;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ─── Plage de dates pour les statistiques ─────────────────────────────
        $defaultFrom = now()->subDays(29)->toDateString();
        $defaultTo   = now()->toDateString();

        $rawFrom = $request->query('stats_from', $defaultFrom);
        $rawTo   = $request->query('stats_to',   $defaultTo);

        try {
            $from = Carbon::parse($rawFrom)->startOfDay();
            $to   = Carbon::parse($rawTo)->endOfDay();
            // Sécurité : ne pas accepter des dates incohérentes
            if ($from->gt($to)) {
                $from = Carbon::parse($defaultFrom)->startOfDay();
                $to   = Carbon::parse($defaultTo)->endOfDay();
                $rawFrom = $defaultFrom;
                $rawTo   = $defaultTo;
            }
        } catch (\Exception) {
            $from    = Carbon::parse($defaultFrom)->startOfDay();
            $to      = Carbon::parse($defaultTo)->endOfDay();
            $rawFrom = $defaultFrom;
            $rawTo   = $defaultTo;
        }

        $statsFrom = $rawFrom;
        $statsTo   = $rawTo;

        // Helper : applique le filtre created_at sur n'importe quelle query
        $inPeriod = fn ($q) => $q->whereBetween('created_at', [$from, $to]);

        // ─── Statuts ──────────────────────────────────────────────────────────
        $cancelledStatuses = [Reservation::STATUS_CANCELLED, Reservation::STATUS_REFUSED];
        $activeStatuses    = [
            Reservation::STATUS_PENDING,
            Reservation::STATUS_MODIFICATION_PENDING,
            Reservation::STATUS_ACCEPTED,
            Reservation::STATUS_WAITING_PAYMENT,
            Reservation::STATUS_PARTIALLY_PAID,
            Reservation::STATUS_CONFIRMED,
        ];

        // ─── KPIs filtrés par période ─────────────────────────────────────────
        $stats = [
            // Compteurs statut (créées dans la période)
            'total'           => $inPeriod(Reservation::query())->count(),
            'pending'         => $inPeriod(Reservation::byStatus(Reservation::STATUS_PENDING))->count(),
            'modification'    => $inPeriod(Reservation::byStatus(Reservation::STATUS_MODIFICATION_PENDING))->count(),
            'waiting_payment' => $inPeriod(Reservation::byStatus(Reservation::STATUS_WAITING_PAYMENT))->count(),
            'partially_paid'  => $inPeriod(Reservation::byStatus(Reservation::STATUS_PARTIALLY_PAID))->count(),
            'confirmed'       => $inPeriod(Reservation::byStatus(Reservation::STATUS_CONFIRMED))->count(),
            'cancelled'       => $inPeriod(Reservation::whereIn('status', $cancelledStatuses))->count(),

            // Chiffre d'affaires (réservations créées dans la période)
            'revenue_confirmed'    => $this->sumRevenue(
                                          $inPeriod(Reservation::byStatus(Reservation::STATUS_CONFIRMED))
                                      ),
            'revenue_in_progress'  => $this->sumRevenue(
                                          $inPeriod(Reservation::whereIn('status', [
                                              Reservation::STATUS_WAITING_PAYMENT,
                                              Reservation::STATUS_PARTIALLY_PAID,
                                          ]))
                                      ),
            'revenue_total'        => $this->sumRevenue(
                                          $inPeriod(Reservation::whereIn('status', $activeStatuses))
                                      ),

            // ─── Activité opérationnelle (toujours basée sur aujourd'hui / à venir) ──
            // On cherche check_in sur la réservation OU sur les rooms (multi-séjour)
            'arrivals_today'   => Reservation::whereNotIn('status', $cancelledStatuses)
                                      ->where(function ($q) {
                                          $today = now()->toDateString();
                                          $q->whereDate('check_in', $today)
                                            ->orWhereHas('rooms', fn ($rq) =>
                                                $rq->whereDate('check_in', $today)
                                            );
                                      })->count(),
            'departures_today' => Reservation::whereNotIn('status', $cancelledStatuses)
                                      ->where(function ($q) {
                                          $today = now()->toDateString();
                                          $q->whereDate('check_out', $today)
                                            ->orWhereHas('rooms', fn ($rq) =>
                                                $rq->whereDate('check_out', $today)
                                            );
                                      })->count(),
            'arrivals_week'    => Reservation::whereNotIn('status', $cancelledStatuses)
                                      ->where(function ($q) {
                                          $from = now()->toDateString();
                                          $to   = now()->addDays(7)->toDateString();
                                          $q->whereBetween('check_in', [$from, $to])
                                            ->orWhereHas('rooms', fn ($rq) =>
                                                $rq->whereBetween('check_in', [$from, $to])
                                            );
                                      })->count(),

            // Répartition par statut dans la période (pour le mini-graphe en barres)
            'by_status' => $inPeriod(Reservation::query())
                ->selectRaw('status, COUNT(*) as cnt')
                ->groupBy('status')
                ->pluck('cnt', 'status'),
        ];

        // ─── Tableau des demandes (filtres indépendants) ──────────────────────
        $sortable = ['created_at', 'check_in', 'check_out', 'total_price', 'reference'];
        $sort     = in_array($request->query('sort'), $sortable) ? $request->query('sort') : 'created_at';
        $dir      = $request->query('dir') === 'asc' ? 'asc' : 'desc';

        $query = Reservation::with(['hotel', 'rooms'])
            ->orderBy($sort, $dir);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference',    'like', "%{$search}%")
                  ->orWhere('agency_name',  'like', "%{$search}%")
                  ->orWhere('email',         'like', "%{$search}%")
                  ->orWhere('contact_name',  'like', "%{$search}%")
                  ->orWhere('phone',         'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->byStatus($status);
        }

        if ($hotelId = $request->query('hotel_id')) {
            $query->where('hotel_id', $hotelId);
        }

        if ($tariffCode = $request->query('tariff_code')) {
            $query->where('tariff_code', $tariffCode);
        }

        if ($checkInFrom = $request->query('check_in_from')) {
            $query->whereDate('check_in', '>=', $checkInFrom);
        }
        if ($checkInTo = $request->query('check_in_to')) {
            $query->whereDate('check_in', '<=', $checkInTo);
        }

        if ($createdFrom = $request->query('created_from')) {
            $query->whereDate('created_at', '>=', $createdFrom);
        }
        if ($createdTo = $request->query('created_to')) {
            $query->whereDate('created_at', '<=', $createdTo);
        }

        $recent      = $query->paginate(15)->withQueryString();
        $hotels      = Hotel::orderBy('name')->get(['id', 'name']);
        $tariffCodes = Reservation::distinct()->pluck('tariff_code')->filter()->sort()->values();
        $hasFilters  = $request->hasAny([
            'search', 'status', 'hotel_id', 'tariff_code',
            'check_in_from', 'check_in_to', 'created_from', 'created_to',
        ]);

        // Requête AJAX : retourner uniquement le partial du tableau
        if ($request->ajax()) {
            return view('admin.partials.dashboard-table', compact(
                'recent', 'hasFilters', 'statsFrom', 'statsTo', 'sort', 'dir'
            ))->render();
        }

        return view('admin.dashboard', compact(
            'stats', 'recent', 'hotels', 'tariffCodes', 'hasFilters',
            'statsFrom', 'statsTo', 'sort', 'dir'
        ));
    }

    /** Somme total_price + taxe_total sur une query déjà construite. */
    private function sumRevenue($query): float
    {
        $row = (clone $query)
            ->selectRaw('COALESCE(SUM(COALESCE(total_price,0)),0) + COALESCE(SUM(COALESCE(taxe_total,0)),0) AS revenue')
            ->first();

        return (float) ($row?->revenue ?? 0);
    }
}
