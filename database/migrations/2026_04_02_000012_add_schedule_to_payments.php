<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('payment_schedule_id')
                  ->nullable()
                  ->after('reservation_id')
                  ->constrained('payment_schedules')
                  ->nullOnDelete();
            $table->boolean('submitted_by_client')->default(false)->after('proof_path');
            $table->timestamp('submitted_at')->nullable()->after('submitted_by_client');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_schedule_id');
            $table->dropColumn(['submitted_by_client', 'submitted_at']);
        });
    }
};
