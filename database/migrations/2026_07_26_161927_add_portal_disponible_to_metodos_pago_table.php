<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('metodos_pago', function (Blueprint $table) {
            $table->boolean('portal_disponible')->default(true)->after('estado');
        });

        DB::table('metodos_pago')
            ->whereIn('codigo', ['EFE', 'CHE'])
            ->update(['portal_disponible' => false]);
    }

    public function down(): void
    {
        Schema::table('metodos_pago', function (Blueprint $table) {
            $table->dropColumn('portal_disponible');
        });
    }
};
