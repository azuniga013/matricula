<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            $table->boolean('lunes')->default(false)->after('descripcion');
            $table->boolean('martes')->default(false)->after('lunes');
            $table->boolean('miercoles')->default(false)->after('martes');
            $table->boolean('jueves')->default(false)->after('miercoles');
            $table->boolean('viernes')->default(false)->after('jueves');
            $table->boolean('sabado')->default(false)->after('viernes');
            $table->boolean('domingo')->default(false)->after('sabado');
        });

        Schema::dropIfExists('horario_dias');
    }

    public function down(): void
    {
        Schema::create('horario_dias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horario_id')->constrained('horarios')->cascadeOnDelete();
            $table->string('dia', 20)->comment('lunes, martes, miercoles, jueves, viernes, sabado, domingo');
            $table->unique(['horario_id', 'dia']);
        });

        Schema::table('horarios', function (Blueprint $table) {
            $table->dropColumn(['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo']);
        });
    }
};
