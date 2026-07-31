<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opciones_modulo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('modulo_id');
            $table->string('codigo', 80)->unique();
            $table->string('nombre', 100);
            $table->string('ruta', 255)->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->string('estado', 20)->default('activo');
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('modulo_id')->references('id')->on('modulos');
            $table->index('estado');
            $table->index('modulo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opciones_modulo');
    }
};
