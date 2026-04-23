<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_supplements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplement_id')->constrained()->onDelete('cascade');
            $table->unsignedSmallInteger('adults_count')->default(0);
            $table->unsignedSmallInteger('children_count')->default(0);
            $table->unsignedSmallInteger('babies_count')->default(0);
            $table->decimal('unit_price_adult', 10, 2)->default(0);
            $table->decimal('unit_price_child',  10, 2)->default(0);
            $table->decimal('unit_price_baby',   10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->boolean('is_mandatory')->default(false);
            $table->timestamps();
        });

        // Ajouter supplement_total à reservations
        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('supplement_total', 10, 2)->nullable()->after('total_price');
            $table->decimal('promo_discount_rate', 5, 2)->nullable()->after('supplement_total');
            $table->decimal('promo_discount_amount', 10, 2)->nullable()->after('promo_discount_rate');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_supplements');
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['supplement_total', 'promo_discount_rate', 'promo_discount_amount']);
        });
    }
};
