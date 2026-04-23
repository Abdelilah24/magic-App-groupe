<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\ReservationSupplement;
use App\Models\StatusHistory;
use App\Models\Supplement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service de gestion du workflow des réservations.
 */
class ReservationService
{
    public function __construct(
        private readonly PricingService      $pricingService,
        private readonly NotificationService $notificationService,
    ) {}

    // ─── Création ────────────────────────────────────────────────────────────

    /**
     * Crée une nouvelle réservation depuis le formulaire client.
     */
    public function createFromClientForm(array $data, ?\App\Models\SecureLink $secureLink = null): Reservation
    {
        return DB::transaction(function () use ($data, $secureLink) {
            // ── Grille tarifaire : identique à la prévisualisation du formulaire ──
            $agencyStatusSlug = $secureLink?->agency?->agencyStatus?->slug;
            $stays = $data['stays'] ?? [];
            if (empty($stays) && ! empty($data['rooms'])) {
                $stays = [[
                    'check_in'  => $data['check_in'],
                    'check_out' => $data['check_out'],
                    'rooms'     => $data['rooms'],
                ]];
            }
            $totalRooms = collect($stays)
                ->flatMap(fn ($s) => $s['rooms'] ?? [])
                ->sum(fn ($r) => max(1, (int) ($r['quantity'] ?? 1)));
            $tariffCode = $this->pricingService->determineTariffCode($agencyStatusSlug, $totalRooms);

            $reservation = Reservation::create([
                'hotel_id'        => $data['hotel_id'],
                'secure_link_id'  => $secureLink?->id,
                'agency_name'     => $data['agency_name'],
                'contact_name'    => $data['contact_name'],
                'email'           => $data['email'],
                'phone'           => $data['phone'] ?? null,
                'check_in'        => $data['check_in'],
                'check_out'       => $data['check_out'],
                'total_persons'   => $data['total_persons'],
                'special_requests'=> $data['special_requests'] ?? null,
                'flexible_dates'  => ! empty($data['flexible_dates']),
                'flexible_hotel'  => ! empty($data['flexible_hotel']),
                'status'          => Reservation::STATUS_PENDING,
                'tariff_code'     => $tariffCode,
            ]);

            // Créer les lignes de chambres (multi-séjours)
            // Compatibilité ascendante : si l'ancien format flat rooms[] est passé (déjà traité ci-dessus)
            $totalReservationPrice = 0.0;
            $totalTaxePrice        = 0.0;
            foreach ($stays as $stay) {
                // Calculer les prix pour ce séjour avec le MÊME tariff que le formulaire
                $stayRoomsForCalc = array_map(fn ($r) => [
                    'room_type_id'        => $r['room_type_id'],
                    'quantity'            => $r['quantity'],
                    'adults'              => (int) ($r['adults']   ?? 0),
                    'children'            => (int) ($r['children'] ?? 0),
                    'babies'              => (int) ($r['babies']   ?? 0),
                    'occupancy_config_id' => ! empty($r['occupancy_config_id']) ? (int) $r['occupancy_config_id'] : null,
                ], $stay['rooms']);

                $stayCalc = null;
                try {
                    $stayCalc = $this->pricingService->calculate(
                        $data['hotel_id'],
                        is_string($stay['check_in']) ? $stay['check_in'] : \Carbon\Carbon::parse($stay['check_in'])->toDateString(),
                        is_string($stay['check_out']) ? $stay['check_out'] : \Carbon\Carbon::parse($stay['check_out'])->toDateString(),
                        $stayRoomsForCalc,
                        0.0,
                        $tariffCode  // Même grille que la prévisualisation du formulaire
                    );
                } catch (\Exception $e) {
                    // Prix non disponibles pour ce séjour, on laisse null
                }

                // Matcher le breakdown par index d'ordre (même ordre que $stay['rooms'])
                // — évite les collisions quand deux chambres ont le même type+config dans le même séjour
                $breakdownLines = [];
                if ($stayCalc) {
                    $breakdownLines         = array_values($stayCalc['breakdown']);
                    $totalReservationPrice += $stayCalc['total'];
                    $totalTaxePrice        += round($stayCalc['taxe_sejour_total'] ?? 0, 2);
                }

                foreach (array_values($stay['rooms']) as $i => $room) {
                    $configId = ! empty($room['occupancy_config_id']) ? (int) $room['occupancy_config_id'] : null;
                    $line     = $breakdownLines[$i] ?? null;

                    // Charger le label de la config si présent
                    $configLabel = null;
                    if ($configId) {
                        $config = \App\Models\RoomOccupancyConfig::find($configId);
                        $configLabel = $config?->label;
                    }

                    ReservationRoom::create([
                        'reservation_id'       => $reservation->id,
                        'room_type_id'         => $room['room_type_id'],
                        'quantity'             => $room['quantity'],
                        'adults'               => $room['adults'] ?? 1,
                        'children'             => $room['children'] ?? 0,
                        'babies'               => $room['babies'] ?? 0,
                        'baby_bed'             => ! empty($room['baby_bed']),
                        'occupancy_config_id'  => $configId,
                        'occupancy_config_label' => $configLabel,
                        'check_in'             => $stay['check_in'],
                        'check_out'            => $stay['check_out'],
                        'price_per_night'      => $line ? (count($line['night_detail']) > 0 ? $line['night_detail'][0]['unit_price'] : null) : null,
                        'total_price'          => $line ? $line['line_total'] : null,
                        'price_detail'         => $line ? $line['night_detail'] : null,
                    ]);
                }
            }

            // Sauvegarder le prix total et la taxe de séjour
            if ($totalReservationPrice > 0) {
                $reservation->update([
                    'total_price' => round($totalReservationPrice, 2),
                    'taxe_total'  => round($totalTaxePrice, 2),
                ]);
            }

            // Appliquer la remise long séjour AVANT les suppléments (cohérence avec accept())
            // → promo calculée sur le total chambres uniquement
            $this->pricingService->applyLongStayPromo($reservation);
            $reservation->refresh();

            // ── Suppléments : sauvegarder les obligatoires auto-détectés + optionnels sélectionnés ──
            $this->attachClientSupplements($reservation, $data);
            $reservation->refresh();

            // Inclure supplement_total dans total_price dès la création (cohérence avec l'après-acceptation)
            if (($reservation->supplement_total ?? 0) > 0) {
                $reservation->update([
                    'total_price' => round(($reservation->total_price ?? 0) + $reservation->supplement_total, 2),
                ]);
            }

            // Historique
            $this->recordHistory($reservation, null, Reservation::STATUS_PENDING, 'Demande soumise par le client.', 'client');
            \App\Models\ReservationLog::record($reservation, 'created',
                "Réservation créée — {$reservation->reference}",
                [], $this->snapshotReservation($reservation),
                null, 'agency', null, $reservation->contact_name ?? $reservation->agency_name
            );

            // Marquer le lien utilisé
            if ($secureLink) {
                $secureLink->increment('uses_count');
                if ($secureLink->uses_count === 1) {
                    $secureLink->update(['used_at' => now()]);
                }
            }

            // Notification admin
            $this->notificationService->notifyAdminNewReservation($reservation);

            // Email client
            $this->notificationService->sendClientReservationReceived($reservation);

            Log::info("Réservation créée : {$reservation->reference}", ['id' => $reservation->id]);

            return $reservation;
        });
    }

