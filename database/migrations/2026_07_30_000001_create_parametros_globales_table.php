<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametros_globales', function (Blueprint $table) {
            $table->id();
            $table->string('grupo', 10)->default('01')->comment('Código de grupo, ej 01 = Generales');
            $table->string('codigo', 100)->comment('Código único del parámetro dentro del grupo');
            $table->string('nombre', 150)->comment('Etiqueta visible del parámetro');
            $table->text('valor')->nullable()->comment('Valor del parámetro');
            $table->string('tipo', 20)->default('texto')->comment('texto|numero|booleano|seleccion');
            $table->text('opciones')->nullable()->comment('JSON con opciones para tipo seleccion');
            $table->string('descripcion', 255)->nullable();
            $table->boolean('estado')->default(true);
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamps();

            $table->unique(['grupo', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametros_globales');
    }
};