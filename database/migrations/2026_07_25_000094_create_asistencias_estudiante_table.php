<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias_estudiante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained('matriculas');
            $table->foreignId('oferta_academica_id')->constrained('ofertas_academicas');
            $table->date('fecha');
            $table->string('estado', 20)->comment('presente, falta, justificada, tardanza');
            $table->boolean('cuenta_como_falta')->default(true);
            $table->text('observacion')->nullable();
            $table->unsignedBigInteger('registrado_por')->nullable()->comment('User ID del docente');
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->unique(['matricula_id', 'fecha'], 'asistencia_unica_por_dia');
            $table->index('oferta_academica_id');
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias_estudiante');
    }
};