    // ─── Actions Admin ────────────────────────────────────────────────────────

    /**
     * Accepter une réservation et calculer le prix.
     */
    public function accept(Reservation $reservation, User $admin, ?string $notes = null): Reservation
    {
        return DB::transaction(function () use ($reservation, $admin, $notes) {
            $reservation->load('rooms.roomType');

            // Réutiliser la grille tarifaire appliquée à la création (tarif agence préservé)
            $tariffCode = $reservation->tariff_code ?? 'NRF';

            // Grouper les chambres par séjour (check_in + check_out)
            $stayGroups = $reservation->rooms
                ->groupBy(fn ($r) => ($r->check_in?->toDateString() ?? $reservation->check_in->toDateString())
                                   . '_'
                                   . ($r->check_out?->toDateString() ?? $reservation->check_out->toDateString()));

            $totalPrice      = 0.0;
            $totalTaxeAccept = 0.0;
            $allBreakdowns   = [];

            foreach ($stayGroups as $stayRooms) {
                $first    = $stayRooms->first();
                $checkIn  = $first->check_in?->toDateString()  ?? $reservation->check_in->toDateString();
                $checkOut = $first->check_out?->toDateString() ?? $reservation->check_out->toDateString();

                $roomsData = $stayRooms->map(fn ($r) => [
                    'room_type_id'        => $r->room_type_id,
                    'quantity'            => $r->quantity,
                    'adults'              => $r->adults   ?? 1,
                    'children'            => $r->children ?? 0,
                    'babies'              => $r->babies   ?? 0,
                    'occupancy_config_id' => $r->occupancy_config_id,
                ])->toArray();

                $calcResult = $this->pricingService->calculate(
                    $reservation->hotel_id,
                    $checkIn,
                    $checkOut,
                    $roomsData,
                    (float) ($reservation->discount_percent ?? 0),
                    $tariffCode  // Même grille que lors de la création
                );

                // Appliquer les prix aux lignes de ce séjour — par index d'ordre
                // (évite les collisions quand deux chambres ont le même type+config)
                $stayRoomsList = array_values($stayRooms->all());
                foreach (array_values($calcResult['breakdown']) as $i => $line) {
                    $room = $stayRoomsList[$i] ?? null;
                    if (! $room) continue;
                    $room->update([
                        'price_per_night' => count($line['night_detail']) > 0
                            ? $line['night_detail'][0]['unit_price']
                            : null,
                        'total_price'         => $line['line_total'],
                        'price_detail'        => $line['night_detail'],
                        'occupancy_config_id' => $line['occupancy_config_id'] ?? $room->occupancy_config_id,
                    ]);
                }

                $totalPrice      += $calcResult['total'];
                $totalTaxeAccept += round($calcResult['taxe_sejour_total'] ?? 0, 2);
                $allBreakdowns    = array_merge($allBreakdowns, $calcResult['breakdown']);
            }

            // Sauvegarder le total, la taxe et le breakdown sur la réservation
            $reservation->update([
                'total_price'     => round($totalPrice, 2),
                'taxe_total'      => round($totalTaxeAccept, 2),
                'discount_percent'=> 0,
                'price_breakdown' => $allBreakdowns,
            ]);

            // Appliquer la promo long séjour (avant remise groupe)
            $this->pricingService->applyLongStayPromo($reservation);
            $reservation->refresh();

            // Appliquer les suppléments obligatoires
            $reservation->load('rooms');
            $this->pricingService->applyMandatorySupplements($reservation);
            $reservation->refresh();

            // Appliquer la remise groupe (1 gratuit pour 20 payants)
            $reservation->load('rooms.roomType');
            $this->pricingService->applyGroupDiscount($reservation);
            $reservation->refresh();

            // Réintégrer les suppléments optionnels (choisis par le client lors de la demande)
            // applyMandatorySupplements() ne gère que les obligatoires → les optionnels restent en DB
            // mais ne sont pas inclus dans total_price ni supplement_total
            $optionalSupTotal = (float) $reservation->supplements()
                ->where('is_mandatory', false)
                ->sum('total_price');
            if ($optionalSupTotal > 0) {
                $reservation->update([
                    'supplement_total' => round(($reservation->supplement_total ?? 0) + $optionalSupTotal, 2),
                    'total_price'      => round(($reservation->total_price ?? 0) + $optionalSupTotal, 2),
                ]);
                $reservation->refresh();
            }

            // Générer le token de paiement
            $paymentToken = $reservation->generatePaymentToken();

            $reservation->update([
                'status'     => Reservation::STATUS_WAITING_PAYMENT,
                'handled_by' => $admin->id,
                'admin_notes'=> $notes,
            ]);

            $groupDiscount = $reservation->group_discount_amount ?? 0;
            $this->recordHistory(
                $reservation,
                Reservation::STATUS_PENDING,
                Reservation::STATUS_WAITING_PAYMENT,
                $notes ?? 'Réservation acceptée. En attente de paiement.',
                'admin',
                $admin->id,
                $admin->name,
                [
                    'total_price'           => $reservation->total_price,
                    'group_discount_amount' => $groupDiscount,
                ]
            );

            // Log détaillé
            \App\Models\ReservationLog::record($reservation, 'status_changed',
                "Réservation acceptée → En attente de paiement",
                ['status' => Reservation::STATUS_PENDING],
                ['status' => Reservation::STATUS_WAITING_PAYMENT, 'total_price' => $reservation->total_price, 'group_discount' => $groupDiscount],
                $notes, 'admin', $admin->id, $admin->name
            );

            // Email devis (disponibilités, tarifs, conditions, échéancier)
            $this->notificationService->sendQuote($reservation);

            Log::info("Réservation acceptée : {$reservation->reference}", ['admin' => $admin->name]);

            return $reservation->fresh();
        });
    }

