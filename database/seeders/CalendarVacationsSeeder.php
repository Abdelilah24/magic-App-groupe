<?php

namespace Database\Seeders;

use App\Models\CalendarEvent;
use Illuminate\Database\Seeder;

/**
 * Seeder — Vacances scolaires MA · FR · GB (2024–2027)
 *
 * Usage :
 *   php artisan db:seed --class=CalendarVacationsSeeder
 *
 * Idempotent : utilise updateOrCreate sur (country, type, start_date, end_date, zone).
 * Ne touche jamais aux jours fériés (gérés par l'API Nager.Date).
 * Ne touche jamais aux événements déjà saisis manuellement avec la même clé.
 *
 * Conventions :
 *  - end_date = dernier jour INCLUS des vacances (le service ajoute +1 pour FullCalendar)
 *  - zone = null pour MA et GB (pas de système de zones)
 *  - zone = 'Zone A', 'Zone B', 'Zone C' pour FR
 */
class CalendarVacationsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFrance();
        $this->seedMaroc();
        $this->seedUK();
        $this->seedUserProvided();

        $this->command->info('✓ Vacances scolaires MA · FR · GB importées.');
    }

    // ────────────────────────────────────────────────────────────────────────────
    // FRANCE — calendrier officiel Éducation Nationale
    // Zones : A (Lyon, Clermont…) · B (Paris, Bordeaux…) · C (Marseille, Nice…)
    // Source : https://www.education.gouv.fr/les-dates-des-vacances-scolaires
    // ────────────────────────────────────────────────────────────────────────────
    private function seedFrance(): void
    {
        $vacances = [

            // ── 2024–2025 ────────────────────────────────────────────────────
            // Toussaint (toutes zones)
            ['name' => 'Vacances de la Toussaint', 'start' => '2024-10-19', 'end' => '2024-11-04', 'zone' => null,     'year' => 2024],

            // Noël (toutes zones)
            ['name' => 'Vacances de Noël',         'start' => '2024-12-21', 'end' => '2025-01-06', 'zone' => null,     'year' => 2024],

            // Hiver
            ['name' => 'Vacances d\'hiver',        'start' => '2025-02-08', 'end' => '2025-02-24', 'zone' => 'Zone A', 'year' => 2025],
            ['name' => 'Vacances d\'hiver',        'start' => '2025-02-22', 'end' => '2025-03-10', 'zone' => 'Zone B', 'year' => 2025],
            ['name' => 'Vacances d\'hiver',        'start' => '2025-02-15', 'end' => '2025-03-03', 'zone' => 'Zone C', 'year' => 2025],

            // Printemps
            ['name' => 'Vacances de printemps',    'start' => '2025-04-05', 'end' => '2025-04-21', 'zone' => 'Zone A', 'year' => 2025],
            ['name' => 'Vacances de printemps',    'start' => '2025-04-19', 'end' => '2025-05-05', 'zone' => 'Zone B', 'year' => 2025],
            ['name' => 'Vacances de printemps',    'start' => '2025-04-12', 'end' => '2025-04-28', 'zone' => 'Zone C', 'year' => 2025],

            // Été (toutes zones)
            ['name' => 'Vacances d\'été',          'start' => '2025-07-05', 'end' => '2025-09-01', 'zone' => null,     'year' => 2025],

            // ── 2025–2026 ────────────────────────────────────────────────────
            // Toussaint (toutes zones)
            ['name' => 'Vacances de la Toussaint', 'start' => '2025-10-18', 'end' => '2025-11-03', 'zone' => null,     'year' => 2025],

            // Noël (toutes zones)
            ['name' => 'Vacances de Noël',         'start' => '2025-12-20', 'end' => '2026-01-05', 'zone' => null,     'year' => 2025],

            // Hiver
            ['name' => 'Vacances d\'hiver',        'start' => '2026-02-07', 'end' => '2026-02-23', 'zone' => 'Zone A', 'year' => 2026],
            ['name' => 'Vacances d\'hiver',        'start' => '2026-02-21', 'end' => '2026-03-09', 'zone' => 'Zone B', 'year' => 2026],
            ['name' => 'Vacances d\'hiver',        'start' => '2026-02-14', 'end' => '2026-03-02', 'zone' => 'Zone C', 'year' => 2026],

            // Printemps
            ['name' => 'Vacances de printemps',    'start' => '2026-04-11', 'end' => '2026-04-27', 'zone' => 'Zone A', 'year' => 2026],
            ['name' => 'Vacances de printemps',    'start' => '2026-04-25', 'end' => '2026-05-11', 'zone' => 'Zone B', 'year' => 2026],
            ['name' => 'Vacances de printemps',    'start' => '2026-04-18', 'end' => '2026-05-04', 'zone' => 'Zone C', 'year' => 2026],

            // Été (toutes zones)
            ['name' => 'Vacances d\'été',          'start' => '2026-07-04', 'end' => '2026-09-01', 'zone' => null,     'year' => 2026],

            // ── 2026–2027 ────────────────────────────────────────────────────
            // Toussaint (toutes zones)
            ['name' => 'Vacances de la Toussaint', 'start' => '2026-10-17', 'end' => '2026-11-02', 'zone' => null,     'year' => 2026],

            // Noël (toutes zones)
            ['name' => 'Vacances de Noël',         'start' => '2026-12-19', 'end' => '2027-01-04', 'zone' => null,     'year' => 2026],

            // Hiver
            ['name' => 'Vacances d\'hiver',        'start' => '2027-02-06', 'end' => '2027-02-22', 'zone' => 'Zone A', 'year' => 2027],
            ['name' => 'Vacances d\'hiver',        'start' => '2027-02-20', 'end' => '2027-03-08', 'zone' => 'Zone B', 'year' => 2027],
            ['name' => 'Vacances d\'hiver',        'start' => '2027-02-13', 'end' => '2027-03-01', 'zone' => 'Zone C', 'year' => 2027],

            // Printemps
            ['name' => 'Vacances de printemps',    'start' => '2027-04-10', 'end' => '2027-04-26', 'zone' => 'Zone A', 'year' => 2027],
            ['name' => 'Vacances de printemps',    'start' => '2027-04-24', 'end' => '2027-05-10', 'zone' => 'Zone B', 'year' => 2027],
            ['name' => 'Vacances de printemps',    'start' => '2027-04-17', 'end' => '2027-05-03', 'zone' => 'Zone C', 'year' => 2027],

            // Été (toutes zones)
            ['name' => 'Vacances d\'été',          'start' => '2027-07-03', 'end' => '2027-09-01', 'zone' => null,     'year' => 2027],
        ];

        foreach ($vacances as $v) {
            CalendarEvent::updateOrCreate(
                [
                    'country'    => 'FR',
                    'type'       => 'school_vacation',
                    'start_date' => $v['start'],
                    'end_date'   => $v['end'],
                    'zone'       => $v['zone'],
                ],
                [
                    'name'   => $v['name'],
                    'year'   => $v['year'],
                    'source' => 'manual',
                ]
            );
        }

        $this->command->info('  → France : ' . count($vacances) . ' entrées');
    }

    // ────────────────────────────────────────────────────────────────────────────
    // MAROC — calendrier Ministère de l'Éducation Nationale
    // Pas de zones. Dates approximatives basées sur les calendriers officiels.
    // ────────────────────────────────────────────────────────────────────────────
    private function seedMaroc(): void
    {
        $vacances = [

            // ── 2024–2025 ────────────────────────────────────────────────────
            ['name' => 'Vacances d\'automne',      'start' => '2024-10-26', 'end' => '2024-11-03', 'year' => 2024],
            ['name' => 'Vacances de fin d\'année', 'start' => '2024-12-28', 'end' => '2025-01-05', 'year' => 2024],
            ['name' => 'Vacances de mi-hiver',     'start' => '2025-02-15', 'end' => '2025-02-23', 'year' => 2025],
            ['name' => 'Vacances de printemps',    'start' => '2025-04-05', 'end' => '2025-04-20', 'year' => 2025],
            ['name' => 'Vacances d\'été',          'start' => '2025-06-28', 'end' => '2025-09-03', 'year' => 2025],

            // ── 2025–2026 ────────────────────────────────────────────────────
            ['name' => 'Vacances d\'automne',      'start' => '2025-10-25', 'end' => '2025-11-02', 'year' => 2025],
            ['name' => 'Vacances de fin d\'année', 'start' => '2025-12-27', 'end' => '2026-01-04', 'year' => 2025],
            ['name' => 'Vacances de mi-hiver',     'start' => '2026-02-14', 'end' => '2026-02-22', 'year' => 2026],
            ['name' => 'Vacances de printemps',    'start' => '2026-04-04', 'end' => '2026-04-19', 'year' => 2026],
            ['name' => 'Vacances d\'été',          'start' => '2026-06-27', 'end' => '2026-09-02', 'year' => 2026],

            // ── 2026–2027 ────────────────────────────────────────────────────
            ['name' => 'Vacances d\'automne',      'start' => '2026-10-24', 'end' => '2026-11-01', 'year' => 2026],
            ['name' => 'Vacances de fin d\'année', 'start' => '2026-12-26', 'end' => '2027-01-03', 'year' => 2026],
            ['name' => 'Vacances de mi-hiver',     'start' => '2027-02-13', 'end' => '2027-02-21', 'year' => 2027],
            ['name' => 'Vacances de printemps',    'start' => '2027-04-03', 'end' => '2027-04-18', 'year' => 2027],
            ['name' => 'Vacances d\'été',          'start' => '2027-06-26', 'end' => '2027-09-01', 'year' => 2027],
        ];

        foreach ($vacances as $v) {
            CalendarEvent::updateOrCreate(
                [
                    'country'    => 'MA',
                    'type'       => 'school_vacation',
                    'start_date' => $v['start'],
                    'end_date'   => $v['end'],
                    'zone'       => null,
                ],
                [
                    'name'   => $v['name'],
                    'year'   => $v['year'],
                    'source' => 'manual',
                ]
            );
        }

        $this->command->info('  → Maroc  : ' . count($vacances) . ' entrées');
    }

    // ────────────────────────────────────────────────────────────────────────────
    // ROYAUME-UNI (Angleterre) — calendrier DfE (Department for Education)
    // Pas de zones. Dates valables pour l'Angleterre (England).
    // Source : https://www.gov.uk/school-term-and-holiday-dates
    // ────────────────────────────────────────────────────────────────────────────
    private function seedUK(): void
    {
        $vacances = [

            // ── 2024–2025 ────────────────────────────────────────────────────
            ['name' => 'Summer Holidays',            'start' => '2024-07-23', 'end' => '2024-09-02', 'year' => 2024],
            ['name' => 'Autumn Half-Term',           'start' => '2024-10-28', 'end' => '2024-11-01', 'year' => 2024],
            ['name' => 'Christmas Holidays',         'start' => '2024-12-23', 'end' => '2025-01-03', 'year' => 2024],
            ['name' => 'Spring Half-Term',           'start' => '2025-02-17', 'end' => '2025-02-21', 'year' => 2025],
            ['name' => 'Easter Holidays',            'start' => '2025-04-11', 'end' => '2025-04-25', 'year' => 2025],
            ['name' => 'May Half-Term',              'start' => '2025-05-26', 'end' => '2025-05-30', 'year' => 2025],

            // ── 2025–2026 ────────────────────────────────────────────────────
            ['name' => 'Summer Holidays',            'start' => '2025-07-22', 'end' => '2025-09-01', 'year' => 2025],
            ['name' => 'Autumn Half-Term',           'start' => '2025-10-27', 'end' => '2025-10-31', 'year' => 2025],
            ['name' => 'Christmas Holidays',         'start' => '2025-12-22', 'end' => '2026-01-02', 'year' => 2025],
            ['name' => 'Spring Half-Term',           'start' => '2026-02-16', 'end' => '2026-02-20', 'year' => 2026],
            ['name' => 'Easter Holidays',            'start' => '2026-04-03', 'end' => '2026-04-17', 'year' => 2026],
            ['name' => 'May Half-Term',              'start' => '2026-05-25', 'end' => '2026-05-29', 'year' => 2026],

            // ── 2026–2027 ────────────────────────────────────────────────────
            ['name' => 'Summer Holidays',            'start' => '2026-07-23', 'end' => '2026-09-01', 'year' => 2026],
            ['name' => 'Autumn Half-Term',           'start' => '2026-10-26', 'end' => '2026-10-30', 'year' => 2026],
            ['name' => 'Christmas Holidays',         'start' => '2026-12-21', 'end' => '2027-01-01', 'year' => 2026],
            ['name' => 'Spring Half-Term',           'start' => '2027-02-15', 'end' => '2027-02-19', 'year' => 2027],
            ['name' => 'Easter Holidays',            'start' => '2027-03-26', 'end' => '2027-04-09', 'year' => 2027],
            ['name' => 'May Half-Term',              'start' => '2027-05-31', 'end' => '2027-06-04', 'year' => 2027],
            ['name' => 'Summer Holidays',            'start' => '2027-07-22', 'end' => '2027-09-01', 'year' => 2027],
        ];

        foreach ($vacances as $v) {
            CalendarEvent::updateOrCreate(
                [
                    'country'    => 'GB',
                    'type'       => 'school_vacation',
                    'start_date' => $v['start'],
                    'end_date'   => $v['end'],
                    'zone'       => null,
                ],
                [
                    'name'   => $v['name'],
                    'year'   => $v['year'],
                    'source' => 'manual',
                ]
            );
        }

        $this->command->info('  → UK     : ' . count($vacances) . ' entrées');
    }

    // ────────────────────────────────────────────────────────────────────────────
    // DONNÉES FOURNIES — saisie manuelle complémentaire 2026
    // ────────────────────────────────────────────────────────────────────────────
    private function seedUserProvided(): void
    {
        // Correspondances : type "vacance" → school_vacation | zone "A" → "Zone A"
        $entries = [
            // ── Maroc ────────────────────────────────────────────────────────
            ['country' => 'MA', 'name' => 'Vacances mi-année',    'start' => '2026-01-25', 'end' => '2026-02-01', 'year' => 2026, 'zone' => null],
            ['country' => 'MA', 'name' => 'Vacances printemps',   'start' => '2026-03-15', 'end' => '2026-03-22', 'year' => 2026, 'zone' => null],
            ['country' => 'MA', 'name' => 'Vacances mai',         'start' => '2026-05-03', 'end' => '2026-05-10', 'year' => 2026, 'zone' => null],

            // ── France — Zone A ───────────────────────────────────────────────
            ['country' => 'FR', 'name' => "Vacances d'hiver",     'start' => '2026-02-07', 'end' => '2026-02-23', 'year' => 2026, 'zone' => 'Zone A'],
            ['country' => 'FR', 'name' => 'Vacances de printemps','start' => '2026-04-04', 'end' => '2026-04-20', 'year' => 2026, 'zone' => 'Zone A'],
            ['country' => 'FR', 'name' => "Vacances d'été",       'start' => '2026-07-04', 'end' => '2026-09-01', 'year' => 2026, 'zone' => 'Zone A'],

            // ── Royaume-Uni ───────────────────────────────────────────────────
            ['country' => 'GB', 'name' => 'Spring Holidays',      'start' => '2026-04-01', 'end' => '2026-04-15', 'year' => 2026, 'zone' => null],
            ['country' => 'GB', 'name' => 'Summer Holidays',      'start' => '2026-07-20', 'end' => '2026-09-01', 'year' => 2026, 'zone' => null],
            ['country' => 'GB', 'name' => 'Winter Holidays',      'start' => '2026-12-20', 'end' => '2027-01-05', 'year' => 2026, 'zone' => null],
        ];

        $count = 0;
        foreach ($entries as $e) {
            CalendarEvent::updateOrCreate(
                [
                    'country'    => $e['country'],
                    'type'       => 'school_vacation',
                    'start_date' => $e['start'],
                    'end_date'   => $e['end'],
                    'zone'       => $e['zone'],
                ],
                [
                    'name'   => $e['name'],
                    'year'   => $e['year'],
                    'source' => 'manual',
                ]
            );
            $count++;
        }

        $this->command->info("  → Saisie manuelle 2026 : {$count} entrées");
    }
}
