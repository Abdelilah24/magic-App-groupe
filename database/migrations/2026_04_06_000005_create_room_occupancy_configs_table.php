<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Table principale des configs d'occupation ──────────────────────────
        Schema::create('room_occupancy_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();

            // Code court unique par room_type (ex : QUAD_3, SGL_DBL, COM_5)
            $table->string('code', 30);

            // Libellé affiché (ex : "QUAD 3 — 3 adultes + 1 enfant")
            $table->string('label');

            // Contraintes d'occupation
            $table->unsignedTinyInteger('min_adults')->default(1);
            $table->unsignedTinyInteger('max_adults');
            $table->unsignedTinyInteger('min_children')->default(0);
            $table->unsignedTinyInteger('max_children')->default(0);
            $table->unsignedTinyInteger('min_babies')->default(0);
            $table->unsignedTinyInteger('max_babies')->default(0);

            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['room_type_id', 'code']);
        });

        // ── Colonne occupancy_config_id dans room_prices (nullable = général) ─
        Schema::table('room_prices', function (Blueprint $table) {
            $table->foreignId('occupancy_config_id')
                  ->nullable()
                  ->after('room_type_id')
                  ->constrained('room_occupancy_configs')
                  ->nullOnDelete();
        });

        // ── Colonne occupancy_config_id dans reservation_rooms ────────────────
        Schema::table('reservation_rooms', function (Blueprint $table) {
            $table->foreignId('occupancy_config_id')
                  ->nullable()
                  ->after('room_type_id')
                  ->constrained('room_occupancy_configs')
                  ->nullOnDelete();

            // Libellé de la config enregistré pour l'historique
            $table->string('occupancy_config_label')->nullable()->after('occupancy_config_id');
        });
    }

    public function down(): void
    {
        Schema::table('reservation_rooms', function (Blueprint $table) {
            $table->dropForeign(['occupancy_config_id']);
            $table->dropColumn(['occupancy_config_id', 'occupancy_config_label']);
        });

        Schema::table('room_prices', function (Blueprint $table) {
            $table->dropForeign(['occupancy_config_id']);
            $table->dropColumn('occupancy_config_id');
        });

        Schema::dropIfExists('room_occupancy_configs');
    }
};
