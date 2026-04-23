<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();           // 'agency_approved', 'payment_request'…
            $table->string('name');                    // Nom lisible
            $table->string('description')->nullable(); // Description courte
            $table->string('subject');                 // Sujet (peut contenir {{ placeholders }})
            $table->longText('html_body');             // Corps HTML éditable
            $table->json('placeholders')->nullable();  // Liste des variables disponibles
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
