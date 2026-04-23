<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Table des permissions ────────────────────────────────────────
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();         // 'reservations.accept'
            $table->string('label');                        // 'Accepter une réservation'
            $table->string('group', 100);                  // 'Réservations'
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ── Pivot rôle ↔ permissions (rôle stocké comme string) ─────────
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 50);                    // 'admin', 'staff'
            $table->foreignId('permission_id')
                  ->constrained('permissions')
                  ->cascadeOnDelete();
            $table->unique(['role', 'permission_id']);
        });

        // ── Ajouter ROLE_SUPER_ADMIN aux utilisateurs existants de type admin ─
        // (fait via le seeder, pas ici pour éviter une dépendance data dans migration)
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
    }
};
