<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\ReservationSupplement;
use App\Models\RoomPrice;
use App\Models\RoomType;
use App\Models\Supplement;
use App\Models\TariffGrid;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Service de calcul de prix dynamique.
 * Logique type Booking.com : calcul nuit par nuit selon calendrier tarifaire.
 */
class PricingService
{
    /**
     * Calcule le prix complet d'une réservation.
     *
     * @param  int         $hotelId
     * @param  string      $checkIn         (Y-m-d)
     * @param  string      $checkOut        (Y-m-d)
     * @param  array       $rooms           [['room_type_id' => X, 'quantity' => Y, 'adults' => N, ...], ...]
     * @param  float       $discountPercent Remise à appliquer (ex : 10 pour −10 %)
     * @return array       ['total' => float, 'total_before_discount' => float, 'discount_percent' => float,
     *                      'discount_amount' => float, 'breakdown' => [...], 'nights' => int, 'missing_prices' => [...],
     *                      'taxe_sejour_rate' => float, 'taxe_sejour_adults' => int, 'taxe_sejour_total' => float,
     *                      'supplements' => [...], 'supplement_mandatory_total' => float, 'grand_total' => float]
     */
    public function calculate(int $hotelId, string $checkIn, string $checkOut, array $rooms, float $discountPercent = 0.0, ?string $tariffCode = null): array
    {
        $checkInDate  = Carbon::parse($checkIn);
        $checkOutDate = Carbon::parse($checkOut);
        $nights       = $checkInDate->diffInDays($checkOutDate);

        if ($nights <= 0) {
            throw new \InvalidArgumentException('La date de départ doit être après la date d\'arrivée.');
        }

        // ── Grille tarifaire ──────────────────────────────────────────────────
        $tariffGrid = null;
        $allGrids   = [];
        if ($tariffCode && $tariffCode !== 'NRF') {
            $gridsCollection = TariffGrid::where('hotel_id', $hotelId)
                ->where('is_active', true)
                ->get();
            $allGrids   = $gridsCollection->keyBy('id')->all();
            $tariffGrid = $gridsCollection->firstWhere('code', $tariffCode);
        }

        $breakdown    = [];
        $totalGlobal  = 0.0;
        $missingPrices = [];
        $totalAdults  = 0;
        $totalChildren = 0;
        $totalBabies  = 0;

        foreach ($rooms as $roomLine) {
            $roomTypeId         = $roomLine['room_type_id'];
            $quantity           = (int) $roomLine['quantity'];
            $adults             = (int) ($roomLine['adults']   ?? 1);
            $children           = (int) ($roomLine['children'] ?? 0);
            $babies             = (int) ($roomLine['babies']   ?? 0);
            $occupancyConfigId  = $roomLine['occupancy_config_id'] ?? null;

            $roomType = RoomType::with('prices')->find($roomTypeId);

            if (! $roomType || $quantity <= 0) {
                continue;
            }

            // Cumul des personnes pour taxe de séjour et suppléments
            $totalAdults   += $adults   * $quantity;
            $totalChildren += $children * $quantity;
            $totalBabies   += $babies   * $quantity;

            // Résolution automatique de la config d'occupation si pas fournie
            if (! $occupancyConfigId) {
                $matchedConfig     = $roomType->findMatchingOccupancyConfig($adults, $children, $babies);
                $occupancyConfigId = $matchedConfig?->id;
            }

            // Label de l'occupation pour l'affichage
            $occupancyLabel = null;
            if ($occupancyConfigId) {
                $occupancyLabel = $roomType->occupancyConfigs()->find($occupancyConfigId)?->label;
            }

            [$lineTotal, $nightDetail, $missingDates, $rawUnitTotal] = $this->calculateRoomLine(
                $roomType, $checkInDate, $checkOutDate, $quantity, $occupancyConfigId,
                $tariffGrid, $allGrids
            );

            if (! empty($missingDates)) {
                $missingPrices[] = [
                    'room_type'     => $roomType->name,
                    'missing_dates' => $missingDates,
                ];
            }

            $breakdown[] = [
                'room_type_id'        => $roomTypeId,
                'room_type_name'      => $roomType->name,
                'room_type_slug'      => $roomType->slug     ?? '',
                'room_type_capacity'  => $roomType->capacity ?? 99,
                'occupancy_config_id' => $occupancyConfigId,
                'occupancy_label'     => $occupancyLabel ?? $roomType->name,
                'quantity'            => $quantity,
                'nights'              => $nights,
                'night_detail'        => $nightDetail,
                'line_total'          => $lineTotal,
                'unit_price_raw'      => round($rawUnitTotal, 2), // prix par chambre, toutes nuits (= line_total / quantity)
            ];

            $totalGlobal += $lineTotal;
        }

        // Application de la remise
        $discountAmount = $discountPercent > 0
            ? round($totalGlobal * $discountPercent / 100, 2)
            : 0.0;
        $totalAfterDiscount = round($totalGlobal - $discountAmount, 2);

        // ── Taxe de séjour ────────────────────────────────────────────────────
        $hotel          = Hotel::find($hotelId);
        $taxeRate       = $hotel ? (float) ($hotel->taxe_sejour ?? 19.80) : 19.80;
        $taxeTotal      = round($totalAdults * $nights * $taxeRate, 2);

        // ── Suppléments applicables à ce séjour ───────────────────────────────
        $checkInStr  = $checkInDate->toDateString();
        $checkOutStr = $checkOutDate->clone()->subDay()->toDateString(); // dernière nuit

        $applicableSupplements = [];
        $supplementMandatoryTotal = 0.0;

        if ($hotel) {
            $supplements = Supplement::where('hotel_id', $hotelId)
                ->where('is_active', true)
                ->overlapping($checkInStr, $checkOutStr)
                ->orderBy('date_from')
                ->get();

            foreach ($supplements as $sup) {
                $supTotal = $sup->calculateTotal($totalAdults, $totalChildren, $totalBabies);
                $dateLabel = $sup->date_from->eq($sup->date_to)
                    ? $sup->date_from->format('d/m/Y')
                    : $sup->date_from->format('d/m/Y') . ' – ' . $sup->date_to->format('d/m/Y');
                $applicableSupplements[] = [
                    'id'          => $sup->id,
                    'title'       => $sup->title,
                    'date'        => $dateLabel,
                    'date_raw'    => $sup->date_from->toDateString(),
                    'status'      => $sup->status,
                    'is_mandatory'=> $sup->isMandatory(),
                    'price_adult' => $sup->price_adult,
                    'price_child' => $sup->price_child,
                    'price_baby'  => $sup->price_baby,
                    'adults'      => $totalAdults,
                    'children'    => $totalChildren,
                    'babies'      => $totalBabies,
                    'total'       => $supTotal,
                ];
                if ($sup->isMandatory()) {
                    $supplementMandatoryTotal += $supTotal;
                }
            }
        }

        $grandTotal = round($totalAfterDiscount + $taxeTotal + $supplementMandatoryTotal, 2);

        return [
            'total'                     => $totalAfterDiscount,
            'total_before_discount'     => round($totalGlobal, 2),
            'discount_percent'          => $discountPercent,
            'discount_amount'           => $discountAmount,
            'nights'                    => $nights,
            'breakdown'                 => $breakdown,
            'missing_prices'            => $missingPrices,
            'promo_rate'                => 0.0,
            'promo_amount'              => 0.0,
            // Grille tarifaire appliquée
            'tariff_code'               => $tariffCode ?? 'NRF',
            'tariff_name'               => $tariffGrid?->name ?? 'NRF',
            // Taxe de séjour
            'taxe_sejour_rate'          => $taxeRate,
            'taxe_sejour_adults'        => $totalAdults,
            'taxe_sejour_total'         => $taxeTotal,
            // Suppléments
            'supplements'               => $applicableSupplements,
            'supplement_mandatory_total'=> round($supplementMandatoryTotal, 2),
            // Grand total (chambres + taxe + suppléments obligatoires)
            'grand_total'               => $grandTotal,
        ];
    }

