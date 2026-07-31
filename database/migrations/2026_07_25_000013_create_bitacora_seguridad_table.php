<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora_seguridad', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('accion', 50);
            $table->string('modulo', 50)->nullable();
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->json('valores_antes')->nullable();
            $table->json('valores_despues')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('agente', 500)->nullable();
            $table->string('resultado', 20)->default('exitoso');
            $table->string('motivo', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('usuario_id')->references('id')->on('users')->nullOnDelete();
            $table->index('usuario_id');
            $table->index('accion');
            $table->index('modulo');
            $table->index('resultado');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_seguridad');
    }
};
