<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('password')->nullable()->after('access_token');
            $table->rememberToken()->after('password');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('payment_amount_requested', 12, 2)->nullable()->after('payment_token_expires_at')
                  ->comment('Montant demandé pour ce lien de paiement');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('proof_path')->nullable()->after('notes')
                  ->comment('Chemin du fichier preuve de paiement');
        });
    }

    public function down(): void
    {
        Schema::table('agencies',     fn($t) => $t->dropColumn(['password', 'remember_token']));
        Schema::table('reservations', fn($t) => $t->dropColumn('payment_amount_requested'));
        Schema::table('payments',     fn($t) => $t->dropColumn('proof_path'));
    }
};
