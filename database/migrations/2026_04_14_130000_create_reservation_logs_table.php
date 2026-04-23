<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();

            // Type d'événement
            $table->string('event_type', 50)
                  ->comment('created|status_changed|modification_requested|modification_accepted|modification_refused|payment_added|payment_validated|payment_refused|devis_sent|price_recalculated|cancelled');

            // Résumé lisible
            $table->string('summary', 500);

            // Raison / commentaire libre
            $table->text('reason')->nullable();

            // Snapshots avant / après
            $table->json('old_data')->nullable()->comment('Snapshot des valeurs avant le changement');
            $table->json('new_data')->nullable()->comment('Snapshot des valeurs après le changement');

            // Acteur
            $table->string('actor_type', 20)->default('system')->comment('admin|agency|system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name', 100)->nullable();

            $table->timestamps();

            $table->index(['reservation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_logs');
    }
};
