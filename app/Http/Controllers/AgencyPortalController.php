<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\SecureLink;
use App\Services\PricingService;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AgencyPortalController extends Controller
{
    private function agency()
    {
        return auth('agency')->user();
    }

    /**
     * Tableau de bord principal.
     */
    public function dashboard()
    {
        $agency = $this->agency();

        // 3 façons de lier une réservation à cette agence :
        // 1. agency_id direct
        // 2. via secure_link_id → secure_links.agency_id
        // 3. reservations.email = agency.email (réservations anciennes / lien sans agency_id)
        $reservations = Reservation::where(function ($q) use ($agency) {
            $q->where('agency_id', $agency->id)
              ->orWhereHas('secureLink', fn ($sq) => $sq->where('agency_id', $agency->id))
              ->orWhere('email', $agency->email);
        })
            ->with(['hotel', 'rooms.roomType', 'payments', 'paymentSchedules.payment', 'guestRegistrations', 'supplements.supplement'])
            ->latest()
            ->get();

        $secureLinks = $agency->secureLinks()
            ->with('hotel')
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->get();

        // Stats rapides
        $stats = [
            'total'        => $reservations->count(),
            'active'       => $reservations->whereNotIn('status', ['cancelled', 'refused'])->count(),
            'pending_pay'  => $reservations->whereIn('status', ['waiting_payment', 'accepted'])->count(),
            'confirmed'    => $reservations->whereIn('status', ['confirmed', 'paid'])->count(),
            'total_paid'   => $reservations->sum(fn ($r) => $r->payments->where('status', 'completed')->sum('amount')),
        ];

        $hotels = Hotel::where('is_active', true)->orderBy('name')->get();

        // Données pour le formulaire de réservation intégré (pré-sérialisées pour le JS)
        $roomTypesByHotel = $hotels->mapWithKeys(function ($h) {
            $roomTypes = RoomType::where('hotel_id', $h->id)
                ->where('is_active', true)
                ->orderBy('created_at', 'asc')
                ->with(['activeOccupancyConfigs' => fn ($q) => $q->orderBy('sort_order')])
                ->get();
            return [$h->id => $roomTypes];
        });

        // Capacité par type de chambre, indexée par hotel_id puis room_type_id
        $roomTypeCapacityByHotel = [];
        foreach ($roomTypesByHotel as $hotelId => $roomTypes) {
            $roomTypeCapacityByHotel[$hotelId] = [];
            foreach ($roomTypes as $rt) {
                $roomTypeCapacityByHotel[$hotelId][$rt->id] = [
                    'min'         => $rt->min_persons ?? 1,
                    'max'         => $rt->max_persons ?? 999,
                    'maxAdults'   => $rt->max_adults,
                    'maxChildren' => $rt->max_children,
                    'babyBed'     => (bool) $rt->baby_bed_available,
                ];
            }
        }

        // Configs d'occupation par hotel_id — tableau ORDONNÉ (pas d'objet indexé par id,
        // car JS réordonnerait les clés numériques par ordre croissant).
        // Structure : [ ['room_type_id' => X, 'configs' => [...]], ... ]
        $roomTypeConfigsByHotel = [];
        foreach ($roomTypesByHotel as $hotelId => $roomTypes) {
            $roomTypeConfigsByHotel[$hotelId] = [];
            foreach ($roomTypes as $rt) {
                $configs = $rt->activeOccupancyConfigs->map(function ($c) use ($rt) {
                    return [
                        'id'             => $c->id,
                        'code'           => $c->code,
                        'label'          => $c->label,
                        'room_type_name' => $rt->name,
                        'min_adults'     => $c->min_adults,
                        'max_adults'     => $c->max_adults,
                        'min_children'   => $c->min_children,
                        'max_children'   => $c->max_children,
                        'min_babies'     => $c->min_babies ?? 0,
                        'max_babies'     => $c->max_babies ?? 0,
                    ];
                })->values()->all();

                // Si pas de configs d'occupation, on ajoute une entrée simple
                if (empty($configs)) {
                    $configs = [[
                        'id'             => null,
                        'code'           => null,
                        'label'          => $rt->name,
                        'room_type_name' => $rt->name,
                        'min_adults'     => $rt->min_persons ?? 1,
                        'max_adults'     => $rt->max_adults ?? 20,
                        'min_children'   => 0,
                        'max_children'   => $rt->max_children ?? 10,
                        'min_babies'     => 0,
                        'max_babies'     => 5,
                    ]];
                }

                $roomTypeConfigsByHotel[$hotelId][] = [
                    'room_type_id' => $rt->id,
                    'configs'      => $configs,
                ];
            }
        }

        return view('agencies.portal', compact(
            'agency', 'reservations', 'secureLinks', 'stats', 'hotels',
            'roomTypesByHotel', 'roomTypeCapacityByHotel', 'roomTypeConfigsByHotel'
        ));
    }

    /**
     * L'agence génère elle-même un lien de réservation.
     */
    public function generateLink(Request $request)
    {
        $agency = $this->agency();

        abort_if($agency->status !== 'approved', 403, 'Votre compte doit être approuvé pour générer des liens.');

        $data = $request->validate([
            'hotel_id'        => 'required|exists:hotels,id',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);

        SecureLink::create([
            'token'         => Str::random(64),
            'agency_id'     => $agency->id,
            'agency_name'   => $agency->name,
            'agency_email'  => $agency->email,
            'contact_name'  => $agency->contact_name,
            'contact_phone' => $agency->phone,
            'hotel_id'      => $data['hotel_id'],
            'expires_at'    => isset($data['expires_in_days'])
                ? now()->addDays((int) $data['expires_in_days'])
                : null,
            'max_uses'      => 50,
            'is_active'     => true,
        ]);

        return redirect()->route('agency.portal.dashboard')
            ->with('success', 'Lien de réservation généré avec succès.');
    }

    /**
     * Formulaire fiche de police (agence).
     * Utilise le rendu client pour ≤ 300 personnes, paginé au-delà.
     */
    public function guestForm(Request $request, Reservation $reservation)
    {
        $agency = $this->agency();
        $belongs = $reservation->agency_id === $agency->id
            || ($reservation->secureLink && $reservation->secureLink->agency_id === $agency->id)
            || $reservation->email === $agency->email;
        abort_if(! $belongs, 403);

        // Les fiches de police sont accessibles dès le premier paiement
        $policeStatuses = [
            \App\Models\Reservation::STATUS_PARTIALLY_PAID,
            \App\Models\Reservation::STATUS_PAID,
            \App\Models\Reservation::STATUS_CONFIRMED,
        ];
        if (! in_array($reservation->status, $policeStatuses)) {
            return redirect()->route('agency.portal.show-reservation', $reservation)
                ->with('error', 'Les fiches de police ne peuvent être remplies qu\'après le premier paiement.');
        }

        $reservation->load([
            'hotel:id,name',
            'rooms:id,reservation_id,room_type_id,quantity,adults,children,babies,check_in,check_out',
            'rooms.roomType:id,name',
            'guestRegistrations',
        ]);

        $formAction     = route('agency.portal.save-guests', $reservation);
        $autosaveAction = route('agency.portal.autosave-guests', $reservation);
        $backUrl        = route('agency.portal.show-reservation', $reservation);
        $existing       = $reservation->guestRegistrations->keyBy('guest_index');
        $countries      = $this->guestCountryList();

        // Compter les personnes totales
        $totalPersons = $reservation->rooms->sum(
            fn($r) => ((int)($r->adults ?? 0) + (int)($r->children ?? 0) + (int)($r->babies ?? 0)) * max(1, (int)($r->quantity ?? 1))
        );

        // ──────────────────────────────────────────────────────────────────────
        // Vue complète (≤ 300 personnes) — même rendu que le formulaire client
        // ──────────────────────────────────────────────────────────────────────
        if ($totalPersons <= 300) {
            $idx          = 1;
            $groupedSlots = [];
            $allSlots     = [];

            $stayGroups = $reservation->rooms->groupBy(function ($room) use ($reservation) {
                $ci = ($room->check_in  ?? $reservation->check_in)?->format('Y-m-d')  ?? '';
                $co = ($room->check_out ?? $reservation->check_out)?->format('Y-m-d') ?? '';
                return $ci . '_' . $co;
            });

            $sejour_index = 0;
            foreach ($stayGroups as $rooms) {
                $sejour_index++;
                $first    = $rooms->first();
                $checkIn  = $first->check_in  ?? $reservation->check_in;
                $checkOut = $first->check_out ?? $reservation->check_out;

                $sejour   = ['sejour_index' => $sejour_index, 'check_in' => $checkIn, 'check_out' => $checkOut, 'rooms' => []];
                $room_num = 0;

                foreach ($rooms as $room) {
                    $qty = max(1, (int)($room->quantity ?? 1));
                    for ($q = 0; $q < $qty; $q++) {
                        $room_num++;
                        $roomSlots = [];
                        $typeName  = $room->roomType->name ?? 'Chambre';
                        for ($i = 0; $i < (int)($room->adults   ?? 0); $i++) { $s=['index'=>$idx++,'type'=>'adult','label'=>'Adulte '.($i+1)];  $roomSlots[]=$s; $allSlots[]=$s; }
                        for ($i = 0; $i < (int)($room->children ?? 0); $i++) { $s=['index'=>$idx++,'type'=>'child','label'=>'Enfant '.($i+1)];  $roomSlots[]=$s; $allSlots[]=$s; }
                        for ($i = 0; $i < (int)($room->babies   ?? 0); $i++) { $s=['index'=>$idx++,'type'=>'baby', 'label'=>'Bébé '.($i+1)];    $roomSlots[]=$s; $allSlots[]=$s; }
                        if (!empty($roomSlots)) {
                            $sejour['rooms'][] = ['room'=>$room,'room_num'=>$room_num,'room_label'=>$typeName.' '.$room_num,'slots'=>$roomSlots];
                        }
                    }
                }
                if (!empty($sejour['rooms'])) $groupedSlots[] = $sejour;
            }

            $slots = $allSlots;
            return view('client.guests.form', compact(
                'reservation', 'groupedSlots', 'slots', 'existing', 'formAction', 'autosaveAction', 'backUrl', 'countries'
            ) + ['token' => null, 'showAge' => false]);
        }

        // ──────────────────────────────────────────────────────────────────────
        // Vue paginée (> 300 personnes) — 50 personnes par page
        // Construit groupedSlots avec contexte séjour/chambre pour chaque page
        // ──────────────────────────────────────────────────────────────────────
        $perPage  = 50;
        $page     = max(1, (int) $request->query('page', 1));
        $idx      = 1;

        // Construire la liste complète avec contexte séjour/chambre
        $allSlotsFull = [];
        $stayGroupsPaged = $reservation->rooms->groupBy(function ($r) use ($reservation) {
            $ci = ($r->check_in  ?? $reservation->check_in)?->format('Y-m-d')  ?? '';
            $co = ($r->check_out ?? $reservation->check_out)?->format('Y-m-d') ?? '';
            return $ci . '_' . $co;
        });

        $sIdx = 0;
        foreach ($stayGroupsPaged as $sRooms) {
            $sIdx++;
            $sFirst   = $sRooms->first();
            $sCheckIn = $sFirst->check_in  ?? $reservation->check_in;
            $sCheckOut= $sFirst->check_out ?? $reservation->check_out;
            $rNum     = 0;
            foreach ($sRooms as $room) {
                $qty = max(1, (int)($room->quantity ?? 1));
                for ($q = 0; $q < $qty; $q++) {
                    $rNum++;
                    $typeName = $room->roomType->name ?? 'Chambre';
                    $roomCtx  = ['sejour_index'=>$sIdx,'check_in'=>$sCheckIn,'check_out'=>$sCheckOut,'room_num'=>$rNum,'room_label'=>$typeName.' '.$rNum,'room'=>$room];
                    for ($i = 0; $i < (int)($room->adults   ?? 0); $i++) { $allSlotsFull[] = array_merge($roomCtx, ['index'=>$idx++,'type'=>'adult','label'=>'Adulte '.($i+1)]); }
                    for ($i = 0; $i < (int)($room->children ?? 0); $i++) { $allSlotsFull[] = array_merge($roomCtx, ['index'=>$idx++,'type'=>'child','label'=>'Enfant '.($i+1)]); }
                    for ($i = 0; $i < (int)($room->babies   ?? 0); $i++) { $allSlotsFull[] = array_merge($roomCtx, ['index'=>$idx++,'type'=>'baby', 'label'=>'Bébé '.($i+1)]); }
                }
            }
        }

        $totalSlots = count($allSlotsFull);
        $totalPages = max(1, (int) ceil($totalSlots / $perPage));
        $page       = min($page, $totalPages);
        $pageSlots  = array_slice($allSlotsFull, ($page - 1) * $perPage, $perPage);

        // Re-grouper les slots de la page par séjour → chambre
        $groupedSlots = [];
        foreach ($pageSlots as $slot) {
            $sKey = $slot['sejour_index'];
            $rKey = $slot['room_num'];
            if (!isset($groupedSlots[$sKey])) {
                $groupedSlots[$sKey] = ['sejour_index'=>$slot['sejour_index'],'check_in'=>$slot['check_in'],'check_out'=>$slot['check_out'],'rooms'=>[]];
            }
            if (!isset($groupedSlots[$sKey]['rooms'][$rKey])) {
                $groupedSlots[$sKey]['rooms'][$rKey] = ['room'=>$slot['room'],'room_num'=>$slot['room_num'],'room_label'=>$slot['room_label'],'slots'=>[]];
            }
            $groupedSlots[$sKey]['rooms'][$rKey]['slots'][] = ['index'=>$slot['index'],'type'=>$slot['type'],'label'=>$slot['label']];
        }
        // Réindexer pour @foreach Blade
        $groupedSlots = array_values(array_map(fn($s) => array_merge($s, ['rooms'=>array_values($s['rooms'])]), $groupedSlots));
        $slots        = $pageSlots;
        $filledCount  = $existing->filter(fn($g) => $g->isComplete())->count();

        return view('agencies.guest-form', compact(
            'reservation', 'groupedSlots', 'slots', 'existing', 'formAction', 'autosaveAction', 'backUrl',
            'countries', 'page', 'totalPages', 'totalSlots', 'filledCount'
        ));
    }

    /**
     * Enregistrer les fiches de police (agence).
     * _action=draft  → brouillon, pas de validation stricte
     * _action=submit → validation complète des champs obligatoires
     */
    public function saveGuests(Request $request, Reservation $reservation)
    {
        $agency = $this->agency();
        $belongs = $reservation->agency_id === $agency->id
            || ($reservation->secureLink && $reservation->secureLink->agency_id === $agency->id)
            || $reservation->email === $agency->email;
        abort_if(! $belongs, 403);

        $policeStatuses = [
            \App\Models\Reservation::STATUS_PARTIALLY_PAID,
            \App\Models\Reservation::STATUS_PAID,
            \App\Models\Reservation::STATUS_CONFIRMED,
        ];
        abort_if(! in_array($reservation->status, $policeStatuses), 403,
            'Les fiches de police ne peuvent être enregistrées qu\'après le premier paiement.');

        $isDraft = $request->input('_action', 'draft') !== 'submit';

        // Validation complète uniquement en mode soumission finale
        if (! $isDraft) {
            $guests    = $request->input('guests', []);
            $errors    = [];
            $required  = ['nom', 'prenom', 'date_naissance', 'nationalite', 'numero_document'];
            foreach ($guests as $index => $data) {
                $filled = array_filter($data, fn($v) => filled($v));
                // Ignorer les lignes complètement vides
                if (empty($filled) || (count($filled) === 1 && isset($filled['type']))) continue;
                foreach ($required as $field) {
                    if (empty($data[$field])) {
                        $label = ['nom'=>'Nom','prenom'=>'Prénom','date_naissance'=>'Date de naissance','nationalite'=>'Nationalité','numero_document'=>'N° document'][$field];
                        $errors[] = "Voyageur #{$index} — {$label} est obligatoire.";
                    }
                }
            }
            if (! empty($errors)) {
                return back()->withErrors(['guests' => array_slice($errors, 0, 5)])->withInput();
            }
        }

        $saved = $this->persistGuests($reservation, $request->input('guests', []));

        // "Enregistrer et continuer" → page suivante (mode paginé)
        if ($request->has('_next')) {
            $nextPage = max(1, (int) $request->query('page', 1) + 1);
            return redirect(route('agency.portal.guest-form', $reservation) . '?page=' . $nextPage)
                ->with('success', 'Page sauvegardée (' . $saved . ' fiche(s)).');
        }

        if ($isDraft) {
            return redirect()
                ->route('agency.portal.guest-form', $reservation)
                ->with('success', "Brouillon enregistré ({$saved} fiche(s) sauvegardée(s)). Vous pouvez continuer la saisie.");
        }

        return redirect()
            ->route('agency.portal.show-reservation', $reservation)
            ->with('success', 'Fiches de police soumises avec succès (' . $saved . ' fiche(s)).');
    }

    /**
     * Sauvegarde automatique AJAX (sans validation, retourne JSON).
     */
    public function autosaveGuests(Request $request, Reservation $reservation)
    {
        $agency = $this->agency();
        $belongs = $reservation->agency_id === $agency->id
            || ($reservation->secureLink && $reservation->secureLink->agency_id === $agency->id)
            || $reservation->email === $agency->email;

        if (! $belongs) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $policeStatuses = [
            \App\Models\Reservation::STATUS_PARTIALLY_PAID,
            \App\Models\Reservation::STATUS_PAID,
            \App\Models\Reservation::STATUS_CONFIRMED,
        ];
        if (! in_array($reservation->status, $policeStatuses)) {
            return response()->json(['ok' => false, 'error' => 'Not allowed'], 403);
        }

        $saved = $this->persistGuests($reservation, $request->input('guests', []));

        return response()->json(['ok' => true, 'saved' => $saved, 'ts' => now()->toIso8601String()]);
    }

    /**
     * Persiste les données guests (partagé entre saveGuests et autosaveGuests).
     */
    private function persistGuests(Reservation $reservation, array $guests): int
    {
        $saved = 0;
        foreach ($guests as $index => $data) {
            $filled = array_filter($data, fn($v) => filled($v));
            if (empty($filled) || (count($filled) === 1 && isset($filled['type']))) continue;

            \App\Models\GuestRegistration::updateOrCreate(
                ['reservation_id' => $reservation->id, 'guest_index' => (int)$index],
                [
                    'guest_type'               => $data['type']                     ?? 'adult',
                    'civilite'                 => $data['civilite']                 ?? null,
                    'nom'                      => strtoupper(trim($data['nom']      ?? '')),
                    'prenom'                   => ucfirst(mb_strtolower(trim($data['prenom'] ?? ''))),
                    'date_naissance'           => $data['date_naissance']           ?: null,
                    'lieu_naissance'           => $data['lieu_naissance']           ?? null,
                    'pays_naissance'           => $data['pays_naissance']           ?? null,
                    'nationalite'              => $data['nationalite']              ?? null,
                    'type_document'            => $data['type_document']            ?? null,
                    'numero_document'          => strtoupper(trim($data['numero_document'] ?? '')),
                    'date_expiration_document' => $data['date_expiration_document'] ?: null,
                    'pays_emission_document'   => $data['pays_emission_document']   ?? null,
                    'adresse'                  => $data['adresse']                  ?? null,
                    'ville'                    => $data['ville']                    ?? null,
                    'code_postal'              => $data['code_postal']              ?? null,
                    'pays_residence'           => $data['pays_residence']           ?? null,
                    'profession'               => $data['profession']               ?? null,
                ]
            );
            $saved++;
        }
        return $saved;
    }

    private function guestCountryList(): array
    {
        return ['MA'=>'Maroc','FR'=>'France','ES'=>'Espagne','GB'=>'Royaume-Uni','DE'=>'Allemagne','IT'=>'Italie','BE'=>'Belgique','CH'=>'Suisse','NL'=>'Pays-Bas','PT'=>'Portugal','US'=>'États-Unis','CA'=>'Canada','DZ'=>'Algérie','TN'=>'Tunisie','LY'=>'Libye','EG'=>'Égypte','SA'=>'Arabie Saoudite','AE'=>'Émirats Arabes Unis','QA'=>'Qatar','KW'=>'Koweït','SN'=>'Sénégal','CI'=>"Côte d'Ivoire",'CM'=>'Cameroun','SY'=>'Syrie','LB'=>'Liban','JO'=>'Jordanie','RU'=>'Russie','CN'=>'Chine','JP'=>'Japon','BR'=>'Brésil','MX'=>'Mexique','AU'=>'Australie','ZA'=>'Afrique du Sud','TR'=>'Turquie','PL'=>'Pologne','SE'=>'Suède','NO'=>'Norvège','DK'=>'Danemark','FI'=>'Finlande','AT'=>'Autriche','OTHER'=>'Autre'];
    }

    /**
     * Page de détail d'une réservation (agence).
     */
    public function showReservation(Reservation $reservation)
    {
        $agency = $this->agency();

        $belongs = $reservation->agency_id === $agency->id
            || ($reservation->secureLink && $reservation->secureLink->agency_id === $agency->id)
            || $reservation->email === $agency->email;
        abort_if(! $belongs, 403);

        $reservation->load(['hotel', 'rooms.roomType', 'payments', 'paymentSchedules.payment', 'guestRegistrations', 'supplements.supplement', 'extras']);

        // Calcul taxe de séjour par séjour
        $taxeRate = 0;
        try { $taxeRate = (float)($reservation->hotel->taxe_sejour ?? 0); } catch (\Exception $e) {}

        if ((float)($reservation->taxe_total ?? 0) > 0) {
            $taxeTotal = (float)$reservation->taxe_total;
            $taxeLines = [];
        } else {
            $taxeTotal = 0;
            $taxeLines = [];
            $stayGroups = $reservation->rooms->groupBy(fn($r) =>
                ($r->check_in?->format('Y-m-d') ?? 'x') . '_' .
                ($r->check_out?->format('Y-m-d') ?? 'x')
            );
            foreach ($stayGroups as $sRooms) {
                $sFirst  = $sRooms->first();
                $sNights = ($sFirst->check_in && $sFirst->check_out)
                    ? (int)$sFirst->check_in->diffInDays($sFirst->check_out)
                    : (int)$reservation->nights;
                $sAdults = (int)$sRooms->sum(fn($r) => ($r->adults ?? 0) * max(1, $r->quantity ?? 1));
                if ($sAdults > 0 && $sNights > 0 && $taxeRate > 0) {
                    $sLine = round($sAdults * $sNights * $taxeRate, 2);
                    $taxeTotal += $sLine;
                    $taxeLines[] = [
                        'adults' => $sAdults,
                        'nights' => $sNights,
                        'sub'    => $sLine,
                        'label'  => $sFirst->check_in ? $sFirst->check_in->format('d/m') . '–' . $sFirst->check_out->format('d/m/Y') : '',
                    ];
                }
            }
        }

        $suppTotal   = (float)($reservation->supplement_total ?? 0);
        $extrasTotal = (float)$reservation->extras->sum('total_price');
        $roomsTotal  = round(($reservation->total_price ?? 0) - $suppTotal - $extrasTotal, 2);
        $grandTotal  = round(($reservation->total_price ?? 0) + $taxeTotal, 2);
        $amountPaid  = $reservation->payments->where('status', 'completed')->sum('amount');
        $remaining   = max(0, $grandTotal - $amountPaid);
        $pct         = $grandTotal > 0 ? min(100, round($amountPaid / $grandTotal * 100)) : 0;
        $_ciEdit = $reservation->check_in instanceof \Carbon\Carbon
            ? $reservation->check_in
            : \Carbon\Carbon::parse($reservation->check_in);
        $canEdit = in_array($reservation->status, ['draft', 'pending', 'accepted', 'waiting_payment', 'partially_paid', 'modification_pending'])
            && now()->startOfDay()->diffInDays($_ciEdit->copy()->startOfDay(), false) > 7;
        $canPay      = in_array($reservation->status, ['waiting_payment', 'accepted', 'partially_paid', 'confirmed']) && $remaining > 0;

        $sejours     = $reservation->sejours;

        return view('agencies.reservation-show', compact(
            'agency', 'reservation', 'taxeRate', 'taxeTotal', 'taxeLines',
            'suppTotal', 'extrasTotal', 'roomsTotal', 'grandTotal', 'amountPaid', 'remaining',
            'pct', 'canEdit', 'canPay', 'sejours'
        ));
    }

    /**
     * Formulaire de modification d'une réservation (page dédiée, agence).
     */
    public function editReservation(Reservation $reservation)
    {
        $agency = $this->agency();

        $belongs = $reservation->agency_id === $agency->id
            || ($reservation->secureLink && $reservation->secureLink->agency_id === $agency->id)
            || $reservation->email === $agency->email;
        abort_if(! $belongs, 403);

        $editableStatuses = ['draft', 'pending', 'accepted', 'waiting_payment', 'partially_paid', 'modification_pending'];
        if (! in_array($reservation->status, $editableStatuses)) {
            return redirect()->route('agency.portal.dashboard')
                ->with('error', 'Cette réservation ne peut plus être modifiée.');
        }

        // Blocage 7 jours avant l'arrivée
        $checkIn = $reservation->check_in instanceof \Carbon\Carbon
            ? $reservation->check_in
            : \Carbon\Carbon::parse($reservation->check_in);

        if (now()->startOfDay()->diffInDays($checkIn->copy()->startOfDay(), false) <= 7) {
            return redirect()->route('agency.portal.dashboard')
                ->with('error', 'Modification impossible : l\'arrivée est dans moins de 7 jours. Veuillez contacter directement l\'hôtel.');
        }

        $hotel     = $reservation->hotel;
        $roomTypes = $hotel->activeRoomTypes()->with('activeOccupancyConfigs')->get();

        $roomTypeConfigs = $roomTypes->mapWithKeys(function ($rt) {
            return [$rt->id => $rt->activeOccupancyConfigs->map(fn ($cfg) => [
                'id'           => $cfg->id,
                'label'        => $cfg->label,
                'min_adults'   => $cfg->min_adults,
                'max_adults'   => $cfg->max_adults,
                'min_children' => $cfg->min_children,
                'max_children' => $cfg->max_children,
                'max_babies'   => $cfg->max_babies ?? 0,
            ])->values()->all()];
        });

        $roomTypeCapacity = $roomTypes->mapWithKeys(fn ($rt) => [$rt->id => [
            'min'         => $rt->min_persons ?? 1,
            'max'         => $rt->max_persons ?? 999,
            'maxAdults'   => $rt->max_adults,
            'maxChildren' => $rt->max_children,
            'babyBed'     => (bool) $rt->baby_bed_available,
        ]]);

        $reservation->load('rooms.roomType', 'rooms.occupancyConfig', 'supplements.supplement', 'extras');

        $extrasTotal = (float) $reservation->extras->sum('total_price');

        $selectedSupplementIds = $reservation->supplements
            ->where('is_mandatory', false)
            ->pluck('supplement_id')
            ->values()
            ->all();

        $staysData = $reservation->sejours->map(fn ($sejour) => [
            'check_in'  => $sejour['check_in']->format('Y-m-d'),
            'check_out' => $sejour['check_out']->format('Y-m-d'),
            'rooms'     => $sejour['rooms']->map(fn ($r) => [
                'room_type_id'        => $r->room_type_id,
                'occupancy_config_id' => $r->occupancy_config_id,
                'quantity'            => $r->quantity,
                'adults'              => $r->adults   ?? 1,
                'children'            => $r->children ?? 0,
                'babies'              => $r->babies   ?? 0,
                'baby_bed'            => $r->baby_bed ?? false,
            ])->values()->all(),
        ])->values()->all();

        // Prix stockés par séjour pour pré-remplir priceResults sans recalcul API
        $taxeRate = 0;
        try { $taxeRate = (float)($hotel->taxe_sejour ?? 0); } catch (\Exception $e) {}

        // Charger tous les suppléments de l'hôtel pour les mapper aux séjours
        $allHotelSupps = \App\Models\Supplement::where('hotel_id', $reservation->hotel_id)
            ->where('is_active', true)
            ->get();

        // Suppléments déjà attachés à la réservation (pour récupérer les prix unitaires stockés)
        $existingSupps = $reservation->supplements->keyBy('supplement_id');

        $initialPriceResults = $reservation->sejours->map(function ($sejour) use ($taxeRate, $allHotelSupps, $existingSupps) {
            $checkIn   = $sejour['check_in'];
            $checkOut  = $sejour['check_out'];
            $nights    = (int) $sejour['nights'];
            $lastNight = $checkOut->copy()->subDay();

            $stayAdults   = (int) $sejour['rooms']->sum(fn($r) => ($r->adults   ?? 0) * max(1, $r->quantity ?? 1));
            $stayChildren = (int) $sejour['rooms']->sum(fn($r) => ($r->children ?? 0) * max(1, $r->quantity ?? 1));
            $stayBabies   = (int) $sejour['rooms']->sum(fn($r) => ($r->babies   ?? 0) * max(1, $r->quantity ?? 1));

            // Suppléments applicables à CE séjour (même logique lastNight que le backend)
            $staySupplements = $allHotelSupps->filter(function ($sup) use ($checkIn, $lastNight) {
                if (! $sup->date_from || ! $sup->date_to) return true;
                return $sup->date_from->lte($lastNight) && $sup->date_to->gte($checkIn);
            })->map(function ($sup) use ($stayAdults, $stayChildren, $stayBabies, $existingSupps) {
                $existing    = $existingSupps[$sup->id] ?? null;
                $priceAdult  = (float) ($existing?->unit_price_adult ?? $sup->price_adult ?? 0);
                $priceChild  = (float) ($existing?->unit_price_child ?? $sup->price_child ?? 0);
                $priceBaby   = (float) ($existing?->unit_price_baby  ?? $sup->price_baby  ?? 0);
                $total       = $stayAdults * $priceAdult + $stayChildren * $priceChild + $stayBabies * $priceBaby;
                return [
                    'id'           => $sup->id,
                    'title'        => $sup->title,
                    'date'         => $sup->date_from?->eq($sup->date_to)
                        ? $sup->date_from->format('d/m/Y')
                        : ($sup->date_from?->format('d/m') . '–' . $sup->date_to?->format('d/m/Y')),
                    'date_raw'     => $sup->date_from?->toDateString(),
                    'status'       => $sup->status,
                    'is_mandatory' => $sup->isMandatory(),
                    'price_adult'  => $priceAdult,
                    'price_child'  => $priceChild,
                    'price_baby'   => $priceBaby,
                    'adults'       => $stayAdults,
                    'children'     => $stayChildren,
                    'babies'       => $stayBabies,
                    'total'        => (int) round($total),
                ];
            })->values()->all();

            return [
                'total'                   => (float) $sejour['rooms']->sum(fn($r) => $r->total_price ?? 0),
                'nights'                  => $nights,
                'taxe_sejour_rate'        => $taxeRate,
                'taxe_sejour_adults'      => $stayAdults,
                'taxe_sejour_total'       => round($stayAdults * $nights * $taxeRate, 2),
                'breakdown'               => $sejour['rooms']->map(function($r) use ($nights) {
                    // unit_price_raw = sum des prix unitaires par nuit pour 1 chambre
                    // Si l'admin a surchargé le prix (price_override=true), price_detail peut être
                    // obsolète → on force le fallback sur total_price/quantity qui est toujours à jour.
                    // Sinon : lire depuis price_detail (exact même si qty a changé).
                    $_pd = ($r->price_override)
                        ? []
                        : (is_array($r->price_detail) ? $r->price_detail : []);
                    $_unitPriceRaw = !empty($_pd)
                        ? round(collect($_pd)->sum(fn($_n) => (float)($_n['unit_price'] ?? 0)), 2)
                        : (($r->total_price && ($r->quantity ?? 1) > 0)
                            ? round((float)$r->total_price / max(1, (int)($r->quantity ?? 1)), 2)
                            : null);
                    return [
                        'room_type_name'  => $r->roomType?->name ?? '',
                        'occupancy_label' => $r->occupancyConfig?->code . ' — ' . ($r->occupancyConfig?->occupancy_description ?? ''),
                        'quantity'        => $r->quantity ?? 1,
                        'nights'          => $nights,
                        'line_total'      => (float) ($r->total_price ?? 0),
                        'unit_price_raw'  => $_unitPriceRaw,
                    ];
                })->values()->all(),
                'supplements'             => $staySupplements,
                'success'                 => true,
            ];
        })->values()->all();

        return view('agencies.reservation-edit', compact(
            'agency', 'reservation', 'hotel', 'roomTypes',
            'roomTypeConfigs', 'staysData', 'roomTypeCapacity',
            'selectedSupplementIds', 'initialPriceResults', 'extrasTotal'
        ));
    }

    /**
     * Soumet la demande de modification (agence) via ReservationService.
     */
    public function updateReservation(Request $request, Reservation $reservation)
    {
        $agency = $this->agency();

        $belongs = $reservation->agency_id === $agency->id
            || ($reservation->secureLink && $reservation->secureLink->agency_id === $agency->id)
            || $reservation->email === $agency->email;
        abort_if(! $belongs, 403);

        $editableStatuses = ['draft', 'pending', 'accepted', 'waiting_payment', 'partially_paid', 'modification_pending'];
        abort_if(! in_array($reservation->status, $editableStatuses), 403, 'Cette réservation ne peut plus être modifiée.');

        $isDraft = $reservation->status === \App\Models\Reservation::STATUS_DRAFT;

        // Blocage 7 jours avant l'arrivée (non applicable aux brouillons)
        if (! $isDraft) {
            $checkIn = $reservation->check_in instanceof \Carbon\Carbon
                ? $reservation->check_in
                : \Carbon\Carbon::parse($reservation->check_in);

            abort_if(
                now()->startOfDay()->diffInDays($checkIn->copy()->startOfDay(), false) <= 7,
                403,
                'Modification impossible : l\'arrivée est dans moins de 7 jours.'
            );
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
            'contact_name'                         => 'nullable|string|max:100',
            'phone'                                => 'nullable|string|max:30',
        ]);

        // Règle : minimum 11 chambres sauf pour les agences de voyages
        $totalRoomsUpd = collect($data['stays'])
            ->flatMap(fn($s) => $s['rooms'])
            ->sum(fn($r) => max(1, (int)($r['quantity'] ?? 1)));
        $agencySlugUpd = $agency->agencyStatus?->slug;
        if ($agencySlugUpd !== 'agence-de-voyages' && $totalRoomsUpd < 11) {
            return back()->withErrors([
                'min_rooms' => 'Les modifications avec moins de 11 chambres doivent être effectuées via notre site web.'
            ])->withInput();
        }

        $data['check_in']  = collect($data['stays'])->pluck('check_in')->min();
        $data['check_out'] = collect($data['stays'])->pluck('check_out')->max();
        $data['total_persons'] = collect($data['stays'])
            ->flatMap(fn ($s) => $s['rooms'])
            ->sum(fn ($r) => (($r['adults'] ?? 0) + ($r['children'] ?? 0) + ($r['babies'] ?? 0)) * max(1, (int)($r['quantity'] ?? 1))) ?: 1;

        $reservationService = app(ReservationService::class);

        // Brouillon : soumettre directement (recalcul prix + pending + notifs)
        if ($isDraft) {
            $reservationService->submitDraft($reservation, $data);
            return redirect()->route('agency.portal.dashboard')
                ->with('success', "Votre demande {$reservation->reference} a bien été soumise. Vous serez notifié dès sa validation.");
        }

        // Réservations en statut « En attente » : pas de validation admin requise,
        // la modification est appliquée immédiatement.
        if ($reservation->status === \App\Models\Reservation::STATUS_PENDING) {
            $reservationService->requestModification($reservation, $data);
            $reservation->refresh();
            $reservationService->acceptModification($reservation); // null admin = système
            return redirect()->route('agency.portal.dashboard')
                ->with('success', "Modification de la réservation {$reservation->reference} appliquée avec succès.");
        }

        $reservationService->requestModification($reservation, $data);

        return redirect()->route('agency.portal.dashboard')
            ->with('success', "Demande de modification envoyée pour la réservation {$reservation->reference}. En attente de validation.");
    }

    /**
     * Annulation immédiate d'une réservation (statuts « En attente » et « En attente de paiement »).
     */
    public function cancelReservation(Request $request, Reservation $reservation)
    {
        $agency = $this->agency();
        $belongs = $reservation->agency_id === $agency->id
            || ($reservation->secureLink && $reservation->secureLink->agency_id === $agency->id)
            || $reservation->email === $agency->email;
        abort_if(! $belongs, 403);

        $immediatelyCancellable = [
            \App\Models\Reservation::STATUS_PENDING,
            \App\Models\Reservation::STATUS_WAITING_PAYMENT,
        ];

        if (! in_array($reservation->status, $immediatelyCancellable)) {
            return redirect()->route('agency.portal.show-reservation', $reservation)
                ->with('error', 'Cette réservation ne peut pas être annulée directement. Veuillez contacter l\'hôtel.');
        }

        $reason = $request->input('reason', 'Annulée par l\'agence.');
        app(ReservationService::class)->cancel($reservation, $reason);

        return redirect()->route('agency.portal.dashboard')
            ->with('success', "Réservation {$reservation->reference} annulée avec succès.");
    }

    /**
     * Dupliquer une réservation refusée avec suggestion pour en créer une nouvelle modifiable.
     */
    public function duplicateReservation(Reservation $reservation)
    {
        $agency = $this->agency();

        $belongs = $reservation->agency_id === $agency->id
            || ($reservation->secureLink && $reservation->secureLink->agency_id === $agency->id)
            || $reservation->email === $agency->email;
        abort_if(! $belongs, 403);

        // Seules les réservations refusées avec suggestion peuvent être dupliquées, et une seule fois
        abort_if(
            $reservation->status !== \App\Models\Reservation::STATUS_REFUSED
                || ! $reservation->refused_with_suggestion
                || $reservation->suggestion_copied,
            403,
            'Cette demande ne peut pas être dupliquée.'
        );

        // Construire le tableau stays depuis les chambres de la réservation originale
        $stayGroups = $reservation->rooms->groupBy(function ($r) {
            $ci = $r->check_in  instanceof \Carbon\Carbon ? $r->check_in->toDateString()  : $r->check_in;
            $co = $r->check_out instanceof \Carbon\Carbon ? $r->check_out->toDateString() : $r->check_out;
            return $ci . '_' . $co;
        });

        $stays = $stayGroups->map(function ($rooms) {
            $first = $rooms->first();
            $ci    = $first->check_in  instanceof \Carbon\Carbon ? $first->check_in->toDateString()  : $first->check_in;
            $co    = $first->check_out instanceof \Carbon\Carbon ? $first->check_out->toDateString() : $first->check_out;
            return [
                'check_in'  => $ci,
                'check_out' => $co,
                'rooms'     => $rooms->map(fn ($r) => [
                    'room_type_id'        => $r->room_type_id,
                    'quantity'            => $r->quantity ?? 1,
                    'adults'              => $r->adults   ?? 1,
                    'children'            => $r->children ?? 0,
                    'babies'              => $r->babies   ?? 0,
                    'occupancy_config_id' => $r->occupancy_config_id,
                ])->values()->toArray(),
            ];
        })->values()->toArray();

        $data = [
            'hotel_id'             => $reservation->hotel_id,
            'agency_name'          => $agency->name,
            'contact_name'         => $reservation->contact_name ?? $agency->contact_name,
            'email'                => $agency->email,
            'phone'                => $reservation->phone ?? $agency->phone,
            'special_requests'     => $reservation->special_requests,
            'flexible_dates'       => $reservation->flexible_dates,
            'flexible_hotel'       => $reservation->flexible_hotel,
            'stays'                => $stays,
            'selected_supplements' => $reservation->supplements->pluck('supplement_id')->toArray(),
            'check_in'             => collect($stays)->pluck('check_in')->min(),
            'check_out'            => collect($stays)->pluck('check_out')->max(),
            'total_persons'        => $reservation->total_persons ?? 1,
        ];

        // Récupérer ou créer le SecureLink
        $link = $agency->secureLinks()
            ->where('hotel_id', $reservation->hotel_id)
            ->where('is_active', true)
            ->first();

        if (! $link) {
            $link = \App\Models\SecureLink::create([
                'token'         => \Illuminate\Support\Str::random(64),
                'agency_id'     => $agency->id,
                'hotel_id'      => $reservation->hotel_id,
                'agency_name'   => $agency->name,
                'agency_email'  => $agency->email,
                'contact_name'  => $agency->contact_name,
                'contact_phone' => $agency->phone,
                'is_active'     => true,
                'max_uses'      => null,
            ]);
        }

        $newReservation = app(ReservationService::class)->createFromClientForm($data, $link, asDraft: true);

        // Marquer l'original comme déjà copié (usage unique)
        $reservation->update(['suggestion_copied' => true]);

        return redirect()->route('agency.portal.edit-reservation', $newReservation)
            ->with('success', "Brouillon {$newReservation->reference} créé à partir de {$reservation->reference}. Modifiez-le puis confirmez votre demande.");
    }

    /**
     * Formulaire de modification du profil.
     */
    public function editProfile()
    {
        $agency = $this->agency();
        return view('agencies.profile', compact('agency'));
    }

    /**
     * Changer le mot de passe depuis le portail agence.
     */
    public function updatePassword(Request $request)
    {
        $agency = $this->agency();

        $request->validate([
            'current_password' => ['required', function ($attribute, $value, $fail) use ($agency) {
                if (! \Illuminate\Support\Facades\Hash::check($value, $agency->password)) {
                    $fail('Le mot de passe actuel est incorrect.');
                }
            }],
            'password'         => 'required|string|min:8|confirmed',
        ], [
            'password.min'       => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation ne correspond pas au nouveau mot de passe.',
        ]);

        $agency->update(['password' => $request->password]);

        return redirect()
            ->route('agency.portal.profile')
            ->with('success', 'Mot de passe modifié avec succès.');
    }

    /**
     * Soumettre une demande de modification du profil (en attente d'approbation admin).
     */
    public function updateProfile(Request $request)
    {
        $agency = $this->agency();

        // Bloquer si une demande est déjà en attente
        if (! empty($agency->pending_changes)) {
            return redirect()
                ->route('agency.portal.profile')
                ->with('error', 'Une demande de modification est déjà en cours d\'examen. Veuillez attendre la décision de l\'administrateur.');
        }

        $data = $request->validate([
            'contact_name' => 'required|string|max:100',
            'phone'        => 'nullable|string|max:30',
            'address'      => 'nullable|string|max:255',
            'city'         => 'nullable|string|max:100',
            'country'      => 'nullable|string|max:100',
            'website'      => 'nullable|url|max:255',
        ]);

        // Ne stocker que les champs réellement modifiés
        $changes = [];
        foreach ($data as $key => $value) {
            if ((string) ($agency->$key ?? '') !== (string) ($value ?? '')) {
                $changes[$key] = [
                    'old' => $agency->$key,
                    'new' => $value,
                ];
            }
        }

        if (empty($changes)) {
            return redirect()
                ->route('agency.portal.profile')
                ->with('error', 'Aucune modification détectée.');
        }

        $agency->update(['pending_changes' => $changes]);

        return redirect()
            ->route('agency.portal.profile')
            ->with('success', 'Votre demande de modification a été envoyée. Elle sera appliquée après approbation par nos garants.');
    }

    /**
     * Paiement partiel depuis le portail agence.
     * Supporte le paiement d'une échéance spécifique via payment_schedule_id.
     */
    public function payReservation(Request $request, Reservation $reservation)
    {
        $agency = $this->agency();

        // Sécurité : la réservation appartient bien à cette agence
        // (par agency_id direct, via lien sécurisé, ou via email)
        $belongs = $reservation->agency_id === $agency->id
            || ($reservation->secureLink && $reservation->secureLink->agency_id === $agency->id)
            || $reservation->email === $agency->email;
        abort_if(! $belongs, 403);
        abort_if(! in_array($reservation->status, ['waiting_payment', 'accepted', 'partially_paid']), 403);

        $remaining = $reservation->remaining_amount;

        // Si une échéance est ciblée
        $scheduleId = $request->input('payment_schedule_id');
        $schedule   = null;

        if ($scheduleId) {
            $schedule = $reservation->paymentSchedules()->find($scheduleId);
            abort_if(! $schedule, 422, 'Échéance introuvable.');

            // Vérifier qu'elle n'est pas déjà payée ou en attente de validation
            if ($schedule->computed_status === 'paid') {
                return back()->withErrors(['payment_schedule_id' => 'Cette échéance est déjà réglée.']);
            }
            if ($schedule->payment && $schedule->payment->status === 'pending') {
                return back()->withErrors(['payment_schedule_id' => 'Un paiement est déjà en attente de validation pour cette échéance.']);
            }
        }

        // Montant : si une échéance est ciblée, le montant est fixé sur l'échéance
        if ($schedule) {
            $amount  = $schedule->amount;
            $method  = $request->validate(['method' => 'required|in:bank_transfer,cash,card,check,other'])['method'];
            $ref     = $request->input('reference');
            $proofPath = null;
            if ($request->hasFile('proof')) {
                $proofPath = $request->file('proof')->store('payment-proofs', 'public');
            }

            Payment::create([
                'reservation_id'      => $reservation->id,
                'payment_schedule_id' => $schedule->id,
                'amount'              => $amount,
                'currency'            => 'MAD',
                'method'              => $method,
                'status'              => 'pending',
                'reference'           => $ref,
                'notes'               => 'Soumis par l\'agence via portail (échéance #' . $schedule->id . ')',
                'proof_path'          => $proofPath,
            ]);

            $methods = ['bank_transfer'=>'Virement','cash'=>'Espèces','card'=>'Carte','check'=>'Chèque','other'=>'Autre'];
            \App\Models\ReservationLog::record($reservation, 'payment_added',
                "Paiement soumis par l'agence — " . number_format($amount, 2, ',', ' ') . " MAD (échéance #{$schedule->installment_number}) — En attente de validation",
                [],
                ['amount' => $amount, 'method' => $methods[$method] ?? $method, 'reference' => $ref, 'schedule_installment' => $schedule->installment_number, 'status' => 'pending'],
                null, 'agency', null, $this->agency()->name
            );

            return redirect()->route('agency.portal.show-reservation', $reservation)
                ->with('success', "Paiement de {$amount} MAD soumis avec succès pour l'échéance du {$schedule->due_date->format('d/m/Y')}. En attente de validation.");
        }

        // Paiement libre (sans échéance)
        $data = $request->validate([
            'amount'    => 'required|numeric|min:1|max:' . ($remaining ?: 9999999),
            'method'    => 'required|in:bank_transfer,cash,card,check,other',
            'reference' => 'nullable|string|max:100',
            'proof'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('payment-proofs', 'public');
        }

        Payment::create([
            'reservation_id' => $reservation->id,
            'amount'         => $data['amount'],
            'currency'       => 'MAD',
            'method'         => $data['method'],
            'status'         => 'pending', // En attente de validation admin
            'reference'      => $data['reference'] ?? null,
            'notes'          => 'Soumis par l\'agence via portail',
            'proof_path'     => $proofPath,
        ]);

        $methods = ['bank_transfer'=>'Virement','cash'=>'Espèces','card'=>'Carte','check'=>'Chèque','other'=>'Autre'];
        \App\Models\ReservationLog::record($reservation, 'payment_added',
            "Paiement libre soumis par l'agence — " . number_format($data['amount'], 2, ',', ' ') . " MAD — En attente de validation",
            [],
            ['amount' => $data['amount'], 'method' => $methods[$data['method']] ?? $data['method'], 'reference' => $data['reference'] ?? null, 'status' => 'pending'],
            null, 'agency', null, $this->agency()->name
        );

        return redirect()->route('agency.portal.show-reservation', $reservation)
            ->with('success', "Paiement de {$data['amount']} MAD soumis avec succès. En attente de validation par Magic Hotels.");
    }

    /**
     * Soumettre une demande de réservation depuis le portail agence.
     */
    public function storeReservation(Request $request)
    {
        $agency = $this->agency();

        abort_if($agency->status !== 'approved', 403, 'Votre compte doit être approuvé pour soumettre une demande.');

        $data = $request->validate([
            'hotel_id'                             => 'required|exists:hotels,id',
            'special_requests'                     => 'nullable|string|max:1000',
            'stays'                                => 'required|array|min:1',
            'stays.*.check_in'                     => 'required|date|after_or_equal:today',
            'stays.*.check_out'                    => 'required|date',
            'stays.*.rooms'                        => 'required|array|min:1',
            'stays.*.rooms.*.room_type_id'         => 'required|exists:room_types,id',
            'stays.*.rooms.*.quantity'             => 'required|integer|min:1',
            'stays.*.rooms.*.adults'               => 'nullable|integer|min:0',
            'stays.*.rooms.*.children'             => 'nullable|integer|min:0',
            'stays.*.rooms.*.babies'               => 'nullable|integer|min:0',
            'stays.*.rooms.*.baby_bed'             => 'nullable|boolean',
            'stays.*.rooms.*.occupancy_config_id'  => 'nullable|exists:room_occupancy_configs,id',
            'selected_supplements'                 => 'nullable|array',
            'selected_supplements.*'               => 'nullable|exists:supplements,id',
            'flexible_dates'                       => 'nullable|boolean',
            'flexible_hotel'                       => 'nullable|boolean',
            'contact_name'                         => 'nullable|string|max:100',
            'phone'                                => 'nullable|string|max:30',
        ]);

        // Pré-remplir les informations de l'agence (le responsable peut être surchargé via le formulaire)
        $data['agency_name']  = $agency->name;
        $data['contact_name'] = $data['contact_name'] ?: $agency->contact_name;
        $data['email']        = $agency->email;
        $data['phone']        = $data['phone'] ?: $agency->phone;

        // Vérification capacités par chambre
        $roomTypeIds = collect($data['stays'])
            ->flatMap(fn ($s) => collect($s['rooms'])->pluck('room_type_id'))
            ->unique()->filter()->values()->toArray();

        $roomTypes    = RoomType::whereIn('id', $roomTypeIds)->get()->keyBy('id');
        $capacityErrors = [];

        foreach ($data['stays'] as $si => $stay) {
            foreach ($stay['rooms'] as $ri => $room) {
                $rt  = $roomTypes->get($room['room_type_id']);
                if (! $rt) continue;
                $qty = max(1, (int) ($room['quantity'] ?? 1));

                if ($rt->max_persons) {
                    $persons  = ($room['adults'] ?? 0) + ($room['children'] ?? 0) + ($room['babies'] ?? 0);
                    $maxTotal = $rt->max_persons * $qty;
                    if ($persons > $maxTotal) {
                        $capacityErrors["stays.{$si}.rooms.{$ri}.adults"] =
                            "Séjour " . ($si + 1) . " — \"{$rt->name}\" : {$persons} pers. dépassent la capacité max ({$maxTotal} max).";
                    }
                }
                if ($rt->max_adults !== null) {
                    $adults   = (int) ($room['adults'] ?? 0);
                    $maxAdult = $rt->max_adults * $qty;
                    if ($adults > $maxAdult) {
                        $capacityErrors["stays.{$si}.rooms.{$ri}.adults"] =
                            "Séjour " . ($si + 1) . " — \"{$rt->name}\" : {$adults} adultes dépassent le max ({$maxAdult}).";
                    }
                }
                if ($rt->max_children !== null) {
                    $children = (int) ($room['children'] ?? 0);
                    $maxChild = $rt->max_children * $qty;
                    if ($children > $maxChild) {
                        $capacityErrors["stays.{$si}.rooms.{$ri}.children"] =
                            "Séjour " . ($si + 1) . " — \"{$rt->name}\" : {$children} enfants dépassent le max ({$maxChild}).";
                    }
                }
                if (! empty($room['baby_bed']) && ! $rt->baby_bed_available) {
                    $capacityErrors["stays.{$si}.rooms.{$ri}.baby_bed"] =
                        "Séjour " . ($si + 1) . " — \"{$rt->name}\" : lit bébé non disponible.";
                }
            }
        }

        if (! empty($capacityErrors)) {
            return back()->withErrors($capacityErrors)->withInput();
        }

        // Règle : minimum 11 chambres sauf pour les agences de voyages
        $totalRooms = collect($data['stays'])
            ->flatMap(fn($s) => $s['rooms'])
            ->sum(fn($r) => max(1, (int)($r['quantity'] ?? 1)));
        $agencySlug = $agency->agencyStatus?->slug;
        if ($agencySlug !== 'agence-de-voyages' && $totalRooms < 11) {
            return back()->withErrors([
                'min_rooms' => 'Les réservations de moins de 11 chambres doivent être effectuées via notre site web.'
            ])->withInput();
        }

        // Dates globales
        $data['check_in']  = collect($data['stays'])->pluck('check_in')->min();
        $data['check_out'] = collect($data['stays'])->pluck('check_out')->max();

        // Total personnes
        $data['total_persons'] = collect($data['stays'])
            ->flatMap(fn ($s) => $s['rooms'])
            ->sum(fn ($r) => (($r['adults'] ?? 0) + ($r['children'] ?? 0) + ($r['babies'] ?? 0)) * max(1, (int)($r['quantity'] ?? 1))) ?: 1;

        // Récupérer ou créer un SecureLink pour cette agence + hôtel (pour la grille tarifaire)
        $link = $agency->secureLinks()
            ->where('hotel_id', $data['hotel_id'])
            ->where('is_active', true)
            ->first();

        if (! $link) {
            $link = SecureLink::create([
                'token'         => Str::random(64),
                'agency_id'     => $agency->id,
                'hotel_id'      => $data['hotel_id'],
                'agency_name'   => $agency->name,
                'agency_email'  => $agency->email,
                'contact_name'  => $agency->contact_name,
                'contact_phone' => $agency->phone,
                'is_active'     => true,
                'max_uses'      => null,
            ]);
        }

        $reservation = app(ReservationService::class)->createFromClientForm($data, $link);

        return redirect()->route('agency.portal.dashboard')
            ->with('success', "Votre demande a bien été enregistrée. Référence : {$reservation->reference}");
    }
}
