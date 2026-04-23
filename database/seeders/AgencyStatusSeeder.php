<?php

namespace Database\Seeders;

use App\Models\AgencyStatus;
use Illuminate\Database\Seeder;

class AgencyStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'name'             => 'Individu',
                'slug'             => 'individu',
                'discount_percent' => 0.00,
                'description'      => 'Client particulier — tarif de base sans remise.',
                'is_default'       => true,
                'is_active'        => true,
                'sort_order'       => 10,
            ],
            [
                'name'             => 'Statut2',
                'slug'             => 'statut2',
                'discount_percent' => 5.00,
                'description'      => 'Statut intermédiaire — remise de 5 % sur le tarif de base.',
                'is_default'       => false,
                'is_active'        => true,
                'sort_order'       => 20,
            ],
            [
                'name'             => 'Agence de voyages',
                'slug'             => 'agence-de-voyages',
                'discount_percent' => 10.00,
                'description'      => 'Agence de voyages — remise de 10 % sur le tarif de base.',
                'is_default'       => false,
                'is_active'        => true,
                'sort_order'       => 30,
            ],
        ];

        foreach ($statuses as $s) {
            AgencyStatus::updateOrCreate(['slug' => $s['slug']], $s);
        }

        $this->command->info('✓ 3 statuts tarifaires créés (Individu, Statut2, Agence de voyages).');
    }
}
