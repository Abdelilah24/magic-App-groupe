<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration corrective : ajoute les colonnes manquantes à la table reservations
 * si elles n'ont pas été créées par les migrations précédentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {

            if (! Schema::hasColumn('reservations', 'supplement_total')) {
                $table->decimal('supplement_total', 10, 2)->nullable()->after('total_price');
            }

            if (! Schema::hasColumn('reservations', 'promo_discount_rate')) {
                $table->decimal('promo_discount_rate', 5, 2)->nullable()->after('supplement_total');
            }

            if (! Schema::hasColumn('reservations', 'promo_discount_amount')) {
                $table->decimal('promo_discount_amount', 10, 2)->nullable()->after('promo_discount_rate');
            }

            if (! Schema::hasColumn('reservations', 'group_discount_amount')) {
                $table->decimal('group_discount_amount', 10, 2)->nullable()->default(null)->after('discount_percent');
            }

            if (! Schema::hasColumn('reservations', 'group_discount_detail')) {
                $table->json('group_discount_detail')->nullable()->default(null)->after('group_discount_amount');
            }

            if (! Schema::hasColumn('reservations', 'payment_deadline')) {
                $table->date('payment_deadline')->nullable()->default(null);
            }
        });

        // Colonnes manquantes sur reservation_rooms
        Schema::table('reservation_rooms', function (Blueprint $table) {

            if (! Schema::hasColumn('reservation_rooms', 'occupancy_config_id')) {
                $table->foreignId('occupancy_config_id')
                      ->nullable()
                      ->after('room_type_id')
                      ->constrained('room_occupancy_configs')
                      ->nullOnDelete();
            }

            if (! Schema::hasColumn('reservation_rooms', 'occupancy_config_label')) {
                $table->string('occupancy_config_label')->nullable()->after('occupancy_config_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $cols = ['supplement_total', 'promo_discount_rate', 'promo_discount_amount',
                     'group_discount_amount', 'group_discount_detail', 'payment_deadline'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('reservations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('reservation_rooms', function (Blueprint $table) {
            if (Schema::hasColumn('reservation_rooms', 'occupancy_config_id')) {
                $table->dropForeign(['occupancy_config_id']);
                $table->dropColumn('occupancy_config_id');
            }
            if (Schema::hasColumn('reservation_rooms', 'occupancy_config_label')) {
                $table->dropColumn('occupancy_config_label');
            }
        });
    }
};
