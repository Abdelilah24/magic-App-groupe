<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplements', function (Blueprint $table) {
            // Remplacer la colonne 'date' par 'date_from' + 'date_to'
            $table->date('date_from')->nullable()->after('description');
            $table->date('date_to')->nullable()->after('date_from');
        });

        // Migrer les données existantes : copier date → date_from et date_to
        DB::table('supplements')->whereNotNull('date')->update([
            'date_from' => DB::raw('`date`'),
            'date_to'   => DB::raw('`date`'),
        ]);

        Schema::table('supplements', function (Blueprint $table) {
            $table->dropColumn('date');
            // Rendre date_from et date_to obligatoires après la migration des données
            $table->date('date_from')->nullable(false)->change();
            $table->date('date_to')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('supplements', function (Blueprint $table) {
            $table->date('date')->nullable()->after('description');
        });

        DB::table('supplements')->update([
            'date' => DB::raw('`date_from`'),
        ]);

        Schema::table('supplements', function (Blueprint $table) {
            $table->dropColumn(['date_from', 'date_to']);
        });
    }
};
