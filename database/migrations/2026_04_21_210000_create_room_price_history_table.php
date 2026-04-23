<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_price_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hotel_id')->index();
            $table->unsignedBigInteger('occupancy_config_id')->nullable();
            $table->unsignedBigInteger('room_type_id')->nullable();
            $table->date('date_from');
            $table->date('date_to');
            $table->string('label')->nullable();
            $table->decimal('old_price', 10, 2)->nullable(); // null = new record
            $table->decimal('new_price', 10, 2);
            $table->decimal('delta', 10, 2)->nullable();     // new - old, null if new
            $table->unsignedBigInteger('changed_by_id')->nullable();
            $table->string('changed_by_name', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_price_history');
    }
};
