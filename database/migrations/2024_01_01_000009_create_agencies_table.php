<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('contact_name');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Maroc');
            $table->string('website')->nullable();
            $table->text('notes')->nullable();

            // Statut d'approbation
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });

        // Ajouter la colonne agency_id dans secure_links et reservations
        Schema::table('secure_links', function (Blueprint $table) {
            $table->foreignId('agency_id')->nullable()->after('agency_email')
                  ->constrained('agencies')->nullOnDelete();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('agency_id')->nullable()->after('secure_link_id')
                  ->constrained('agencies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', fn($t) => $t->dropForeignIdFor(\App\Models\Agency::class));
        Schema::table('secure_links',  fn($t) => $t->dropForeignIdFor(\App\Models\Agency::class));
        Schema::dropIfExists('agencies');
    }
};
