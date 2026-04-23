<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('image');
            $table->string('bank_name')->nullable()->after('description');
            $table->string('bank_rib')->nullable()->after('bank_name');
            $table->string('bank_iban')->nullable()->after('bank_rib');
            $table->string('bank_swift')->nullable()->after('bank_iban');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn(['logo', 'bank_name', 'bank_rib', 'bank_iban', 'bank_swift']);
        });
    }
};
