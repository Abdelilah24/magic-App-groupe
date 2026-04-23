<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservation_rooms', function (Blueprint $table) {
            $table->unsignedTinyInteger('adults')->default(1)->after('quantity');
            $table->unsignedTinyInteger('children')->default(0)->after('adults');
            $table->unsignedTinyInteger('babies')->default(0)->after('children');
        });
    }

    public function down(): void
    {
        Schema::table('reservation_rooms', function (Blueprint $table) {
            $table->dropColumn(['adults', 'children', 'babies']);
        });
    }
};
