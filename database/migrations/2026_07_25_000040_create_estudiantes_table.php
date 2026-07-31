<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 150);
            $table->string('apellido', 150);
            $table->string('identidad', 30)->nullable()->unique();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('sexo', 10)->nullable();
            $table->string('correo', 100)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->string('nombre_padre', 150)->nullable();
            $table->string('telefono_padre', 30)->nullable();
            $table->string('correo_padre', 100)->nullable();
            $table->string('estado', 20)->default('activo')->comment('activo, inactivo, pendiente');
            $table->boolean('es_primer_ingreso')->default(true);
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->index('estado');
            $table->index('sucursal_id');
            $table->index(['nombre', 'apellido']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
