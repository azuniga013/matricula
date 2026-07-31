<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('niveles_academicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_plan_estudio_id')->constrained('versiones_plan_estudio');
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 150);
            $table->unsignedInteger('orden')->default(0);
            $table->unsignedInteger('nota_minima_aprobar')->default(80);
            $table->unsignedInteger('faltas_maximas_permitidas')->default(7);
            $table->string('estado', 20)->default('activo');
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->index('estado');
            $table->index('version_plan_estudio_id');
            $table->index('orden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('niveles_academicos');
    }
};
