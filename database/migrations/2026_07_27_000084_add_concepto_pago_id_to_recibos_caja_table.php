<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibos_caja', function (Blueprint $table) {
            $table->foreignId('concepto_pago_id')->nullable()->after('sucursal_id')->constrained('conceptos_pago');
        });
    }

    public function down(): void
    {
        Schema::table('recibos_caja', function (Blueprint $table) {
            $table->dropConstrainedForeignId('concepto_pago_id');
        });
    }
};
