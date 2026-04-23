<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->date('date_from');
            $table->date('date_to');
            $table->decimal('price_per_night', 10, 2);
            $table->string('currency', 3)->default('MAD');
            $table->string('label')->nullable()->comment('Ex: Haute saison, Basse saison');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['room_type_id', 'date_from', 'date_to']);
            $table->index(['hotel_id', 'date_from', 'date_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_prices');
    }
};