    /**
     * Calcule le prix pour un type de chambre sur toute la période.
     * Parcourt chaque nuit individuellement et trouve le tarif applicable.
     * Si occupancyConfigId est fourni, recherche d'abord le tarif spécifique à cette config.
     */
    private function calculateRoomLine(
        RoomType $roomType,
        Carbon $checkIn,
        Carbon $checkOut,
        int $quantity,
        ?int $occupancyConfigId = null,
        ?TariffGrid $tariffGrid = null,
        array $allGrids = []
    ): array {
        $nightDetail   = [];
        $lineTotal     = 0.0;
        $rawUnitTotal  = 0.0; // prix exact par chambre (sans arrondi), pour affichage
        $missingDates  = [];

        // Période = chaque nuit (dernière nuit = nuit du checkOut-1)
        $current = $checkIn->copy();
        while ($current->lt($checkOut)) {
            $dateStr = $current->toDateString();

            $price = $this->getPriceForNight($roomType->id, $dateStr, $occupancyConfigId);

            if ($price === null) {
                $missingDates[] = $dateStr;
                $current->addDay();
                continue;
            }

            // Appliquer la grille tarifaire si fournie
            if ($tariffGrid !== null) {
                $price = $tariffGrid->calculatePrice($price, $allGrids);
            }

            $rawUnitTotal += $price; // prix exact par chambre par nuit (= valeur DB)

            $nightTotal = $price * $quantity;
            $lineTotal += $nightTotal;

            $nightDetail[] = [
                'date'       => $dateStr,
                'unit_price' => $price,
                'quantity'   => $quantity,
                'subtotal'   => $nightTotal,
            ];

            $current->addDay();
        }

        return [$lineTotal, $nightDetail, $missingDates, $rawUnitTotal];
    }

