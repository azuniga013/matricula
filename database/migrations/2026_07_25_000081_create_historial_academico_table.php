<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_academico', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->foreignId('estudiante_id')->constrained('estudiantes');
            $table->foreignId('matricula_id')->constrained('matriculas');
            $table->foreignId('oferta_academica_id')->constrained('ofertas_academicas');
            $table->foreignId('nivel_academico_id')->constrained('niveles_academicos');
            $table->foreignId('periodo_academico_id')->constrained('periodos_academicos');
            $table->string('estado', 20)->default('matriculado');
            $table->decimal('nota_final', 5, 2)->nullable();
            $table->integer('faltas')->default(0);
            $table->text('observaciones')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users');
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_academico');
    }
};
