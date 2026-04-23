<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lier les agences à leur statut tarifaire
        Schema::table('agencies', function (Blueprint $table) {
            $table->foreignId('agency_status_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('agency_statuses')
                  ->nullOnDelete();
        });

        // Stocker la remise appliquée sur chaque réservation
        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->default(0)->after('total_price');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('discount_percent');
        });
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agency_status_id');
        });
    }
};