    /**
     * Récupère le prix pour une nuit donnée.
     * Les tarifs sont uniquement définis par config d'occupation — pas de fallback sur le type de chambre.
     */
    private function getPriceForNight(int $roomTypeId, string $date, ?int $occupancyConfigId = null): ?float
    {
        // Sans config d'occupation → pas de tarif possible
        if (! $occupancyConfigId) {
            return null;
        }

        $price = RoomPrice::where('room_type_id', $roomTypeId)
            ->where('occupancy_config_id', $occupancyConfigId)
            ->where('date_from', '<=', $date)
            ->where('date_to',   '>=', $date)
            ->where('is_active', true)
            ->orderByDesc('date_from')
            ->first();

        return $price?->price_per_night;
    }

    /**
     * Applique le résultat du calcul à une réservation existante.
     * Met à jour total_price, price_detail et occupancy_config_id sur chaque ReservationRoom.
     */
    public function applyToReservation(Reservation $reservation, array $calcResult): void
    {
        $reservation->update([
            'total_price'          => $calcResult['total'],
            'discount_percent'     => $calcResult['discount_percent'] ?? 0,
            'price_breakdown'      => $calcResult['breakdown'],
            'promo_discount_rate'  => $calcResult['promo_rate']   ?? 0,
            'promo_discount_amount'=> $calcResult['promo_amount'] ?? 0,
        ]);

        foreach ($calcResult['breakdown'] as $line) {
            $query = ReservationRoom::where('reservation_id', $reservation->id)
                ->where('room_type_id', $line['room_type_id']);

            // Si la config d'occupation est précisée, cibler la bonne ligne
            if (! empty($line['occupancy_config_id'])) {
                $query->where('occupancy_config_id', $line['occupancy_config_id']);
            }

            // price_per_night = prix moyen par nuit par chambre (= unit_price_raw / nights)
            // Evite d'afficher uniquement le tarif de la 1ère nuit pour les séjours à tarif variable
            $nights       = max(1, count($line['night_detail']));
            $avgPpn       = isset($line['unit_price_raw']) && $nights > 0
                ? round($line['unit_price_raw'] / $nights, 2)
                : (count($line['night_detail']) > 0 ? $line['night_detail'][0]['unit_price'] : null);

            $query->update([
                'price_per_night'     => $avgPpn,
                'total_price'         => $line['line_total'],
                'price_detail'        => $line['night_detail'],
                'occupancy_config_id' => $line['occupancy_config_id'] ?? null,
            ]);
        }
    }

    // ─── Promo long séjour ────────────────────────────────────────────────────

