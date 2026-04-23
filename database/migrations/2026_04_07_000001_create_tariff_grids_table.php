<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariff_grids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->onDelete('cascade');

            // Identité
            $table->string('name');         // "Site web NRF", "AVM - FIT NRF", …
            $table->string('code', 30);     // "NRF", "AVM", "GROUPE_DIRECT", …

            // Grille de base (saisie manuelle) vs grille calculée
            $table->boolean('is_base')->default(false);

            // Formule : basée sur quelle autre grille ?
            $table->foreignId('base_grid_id')->nullable()->constrained('tariff_grids')->nullOnDelete();

            // Opérateur : 'divide' | 'multiply' | 'subtract_percent'
            $table->string('operator', 20)->nullable();
            // Valeur opérateur : ex. 1.1 pour ÷1.1, 0.96 pour ×0.96, 4 pour -4%
            $table->decimal('operator_value', 10, 4)->nullable();

            // Arrondi : 'none' | 'round' | 'ceil' | 'floor'
            $table->string('rounding', 10)->default('round');

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['hotel_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_grids');
    }
};
