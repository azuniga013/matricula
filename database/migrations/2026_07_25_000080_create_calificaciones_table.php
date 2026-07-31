<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificaciones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->foreignId('matricula_id')->constrained('matriculas');
            $table->foreignId('estudiante_id')->constrained('estudiantes');
            $table->foreignId('oferta_academica_id')->constrained('ofertas_academicas');
            $table->decimal('nota_final', 5, 2)->nullable();
            $table->integer('faltas')->default(0);
            $table->string('estado', 20)->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->foreignId('docente_id')->nullable()->constrained('docentes');
            $table->foreignId('creado_por')->nullable()->constrained('users');
            $table->foreignId('actualizado_por')->nullable()->constrained('users');
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones');
    }
};