    /**
     * Refuser une réservation.
     */
    public function refuse(Reservation $reservation, User $admin, string $reason): Reservation
    {
        return DB::transaction(function () use ($reservation, $admin, $reason) {
            $prevStatus = $reservation->status;

            $reservation->update([
                'status'         => Reservation::STATUS_REFUSED,
                'handled_by'     => $admin->id,
                'refusal_reason' => $reason,
            ]);

            $this->recordHistory(
                $reservation, $prevStatus, Reservation::STATUS_REFUSED,
                $reason, 'admin', $admin->id, $admin->name
            );
            \App\Models\ReservationLog::record($reservation, 'status_changed',
                "Réservation refusée",
                ['status' => $prevStatus], ['status' => Reservation::STATUS_REFUSED],
                $reason, 'admin', $admin->id, $admin->name
            );

            $this->notificationService->sendRefusal($reservation);

            return $reservation->fresh();
        });
    }

    /**
     * Marquer comme payé.
     */
    public function markAsPaid(Reservation $reservation, User $admin, array $paymentData): Reservation
    {
        return DB::transaction(function () use ($reservation, $admin, $paymentData) {
            // Enregistrer le paiement
            \App\Models\Payment::create([
                'reservation_id' => $reservation->id,
                'amount'         => $paymentData['amount'] ?? $reservation->total_price,
                'currency'       => $reservation->currency,
                'method'         => $paymentData['method'] ?? 'bank_transfer',
                'status'         => 'completed',
                'reference'      => $paymentData['reference'] ?? null,
                'notes'          => $paymentData['notes'] ?? null,
                'recorded_by'    => $admin->id,
                'paid_at'        => now(),
            ]);

            $reservation->update([
                'status' => Reservation::STATUS_CONFIRMED,
            ]);

            $this->recordHistory(
                $reservation,
                Reservation::STATUS_WAITING_PAYMENT,
                Reservation::STATUS_CONFIRMED,
                'Paiement reçu et confirmé.',
                'admin', $admin->id, $admin->name,
                ['amount' => $paymentData['amount'] ?? $reservation->total_price]
            );

            \App\Models\ReservationLog::record($reservation, 'payment_validated',
                "Paiement complet — Réservation confirmée",
                [], ['status' => 'confirmed', 'amount' => $paymentData['amount'] ?? $reservation->total_price],
                null, 'admin', $admin->id, $admin->name
            );

            $this->notificationService->sendPaymentConfirmation($reservation);

            return $reservation->fresh();
        });
    }

