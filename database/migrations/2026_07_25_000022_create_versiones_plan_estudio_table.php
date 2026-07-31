<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('versiones_plan_estudio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_estudio_id')->constrained('planes_estudio');
            $table->unsignedInteger('numero_version');
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->string('estado', 20)->default('activo');
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['plan_estudio_id', 'numero_version']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('versiones_plan_estudio');
    }
};