    /**
     * Applique la promo long séjour à une réservation déjà tarifée.
     * La remise s'applique sur le total chambres (avant remise groupe).
     */
    /**
     * Applique la remise long séjour par séjour individuel.
     * Chaque séjour a son propre nombre de nuits → son propre taux de remise.
     * Ex: Séjour 1 = 2 nuits → pas de remise
     *     Séjour 2 = 4 nuits → 10%
     *     Séjour 3 = 13 nuits → 15%
     */
    public function applyLongStayPromo(Reservation $reservation): float
    {
        $hotel = $reservation->hotel ?? Hotel::find($reservation->hotel_id);
        if (! $hotel || ! $hotel->promo_long_stay_enabled) {
            $reservation->update(['promo_discount_rate' => 0, 'promo_discount_amount' => 0]);
            return 0.0;
        }

        // Forcer le rechargement pour avoir les chambres à jour
        // (evite le cache mémoire après delete/recreate dans acceptModification)
        $reservation->load('rooms');

        // Grouper les chambres par séjour (check_in_check_out)
        $stayGroups = $reservation->rooms->groupBy(fn($r) =>
            ($r->check_in?->format('Y-m-d')  ?? $reservation->check_in->format('Y-m-d')) . '_' .
            ($r->check_out?->format('Y-m-d') ?? $reservation->check_out->format('Y-m-d'))
        );

        $totalPromoAmount = 0.0;

        foreach ($stayGroups as $rooms) {
            $first    = $rooms->first();
            $checkIn  = $first->check_in  ?? $reservation->check_in;
            $checkOut = $first->check_out ?? $reservation->check_out;
            $nights   = (int) $checkIn->diffInDays($checkOut);

            $promoRate = $hotel->getPromoRate($nights);
            if ($promoRate <= 0) continue;

            // Base = somme des prix des chambres de CE séjour uniquement
            $stayRoomsTotal = (float) $rooms->sum(fn($r) => $r->total_price ?? 0);
            if ($stayRoomsTotal <= 0) continue;

            $totalPromoAmount += round($stayRoomsTotal * $promoRate / 100, 2);
        }

        if ($totalPromoAmount <= 0) {
            $reservation->update(['promo_discount_rate' => 0, 'promo_discount_amount' => 0]);
            return 0.0;
        }

        $base         = (float) ($reservation->total_price ?? 0);
        $newTotal     = max(0, round($base - $totalPromoAmount, 2));
        // Taux effectif global (pour affichage synthétique)
        $effectiveRate = $base > 0 ? round($totalPromoAmount / $base * 100, 2) : 0;

        $reservation->update([
            'promo_discount_rate'   => $effectiveRate,
            'promo_discount_amount' => round($totalPromoAmount, 2),
            'total_price'           => $newTotal,
        ]);

        return $totalPromoAmount;
    }

    // ─── Suppléments ─────────────────────────────────────────────────────────

