<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservation_rooms', function (Blueprint $table) {
            if (! Schema::hasColumn('reservation_rooms', 'occupancy_config_id')) {
                $table->foreignId('occupancy_config_id')
                      ->nullable()
                      ->after('room_type_id')
                      ->constrained('room_occupancy_configs')
                      ->nullOnDelete();
            }

            if (! Schema::hasColumn('reservation_rooms', 'occupancy_config_label')) {
                $table->string('occupancy_config_label')->nullable()->after('occupancy_config_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservation_rooms', function (Blueprint $table) {
            $table->dropForeign(['occupancy_config_id']);
            $table->dropColumn(['occupancy_config_id', 'occupancy_config_label']);
        });
    }
};
