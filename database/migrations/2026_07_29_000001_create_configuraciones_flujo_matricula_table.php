<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones_flujo_matricula', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('origen', 30)->comment('portal_administrativo, portal_estudiante');
            $table->foreignId('concepto_pago_id')->constrained('conceptos_pago');
            $table->foreignId('metodo_pago_id')->nullable()->constrained('metodos_pago');
            $table->string('estado', 20)->default('activo');

            $table->boolean('habilita_reserva_cupo')->default(true);
            $table->boolean('habilita_carga_comprobante')->default(true);
            $table->boolean('requiere_comprobante')->default(true);
            $table->boolean('habilita_revision_contable')->default(true);
            $table->boolean('habilita_aprobacion_pago')->default(true);
            $table->boolean('habilita_generacion_recibo')->default(true);
            $table->boolean('habilita_confirmacion_matricula')->default(true);
            $table->boolean('habilita_whatsapp')->default(true);
            $table->boolean('habilita_reenganche')->default(true);
            $table->boolean('habilita_solicitud_link')->default(true);

            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->index(['origen', 'estado'], 'cfg_flujo_origen_estado_idx');
            $table->index(['concepto_pago_id', 'metodo_pago_id'], 'cfg_flujo_concepto_metodo_idx');
            $table->unique(['origen', 'concepto_pago_id', 'metodo_pago_id'], 'cfg_flujo_matricula_origen_concepto_metodo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones_flujo_matricula');
    }
};
