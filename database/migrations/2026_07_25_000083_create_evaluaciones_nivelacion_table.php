<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_nivelacion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->foreignId('estudiante_id')->constrained('estudiantes');
            $table->foreignId('nivel_academico_id')->constrained('niveles_academicos');
            $table->decimal('nota_obtenida', 5, 2);
            $table->boolean('aprobado')->default(false);
            $table->text('observaciones')->nullable();
            $table->string('autorizado_por', 150)->nullable();
            $table->string('estado', 20)->default('activo');
            $table->foreignId('creado_por')->nullable()->constrained('users');
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_nivelacion');
    }
};
