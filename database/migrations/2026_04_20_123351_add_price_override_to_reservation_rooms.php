<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservation_rooms', function (Blueprint $table) {
            $table->boolean('price_override')->default(false)->after('total_price');
            $table->decimal('original_price_per_night', 10, 2)->nullable()->after('price_override');
            $table->decimal('original_total_price',     10, 2)->nullable()->after('original_price_per_night');
        });
    }

    public function down(): void
    {
        Schema::table('reservation_rooms', function (Blueprint $table) {
            $table->dropColumn(['price_override', 'original_price_per_night', 'original_total_price']);
        });
    }
};
