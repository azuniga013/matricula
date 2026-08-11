<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sincronizaciones_docente_movil', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('docente_id')->nullable();
            $table->string('tipo', 40);
            $table->unsignedBigInteger('oferta_academica_id')->nullable();
            $table->string('estado', 20)->default('aplicada');
            $table->longText('respuesta_json');
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->index(['usuario_id', 'tipo']);
            $table->index('oferta_academica_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sincronizaciones_docente_movil');
    }
};
