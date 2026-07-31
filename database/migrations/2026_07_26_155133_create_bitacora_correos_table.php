<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bitacora_correos', function (Blueprint $table) {
            $table->id();
            $table->string('destinatario', 150);
            $table->string('asunto', 200);
            $table->string('tipo', 30)->comment('registro, activacion, reenvio');
            $table->string('codigo_estudiante', 50)->nullable();
            $table->string('estado', 20)->default('enviado')->comment('enviado, fallido');
            $table->text('error')->nullable();
            $table->timestamp('creado_en')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacora_correos');
    }
};
