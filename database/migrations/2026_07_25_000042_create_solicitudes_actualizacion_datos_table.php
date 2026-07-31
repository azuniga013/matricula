<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_actualizacion_datos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes');
            $table->string('campo', 50);
            $table->string('valor_anterior', 255)->nullable();
            $table->string('valor_nuevo', 255);
            $table->string('estado', 20)->default('pendiente')->comment('pendiente, aprobada, rechazada');
            $table->text('motivo')->nullable();
            $table->unsignedBigInteger('revisado_por')->nullable();
            $table->timestamp('fecha_revision')->nullable();
            $table->string('motivo_rechazo')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->index('estado');
            $table->index('estudiante_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_actualizacion_datos');
    }
};
