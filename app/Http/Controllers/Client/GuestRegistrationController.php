<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\GuestRegistration;
use App\Models\Reservation;
use App\Models\SecureLink;
use Illuminate\Http\Request;

class GuestRegistrationController extends Controller
{
    /**
     * Affiche le formulaire de fiche de police pour tous les voyageurs.
     */
    public function form(string $token, Reservation $reservation)
    {
        $this->authorizeClientAccess($token, $reservation);

        $reservation->load(['rooms.roomType', 'rooms.occupancyConfig', 'guestRegistrations']);

        $idx          = 1;
        $groupedSlots = [];   // structure par séjour → chambre
        $allSlots     = [];   // liste plate pour la barre de progression

        // Grouper les chambres par période de séjour (check_in_check_out)
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

            $sejour = [
                'sejour_index' => $sejour_index,
                'check_in'     => $checkIn,
                'check_out'    => $checkOut,
                'rooms'        => [],
            ];

            $room_num = 0;
            foreach ($rooms as $room) {
                $qty = max(1, (int)($room->quantity ?? 1));
                for ($q = 0; $q < $qty; $q++) {
                    $room_num++;
                    $roomSlots = [];

                    $typeName = $room->roomType->name ?? 'Chambre';

                    for ($i = 0; $i < (int)($room->adults   ?? 0); $i++) {
                        $s = ['index' => $idx++, 'type' => 'adult', 'label' => 'Adulte '  . ($i + 1)];
                        $roomSlots[] = $s; $allSlots[] = $s;
                    }
                    for ($i = 0; $i < (int)($room->children ?? 0); $i++) {
                        $s = ['index' => $idx++, 'type' => 'child', 'label' => 'Enfant '  . ($i + 1)];
                        $roomSlots[] = $s; $allSlots[] = $s;
                    }
                    for ($i = 0; $i < (int)($room->babies   ?? 0); $i++) {
                        $s = ['index' => $idx++, 'type' => 'baby',  'label' => 'Bébé '    . ($i + 1)];
                        $roomSlots[] = $s; $allSlots[] = $s;
                    }

                    if (!empty($roomSlots)) {
                        $sejour['rooms'][] = [
                            'room'       => $room,
                            'room_num'   => $room_num,
                            'room_label' => $typeName . ' ' . $room_num,
                            'slots'      => $roomSlots,
                        ];
                    }
                }
            }

            if (!empty($sejour['rooms'])) {
                $groupedSlots[] = $sejour;
            }
        }

        // Liste plate pour compatibilité barre de progression
        $slots    = $allSlots;
        $existing = $reservation->guestRegistrations->keyBy('guest_index');
        $countries = $this->countryList();

        return view('client.guests.form', compact(
            'reservation', 'token', 'groupedSlots', 'slots', 'existing', 'countries'
        ));
    }

    /**
     * Sauvegarde les fiches de police soumises.
     */
    public function save(Request $request, string $token, Reservation $reservation)
    {
        $this->authorizeClientAccess($token, $reservation);

        $guests = $request->input('guests', []);

        foreach ($guests as $index => $data) {
            // Ignorer les fiches entièrement vides
            $filled = array_filter($data, fn($v) => filled($v));
            if (empty($filled) || (count($filled) === 1 && isset($filled['type']))) {
                continue;
            }

            GuestRegistration::updateOrCreate(
                [
                    'reservation_id' => $reservation->id,
                    'guest_index'    => (int) $index,
                ],
                [
                    'guest_type'               => $data['type']                    ?? 'adult',
                    'civilite'                 => $data['civilite']                ?? null,
                    'nom'                      => strtoupper(trim($data['nom']     ?? '')),
                    'prenom'                   => ucfirst(mb_strtolower(trim($data['prenom'] ?? ''))),
                    'date_naissance'           => $data['date_naissance']          ?: null,
                    'lieu_naissance'           => $data['lieu_naissance']          ?? null,
                    'pays_naissance'           => $data['pays_naissance']          ?? null,
                    'nationalite'              => $data['nationalite']             ?? null,
                    'type_document'            => $data['type_document']           ?? null,
                    'numero_document'          => strtoupper(trim($data['numero_document'] ?? '')),
                    'date_expiration_document' => $data['date_expiration_document'] ?: null,
                    'pays_emission_document'   => $data['pays_emission_document']  ?? null,
                    'adresse'                  => $data['adresse']                 ?? null,
                    'ville'                    => $data['ville']                   ?? null,
                    'code_postal'              => $data['code_postal']             ?? null,
                    'pays_residence'           => $data['pays_residence']          ?? null,
                    'profession'               => $data['profession']              ?? null,
                    'numero_entree_maroc'      => strtoupper(trim($data['numero_entree_maroc'] ?? '')),
                ]
            );
        }

        return redirect()
            ->route('client.reservation.show', compact('token', 'reservation'))
            ->with('success', 'Fiches de police enregistrées. Merci !');
    }

    private function authorizeClientAccess(string $token, Reservation $reservation): void
    {
        $link = SecureLink::where('token', $token)->firstOrFail();

        if ($reservation->secure_link_id !== $link->id
            && $reservation->email !== $link->agency_email) {
            abort(403);
        }
    }

    /**
     * Liste des pays (FR) pour les selects.
     */
    private function countryList(): array
    {
        return [
            'MA' => 'Maroc',
            'FR' => 'France',
            'ES' => 'Espagne',
            'GB' => 'Royaume-Uni',
            'DE' => 'Allemagne',
            'IT' => 'Italie',
            'BE' => 'Belgique',
            'CH' => 'Suisse',
            'NL' => 'Pays-Bas',
            'PT' => 'Portugal',
            'US' => 'États-Unis',
            'CA' => 'Canada',
            'DZ' => 'Algérie',
            'TN' => 'Tunisie',
            'LY' => 'Libye',
            'EG' => 'Égypte',
            'SA' => 'Arabie Saoudite',
            'AE' => 'Émirats Arabes Unis',
            'QA' => 'Qatar',
            'KW' => 'Koweït',
            'SN' => 'Sénégal',
            'CI' => "Côte d'Ivoire",
            'CM' => 'Cameroun',
            'SY' => 'Syrie',
            'LB' => 'Liban',
            'JO' => 'Jordanie',
            'RU' => 'Russie',
            'CN' => 'Chine',
            'JP' => 'Japon',
            'BR' => 'Brésil',
            'MX' => 'Mexique',
            'AU' => 'Australie',
            'ZA' => 'Afrique du Sud',
            'TR' => 'Turquie',
            'PL' => 'Pologne',
            'SE' => 'Suède',
            'NO' => 'Norvège',
            'DK' => 'Danemark',
            'FI' => 'Finlande',
            'AT' => 'Autriche',
            'OTHER' => 'Autre',
        ];
    }
}
