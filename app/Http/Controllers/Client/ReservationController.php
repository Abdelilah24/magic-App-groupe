<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\SecureLink;
use App\Services\PricingService;
use App\Services\ReservationService;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationService $reservationService,
        private readonly PricingService     $pricingService,
    ) {}

    /**
     * Affiche le formulaire de demande de réservation.
     */
   public function form(string $token)
{
    $link = SecureLink::where('token', $token)->firstOrFail();

    if (! $link->isValid()) {
        return view('client.link-invalid', ['reason' => $this->getInvalidReason($link)]);
    }

    $hotel       = $link->hotel ?? Hotel::active()->first();
    $roomTypes   = $hotel ? $hotel->activeRoomTypes()->with('activeOccupancyConfigs')->get() : collect();
    $supplements = $hotel ? $hotel->activeSupplements()->get() : collect();

    // 👇 الحل هنا
    $roomTypeConfigs = $roomTypes->mapWithKeys(function ($rt) {
        return [
            $rt->id => $rt->activeOccupancyConfigs->map(function ($c) {
                return [
                    'id'           => $c->id,
                    'code'         => $c->code,
                    'label'        => $c->label,
                    'min_adults'   => $c->min_adults,
                    'max_adults'   => $c->max_adults,
                    'min_children' => $c->min_children,
                    'max_children' => $c->max_children,
                    'min_babies'   => $c->min_babies ?? 0,
                    'max_babies'   => $c->max_babies ?? 0,
                ];
            })
        ];
    });

    return view('client.reservation.form', compact(
        'link',
        'hotel',
        'roomTypes',
        'supplements',
        'token',
        'roomTypeConfigs' // 👈 مهم
    ));
}

    /**
     * Soumet la demande.
     */
    public function store(Request $request, string $token)
    {
        $link = SecureLink::where('token', $token)->firstOrFail();

        if (! $link->isValid()) {
            return redirect()->route('client.form', $token)->with('error', 'Ce lien n\'est plus valide.');
        }

        $data = $request->validate([
            'agency_name'     => 'required|string|max:255',
            'contact_name'    => 'required|string|max:255',
            'email'           => 'required|email',
            'phone'           => 'nullable|string|max:30',
            'hotel_id'        => 'required|exists:hotels,id',
            'special_requests'=> 'nullable|string|max:1000',
            'stays'           => 'required|array|min:1',
            'stays.*.check_in'  => 'required|date|after_or_equal:today',
            'stays.*.check_out' => 'required|date',
            'stays.*.rooms'                    => 'required|array|min:1',
            'stays.*.rooms.*.room_type_id'        => 'required|exists:room_types,id',
            'stays.*.rooms.*.quantity'            => 'required|integer|min:1',
            'stays.*.rooms.*.adults'              => 'nullable|integer|min:0',
            'stays.*.rooms.*.children'            => 'nullable|integer|min:0',
            'stays.*.rooms.*.babies'              => 'nullable|integer|min:0',
            'stays.*.rooms.*.baby_bed'            => 'nullable|boolean',
            'stays.*.rooms.*.occupancy_config_id' => 'nullable|exists:room_occupancy_configs,id',
            // Suppléments optionnels sélectionnés par le client
            'selected_supplements'   => 'nullable|array',
            'selected_supplements.*' => 'nullable|exists:supplements,id',
            // Options flexibles
            'flexible_dates'         => 'nullable|boolean',
            'flexible_hotel'         => 'nullable|boolean',
        ]);

        // ── Vérification capacités par chambre ────────────────────────────────
        $roomTypeIds = collect($data['stays'])
            ->flatMap(fn ($s) => collect($s['rooms'])->pluck('room_type_id'))
            ->unique()->filter()->values()->toArray();

        $roomTypes = RoomType::whereIn('id', $roomTypeIds)->get()->keyBy('id');

        $capacityErrors = [];
        foreach ($data['stays'] as $si => $stay) {
            foreach ($stay['rooms'] as $ri => $room) {
                $rt  = $roomTypes->get($room['room_type_id']);
                if (! $rt) continue;
                $qty = max(1, (int) ($room['quantity'] ?? 1));

                // Capacité totale (adultes + enfants + bébés ≤ max_persons × qty)
                if ($rt->max_persons) {
                    $persons  = ($room['adults'] ?? 0) + ($room['children'] ?? 0) + ($room['babies'] ?? 0);
                    $maxTotal = $rt->max_persons * $qty;
                    if ($persons > $maxTotal) {
                        $capacityErrors["stays.{$si}.rooms.{$ri}.adults"] =
                            "Séjour " . ($si + 1) . " — \"{$rt->name}\" : {$persons} pers. dépassent la capacité max ({$rt->max_persons} × {$qty} = {$maxTotal} max).";
                    }
                }

                // Adultes ≤ max_adults × qty
                if ($rt->max_adults !== null) {
                    $adults   = (int) ($room['adults'] ?? 0);
                    $maxAdult = $rt->max_adults * $qty;
                    if ($adults > $maxAdult) {
                        $capacityErrors["stays.{$si}.rooms.{$ri}.adults"] =
                            "Séjour " . ($si + 1) . " — \"{$rt->name}\" : {$adults} adultes dépassent le max ({$rt->max_adults} × {$qty} = {$maxAdult}).";
                    }
                }

                // Enfants ≤ max_children × qty
                if ($rt->max_children !== null) {
                    $children   = (int) ($room['children'] ?? 0);
                    $maxChild   = $rt->max_children * $qty;
                    if ($children > $maxChild) {
                        $capacityErrors["stays.{$si}.rooms.{$ri}.children"] =
                            "Séjour " . ($si + 1) . " — \"{$rt->name}\" : {$children} enfants dépassent le max ({$rt->max_children} × {$qty} = {$maxChild}).";
                    }
                }

                // Lit bébé non disponible
                if (! empty($room['baby_bed']) && ! $rt->baby_bed_available) {
                    $capacityErrors["stays.{$si}.rooms.{$ri}.baby_bed"] =
                        "Séjour " . ($si + 1) . " — \"{$rt->name}\" : lit bébé non disponible pour ce type de chambre.";
                }
            }
        }

        if (! empty($capacityErrors)) {
            return back()->withErrors($capacityErrors)->withInput();
        }

        // ── Dates globales (min/max de tous les séjours) ──────────────────────
        $allCheckIns  = collect($data['stays'])->pluck('check_in');
        $allCheckOuts = collect($data['stays'])->pluck('check_out');
        $data['check_in']  = $allCheckIns->min();
        $data['check_out'] = $allCheckOuts->max();

        // Total personnes : (adultes + enfants + bébés par chambre) × quantité
        $data['total_persons'] = collect($data['stays'])
            ->flatMap(fn ($s) => $s['rooms'])
            ->sum(fn ($r) => (($r['adults'] ?? 0) + ($r['children'] ?? 0) + ($r['babies'] ?? 0)) * (max(1, (int)($r['quantity'] ?? 1)))) ?: 1;

        $reservation = $this->reservationService->createFromClientForm($data, $link);

        return redirect()->route('client.reservation.show', [
            'token'       => $token,
            'reservation' => $reservation->id,
        ])->with('success', "Votre demande a bien été enregistrée. Référence : {$reservation->reference}");
    }

    /**
     * Affiche une réservation existante au client.
     */
    public function show(string $token, Reservation $reservation)
    {
        $this->authorizeClientAccess($token, $reservation);

        $reservation->load(['hotel', 'rooms.roomType', 'rooms.occupancyConfig', 'statusHistories', 'payments', 'supplements.supplement', 'guestRegistrations']);

        return view('client.reservation.show', compact('reservation', 'token'));
    }

    /**
     * Affiche le formulaire de modification.
     */
    public function editForm(string $token, Reservation $reservation)
    {
        $this->authorizeClientAccess($token, $reservation);

        if (! $reservation->canBeModifiedByClient()) {
            $checkIn = $reservation->check_in instanceof \Carbon\Carbon
                ? $reservation->check_in
                : \Carbon\Carbon::parse($reservation->check_in);

            $daysUntilArrival = now()->startOfDay()->diffInDays($checkIn->copy()->startOfDay(), false);

            $message = $daysUntilArrival <= 7
                ? 'Modification impossible : l\'arrivée est dans moins de 7 jours. Veuillez contacter directement l\'hôtel.'
                : 'Cette réservation ne peut pas être modifiée dans son état actuel.';

            return redirect()
                ->route('client.reservation.show', compact('token', 'reservation'))
                ->with('error', $message);
        }

        if ($reservation->hasOverdueSchedule()) {
            return redirect()
                ->route('client.reservation.show', compact('token', 'reservation'))
                ->with('error', 'Modification impossible : une échéance de paiement est dépassée. Contactez votre gestionnaire.');
        }

        $hotel     = $reservation->hotel;
        $roomTypes = $hotel->activeRoomTypes()->with('activeOccupancyConfigs')->get();

        $roomTypeConfigs = $roomTypes->mapWithKeys(function ($rt) {
            return [$rt->id => $rt->activeOccupancyConfigs->map(function ($cfg) {
                return [
                    'id'           => $cfg->id,
                    'label'        => $cfg->label,
                    'min_adults'   => $cfg->min_adults,
                    'max_adults'   => $cfg->max_adults,
                    'min_children' => $cfg->min_children,
                    'max_children' => $cfg->max_children,
                    'max_babies'   => $cfg->max_babies ?? 0,
                ];
            })->values()->all()];
        });

        $reservation->load('rooms.roomType', 'supplements');

        // IDs des suppléments optionnels déjà choisis par le client
        $selectedSupplementIds = $reservation->supplements
            ->where('is_mandatory', false)
            ->pluck('supplement_id')
            ->values()
            ->all();

        // Grouper les chambres par séjour (check_in/check_out distincts)
        $staysData = $reservation->sejours->map(function ($sejour) {
            return [
                'check_in'  => $sejour['check_in']->format('Y-m-d'),
                'check_out' => $sejour['check_out']->format('Y-m-d'),
                'rooms'     => $sejour['rooms']->map(function ($r) {
                    return [
                        'room_type_id'        => $r->room_type_id,
                        'occupancy_config_id' => $r->occupancy_config_id,
                        'quantity'            => $r->quantity,
                        'adults'              => $r->adults   ?? 1,
                        'children'            => $r->children ?? 0,
                        'babies'              => $r->babies   ?? 0,
                        'baby_bed'            => $r->baby_bed ?? false,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        // Aussi préparer roomTypeCapacity pour les warnings de capacité
        $roomTypeCapacity = $roomTypes->mapWithKeys(function ($rt) {
            return [$rt->id => [
                'min'         => $rt->min_persons ?? 1,
                'max'         => $rt->max_persons ?? 999,
                'maxAdults'   => $rt->max_adults,
                'maxChildren' => $rt->max_children,
                'babyBed'     => (bool) $rt->baby_bed_available,
            ]];
        });

        return view('client.reservation.edit', compact(
            'reservation', 'token', 'hotel', 'roomTypes',
            'roomTypeConfigs', 'staysData', 'roomTypeCapacity',
            'selectedSupplementIds'
        ));
    }

    /**
     * Soumet une demande de modification.
     */
    public function update(Request $request, string $token, Reservation $reservation)
    {
        $this->authorizeClientAccess($token, $reservation);

        if (! $reservation->canBeModifiedByClient()) {
            return back()->with('error', 'Modification impossible : l\'arrivée est dans moins de 7 jours ou la réservation ne peut plus être modifiée.');
        }

        $data = $request->validate([
            'special_requests'                     => 'nullable|string|max:1000',
            'stays'                                => 'required|array|min:1',
            'stays.*.check_in'                     => 'required|date',
            'stays.*.check_out'                    => 'required|date',
            'stays.*.rooms'                        => 'required|array|min:1',
            'stays.*.rooms.*.room_type_id'         => 'required|exists:room_types,id',
            'stays.*.rooms.*.quantity'             => 'required|integer|min:1',
            'stays.*.rooms.*.adults'               => 'nullable|integer|min:0',
            'stays.*.rooms.*.children'             => 'nullable|integer|min:0',
            'stays.*.rooms.*.babies'               => 'nullable|integer|min:0',
            'stays.*.rooms.*.occupancy_config_id'  => 'nullable|exists:room_occupancy_configs,id',
            'selected_supplements'                 => 'nullable|array',
            'selected_supplements.*'               => 'nullable|exists:supplements,id',
        ]);

        // Dates globales = min check_in / max check_out de tous les séjours
        $data['check_in']  = collect($data['stays'])->pluck('check_in')->min();
        $data['check_out'] = collect($data['stays'])->pluck('check_out')->max();

        // Total personnes : (adultes + enfants + bébés par chambre) × quantité
        $data['total_persons'] = collect($data['stays'])
            ->flatMap(fn ($s) => $s['rooms'])
            ->sum(fn ($r) => (($r['adults'] ?? 0) + ($r['children'] ?? 0) + ($r['babies'] ?? 0)) * max(1, (int)($r['quantity'] ?? 1))) ?: 1;

        $this->reservationService->requestModification($reservation, $data);

        return redirect()
            ->route('client.reservation.show', compact('token', 'reservation'))
            ->with('success', 'Votre demande de modification a été soumise. En attente de validation.');
    }

    /**
     * Annuler une réservation.
     */
    public function cancel(Request $request, string $token, Reservation $reservation)
    {
        $this->authorizeClientAccess($token, $reservation);

        if (! $reservation->canBeCancelledByClient()) {
            return back()->with('error', 'Annulation impossible dans l\'état actuel.');
        }

        if ($reservation->hasOverdueSchedule()) {
            return back()->with('error', 'Annulation impossible : une échéance de paiement est dépassée. Contactez votre gestionnaire.');
        }

        $this->reservationService->cancel($reservation, $request->input('reason', ''));

        return redirect()
            ->route('client.reservation.show', compact('token', 'reservation'))
            ->with('success', 'Votre réservation a été annulée.');
    }

    /**
     * AJAX : calcule le prix avant soumission.
     * Détermine automatiquement la grille tarifaire selon l'agence et le volume.
     */
    public function calculatePrice(Request $request)
    {
        $data = $request->validate([
            'hotel_id'   => 'required|exists:hotels,id',
            'check_in'   => 'required|date',
            'check_out'  => 'required|date|after:check_in',
            'rooms'      => 'required|array',
            'rooms.*.room_type_id'        => 'required|exists:room_types,id',
            'rooms.*.quantity'            => 'required|integer|min:1',
            'rooms.*.adults'              => 'nullable|integer|min:0',
            'rooms.*.children'            => 'nullable|integer|min:0',
            'rooms.*.babies'              => 'nullable|integer|min:0',
            'rooms.*.occupancy_config_id' => 'nullable|exists:room_occupancy_configs,id',
            'token'        => 'nullable|string',
            'total_rooms'  => 'nullable|integer|min:1',
            'tariff_code'  => 'nullable|string|max:50',
        ]);

        // ── Résolution de la grille tarifaire ────────────────────────────────
        // Si tariff_code fourni directement (ex: admin), l'utiliser tel quel
        if (! empty($data['tariff_code'])) {
            $tariffCode = $data['tariff_code'];
        } else {
            $agencyStatusSlug = null;
            if (! empty($data['token'])) {
                $link = SecureLink::where('token', $data['token'])
                    ->with('agency.agencyStatus')
                    ->first();
                if ($link && $link->agency) {
                    $agencyStatusSlug = $link->agency->agencyStatus?->slug;
                }
            }
            // Utiliser total_rooms si fourni (multi-séjours) sinon compter les chambres de cette requête
            $totalRooms = isset($data['total_rooms']) ? (int) $data['total_rooms'] : collect($data['rooms'])->sum('quantity');
            $tariffCode = $this->pricingService->determineTariffCode($agencyStatusSlug, $totalRooms);
        }

        try {
            $result = $this->pricingService->calculate(
                $data['hotel_id'],
                $data['check_in'],
                $data['check_out'],
                $data['rooms'],
                0.0,
                $tariffCode
            );
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success'   => true,
            'total'     => $result['total'],
            'nights'    => $result['nights'],
            'breakdown' => $result['breakdown'],
            'missing'   => $result['missing_prices'],
            'formatted' => number_format($result['total'], 2, ',', ' ') . ' MAD',
            // Grille tarifaire appliquée
            'tariff_code' => $result['tariff_code'],
            'tariff_name' => $result['tariff_name'],
            // Taxe de séjour
            'taxe_sejour_rate'   => $result['taxe_sejour_rate'],
            'taxe_sejour_adults' => $result['taxe_sejour_adults'],
            'taxe_sejour_total'  => $result['taxe_sejour_total'],
            'taxe_sejour_formatted' => number_format($result['taxe_sejour_total'], 2, ',', ' ') . ' MAD',
            // Suppléments applicables
            'supplements'        => $result['supplements'],
            'supplement_mandatory_total' => $result['supplement_mandatory_total'],
            // Grand total
            'grand_total'        => $result['grand_total'],
            'grand_total_formatted' => number_format($result['grand_total'], 2, ',', ' ') . ' MAD',
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function authorizeClientAccess(string $token, Reservation $reservation): void
    {
        $link = SecureLink::where('token', $token)->firstOrFail();

        if ($reservation->secure_link_id !== $link->id
            && $reservation->email !== $link->agency_email) {
            abort(403);
        }
    }

    private function getInvalidReason(SecureLink $link): string
    {
        if (! $link->is_active) return 'Ce lien a été désactivé.';
        if ($link->expires_at && $link->expires_at->isPast()) return 'Ce lien a expiré.';
        if ($link->uses_count >= $link->max_uses) return 'Ce lien a déjà été utilisé.';
        return 'Ce lien n\'est plus valide.';
    }
}
