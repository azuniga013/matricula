<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_cierre_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_caja_id')->constrained('sesiones_caja');
            $table->foreignId('concepto_pago_id')->constrained('conceptos_pago');
            $table->foreignId('metodo_pago_id')->constrained('metodos_pago');
            $table->integer('cantidad_transacciones')->default(0);
            $table->decimal('monto_total', 10, 2)->default(0);
            $table->string('estado', 20)->default('activo');
            $table->foreignId('creado_por')->nullable()->constrained('users');
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_cierre_caja');
    }
};
