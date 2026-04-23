<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Étend l'enum status avec 'partially_paid'
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM(
            'draft',
            'pending',
            'accepted',
            'refused',
            'waiting_payment',
            'partially_paid',
            'paid',
            'confirmed',
            'modification_pending',
            'cancelled'
        ) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM(
            'draft',
            'pending',
            'accepted',
            'refused',
            'waiting_payment',
            'paid',
            'confirmed',
            'modification_pending',
            'cancelled'
        ) NOT NULL DEFAULT 'draft'");
    }
};
