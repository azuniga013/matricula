<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matriculas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->foreignId('estudiante_id')->constrained('estudiantes');
            $table->foreignId('oferta_academica_id')->constrained('ofertas_academicas');
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->string('estado', 20)->default('iniciada')->comment('iniciada, reservada, en_revision, matriculado, rechazado, cancelado, vencido');
            $table->timestamp('fecha_reserva')->nullable();
            $table->timestamp('fecha_confirmacion')->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->index('estado');
            $table->index('estudiante_id');
            $table->index('oferta_academica_id');
            $table->index('sucursal_id');
            $table->unique(['estudiante_id', 'oferta_academica_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
