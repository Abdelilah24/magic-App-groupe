<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Client;
use Illuminate\Support\Facades\Route;

// ─── Auth (Laravel Breeze / Fortify) ──────────────────────────────────────────
// Route::get('/login', ...) est fournie par Breeze — à installer séparément.
 require __DIR__.'/auth.php'; // Décommentez si vous utilisez Breeze

// ─── Admin ────────────────────────────────────────────────────────────────────

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {

    // Dashboard
    Route::get('/', Admin\DashboardController::class . '@index')->name('dashboard');

    // ─── Hôtels ──────────────────────────────────────────────────────────────────
    Route::middleware('permission:hotels.view')->group(function () {
        Route::get('hotels',         [Admin\HotelController::class, 'index'])->name('hotels.index');
        Route::get('hotels/{hotel}', [Admin\HotelController::class, 'show'])->name('hotels.show');
        Route::get('room-types',                [Admin\RoomTypeController::class, 'index'])->name('room-types.index');
    });
    Route::middleware('permission:hotels.manage')->group(function () {
        Route::get('hotels/create',              [Admin\HotelController::class, 'create'])->name('hotels.create');
        Route::post('hotels',                    [Admin\HotelController::class, 'store'])->name('hotels.store');
        Route::get('hotels/{hotel}/edit',        [Admin\HotelController::class, 'edit'])->name('hotels.edit');
        Route::put('hotels/{hotel}',             [Admin\HotelController::class, 'update'])->name('hotels.update');
        Route::delete('hotels/{hotel}',          [Admin\HotelController::class, 'destroy'])->name('hotels.destroy');
        Route::get('room-types/create',          [Admin\RoomTypeController::class, 'create'])->name('room-types.create');
        Route::post('room-types',                [Admin\RoomTypeController::class, 'store'])->name('room-types.store');
        Route::get('room-types/{roomType}/edit', [Admin\RoomTypeController::class, 'edit'])->name('room-types.edit');
        Route::put('room-types/{roomType}',      [Admin\RoomTypeController::class, 'update'])->name('room-types.update');
        Route::delete('room-types/{roomType}',   [Admin\RoomTypeController::class, 'destroy'])->name('room-types.destroy');
    });

    // ─── Tarification ────────────────────────────────────────────────────────────
    Route::middleware('permission:pricing.manage')->group(function () {
        // Tarifs calendrier
        Route::get('room-prices/hotel/{hotel}/room-types',          [Admin\RoomPriceController::class, 'roomTypesByHotel'])->name('room-prices.room-types-by-hotel');
        Route::get('room-prices/hotel/{hotel}/room-types-with-configs', [Admin\RoomPriceController::class, 'roomTypesWithConfigs'])->name('room-prices.room-types-with-configs');
        Route::get('room-prices/calendar-data',     [Admin\RoomPriceController::class, 'calendarData'])->name('room-prices.calendar-data');
        Route::post('room-prices/bulk-update',      [Admin\RoomPriceController::class, 'bulkUpdate'])->name('room-prices.bulk-update');
        Route::get('room-prices/table',             [Admin\RoomPriceController::class, 'table'])->name('room-prices.table');
        Route::get('room-prices/table/export',      [Admin\RoomPriceController::class, 'exportExcel'])->name('room-prices.export');
        Route::post('room-prices/table/save',       [Admin\RoomPriceController::class, 'tableSave'])->name('room-prices.table-save');
        Route::post('room-prices/table/delete-period', [Admin\RoomPriceController::class, 'deletePeriod'])->name('room-prices.delete-period');
        Route::get('room-prices/history',           [Admin\RoomPriceController::class, 'history'])->name('room-prices.history');
        Route::resource('room-prices', Admin\RoomPriceController::class)->except(['show']);

        // Grilles tarifaires
        Route::get('tariff-grids',                  [Admin\TariffGridController::class, 'index'])->name('tariff-grids.index');
        Route::post('tariff-grids',                 [Admin\TariffGridController::class, 'store'])->name('tariff-grids.store');
        Route::post('tariff-grids/init-defaults',   [Admin\TariffGridController::class, 'initDefaults'])->name('tariff-grids.init-defaults');
        Route::put('tariff-grids/{tariffGrid}',     [Admin\TariffGridController::class, 'update'])->name('tariff-grids.update');
        Route::delete('tariff-grids/{tariffGrid}',  [Admin\TariffGridController::class, 'destroy'])->name('tariff-grids.destroy');
    });

    // ─── Configs d'occupation ─────────────────────────────────────────────────
    Route::middleware('permission:occupancy.manage')->group(function () {
        Route::get('occupancy-configs',                        [Admin\OccupancyConfigController::class, 'index'])->name('occupancy-configs.index');
        Route::get('occupancy-configs/hotel/{hotel}/room-types', [Admin\OccupancyConfigController::class, 'byHotel'])->name('occupancy-configs.by-hotel');
        Route::get('occupancy-configs/room-type/{roomType}',   [Admin\OccupancyConfigController::class, 'byRoomType'])->name('occupancy-configs.by-room-type');
        Route::post('occupancy-configs/bulk',                  [Admin\OccupancyConfigController::class, 'bulkStore'])->name('occupancy-configs.bulk');
        Route::post('occupancy-configs',                       [Admin\OccupancyConfigController::class, 'store'])->name('occupancy-configs.store');
        Route::put('occupancy-configs/{occupancyConfig}',      [Admin\OccupancyConfigController::class, 'update'])->name('occupancy-configs.update');
        Route::delete('occupancy-configs/{occupancyConfig}',   [Admin\OccupancyConfigController::class, 'destroy'])->name('occupancy-configs.destroy');
    });

    // ─── Réservations ────────────────────────────────────────────────────────────
    Route::middleware('permission:reservations.view')->group(function () {
        Route::get('reservations',               [Admin\ReservationController::class, 'index'])->name('reservations.index');
        Route::get('reservations/agenda',        [Admin\ReservationController::class, 'agenda'])->name('reservations.agenda');
        Route::get('reservations/agenda-depart', [Admin\ReservationController::class, 'agendaDepart'])->name('reservations.agenda-depart');
        Route::get('reservations/{reservation}', [Admin\ReservationController::class, 'show'])->name('reservations.show');
        Route::get('reservations/{reservation}/guests/export', [Admin\ReservationController::class, 'exportGuests'])->name('reservations.guests.export');
        Route::patch('reservations/{reservation}/mark-unread', [Admin\ReservationController::class, 'markUnread'])->name('reservations.mark-unread');
    });

    Route::middleware('permission:reservations.edit')->group(function () {
        Route::get('reservations/{reservation}/edit',   [Admin\ReservationController::class, 'edit'])->name('reservations.edit');
        Route::patch('reservations/{reservation}/update',[Admin\ReservationController::class, 'updateReservation'])->name('reservations.update');
        // Prix / tarifs par chambre
        Route::patch('reservations/{reservation}/rooms/{room}/price',  [Admin\ReservationController::class, 'updateRoomPrice'])->name('reservations.rooms.update-price');
        Route::post('reservations/{reservation}/rooms/batch-price',    [Admin\ReservationController::class, 'batchUpdateRoomPrices'])->name('reservations.rooms.batch-price');
    });

    Route::patch('reservations/{reservation}/accept',  [Admin\ReservationController::class, 'accept'])
         ->middleware('permission:reservations.accept')->name('reservations.accept');

    Route::patch('reservations/{reservation}/refuse',  [Admin\ReservationController::class, 'refuse'])
         ->middleware('permission:reservations.refuse')->name('reservations.refuse');

    Route::middleware('permission:reservations.handle_modification')->group(function () {
        Route::patch('reservations/{reservation}/accept-modification', [Admin\ReservationController::class, 'acceptModification'])->name('reservations.accept-modification');
        Route::patch('reservations/{reservation}/refuse-modification', [Admin\ReservationController::class, 'refuseModification'])->name('reservations.refuse-modification');
    });

    Route::middleware('permission:reservations.proforma')->group(function () {
        Route::post('reservations/{reservation}/resend-quote',    [Admin\ReservationController::class, 'resendQuote'])->name('reservations.resend-quote');
        Route::get('reservations/{reservation}/proforma',         [Admin\ReservationController::class, 'proforma'])->name('reservations.proforma');
        Route::post('reservations/{reservation}/proforma/send',   [Admin\ReservationController::class, 'sendProforma'])->name('reservations.proforma.send');
    });

    Route::middleware('permission:reservations.payments')->group(function () {
        Route::patch('reservations/{reservation}/mark-paid',                     [Admin\ReservationController::class, 'markPaid'])->name('reservations.mark-paid');
        Route::patch('reservations/{reservation}/status',                        [Admin\ReservationController::class, 'updateStatus'])->name('reservations.update-status');
        Route::patch('reservations/{reservation}/payments/{payment}/validate',   [Admin\ReservationController::class, 'validatePayment'])->name('reservations.payments.validate');
        Route::post('reservations/{reservation}/set-deadline',                   [Admin\ReservationController::class, 'setDeadline'])->name('reservations.set-deadline');
        Route::post('reservations/{reservation}/schedules',                      [Admin\ReservationController::class, 'storeSchedule'])->name('reservations.schedules.store');
        Route::patch('reservations/{reservation}/schedules/{schedule}',          [Admin\ReservationController::class, 'updateSchedule'])->name('reservations.schedules.update');
        Route::delete('reservations/{reservation}/schedules/{schedule}',         [Admin\ReservationController::class, 'destroySchedule'])->name('reservations.schedules.destroy');
        Route::patch('reservations/{reservation}/schedules/{schedule}/pay',      [Admin\ReservationController::class, 'markSchedulePaid'])->name('reservations.schedules.pay');
    });


    // ─── Suppléments / Événements ────────────────────────────────────────────────
    Route::middleware('permission:supplements.manage')->group(function () {
        Route::resource('supplements', Admin\SupplementController::class)->except(['show']);
    });

    // ─── Statuts tarifaires agences ───────────────────────────────────────────
    Route::middleware('permission:pricing.manage')->group(function () {
        Route::resource('agency-statuses', Admin\AgencyStatusController::class)->except(['show']);
    });

    // ─── Agences ──────────────────────────────────────────────────────────────────
    Route::middleware('permission:agencies.view')->group(function () {
        Route::get('agencies',          [Admin\AgencyController::class, 'index'])->name('agencies.index');
        Route::get('agencies/{agency}', [Admin\AgencyController::class, 'show'])->name('agencies.show');
    });
    Route::middleware('permission:agencies.manage')->group(function () {
        Route::post('agencies/{agency}/reset-password',         [Admin\AgencyController::class, 'resetPassword'])->name('agencies.reset-password');
        Route::patch('agencies/{agency}/update-status',         [Admin\AgencyController::class, 'updateAgencyStatus'])->name('agencies.update-status');
        Route::delete('agencies/{agency}',                      [Admin\AgencyController::class, 'destroy'])->name('agencies.destroy');
    });
    Route::middleware('permission:agencies.approve')->group(function () {
        Route::patch('agencies/{agency}/approve',               [Admin\AgencyController::class, 'approve'])->name('agencies.approve');
        Route::patch('agencies/{agency}/reject',                [Admin\AgencyController::class, 'reject'])->name('agencies.reject');
        Route::post('agencies/{agency}/approve-profile-change', [Admin\AgencyController::class, 'approveProfileChange'])->name('agencies.approve-profile-change');
        Route::post('agencies/{agency}/reject-profile-change',  [Admin\AgencyController::class, 'rejectProfileChange'])->name('agencies.reject-profile-change');
    });

    // ─── Calendrier ───────────────────────────────────────────────────────────────
    Route::middleware('permission:calendar.manage')->group(function () {
        Route::get('calendar',                        [Admin\CalendarController::class, 'index'])->name('calendar.index');
        Route::get('calendar/list',                   [Admin\CalendarController::class, 'list'])->name('calendar.list');
        Route::get('calendar/events',                 [Admin\CalendarController::class, 'events'])->name('calendar.events');
        Route::post('calendar/sync',                  [Admin\CalendarController::class, 'sync'])->name('calendar.sync');
        Route::post('calendar/manual',                [Admin\CalendarController::class, 'storeManualEvent'])->name('calendar.manual.store');
        Route::put('calendar/manual/{calendarEvent}', [Admin\CalendarController::class, 'updateManualEvent'])->name('calendar.manual.update');
        Route::delete('calendar/manual/{calendarEvent}', [Admin\CalendarController::class, 'destroyManualEvent'])->name('calendar.manual.destroy');
        Route::post('calendar/ma-vacations',          [Admin\CalendarController::class, 'storeMaVacation'])->name('calendar.ma-vacations.store');
        Route::put('calendar/ma-vacations/{calendarEvent}',    [Admin\CalendarController::class, 'updateMaVacation'])->name('calendar.ma-vacations.update');
        Route::delete('calendar/ma-vacations/{calendarEvent}', [Admin\CalendarController::class, 'destroyMaVacation'])->name('calendar.ma-vacations.destroy');
    });

    // ─── Motifs de refus ──────────────────────────────────────────────────────────
    Route::middleware('permission:refusal_reasons.manage')->group(function () {
        Route::get('refusal-reasons',                  [Admin\RefusalReasonController::class, 'index'])->name('refusal-reasons.index');
        Route::post('refusal-reasons',                 [Admin\RefusalReasonController::class, 'store'])->name('refusal-reasons.store');
        Route::put('refusal-reasons/{refusalReason}',  [Admin\RefusalReasonController::class, 'update'])->name('refusal-reasons.update');
        Route::delete('refusal-reasons/{refusalReason}',[Admin\RefusalReasonController::class, 'destroy'])->name('refusal-reasons.destroy');
    });

    // ─── Templates emails & PDF ───────────────────────────────────────────────────
    Route::middleware('permission:templates.manage')->group(function () {
        Route::get('email-templates',                       [Admin\EmailTemplateController::class, 'index'])->name('email-templates.index');
        Route::get('email-templates/{emailTemplate}/edit',  [Admin\EmailTemplateController::class, 'edit'])->name('email-templates.edit');
        Route::put('email-templates/{emailTemplate}',       [Admin\EmailTemplateController::class, 'update'])->name('email-templates.update');
        Route::get('email-templates/{emailTemplate}/preview',[Admin\EmailTemplateController::class, 'preview'])->name('email-templates.preview');

        Route::get('pdf-templates',                         [Admin\PdfTemplateController::class, 'index'])->name('pdf-templates.index');
        Route::get('pdf-templates/{pdfTemplate}/edit',      [Admin\PdfTemplateController::class, 'edit'])->name('pdf-templates.edit');
        Route::put('pdf-templates/{pdfTemplate}',           [Admin\PdfTemplateController::class, 'update'])->name('pdf-templates.update');
        Route::get('pdf-templates/{pdfTemplate}/preview',   [Admin\PdfTemplateController::class, 'preview'])->name('pdf-templates.preview');
    });

    // ─── Services Extras (catalogue) ──────────────────────────────────────────────
    Route::middleware('permission:extra_services.manage')->group(function () {
        Route::get('extra-services',                [Admin\ExtraServiceController::class, 'index'])->name('extra-services.index');
        Route::post('extra-services',               [Admin\ExtraServiceController::class, 'store'])->name('extra-services.store');
        Route::put('extra-services/{extraService}', [Admin\ExtraServiceController::class, 'update'])->name('extra-services.update');
        Route::delete('extra-services/{extraService}',[Admin\ExtraServiceController::class, 'destroy'])->name('extra-services.destroy');
    });

    // ─── Extras sur une réservation ───────────────────────────────────────────────
    Route::middleware('permission:reservations.extras')->group(function () {
        Route::post('reservations/{reservation}/extras',          [Admin\ReservationExtraController::class, 'store'])->name('reservations.extras.store');
        Route::delete('reservations/{reservation}/extras/{extra}',[Admin\ReservationExtraController::class, 'destroy'])->name('reservations.extras.destroy');
    });

    // ─── Profil administrateur ────────────────────────────────────────────────
    Route::get('profile',                  [Admin\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile/password',         [Admin\ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('profile/email',            [Admin\ProfileController::class, 'updateEmail'])->name('profile.email');
    Route::put('profile/settings',         [Admin\ProfileController::class, 'updateSettings'])->name('profile.settings');
    Route::put('profile/logo',             [Admin\ProfileController::class, 'updateLogo'])->name('profile.logo');
    Route::delete('profile/logo',          [Admin\ProfileController::class, 'deleteLogo'])->name('profile.logo.delete');

    // ─── Gestion des rôles (super_admin uniquement) ──────────────────────────────
    Route::get('roles',                    [Admin\RoleController::class, 'index'])->name('roles.index');
    Route::get('roles/create',             [Admin\RoleController::class, 'create'])->name('roles.create');
    Route::post('roles',                   [Admin\RoleController::class, 'store'])->name('roles.store');
    Route::get('roles/{role}/edit',        [Admin\RoleController::class, 'edit'])->name('roles.edit');
    Route::put('roles/{role}',             [Admin\RoleController::class, 'update'])->name('roles.update');
    Route::delete('roles/{role}',          [Admin\RoleController::class, 'destroy'])->name('roles.destroy');

    // ─── Gestion des utilisateurs admin ──────────────────────────────────────────
    Route::middleware('permission:users.manage')->group(function () {
        Route::get('users',                [Admin\UserController::class, 'index'])->name('users.index');
        Route::get('users/create',         [Admin\UserController::class, 'create'])->name('users.create');
        Route::post('users',               [Admin\UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit',    [Admin\UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}',         [Admin\UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}',      [Admin\UserController::class, 'destroy'])->name('users.destroy');
    });
});

// ─── Client (via token) ───────────────────────────────────────────────────────


// Calcul de prix AJAX (sans auth)
Route::post('/api/calculate-price', [Client\ReservationController::class, 'calculatePrice'])->name('client.calculate-price');

// Gestion réservation existante
Route::prefix('r/{token}/reservations/{reservation}')->name('client.reservation.')->group(function () {
    Route::get('/',         [Client\ReservationController::class, 'show'])->name('show');
    Route::get('/edit',     [Client\ReservationController::class, 'editForm'])->name('edit');
    Route::patch('/',       [Client\ReservationController::class, 'update'])->name('update');
    Route::patch('/cancel', [Client\ReservationController::class, 'cancel'])->name('cancel');
    // Fiche de police
    Route::get('/guests',  [Client\GuestRegistrationController::class, 'form'])->name('guests.form');
    Route::post('/guests', [Client\GuestRegistrationController::class, 'save'])->name('guests.save');
});

// Page de paiement (via token de paiement)
Route::get('/pay/{paymentToken}', [Client\PaymentController::class, 'show'])->name('client.payment');
// Soumettre la preuve pour une échéance
Route::post('/pay/{paymentToken}/schedules/{schedule}/proof', [Client\PaymentController::class, 'submitScheduleProof'])->name('client.payment.schedule.proof');

// ─── Inscription agence (public) ─────────────────────────────────────────────

Route::get('/devenir-partenaire',         [\App\Http\Controllers\AgencyRegistrationController::class, 'create'])->name('agency.register');
Route::post('/devenir-partenaire',        [\App\Http\Controllers\AgencyRegistrationController::class, 'store'])->name('agency.register.store');
Route::get('/devenir-partenaire/merci',   [\App\Http\Controllers\AgencyRegistrationController::class, 'success'])->name('agency.register.success');

// ─── Authentification agence ─────────────────────────────────────────────────

Route::get('/espace-agence/connexion',    [\App\Http\Controllers\AgencyAuthController::class, 'showLogin'])->name('agency.login');
Route::post('/espace-agence/connexion',   [\App\Http\Controllers\AgencyAuthController::class, 'login'])->name('agency.login.post');
Route::post('/espace-agence/deconnexion', [\App\Http\Controllers\AgencyAuthController::class, 'logout'])->name('agency.logout');

// ─── Mot de passe oublié (agence) ────────────────────────────────────────────

Route::get('/espace-agence/mot-de-passe-oublie',         [\App\Http\Controllers\AgencyPasswordResetController::class, 'showForgotForm'])->name('agency.password.forgot');
Route::post('/espace-agence/mot-de-passe-oublie',        [\App\Http\Controllers\AgencyPasswordResetController::class, 'sendResetLink'])->name('agency.password.forgot.send');
Route::get('/espace-agence/reinitialiser-mot-de-passe/{token}',  [\App\Http\Controllers\AgencyPasswordResetController::class, 'showResetForm'])->name('agency.password.reset.form');
Route::post('/espace-agence/reinitialiser-mot-de-passe', [\App\Http\Controllers\AgencyPasswordResetController::class, 'reset'])->name('agency.password.reset');

// ─── Portail agence (protégé) ─────────────────────────────────────────────────

Route::prefix('espace-agence')->name('agency.portal.')->middleware(\App\Http\Middleware\AgencyMiddleware::class)->group(function () {
    Route::get('/',                    [\App\Http\Controllers\AgencyPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('profil',               [\App\Http\Controllers\AgencyPortalController::class, 'editProfile'])->name('profile');
    Route::patch('profil',             [\App\Http\Controllers\AgencyPortalController::class, 'updateProfile'])->name('profile.update');
    Route::patch('profil/mot-de-passe', [\App\Http\Controllers\AgencyPortalController::class, 'updatePassword'])->name('profile.password');
    Route::post('reservations/{reservation}/payer',
                                       [\App\Http\Controllers\AgencyPortalController::class, 'payReservation'])->name('pay');
    Route::get('reservations/{reservation}',
                                       [\App\Http\Controllers\AgencyPortalController::class, 'showReservation'])->name('show-reservation');
    Route::get('reservations/{reservation}/fiche-police',
                                       [\App\Http\Controllers\AgencyPortalController::class, 'guestForm'])->name('guest-form');
    Route::post('reservations/{reservation}/fiche-police',
                                       [\App\Http\Controllers\AgencyPortalController::class, 'saveGuests'])->name('save-guests');
    Route::post('reservations/{reservation}/fiche-police/autosave',
                                       [\App\Http\Controllers\AgencyPortalController::class, 'autosaveGuests'])->name('autosave-guests');
    Route::get('reservations/{reservation}/modifier',
                                       [\App\Http\Controllers\AgencyPortalController::class, 'editReservation'])->name('edit-reservation');
    Route::patch('reservations/{reservation}/modifier',
                                       [\App\Http\Controllers\AgencyPortalController::class, 'updateReservation'])->name('update-reservation');
    Route::patch('reservations/{reservation}/annuler',
                                       [\App\Http\Controllers\AgencyPortalController::class, 'cancelReservation'])->name('cancel-reservation');
    Route::post('reserver',            [\App\Http\Controllers\AgencyPortalController::class, 'storeReservation'])->name('store-reservation');
    Route::post('reservations/{reservation}/dupliquer',
                                       [\App\Http\Controllers\AgencyPortalController::class, 'duplicateReservation'])->name('duplicate-reservation');
});

// ─── Redirect par défaut ──────────────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('admin.dashboard'))->middleware('auth');
