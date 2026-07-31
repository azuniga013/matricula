<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prerrequisitos_nivel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nivel_academico_id')->constrained('niveles_academicos')->cascadeOnDelete();
            $table->foreignId('prerrequisito_id')->constrained('niveles_academicos')->cascadeOnDelete();

            $table->unique(['nivel_academico_id', 'prerrequisito_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prerrequisitos_nivel');
    }
};
