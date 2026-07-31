<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuraciones_flujo_matricula', function (Blueprint $table) {
            if (!Schema::hasColumn('configuraciones_flujo_matricula', 'habilita_seleccion_obligaciones')) {
                $table->boolean('habilita_seleccion_obligaciones')->default(true)->after('habilita_confirmacion_matricula');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuraciones_flujo_matricula', function (Blueprint $table) {
            if (Schema::hasColumn('configuraciones_flujo_matricula', 'habilita_seleccion_obligaciones')) {
                $table->dropColumn('habilita_seleccion_obligaciones');
            }
        });
    }
};
