<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomenclaturas_codigos', function (Blueprint $table) {
            $table->id();
            $table->string('entidad', 80)->unique();
            $table->string('formato', 50);
            $table->unsignedInteger('longitud_secuencia')->default(6);
            $table->unsignedInteger('secuencia_actual')->default(0);
            $table->string('estado', 20)->default('activo');
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomenclaturas_codigos');
    }
};
