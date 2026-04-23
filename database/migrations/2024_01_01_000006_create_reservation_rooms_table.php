<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('price_per_night', 10, 2)->nullable()->comment('Prix calculé au moment de l\'acceptation');
            $table->decimal('total_price', 12, 2)->nullable()->comment('Total pour ce type');
            $table->json('price_detail')->nullable()->comment('Détail nuit par nuit');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_rooms');
    }
};