    // ─── Actions Client ───────────────────────────────────────────────────────

    /**
     * Soumettre une demande de modification.
     */
    public function requestModification(Reservation $reservation, array $modificationData): Reservation
    {
        return DB::transaction(function () use ($reservation, $modificationData) {
            // Snapshot de l'état AVANT modification
            $oldSnapshot = $this->snapshotReservation($reservation);

            $reservation->update([
                'previous_status'   => $reservation->status,
                'status'            => Reservation::STATUS_MODIFICATION_PENDING,
                'modification_data' => $modificationData,
            ]);

            $this->recordHistory(
                $reservation,
                $reservation->previous_status,
                Reservation::STATUS_MODIFICATION_PENDING,
                'Modification demandée par le client.',
                'client'
            );

            // Log avec snapshot avant + données proposées (avec noms résolus)
            \App\Models\ReservationLog::record($reservation, 'modification_requested',
                "Modification demandée par l'agence/client",
                $oldSnapshot,
                ['proposed' => $this->enrichModificationData($modificationData)],
                null, 'agency', null, $reservation->contact_name ?? $reservation->agency_name
            );

            $this->notificationService->notifyAdminModification($reservation);

            return $reservation->fresh();
        });
    }

    /**
     * Accepter une modification (admin).
     */
    public function acceptModification(Reservation $reservation, User $admin): Reservation
    {
        return DB::transaction(function () use ($reservation, $admin) {
            // Snapshot AVANT application de la modification
            $oldSnapshot = $this->snapshotReservation($reservation);

            $modData = $reservation->modification_data;

            // ── Appliquer les nouvelles chambres ─────────────────────────────
            // Supporte le nouveau format (stays[]) et l'ancien format (rooms[])
            $reservation->rooms()->delete();

            $stays = $modData['stays'] ?? null;

            if ($stays) {
                // Nouveau format : séjours avec check_in/check_out individuels
                foreach ($stays as $stay) {
                    foreach ($stay['rooms'] ?? [] as $room) {
                        ReservationRoom::create([
                            'reservation_id'      => $reservation->id,
                            'room_type_id'        => $room['room_type_id'],
                            'quantity'            => $room['quantity'],
                            'adults'              => $room['adults']   ?? 1,
                            'children'            => $room['children'] ?? 0,
                            'babies'              => $room['babies']   ?? 0,
                            'baby_bed'            => $room['baby_bed'] ?? false,
                            'occupancy_config_id' => $room['occupancy_config_id'] ?? null,
                            'check_in'            => $stay['check_in'],
                            'check_out'           => $stay['check_out'],
                        ]);
                    }
                }
            } else {
                // Ancien format : tableau plat de chambres
                foreach ($modData['rooms'] ?? [] as $room) {
                    ReservationRoom::create([
                        'reservation_id'      => $reservation->id,
                        'room_type_id'        => $room['room_type_id'],
                        'quantity'            => $room['quantity'],
                        'adults'              => $room['adults']   ?? 1,
                        'children'            => $room['children'] ?? 0,
                        'babies'              => $room['babies']   ?? 0,
                        'occupancy_config_id' => $room['occupancy_config_id'] ?? null,
                    ]);
                }
            }

            // ── Mettre à jour les données de base ────────────────────────────
            $updates = array_filter([
                'check_in'         => $modData['check_in'] ?? null,
                'check_out'        => $modData['check_out'] ?? null,
                'total_persons'    => $modData['total_persons'] ?? null,
                'special_requests' => $modData['special_requests'] ?? null,
            ]);
            if ($updates) {
                $reservation->update($updates);
            }

            // ── Recalcul prix par séjour ──────────────────────────────────────
            $reservation->refresh();

            // Recalculer la grille tarifaire selon le nombre TOTAL de chambres
            // sur tous les séjours de la modification (même règle qu'à la création)
            $agencyStatusSlugMod = $reservation->secureLink?->agency?->agencyStatus?->slug;
            $totalRoomsMod = $stays
                ? collect($stays)->flatMap(fn ($s) => $s['rooms'] ?? [])
                                 ->sum(fn ($r) => max(1, (int) ($r['quantity'] ?? 1)))
                : collect($modData['rooms'] ?? [])
                                 ->sum(fn ($r) => max(1, (int) ($r['quantity'] ?? 1)));
            $tariffCodeMod = $this->pricingService->determineTariffCode($agencyStatusSlugMod, $totalRoomsMod);
            $totalPriceAll   = 0.0;
            $totalTaxeModAll = 0.0;
            $allBreakdownsMod = [];

            if ($stays) {
                // Recalcul séjour par séjour
                foreach ($stays as $stay) {
                    $stayRoomsInput = collect($stay['rooms'] ?? [])->map(fn ($r) => [
                        'room_type_id'        => $r['room_type_id'],
                        'quantity'            => $r['quantity'],
                        'adults'              => $r['adults']   ?? 1,
                        'children'            => $r['children'] ?? 0,
                        'babies'              => $r['babies']   ?? 0,
                        'occupancy_config_id' => $r['occupancy_config_id'] ?? null,
                    ])->toArray();

                    if (empty($stayRoomsInput)) continue;

                    $calcResult = $this->pricingService->calculate(
                        $reservation->hotel_id,
                        $stay['check_in'],
                        $stay['check_out'],
                        $stayRoomsInput,
                        0.0,
                        $tariffCodeMod
                    );
                    $totalPriceAll   += $calcResult['total'];
                    $totalTaxeModAll += round($calcResult['taxe_sejour_total'] ?? 0, 2);
                    $allBreakdownsMod = array_merge($allBreakdownsMod, $calcResult['breakdown']);

                    // Mettre à jour le prix de chaque chambre (index-based, évite collisions type+config)
                    $dbStayRooms = $reservation->rooms()
                        ->where('check_in', $stay['check_in'])
                        ->where('check_out', $stay['check_out'])
                        ->orderBy('id')
                        ->get();
                    $dbRoomsList = array_values($dbStayRooms->all());
                    foreach (array_values($calcResult['breakdown']) as $i => $line) {
                        $dbRoom = $dbRoomsList[$i] ?? null;
                        if (! $dbRoom) continue;
                        $dbRoom->update([
                            'price_per_night'     => count($line['night_detail'] ?? []) > 0
                                ? $line['night_detail'][0]['unit_price']
                                : null,
                            'total_price'         => $line['line_total'],
                            'price_detail'        => $line['night_detail'] ?? [],
                            'occupancy_config_id' => $line['occupancy_config_id'] ?? $dbRoom->occupancy_config_id,
                        ]);
                    }
                }

                // Sauvegarder hébergement + taxe + tarif sur la réservation
                $reservation->update([
                    'total_price'     => round($totalPriceAll, 2),
                    'taxe_total'      => round($totalTaxeModAll, 2),
                    'price_breakdown' => $allBreakdownsMod,
                    'discount_percent'=> 0,
                    'tariff_code'     => $tariffCodeMod,
                ]);
            } else {
                // Ancien format : un seul séjour
                $freshRooms = $reservation->fresh()->rooms;
                $roomsInput = $freshRooms->map(fn ($r) => [
                    'room_type_id'        => $r->room_type_id,
                    'quantity'            => $r->quantity,
                    'adults'              => $r->adults   ?? 1,
                    'children'            => $r->children ?? 0,
                    'babies'              => $r->babies   ?? 0,
                    'occupancy_config_id' => $r->occupancy_config_id,
                ])->toArray();

                $calcResult = $this->pricingService->calculate(
                    $reservation->hotel_id,
                    $reservation->check_in->toDateString(),
                    $reservation->check_out->toDateString(),
                    $roomsInput,
                    0.0,
                    $tariffCodeMod
                );

                // Mettre à jour le prix de chaque chambre (index-based)
                $roomsList = array_values($freshRooms->all());
                foreach (array_values($calcResult['breakdown']) as $i => $line) {
                    $dbRoom = $roomsList[$i] ?? null;
                    if (! $dbRoom) continue;
                    $dbRoom->update([
                        'price_per_night'     => count($line['night_detail'] ?? []) > 0
                            ? $line['night_detail'][0]['unit_price']
                            : null,
                        'total_price'         => $line['line_total'],
                        'price_detail'        => $line['night_detail'] ?? [],
                        'occupancy_config_id' => $line['occupancy_config_id'] ?? $dbRoom->occupancy_config_id,
                    ]);
                }

                $reservation->update([
                    'total_price'     => round($calcResult['total'], 2),
                    'taxe_total'      => round($calcResult['taxe_sejour_total'] ?? 0, 2),
                    'price_breakdown' => $calcResult['breakdown'],
                    'discount_percent'=> 0,
                    'tariff_code'     => $tariffCodeMod,
                ]);
            }

            // Appliquer la promo long séjour AVANT les suppléments
            // Base = total chambres uniquement (cohérence avec accept() et createFromClientForm())
            $this->pricingService->applyLongStayPromo($reservation);
            $reservation->refresh();

            // ── Recalcul des suppléments obligatoires ────────────────────────
            // Supprimer tous les anciens suppléments puis les recalculer
            // avec la même logique 2-phases que attachClientSupplements(),
            // basée sur les nouvelles chambres issues de la modification.
            $reservation->supplements()->delete();
            $reservation->update(['supplement_total' => 0]);

            // Reconstruire le tableau stays pour l'accumulateur (même format que $data['stays'])
            $staysForSup = [];
            if ($stays) {
                $staysForSup = $stays;
            } else {
                // Ancien format : chambres plates → un séjour unique
                $staysForSup = [[
                    'check_in'  => $modData['check_in'] ?? $reservation->check_in->toDateString(),
                    'check_out' => $modData['check_out'] ?? $reservation->check_out->toDateString(),
                    'rooms'     => $modData['rooms'] ?? [],
                ]];
            }

            $this->attachClientSupplements($reservation, [
                'stays'                => $staysForSup,
                'selected_supplements' => $modData['selected_supplements'] ?? [],
            ]);
            $reservation->refresh();

            // Inclure supplement_total dans total_price (cohérence avec accept() et createFromClientForm())
            if (($reservation->supplement_total ?? 0) > 0) {
                $reservation->update([
                    'total_price' => round($reservation->total_price + $reservation->supplement_total, 2),
                ]);
                $reservation->refresh();
            }

            // Recalculer la remise groupe selon les nouvelles personnes
            // (floor(total/21) — si ≤20 pers → 0 gratuit → remise supprimée automatiquement)
            $reservation->load('rooms.roomType');
            $this->pricingService->applyGroupDiscount($reservation);
            $reservation->refresh();

            // Supprimer les échéances existantes : le montant a changé,
            // l'ancien échéancier est invalide. L'admin devra en créer un nouveau.
            $hadSchedules = $reservation->paymentSchedules()->exists();
            $reservation->paymentSchedules()->delete();

            // Retour au statut approprié (waiting_payment si prix changé)
            $newStatus = Reservation::STATUS_WAITING_PAYMENT;
            $reservation->update([
                'status'            => $newStatus,
                'modification_data' => null,
                'previous_status'   => null,
                'handled_by'        => $admin->id,
            ]);

            $reservation->generatePaymentToken();

            $this->recordHistory(
                $reservation,
                Reservation::STATUS_MODIFICATION_PENDING,
                $newStatus,
                'Modification acceptée. ' . ($hadSchedules ? 'Échéancier précédent supprimé — recréer un nouvel échéancier.' : 'Nouveau paiement requis.'),
                'admin', $admin->id, $admin->name,
                [
                    'new_total'             => $reservation->total_price,
                    'group_discount_amount' => $reservation->group_discount_amount ?? 0,
                ]
            );

            // Log avec snapshot avant/après
            $newSnapshot = $this->snapshotReservation($reservation);
            \App\Models\ReservationLog::record($reservation, 'modification_accepted',
                "Modification acceptée et prix recalculé",
                $oldSnapshot, $newSnapshot,
                null, 'admin', $admin->id, $admin->name
            );

            $this->notificationService->sendModificationAccepted($reservation);

            return $reservation->fresh();
        });
    }

