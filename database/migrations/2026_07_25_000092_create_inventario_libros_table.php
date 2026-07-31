<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_libros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libro_id')->constrained('libros');
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->integer('existencia_actual')->default(0);
            $table->integer('existencia_minima')->default(0);
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['libro_id', 'sucursal_id']);
            $table->index('sucursal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_libros');
    }
};
