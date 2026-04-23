<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('group_discount_amount', 10, 2)->nullable()->default(null)->after('discount_percent');
            $table->json('group_discount_detail')->nullable()->default(null)->after('group_discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['group_discount_amount', 'group_discount_detail']);
        });
    }
};
