<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obligaciones_pago_estudiante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained('matriculas');
            $table->foreignId('concepto_pago_id')->constrained('conceptos_pago');
            $table->unsignedInteger('numero_cuota')->default(0);
            $table->string('nombre_cargo', 150);
            $table->decimal('monto', 10, 2);
            $table->decimal('monto_pagado', 10, 2)->default(0);
            $table->date('fecha_vencimiento');
            $table->string('estado', 20)->default('pendiente')->comment('pendiente, parcial, pagado, vencido');
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['matricula_id', 'numero_cuota']);
            $table->index('estado');
            $table->index('matricula_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obligaciones_pago_estudiante');
    }
};
