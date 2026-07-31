<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuraciones_flujo_matricula', function (Blueprint $table) {
            if (!Schema::hasColumn('configuraciones_flujo_matricula', 'concepto_pago_id')) {
                $table->foreignId('concepto_pago_id')->nullable()->after('origen')->constrained('conceptos_pago');
            }

            if (!Schema::hasColumn('configuraciones_flujo_matricula', 'metodo_pago_id')) {
                $table->foreignId('metodo_pago_id')->nullable()->after('concepto_pago_id')->constrained('metodos_pago');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuraciones_flujo_matricula', function (Blueprint $table) {
            if (Schema::hasColumn('configuraciones_flujo_matricula', 'metodo_pago_id')) {
                $table->dropConstrainedForeignId('metodo_pago_id');
            }

            if (Schema::hasColumn('configuraciones_flujo_matricula', 'concepto_pago_id')) {
                $table->dropConstrainedForeignId('concepto_pago_id');
            }
        });
    }
};
