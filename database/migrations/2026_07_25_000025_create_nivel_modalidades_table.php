<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nivel_modalidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nivel_academico_id')->constrained('niveles_academicos')->cascadeOnDelete();
            $table->foreignId('modalidad_id')->constrained('modalidades')->cascadeOnDelete();

            $table->unique(['nivel_academico_id', 'modalidad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nivel_modalidades');
    }
};
