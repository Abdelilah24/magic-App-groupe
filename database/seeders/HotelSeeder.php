<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\RoomPrice;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        // ── Hôtel 1 ───────────────────────────────────────────────────────────
        $hotel1 = Hotel::create([
            'name'        => 'Magic Palace Marrakech',
            'city'        => 'Marrakech',
            'country'     => 'Maroc',
            'stars'       => 5,
            'description' => 'Hôtel 5 étoiles au cœur de la médina de Marrakech.',
            'is_active'   => true,
        ]);

        $roomTypes1 = [
            ['name' => 'Chambre Standard', 'capacity' => 2, 'total_rooms' => 50],
            ['name' => 'Chambre Double',   'capacity' => 2, 'total_rooms' => 40],
            ['name' => 'Chambre Twin',     'capacity' => 2, 'total_rooms' => 30],
            ['name' => 'Suite Junior',     'capacity' => 3, 'total_rooms' => 15],
            ['name' => 'Suite Royale',     'capacity' => 4, 'total_rooms' => 5],
        ];

        $pricingMatrix1 = [
            // [date_from, date_to, Standard, Double, Twin, Suite Junior, Suite Royale, label]
            ['2024-01-01', '2024-02-29', 800, 1000, 950, 1800, 3500, 'Basse saison'],
            ['2024-03-01', '2024-04-30', 1000, 1200, 1150, 2200, 4200, 'Moyenne saison'],
            ['2024-05-01', '2024-06-30', 1100, 1350, 1300, 2400, 4500, 'Moyenne saison +'],
            ['2024-07-01', '2024-08-31', 1400, 1700, 1650, 3000, 5500, 'Haute saison'],
            ['2024-09-01', '2024-10-31', 1100, 1350, 1300, 2400, 4500, 'Moyenne saison'],
            ['2024-11-01', '2024-12-20', 900, 1100, 1050, 2000, 3800, 'Basse saison'],
            ['2024-12-21', '2024-12-31', 1600, 2000, 1950, 3500, 6500, 'Fêtes de fin d\'année'],
        ];

        $rts1 = [];
        foreach ($roomTypes1 as $rt) {
            $rts1[] = RoomType::create(array_merge($rt, ['hotel_id' => $hotel1->id]));
        }

        foreach ($pricingMatrix1 as $period) {
            [$from, $to, $std, $dbl, $twn, $sj, $sr, $label] = $period;
            $prices = [$std, $dbl, $twn, $sj, $sr];
            foreach ($rts1 as $i => $rt) {
                RoomPrice::create([
                    'hotel_id'       => $hotel1->id,
                    'room_type_id'   => $rt->id,
                    'date_from'      => $from,
                    'date_to'        => $to,
                    'price_per_night'=> $prices[$i],
                    'label'          => $label,
                ]);
            }
        }

        // ── Hôtel 2 ───────────────────────────────────────────────────────────
        $hotel2 = Hotel::create([
            'name'        => 'Magic Bay Agadir',
            'city'        => 'Agadir',
            'country'     => 'Maroc',
            'stars'       => 4,
            'description' => 'Hôtel balnéaire face à la baie d\'Agadir.',
            'is_active'   => true,
        ]);

        $roomTypes2 = [
            ['name' => 'Chambre Vue Mer',   'capacity' => 2, 'total_rooms' => 60],
            ['name' => 'Chambre Vue Jardin','capacity' => 2, 'total_rooms' => 40],
            ['name' => 'Suite Familiale',   'capacity' => 4, 'total_rooms' => 20],
        ];

        $rts2 = [];
        foreach ($roomTypes2 as $rt) {
            $rts2[] = RoomType::create(array_merge($rt, ['hotel_id' => $hotel2->id]));
        }

        $pricingMatrix2 = [
            ['2024-01-01', '2024-03-31', 700, 600, 1500, 'Basse saison'],
            ['2024-04-01', '2024-06-30', 950, 800, 2000, 'Printemps'],
            ['2024-07-01', '2024-08-31', 1300, 1100, 2800, 'Haute saison'],
            ['2024-09-01', '2024-10-31', 950, 800, 2000, 'Automne'],
            ['2024-11-01', '2024-12-31', 700, 600, 1500, 'Basse saison'],
        ];

        foreach ($pricingMatrix2 as $period) {
            [$from, $to, $mer, $jrd, $fam, $label] = $period;
            $prices = [$mer, $jrd, $fam];
            foreach ($rts2 as $i => $rt) {
                RoomPrice::create([
                    'hotel_id'       => $hotel2->id,
                    'room_type_id'   => $rt->id,
                    'date_from'      => $from,
                    'date_to'        => $to,
                    'price_per_night'=> $prices[$i],
                    'label'          => $label,
                ]);
            }
        }

        $this->command->info('✓ 2 hôtels + types de chambres + tarifs créés.');
    }
}
