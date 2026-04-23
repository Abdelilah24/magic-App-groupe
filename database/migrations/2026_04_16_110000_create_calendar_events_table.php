<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();

            // Pays : MA (Maroc) ou FR (France)
            $table->char('country', 2)->index();

            // Type : jours fériés ou vacances scolaires
            $table->enum('type', ['holiday', 'school_vacation']);

            // Libellé de l'événement
            $table->string('name', 255);

            // Dates (bornes incluses côté base de données)
            $table->date('start_date');
            $table->date('end_date');

            // Année de référence (pour le filtrage rapide)
            $table->smallInteger('year')->index();

            // Source : api (auto-synchronisé) ou manual (vacances MA saisies)
            $table->enum('source', ['api', 'manual'])->default('api');

            // Zone scolaire — FR : "Zone A", "Zone B", "Zone C"
            // MA : null
            $table->string('zone', 100)->nullable();

            // Évite les doublons lors d'une re-synchronisation
            $table->unique(['country', 'type', 'start_date', 'end_date', 'zone'], 'calendar_uniq');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
