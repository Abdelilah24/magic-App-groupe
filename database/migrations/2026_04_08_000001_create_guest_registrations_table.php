<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();

            // Ordre et type de voyageur
            $table->unsignedTinyInteger('guest_index')->default(1); // 1, 2, 3…
            $table->enum('guest_type', ['adult', 'child', 'baby'])->default('adult');

            // Identité
            $table->enum('civilite', ['M.', 'Mme', 'Mlle', 'Autre'])->nullable();
            $table->string('nom');
            $table->string('prenom');
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();
            $table->string('pays_naissance')->nullable();
            $table->string('nationalite')->nullable();

            // Document d'identité (requis fiche de police Maroc)
            $table->enum('type_document', ['passeport', 'cni', 'titre_sejour', 'autre'])->nullable();
            $table->string('numero_document')->nullable();
            $table->date('date_expiration_document')->nullable();
            $table->string('pays_emission_document')->nullable();

            // Coordonnées
            $table->string('adresse')->nullable();
            $table->string('ville')->nullable();
            $table->string('code_postal', 20)->nullable();
            $table->string('pays_residence')->nullable();

            // Profession
            $table->string('profession')->nullable();

            $table->timestamps();

            $table->unique(['reservation_id', 'guest_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_registrations');
    }
};
