<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->onDelete('cascade');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->date('date');                                    // Date à laquelle le supplément s'applique
            $table->enum('status', ['mandatory', 'optional'])->default('optional');
            $table->decimal('price_adult', 10, 2)->default(0);      // Prix par adulte
            $table->decimal('price_child', 10, 2)->default(0);      // Prix par enfant
            $table->decimal('price_baby',  10, 2)->default(0);      // Prix par bébé
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplements');
    }
};
