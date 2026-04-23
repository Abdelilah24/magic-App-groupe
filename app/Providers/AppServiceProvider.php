<?php

namespace App\Providers;

use App\Models\Reservation;
use App\Policies\ReservationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Reservation::class => ReservationPolicy::class,
    ];

    public function register(): void
    {
        $this->app->register(GoogleDriveServiceProvider::class);
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // ── Gates réservations ───────────────────────────────────────────
        Gate::define('accept-reservation',              [ReservationPolicy::class, 'accept']);
        Gate::define('refuse-reservation',              [ReservationPolicy::class, 'refuse']);
        Gate::define('mark-reservation-paid',           [ReservationPolicy::class, 'markPaid']);
        Gate::define('handle-reservation-modification', [ReservationPolicy::class, 'handleModification']);

        // ── Alias middleware ─────────────────────────────────────────────
        Route::aliasMiddleware('permission', \App\Http\Middleware\EnsurePermission::class);
    }
}
