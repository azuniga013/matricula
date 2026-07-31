<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora_peticiones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('metodo', 10);
            $table->string('ruta', 255);
            $table->unsignedSmallInteger('estado_http');
            $table->unsignedInteger('duracion_ms')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('agente', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('usuario_id')->references('id')->on('users')->nullOnDelete();
            $table->index('usuario_id');
            $table->index('metodo');
            $table->index('estado_http');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_peticiones');
    }
};
