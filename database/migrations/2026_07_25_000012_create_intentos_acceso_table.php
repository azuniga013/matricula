<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intentos_acceso', function (Blueprint $table) {
            $table->id();
            $table->string('correo', 100)->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('ip', 45);
            $table->string('agente', 500)->nullable();
            $table->string('resultado', 20);
            $table->string('motivo', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('correo');
            $table->index('usuario_id');
            $table->index('ip');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intentos_acceso');
    }
};
