<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            // Promos long séjour
            $table->boolean('promo_long_stay_enabled')->default(false)->after('is_active');
            $table->unsignedTinyInteger('promo_tier1_nights')->default(4)->after('promo_long_stay_enabled');
            $table->decimal('promo_tier1_rate', 5, 2)->default(10.00)->after('promo_tier1_nights');
            $table->unsignedTinyInteger('promo_tier2_nights')->default(7)->after('promo_tier1_rate');
            $table->decimal('promo_tier2_rate', 5, 2)->default(15.00)->after('promo_tier2_nights');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn([
                'promo_long_stay_enabled',
                'promo_tier1_nights', 'promo_tier1_rate',
                'promo_tier2_nights', 'promo_tier2_rate',
            ]);
        });
    }
};
