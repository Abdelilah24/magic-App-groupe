<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_adults')->nullable()->after('max_persons');
            $table->unsignedTinyInteger('max_children')->nullable()->after('max_adults');
            $table->boolean('baby_bed_available')->default(false)->after('max_children');
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn(['max_adults', 'max_children', 'baby_bed_available']);
        });
    }
};
