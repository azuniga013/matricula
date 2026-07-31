<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('configuracion_flujo_matricula_conceptos')) {
            return;
        }

        Schema::create('configuracion_flujo_matricula_conceptos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('configuracion_flujo_matricula_id')->constrained('configuraciones_flujo_matricula')->cascadeOnDelete();
            $table->foreignId('concepto_pago_id')->constrained('conceptos_pago')->cascadeOnDelete();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('creado_en')->useCurrent();

            $table->unique(['configuracion_flujo_matricula_id', 'concepto_pago_id'], 'cfg_flujo_concepto_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_flujo_matricula_conceptos');
    }
};