    /**
     * Refuser une modification (admin).
     */
    public function refuseModification(Reservation $reservation, User $admin, string $reason): Reservation
    {
        return DB::transaction(function () use ($reservation, $admin, $reason) {
            $previousStatus = $reservation->previous_status ?? Reservation::STATUS_ACCEPTED;

            $reservation->update([
                'status'            => $previousStatus,
                'modification_data' => null,
                'previous_status'   => null,
            ]);

            $this->recordHistory(
                $reservation,
                Reservation::STATUS_MODIFICATION_PENDING,
                $previousStatus,
                "Modification refusée : {$reason}",
                'admin', $admin->id, $admin->name
            );
            \App\Models\ReservationLog::record($reservation, 'modification_refused',
                "Modification refusée par l'admin",
                [], [],
                $reason, 'admin', $admin->id, $admin->name
            );

            $this->notificationService->sendModificationRefused($reservation, $reason);

            return $reservation->fresh();
        });
    }

    /**
     * Annuler une réservation (client).
     */
    public function cancel(Reservation $reservation, string $reason = ''): Reservation
    {
        $prevStatus = $reservation->status;

        $reservation->update(['status' => Reservation::STATUS_CANCELLED]);

        $this->recordHistory(
            $reservation, $prevStatus, Reservation::STATUS_CANCELLED,
            $reason ?: 'Annulée par le client.', 'client'
        );
        \App\Models\ReservationLog::record($reservation, 'cancelled',
            "Réservation annulée",
            ['status' => $prevStatus], ['status' => Reservation::STATUS_CANCELLED],
            $reason ?: 'Annulée par le client.', 'agency'
        );

        $this->notificationService->notifyAdminCancellation($reservation);

        return $reservation->fresh();
    }

