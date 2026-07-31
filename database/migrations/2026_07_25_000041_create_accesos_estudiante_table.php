<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accesos_estudiante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes');
            $table->string('email', 100)->unique();
            $table->string('password');
            $table->string('estado', 20)->default('activo')->comment('activo, inactivo');
            $table->string('token', 255)->nullable();
            $table->timestamp('ultimo_acceso')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accesos_estudiante');
    }
};
