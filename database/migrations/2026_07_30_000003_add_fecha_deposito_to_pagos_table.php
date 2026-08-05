<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->timestamp('fecha_deposito')->nullable()->after('fecha_proceso');
        });

        DB::statement('UPDATE pagos SET fecha_deposito = fecha_proceso WHERE fecha_proceso IS NOT NULL AND fecha_deposito IS NULL');
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn('fecha_deposito');
        });
    }
};