    // ─── Helpers privés ───────────────────────────────────────────────────────

    /**
     * Attache les suppléments à une réservation nouvellement créée :
     * - Les suppléments obligatoires dont la date tombe dans un séjour sont ajoutés automatiquement.
     * - Les suppléments optionnels explicitement sélectionnés par le client sont aussi ajoutés.
     */
    private function attachClientSupplements(Reservation $reservation, array $data): void
    {
        $hotelId = $reservation->hotel_id;

        // Reconstruire la liste des périodes de séjour
        $stays = $data['stays'] ?? [];
        if (empty($stays) && ! empty($data['check_in'])) {
            $stays = [['check_in' => $data['check_in'], 'check_out' => $data['check_out']]];
        }

        // Suppléments optionnels sélectionnés par le client
        $selectedOptionalIds = array_map('intval', $data['selected_supplements'] ?? []);

        // ── Phase 1 : accumuler les personnes par supplément, séjour par séjour ──
        // Un même supplément peut chevaucher plusieurs séjours (ex: soirée le 17/04
        // alors que séjour 1 = 15→18 et séjour 2 = 16→18). Dans ce cas on cumule les
        // personnes de tous les séjours présents ce soir-là avant de créer la ligne DB.
        $accumulator = [];  // supId => ['sup'=>obj, 'adults'=>N, 'children'=>N, 'babies'=>N, 'isMandatory'=>bool]

        foreach ($stays as $stay) {
            $checkIn  = \Carbon\Carbon::parse($stay['check_in']);
            $checkOut = \Carbon\Carbon::parse($stay['check_out']);

            // Personnes uniquement pour CE séjour
            $stayAdults   = 0;
            $stayChildren = 0;
            $stayBabies   = 0;
            foreach ($stay['rooms'] ?? [] as $room) {
                $qty = max(1, (int) ($room['quantity'] ?? 1));
                $stayAdults   += (int) ($room['adults']   ?? 0) * $qty;
                $stayChildren += (int) ($room['children'] ?? 0) * $qty;
                $stayBabies   += (int) ($room['babies']   ?? 0) * $qty;
            }

            $supplements = Supplement::where('hotel_id', $hotelId)
                ->where('is_active', true)
                ->overlapping($checkIn->toDateString(), $checkOut->toDateString())
                ->get();

            foreach ($supplements as $sup) {
                $isMandatory = $sup->isMandatory();
                $isSelected  = in_array($sup->id, $selectedOptionalIds, true);

                if (! $isMandatory && ! $isSelected) continue;

                if (! isset($accumulator[$sup->id])) {
                    $accumulator[$sup->id] = [
                        'sup'         => $sup,
                        'adults'      => 0,
                        'children'    => 0,
                        'babies'      => 0,
                        'isMandatory' => $isMandatory,
                    ];
                }

                // Ajouter les personnes de CE séjour
                $accumulator[$sup->id]['adults']   += $stayAdults;
                $accumulator[$sup->id]['children'] += $stayChildren;
                $accumulator[$sup->id]['babies']   += $stayBabies;
            }
        }

        // ── Phase 2 : créer une ligne DB par supplément avec les totaux cumulés ──
        $supplementRunTotal = 0.0;

        foreach ($accumulator as $acc) {
            $sup      = $acc['sup'];
            $adults   = $acc['adults'];
            $children = $acc['children'];
            $babies   = $acc['babies'];
            $total    = $sup->calculateTotal($adults, $children, $babies);

            ReservationSupplement::create([
                'reservation_id'   => $reservation->id,
                'supplement_id'    => $sup->id,
                'adults_count'     => $adults,
                'children_count'   => $children,
                'babies_count'     => $babies,
                'unit_price_adult' => $sup->price_adult,
                'unit_price_child' => $sup->price_child,
                'unit_price_baby'  => $sup->price_baby,
                'total_price'      => $total,
                'is_mandatory'     => $acc['isMandatory'],
            ]);

            $supplementRunTotal += $total;
        }

        // Mettre à jour supplement_total sur la réservation dès la création
        if ($supplementRunTotal > 0) {
            $reservation->update(['supplement_total' => round($supplementRunTotal, 2)]);
        }
    }

