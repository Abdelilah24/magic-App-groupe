<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->unsignedSmallInteger('min_persons')->default(1)->after('capacity');
            $table->unsignedSmallInteger('max_persons')->default(2)->after('min_persons');
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn(['min_persons', 'max_persons']);
        });
    }
};
