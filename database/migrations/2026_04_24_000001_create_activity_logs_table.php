<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_type')->nullable();      // Classe du modèle
            $table->unsignedBigInteger('subject_id')->nullable(); // ID du modèle
            $table->string('event', 20);                    // created | updated | deleted | custom
            $table->string('section', 80);                  // Réservations, Agences, etc.
            $table->string('description')->nullable();       // Ex : "MH-2026-00127"
            $table->json('properties')->nullable();          // {changed:[], old:{}, new:{}}
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['section', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
