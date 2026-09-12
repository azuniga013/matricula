<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periodos_academicos', function (Blueprint $table) {
            $table->date('fecha_inicio_matricula')->nullable()->after('fecha_inicio');
            $table->date('fecha_cierre_matricula')->nullable()->after('fecha_fin');
        });

        Schema::table('planes_estudio', function (Blueprint $table) {
            $table->boolean('permite_progresion_mismo_periodo')->default(false)->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('planes_estudio', function (Blueprint $table) {
            $table->dropColumn('permite_progresion_mismo_periodo');
        });

        Schema::table('periodos_academicos', function (Blueprint $table) {
            $table->dropColumn(['fecha_inicio_matricula', 'fecha_cierre_matricula']);
        });
    }
};
