<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recibos_caja', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->integer('numero_recibo');
            $table->foreignId('pago_id')->constrained('pagos');
            $table->foreignId('estudiante_id')->constrained('estudiantes');
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->foreignId('concepto_pago_id')->constrained('conceptos_pago');
            $table->foreignId('metodo_pago_id')->constrained('metodos_pago');
            $table->decimal('monto', 10, 2);
            $table->string('estado', 20)->default('emitido');
            $table->string('anio', 4);
            $table->string('periodo', 20)->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users');
            $table->foreignId('anulado_por')->nullable()->constrained('users');
            $table->timestamp('fecha_anulacion')->nullable();
            $table->text('motivo_anulacion')->nullable();
            $table->integer('veces_reimpreso')->default(0);
            $table->foreignId('actualizado_por')->nullable()->constrained('users');
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recibos_caja');
    }
};
