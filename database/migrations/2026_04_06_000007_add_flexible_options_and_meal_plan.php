<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Options flexibles sur les réservations
        Schema::table('reservations', function (Blueprint $table) {
            $table->boolean('flexible_dates')->default(false)->after('special_requests');
            $table->boolean('flexible_hotel')->default(false)->after('flexible_dates');
        });

        // Régime de pension sur les hôtels
        Schema::table('hotels', function (Blueprint $table) {
            $table->string('meal_plan')->nullable()->after('taxe_sejour');
            // Valeurs : all_inclusive | bed_and_breakfast | half_board | full_board
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['flexible_dates', 'flexible_hotel']);
        });

        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn('meal_plan');
        });
    }
};
