<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refusal_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('label', 200);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Motifs par défaut
        $defaults = [
            ['label' => 'Disponibilité insuffisante pour les dates demandées', 'sort_order' => 1],
            ['label' => 'Tarif non conforme aux conditions tarifaires en vigueur', 'sort_order' => 2],
            ['label' => 'Demande incomplète ou informations manquantes', 'sort_order' => 3],
            ['label' => 'Groupe trop important pour la capacité de l\'hôtel', 'sort_order' => 4],
            ['label' => 'Période de fermeture ou d\'événement exclusif', 'sort_order' => 5],
            ['label' => 'Non-respect des conditions de réservation', 'sort_order' => 6],
            ['label' => 'Autre', 'sort_order' => 99],
        ];

        foreach ($defaults as $item) {
            DB::table('refusal_reasons')->insert(array_merge($item, [
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('refusal_reasons');
    }
};
