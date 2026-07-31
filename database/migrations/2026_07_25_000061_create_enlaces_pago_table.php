<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enlaces_pago', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 150);
            $table->decimal('monto', 10, 2)->nullable();
            $table->foreignId('concepto_pago_id')->nullable()->constrained('conceptos_pago');
            $table->foreignId('cuenta_bancaria_id')->nullable()->constrained('cuentas_bancarias');
            $table->date('fecha_vencimiento')->nullable();
            $table->integer('usos_maximos')->nullable();
            $table->integer('usos_actuales')->default(0);
            $table->string('estado', 20)->default('activo');
            $table->foreignId('creado_por')->nullable()->constrained('users');
            $table->foreignId('actualizado_por')->nullable()->constrained('users');
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enlaces_pago');
    }
};
