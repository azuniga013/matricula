<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opcion_modulo_id');
            $table->string('codigo', 80)->unique();
            $table->string('nombre', 100);
            $table->string('accion', 30);
            $table->string('estado', 20)->default('activo');
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('opcion_modulo_id')->references('id')->on('opciones_modulo');
            $table->index('estado');
            $table->index('accion');
            $table->index('opcion_modulo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};
