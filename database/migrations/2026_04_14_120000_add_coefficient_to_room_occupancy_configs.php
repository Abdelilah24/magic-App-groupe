<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_occupancy_configs', function (Blueprint $table) {
            $table->decimal('coefficient', 10, 4)->default(1.0000)->after('sort_order')
                  ->comment('Multiplicateur appliqué au taux de base pour calculer le prix de cette configuration');
        });
    }

    public function down(): void
    {
        Schema::table('room_occupancy_configs', function (Blueprint $table) {
            $table->dropColumn('coefficient');
        });
    }
};
