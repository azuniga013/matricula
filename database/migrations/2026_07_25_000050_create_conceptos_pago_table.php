<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conceptos_pago', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 100);
            $table->string('tipo_monto', 20)->default('fijo')->comment('fijo, manual, por_oferta, por_inventario');
            $table->decimal('monto_fijo', 10, 2)->nullable();
            $table->decimal('monto_minimo', 10, 2)->nullable();
            $table->decimal('monto_maximo', 10, 2)->nullable();
            $table->boolean('requiere_autorizacion_monto')->default(false);
            $table->text('descripcion')->nullable();
            $table->string('estado', 20)->default('activo');
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conceptos_pago');
    }
};
