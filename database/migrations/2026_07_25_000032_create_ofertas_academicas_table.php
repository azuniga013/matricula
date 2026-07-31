<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ofertas_academicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->foreignId('periodo_academico_id')->constrained('periodos_academicos');
            $table->foreignId('nivel_academico_id')->constrained('niveles_academicos');
            $table->foreignId('modalidad_id')->constrained('modalidades');
            $table->foreignId('horario_id')->constrained('horarios');
            $table->foreignId('docente_id')->constrained('docentes');
            $table->foreignId('aula_id')->constrained('aulas');
            $table->unsignedInteger('cupo_maximo')->default(25);
            $table->unsignedInteger('cupos_reservados')->default(0);
            $table->unsignedInteger('cupos_matriculados')->default(0);
            $table->string('estado', 20)->default('borrador')->comment('borrador, abierto, lleno, cerrado, cancelado');
            $table->boolean('acepta_cambios_horario')->default(false);
            $table->string('grupo_whatsapp', 255)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('codigo', 50)->unique();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->index('estado');
            $table->index('sucursal_id');
            $table->index('periodo_academico_id');
            $table->index('nivel_academico_id');
            $table->index(['sucursal_id', 'periodo_academico_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ofertas_academicas');
    }
};
