<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            AgencyStatusSeeder::class, // Statuts tarifaires (Individu, Statut2, Agence de voyages)
            HotelSeeder::class,
            CalendarVacationsSeeder::class, // Vacances scolaires MA · FR · GB (2024–2027)
            PdfTemplateSeeder::class,
        ]);
    }
}
