<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');                              // Ex: "Agence de voyages"
            $table->string('slug')->unique();                   // Ex: "agence-de-voyages"
            $table->decimal('discount_percent', 5, 2)->default(0); // Ex: 10.00 → -10%
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);      // 1 seul statut par défaut
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_statuses');
    }
};
