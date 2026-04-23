<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Reservation;

/*
 * ─── Scheduling ───────────────────────────────────────────────────────────────
 * Synchronise jours fériés + vacances scolaires chaque 1er janvier à 3h
 * pour charger automatiquement l'année nouvelle + 2 ans à l'avance.
 */
Schedule::command('calendar:sync')->yearlyOn(1, 1, '03:00')
    ->withoutOverlapping()
    ->runInBackground();


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Recalcule taxe_total pour les réservations existantes où il n'est pas encore stocké.
 * Usage : php artisan reservations:fix-taxe-total [--all]
 *
 * --all : recalcule même les réservations qui ont déjà une valeur > 0
 */
Artisan::command('reservations:fix-taxe-total {--all : Recalculer même si taxe_total > 0}', function () {
    $query = Reservation::with(['rooms', 'hotel'])->whereNull('deleted_at');

    if (! $this->option('all')) {
        $query->where(function ($q) {
            $q->whereNull('taxe_total')->orWhere('taxe_total', 0);
        });
    }

    $reservations = $query->get();
    $this->info("Réservations à traiter : {$reservations->count()}");

    $updated = 0;

    foreach ($reservations as $reservation) {
        $hotel    = $reservation->hotel;
        $taxeRate = $hotel ? (float) ($hotel->taxe_sejour ?? 19.80) : 19.80;

        // Grouper les chambres par séjour (check_in + check_out)
        $sejours = $reservation->rooms
            ->groupBy(fn ($r) => ($r->check_in?->toDateString()  ?? $reservation->check_in->toDateString())
                               . '_'
                               . ($r->check_out?->toDateString() ?? $reservation->check_out->toDateString()));

        $taxeTotal = 0.0;
        foreach ($sejours as $rooms) {
            $first  = $rooms->first();
            $nights = (int) (($first->check_in ?? $reservation->check_in)
                ->diffInDays($first->check_out ?? $reservation->check_out));
            $adults = (int) $rooms->sum(fn ($r) => ($r->adults ?? 0) * max(1, $r->quantity ?? 1));
            if ($adults > 0 && $nights > 0) {
                $taxeTotal += round($adults * $nights * $taxeRate, 2);
            }
        }

        $taxeTotal = round($taxeTotal, 2);

        // Mise à jour directe (bypass fillable déjà corrigé, mais on utilise DB pour sécurité)
        \Illuminate\Support\Facades\DB::table('reservations')
            ->where('id', $reservation->id)
            ->update(['taxe_total' => $taxeTotal]);

        $this->line("  #{$reservation->reference} → taxe_total = {$taxeTotal} MAD");
        $updated++;
    }

    $this->info("✓ {$updated} réservation(s) mise(s) à jour.");
})->purpose('Recalcule et stocke taxe_total pour les réservations sans taxe sauvegardée');
