<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alcances_rol', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rol_id');
            $table->string('tipo', 30);
            $table->unsignedBigInteger('sucursal_id')->nullable();
            $table->string('estado', 20)->default('activo');
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('rol_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('sucursal_id')->references('id')->on('sucursales');
            $table->index('rol_id');
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alcances_rol');
    }
};
