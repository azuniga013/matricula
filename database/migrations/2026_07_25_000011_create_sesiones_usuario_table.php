<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesiones_usuario', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->string('token_hash', 64);
            $table->string('ip', 45)->nullable();
            $table->string('agente', 500)->nullable();
            $table->timestamp('vencimiento')->nullable();
            $table->timestamp('revocado_en')->nullable();
            $table->timestamp('ultimo_acceso')->nullable();
            $table->timestamp('creado_en')->useCurrent();

            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('usuario_id');
            $table->index('token_hash');
            $table->index('vencimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones_usuario');
    }
};