    /**
     * Enrichit modification_data en remplaçant les IDs par des noms lisibles.
     */
    private function enrichModificationData(array $modificationData): array
    {
        // Collecter tous les IDs nécessaires
        $roomTypeIds = [];
        $configIds   = [];
        foreach ($modificationData['stays'] ?? [] as $stay) {
            foreach ($stay['rooms'] ?? [] as $room) {
                if (!empty($room['room_type_id']))        $roomTypeIds[] = $room['room_type_id'];
                if (!empty($room['occupancy_config_id'])) $configIds[]   = $room['occupancy_config_id'];
            }
        }

        // Charger les modèles en une seule requête
        $roomTypes = \App\Models\RoomType::whereIn('id', array_unique($roomTypeIds))
            ->get()->keyBy('id');
        $configs = \App\Models\RoomOccupancyConfig::whereIn('id', array_unique($configIds))
            ->get()->keyBy('id');

        // Reconstruire les stays avec noms
        $enrichedStays = [];
        foreach ($modificationData['stays'] ?? [] as $stay) {
            $enrichedRooms = [];
            foreach ($stay['rooms'] ?? [] as $room) {
                $rt     = $roomTypes[$room['room_type_id'] ?? null] ?? null;
                $cfg    = $configs[$room['occupancy_config_id'] ?? null] ?? null;

                $roomLabel = $cfg
                    ? ($cfg->code . ' — ' . $cfg->occupancy_description)
                    : ($rt?->name ?? ('type #' . ($room['room_type_id'] ?? '?')));

                $enrichedRooms[] = [
                    'room_type'      => $roomLabel,
                    'config_code'    => $cfg?->code,
                    'room_type_name' => $rt?->name ?? '?',
                    'quantity'       => $room['quantity'] ?? 1,
                    'adults'         => $room['adults']   ?? 0,
                    'children'       => $room['children'] ?? 0,
                    'babies'         => $room['babies']   ?? 0,
                ];
            }
            $enrichedStays[] = [
                'check_in'  => isset($stay['check_in'])
                    ? \Carbon\Carbon::parse($stay['check_in'])->format('d/m/Y') : '?',
                'check_out' => isset($stay['check_out'])
                    ? \Carbon\Carbon::parse($stay['check_out'])->format('d/m/Y') : '?',
                'rooms'     => $enrichedRooms,
            ];
        }

        // ── Suppléments ──────────────────────────────────────────────────────
        $supplements = [];

        // 1. Suppléments optionnels sélectionnés par le client
        $selectedIds = $modificationData['selected_supplements'] ?? [];
        if (!empty($selectedIds)) {
            $optSupps = \App\Models\Supplement::whereIn('id', $selectedIds)->get();
            foreach ($optSupps as $sup) {
                $supplements[] = [
                    'title'        => $sup->title,
                    'is_mandatory' => false,
                    'note'         => 'Prix sera recalculé après validation',
                ];
            }
        }

        // 2. Suppléments obligatoires actifs sur l'hôtel qui couvrent les nouvelles dates
        $allCheckIns  = array_column($modificationData['stays'] ?? [], 'check_in');
        $allCheckOuts = array_column($modificationData['stays'] ?? [], 'check_out');
        if (!empty($allCheckIns) && !empty($allCheckOuts)) {
            $newCheckIn  = min($allCheckIns);
            $newCheckOut = max($allCheckOuts);
            // Chercher les suppléments obligatoires de l'hôtel qui tombent dans le séjour proposé
            // (on n'a pas le hotel_id ici, on le prend depuis les room_types)
            if (!empty($roomTypeIds)) {
                $hotelId = $roomTypes->first()?->hotel_id;
                if ($hotelId) {
                    $mandatorySupps = \App\Models\Supplement::where('hotel_id', $hotelId)
                        ->where('status', 'mandatory')
                        ->where('is_active', true)
                        ->where(fn($q) => $q
                            ->whereNull('date_from')
                            ->orWhere(fn($q2) => $q2
                                ->where('date_from', '<=', $newCheckOut)
                                ->where('date_to',   '>=', $newCheckIn)
                            )
                        )->get();
                    foreach ($mandatorySupps as $sup) {
                        $supplements[] = [
                            'title'        => $sup->title,
                            'is_mandatory' => true,
                            'note'         => 'Obligatoire — Prix recalculé après validation',
                        ];
                    }
                }
            }
        }

        return [
            'stays'        => $enrichedStays,
            'supplements'  => $supplements,
        ];
    }

