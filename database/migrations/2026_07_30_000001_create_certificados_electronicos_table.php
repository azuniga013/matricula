<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificados_electronicos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('token_validacion', 100)->unique();
            $table->unsignedBigInteger('estudiante_id');
            $table->unsignedBigInteger('historial_academico_id');
            $table->unsignedBigInteger('nivel_academico_id');
            $table->decimal('nota_final', 5, 2);
            $table->string('estado', 20)->default('emitido');
            $table->timestamp('emitido_en')->useCurrent();
            $table->timestamp('validado_en')->nullable();
            $table->string('ruta_pdf', 255)->nullable();
            $table->string('hash_documento', 128)->nullable();
            $table->string('codigo_verificacion', 80)->nullable();
            $table->timestamps();

            $table->foreign('estudiante_id')->references('id')->on('estudiantes');
            $table->foreign('historial_academico_id')->references('id')->on('historial_academico');
            $table->foreign('nivel_academico_id')->references('id')->on('niveles_academicos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificados_electronicos');
    }
};
