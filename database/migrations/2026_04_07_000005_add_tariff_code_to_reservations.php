<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('reservations', 'tariff_code')) {
                // Code de la grille tarifaire utilisée lors du calcul de prix
                // Ex: 'NRF' (base), 'AVM' (agences), 'GROUPE_DIRECT' (groupes ≥ 11 ch.)
                $table->string('tariff_code', 30)->nullable()->default('NRF')->after('discount_percent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'tariff_code')) {
                $table->dropColumn('tariff_code');
            }
        });
    }
};
