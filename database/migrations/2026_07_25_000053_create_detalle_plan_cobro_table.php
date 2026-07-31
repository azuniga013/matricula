<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_plan_cobro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_cobro_id')->constrained('planes_cobro');
            $table->foreignId('concepto_pago_id')->constrained('conceptos_pago');
            $table->unsignedInteger('numero_cuota')->default(0);
            $table->string('nombre_cargo', 150);
            $table->decimal('monto', 10, 2);
            $table->unsignedInteger('dias_vencimiento')->default(0)->comment('Días desde la reserva para vencimiento');
            $table->string('estado', 20)->default('activo');
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->index('estado');
            $table->index('plan_cobro_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_plan_cobro');
    }
};
