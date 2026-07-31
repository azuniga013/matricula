<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobantes_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_id')->constrained('pagos');
            $table->string('nombre_archivo', 255);
            $table->string('ruta_archivo', 500);
            $table->string('tipo_archivo', 20);
            $table->decimal('tamano_bytes', 15, 0);
            $table->string('estado', 20)->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users');
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes_pago');
    }
};
