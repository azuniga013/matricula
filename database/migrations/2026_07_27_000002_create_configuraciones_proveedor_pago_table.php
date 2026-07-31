<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones_proveedor_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_pago_id')->constrained('proveedores_pago')->cascadeOnDelete();
            $table->string('clave', 100);
            $table->text('valor')->nullable();
            $table->timestamps();
            $table->unique(['proveedor_pago_id', 'clave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones_proveedor_pago');
    }
};
