<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->foreignId('estudiante_id')->constrained('estudiantes');
            $table->foreignId('matricula_id')->nullable()->constrained('matriculas');
            $table->foreignId('concepto_pago_id')->constrained('conceptos_pago');
            $table->foreignId('metodo_pago_id')->constrained('metodos_pago');
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->decimal('monto', 10, 2);
            $table->string('estado', 20)->default('pendiente');
            $table->string('referencia_externa', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users');
            $table->foreignId('aprobado_por')->nullable()->constrained('users');
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->foreignId('rechazado_por')->nullable()->constrained('users');
            $table->timestamp('fecha_rechazo')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->foreignId('actualizado_por')->nullable()->constrained('users');
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
