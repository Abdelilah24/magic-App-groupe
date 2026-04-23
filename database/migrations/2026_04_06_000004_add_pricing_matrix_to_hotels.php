<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->unsignedBigInteger('pricing_base_room_type_id')->nullable()->after('promo_tier2_rate');
            $table->json('room_type_price_offsets')->nullable()->after('pricing_base_room_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn(['pricing_base_room_type_id', 'room_type_price_offsets']);
        });
    }
};
