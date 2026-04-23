<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

class RealHotelsSeeder extends Seeder
{
    public function run(): void
    {
        // ── Hôtel 1 : Aqua Mirage Marrakech ──────────────────────────────────
        $aquaMirage = Hotel::firstOrCreate(
            ['name' => 'Hotel Aqua Mirage Marrakech'],
            [
                'city'        => 'Marrakech',
                'country'     => 'Maroc',
                'stars'       => 4,
                'description' => 'Hôtel 4 étoiles avec parc aquatique à Marrakech.',
                'is_active'   => true,
            ]
        );

        // Supprimer les anciens types de chambres si on recrée
        $aquaMirage->roomTypes()->forceDelete();

        $roomTypesAqua = [
            [
                'name'        => 'Chambre Standard sans Balcon',
                'min_persons' => 1,
                'max_persons' => 3,
                'capacity'    => 3,
                'total_rooms' => 0,
            ],
            [
                'name'        => 'Chambre Standard avec Balcon/Terrasse',
                'min_persons' => 1,
                'max_persons' => 3,
                'capacity'    => 3,
                'total_rooms' => 0,
            ],
            [
                'name'        => 'Chambre Standard Double pour Personne à Mobilité Réduite',
                'min_persons' => 1,
                'max_persons' => 2,
                'capacity'    => 2,
                'total_rooms' => 0,
            ],
            [
                'name'        => 'Chambre Quadruple avec Balcon/Terrasse',
                'min_persons' => 1,
                'max_persons' => 4,
                'capacity'    => 4,
                'total_rooms' => 0,
            ],
            [
                'name'        => 'Chambre Standard Quintuple',
                'min_persons' => 1,
                'max_persons' => 5,
                'capacity'    => 5,
                'total_rooms' => 0,
            ],
        ];

        foreach ($roomTypesAqua as $rt) {
            RoomType::create(array_merge($rt, ['hotel_id' => $aquaMirage->id, 'is_active' => true]));
        }

        $this->command->info("✓ Hotel Aqua Mirage Marrakech : " . count($roomTypesAqua) . " types de chambres créés.");

        // ── Hôtel 2 : Medina Gardens Marrakech ───────────────────────────────
        $medinaGardens = Hotel::firstOrCreate(
            ['name' => 'Hotel Medina Gardens Marrakech'],
            [
                'city'        => 'Marrakech',
                'country'     => 'Maroc',
                'stars'       => 4,
                'description' => 'Hôtel 4 étoiles en plein cœur de la médina de Marrakech.',
                'is_active'   => true,
            ]
        );

        $medinaGardens->roomTypes()->forceDelete();

        $roomTypesMedina = [
            [
                'name'        => 'Chambre sans Balcon',
                'min_persons' => 1,
                'max_persons' => 2,
                'capacity'    => 2,
                'total_rooms' => 0,
            ],
        ];

        foreach ($roomTypesMedina as $rt) {
            RoomType::create(array_merge($rt, ['hotel_id' => $medinaGardens->id, 'is_active' => true]));
        }

        $this->command->info("✓ Hotel Medina Gardens Marrakech : " . count($roomTypesMedina) . " type de chambre créé.");
        $this->command->info('');
        $this->command->warn('⚠  N\'oubliez pas de saisir les tarifs dans Admin → Tarifs calendrier.');
    }
}