    /**
     * Snapshot lisible d'une réservation (chambres, dates, prix).
     */
    private function snapshotReservation(Reservation $reservation): array
    {
        $reservation->loadMissing(['rooms.roomType', 'rooms.occupancyConfig', 'supplements.supplement']);

        $stayGroups = $reservation->rooms->groupBy(fn($r) =>
            ($r->check_in?->format('Y-m-d') ?? 'x') . '_' . ($r->check_out?->format('Y-m-d') ?? 'x')
        );

        $stays = $stayGroups->map(fn($rooms) => [
            'check_in'  => $rooms->first()->check_in?->format('d/m/Y')  ?? $reservation->check_in?->format('d/m/Y'),
            'check_out' => $rooms->first()->check_out?->format('d/m/Y') ?? $reservation->check_out?->format('d/m/Y'),
            'rooms'     => $rooms->map(fn($r) => [
                // Config d'occupation en priorité, sinon nom du type de chambre
                'room_type'   => $r->occupancyConfig
                    ? ($r->occupancyConfig->code . ' — ' . $r->occupancyConfig->occupancy_description)
                    : ($r->roomType?->name ?? '?'),
                'config_code' => $r->occupancyConfig?->code,
                'room_type_name' => $r->roomType?->name ?? '?',
                'quantity'   => $r->quantity ?? 1,
                'adults'     => $r->adults   ?? 0,
                'children'   => $r->children ?? 0,
                'babies'     => $r->babies   ?? 0,
                'price'      => $r->total_price,
            ])->values()->toArray(),
        ])->values()->toArray();

        $supplements = $reservation->supplements->map(fn($s) => [
            'title'       => $s->supplement?->title ?? 'Supplément',
            'is_mandatory'=> $s->is_mandatory ?? false,
            'total_price' => $s->total_price,
        ])->toArray();

        return [
            'check_in'         => $reservation->check_in?->format('d/m/Y'),
            'check_out'        => $reservation->check_out?->format('d/m/Y'),
            'nights'           => $reservation->nights,
            'total_persons'    => $reservation->total_persons,
            'total_price'      => $reservation->total_price,
            'taxe_total'       => $reservation->taxe_total,
            'supplement_total' => $reservation->supplement_total,
            'stays'            => $stays,
            'supplements'      => $supplements,
            'status'           => $reservation->status,
        ];
    }

    private function recordHistory(
        Reservation $reservation,
        ?string $from,
        string $to,
        string $comment = '',
        string $actorType = 'system',
        ?int $actorId = null,
        ?string $actorName = null,
        array $metadata = []
    ): void {
        StatusHistory::create([
            'reservation_id' => $reservation->id,
            'from_status'    => $from,
            'to_status'      => $to,
            'comment'        => $comment,
            'actor_type'     => $actorType,
            'actor_id'       => $actorId,
            'actor_name'     => $actorName,
            'metadata'       => $metadata ?: null,
        ]);
    }
}
