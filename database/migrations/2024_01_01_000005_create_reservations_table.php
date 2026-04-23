<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique()->comment('Ex: MH-2024-00001');
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('secure_link_id')->nullable()->constrained()->nullOnDelete();

            // Informations agence/client
            $table->string('agency_name');
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone')->nullable();

            // Détails séjour
            $table->date('check_in');
            $table->date('check_out');
            $table->integer('nights')->storedAs('DATEDIFF(check_out, check_in)');
            $table->integer('total_persons')->default(1);
            $table->text('special_requests')->nullable();

            // Pricing
            $table->decimal('total_price', 12, 2)->nullable();
            $table->string('currency', 3)->default('MAD');
            $table->json('price_breakdown')->nullable()->comment('Détail du calcul par type de chambre');

            // Statut
            $table->enum('status', [
                'draft',
                'pending',
                'accepted',
                'refused',
                'waiting_payment',
                'paid',
                'confirmed',
                'modification_pending',
                'cancelled',
            ])->default('draft');

            // Modification
            $table->json('modification_data')->nullable()->comment('Données de la modification en attente');
            $table->string('previous_status')->nullable();

            // Admin
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable();
            $table->text('refusal_reason')->nullable();

            // Paiement
            $table->string('payment_token', 64)->nullable()->unique();
            $table->timestamp('payment_token_expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'check_in']);
            $table->index(['email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
