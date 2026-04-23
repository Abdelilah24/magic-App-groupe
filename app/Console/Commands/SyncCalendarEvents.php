<?php

namespace App\Console\Commands;

use App\Services\CalendarEventService;
use Illuminate\Console\Command;

class SyncCalendarEvents extends Command
{
    protected $signature = 'calendar:sync
        {--year=    : Année spécifique (défaut : année courante + 2 suivantes)}
        {--force    : Re-synchronise même si l\'année est déjà en base}';

    protected $description = 'Synchronise les jours fériés et vacances scolaires (MA + FR) depuis les APIs publiques';

    public function handle(CalendarEventService $service): int
    {
        if ($year = (int) $this->option('year')) {
            $years = [$year];
        } else {
            $current = now()->year;
            $years   = [$current, $current + 1, $current + 2];
        }

        $force = $this->option('force');
        $total = 0;

        foreach ($years as $y) {
            if (! $force && $service->isYearSynced($y)) {
                $this->line("  <comment>{$y}</comment> déjà synchronisé — ignoré (utiliser --force pour re-sync)");
                continue;
            }

            $this->info("Synchronisation de l'année {$y}…");

            ['synced' => $synced, 'errors' => $errors] = $service->syncYear($y);

            $total += $synced;
            $this->line("  <info>+{$synced}</info> événement(s) synchronisé(s) pour {$y}");

            foreach ($errors as $err) {
                $this->warn("  Erreur : {$err}");
            }
        }

        $this->info("Terminé. Total : {$total} événement(s) synchronisé(s).");

        return Command::SUCCESS;
    }
}
