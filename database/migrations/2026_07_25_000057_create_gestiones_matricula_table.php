<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gestiones_matricula', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained('matriculas');
            $table->foreignId('tipo_gestion_matricula_id')->constrained('tipos_gestion_matricula');
            $table->text('motivo');
            $table->string('estado', 20)->default('pendiente')->comment('pendiente, aprobada, rechazada, cancelada');
            $table->foreignId('oferta_academica_destino_id')->nullable()->constrained('ofertas_academicas');
            $table->json('datos_antes')->nullable();
            $table->json('despues')->nullable();
            $table->unsignedBigInteger('solicitado_por')->nullable();
            $table->unsignedBigInteger('decidido_por')->nullable();
            $table->timestamp('fecha_solicitud')->nullable();
            $table->timestamp('fecha_decision')->nullable();
            $table->text('motivo_decision')->nullable();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->index('estado');
            $table->index('matricula_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gestiones_matricula');
    }
};
