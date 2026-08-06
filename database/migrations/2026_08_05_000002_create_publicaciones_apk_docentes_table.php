<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publicaciones_apk_docentes', function (Blueprint $table) {
            $table->id();
            $table->string('version', 40);
            $table->unsignedInteger('version_code')->unique();
            $table->string('nombre_archivo', 180);
            $table->string('ruta_archivo', 255)->unique();
            $table->unsignedBigInteger('tamano_bytes');
            $table->string('sha256', 64);
            $table->text('notas_version')->nullable();
            $table->boolean('publicado')->default(false)->index();
            $table->timestamp('publicado_en')->nullable();
            $table->foreignId('creado_por')->constrained('users');
            $table->foreignId('publicado_por')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publicaciones_apk_docentes');
    }
};