    /**
     * Calcule et attache les suppléments obligatoires d'un hôtel à une réservation.
     * Détecte les suppléments dont la date tombe dans la période de séjour.
     * Retourne le total des suppléments ajoutés.
     */
    public function applyMandatorySupplements(Reservation $reservation): float
    {
        if (! $reservation->relationLoaded('rooms')) {
            $reservation->load('rooms');
        }

        // Supprimer les anciens suppléments obligatoires
        $reservation->supplements()->whereHas('supplement', fn ($q) => $q->where('status', 'mandatory'))->delete();

        // Récupérer les suppléments obligatoires actifs de l'hôtel
        // Grouper les séjours distincts de la réservation
        $stayPeriods = $reservation->rooms->map(function ($room) use ($reservation) {
            return [
                'check_in'  => ($room->check_in  ?? $reservation->check_in)->toDateString(),
                'check_out' => ($room->check_out ?? $reservation->check_out)->toDateString(),
            ];
        })->unique(fn ($p) => $p['check_in'] . '_' . $p['check_out'])->values();

        // Récupérer tous les suppléments obligatoires qui chevauchent au moins un séjour
        // Passer la "dernière nuit" (check_out - 1 jour) — cohérent avec PricingService::calculate()
        $mandatorySupplements = collect();
        foreach ($stayPeriods as $period) {
            $lastNight = \Carbon\Carbon::parse($period['check_out'])->subDay()->toDateString();
            $found = Supplement::where('hotel_id', $reservation->hotel_id)
                ->where('status', 'mandatory')
                ->where('is_active', true)
                ->overlapping($period['check_in'], $lastNight)
                ->get();
            $mandatorySupplements = $mandatorySupplements->merge($found);
        }
        $mandatorySupplements = $mandatorySupplements->unique('id');

        $supplementTotal = 0.0;

        foreach ($mandatorySupplements as $supplement) {
            // Compter uniquement les personnes des chambres dont le séjour
            // chevauche les dates du supplément (date_from / date_to)
            $suppFrom = $supplement->date_from; // Carbon date
            $suppTo   = $supplement->date_to;   // Carbon date

            $adults   = 0;
            $children = 0;
            $babies   = 0;

            foreach ($reservation->rooms as $room) {
                $roomIn  = $room->check_in  ?? $reservation->check_in;
                $roomOut = $room->check_out ?? $reservation->check_out;
                $qty     = max(1, (int) ($room->quantity ?? 1));

                // Chevauchement (lastNight approach) :
                // - suppFrom <= lastNight  (supplément actif pendant la dernière nuit)
                // - suppTo   >= roomIn     (supplément actif dès l'arrivée)
                $lastNight = $roomOut->copy()->subDay();
                $overlaps = (! $suppFrom || ! $suppTo)
                    || ($suppFrom->lte($lastNight) && $suppTo->gte($roomIn));

                if ($overlaps) {
                    $adults   += ($room->adults   ?? 0) * $qty;
                    $children += ($room->children ?? 0) * $qty;
                    $babies   += ($room->babies   ?? 0) * $qty;
                }
            }

            $total = $supplement->calculateTotal($adults, $children, $babies);

            ReservationSupplement::create([
                'reservation_id'   => $reservation->id,
                'supplement_id'    => $supplement->id,
                'adults_count'     => $adults,
                'children_count'   => $children,
                'babies_count'     => $babies,
                'unit_price_adult' => $supplement->price_adult,
                'unit_price_child' => $supplement->price_child,
                'unit_price_baby'  => $supplement->price_baby,
                'total_price'      => $total,
                'is_mandatory'     => true,
            ]);

            $supplementTotal += $total;
        }

        // Mettre à jour le total de la réservation
        if ($supplementTotal > 0) {
            $reservation->update([
                'supplement_total' => round($supplementTotal, 2),
                'total_price'      => round((float) ($reservation->total_price ?? 0) + $supplementTotal, 2),
            ]);
        }

        return round($supplementTotal, 2);
    }

    /**
     * Formate le résumé du prix pour affichage.
     */
    public function formatBreakdown(array $breakdown, string $currency = 'MAD'): array
    {
        return array_map(function ($line) use ($currency) {
            return [
                ...$line,
                'line_total_formatted' => number_format($line['line_total'], 2, ',', ' ') . ' ' . $currency,
            ];
        }, $breakdown);
    }

    // ─── Grille tarifaire ─────────────────────────────────────────────────────

    /**
     * Détermine le code de la grille tarifaire à appliquer selon :
     * - le statut de l'agence (slug)
     * - le nombre total de chambres demandées
     *
     * Règles :
     *   agence-de-voyages         → AVM
     *   groupe ET chambres ≥ 11   → GROUPE_DIRECT
     *   groupe ET chambres < 11   → NRF  (base)
     *   tous les autres cas       → NRF  (base)
     *
     * @param  string|null $agencyStatusSlug  Slug du statut de l'agence (ou null)
     * @param  int         $totalRooms        Nombre total de chambres (sum des quantités)
     * @return string      Code de la grille (ex: 'NRF', 'AVM', 'GROUPE_DIRECT')
     */
    public function determineTariffCode(?string $agencyStatusSlug, int $totalRooms): string
    {
        // Le volume prime sur le statut agence :
        // ≥ 11 chambres → GROUPE_DIRECT quelle que soit l'agence (AVM ou autre)
        if ($totalRooms >= 11) {
            return 'GROUPE_DIRECT';
        }

        // < 11 chambres : tarif selon le statut agence
        if ($agencyStatusSlug === 'agence-de-voyages') {
            return 'AVM';
        }

        return 'NRF';
    }

}
