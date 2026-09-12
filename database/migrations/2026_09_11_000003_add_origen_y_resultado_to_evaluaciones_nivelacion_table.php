<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluaciones_nivelacion', function (Blueprint $table) {
            $table->foreignId('nivel_academico_id')->nullable()->change();
            $table->foreignId('matricula_examen_id')->nullable()->unique()->after('estudiante_id')->constrained('matriculas');
            $table->foreignId('oferta_examen_id')->nullable()->after('matricula_examen_id')->constrained('ofertas_academicas');
            $table->foreignId('nivel_recomendado_id')->nullable()->after('nivel_academico_id')->constrained('niveles_academicos');
            $table->foreignId('evaluado_por')->nullable()->after('autorizado_por')->constrained('users');
            $table->timestamp('evaluado_en')->nullable()->after('evaluado_por');
            $table->text('motivo_anulacion')->nullable()->after('observaciones');
        });
    }

    public function down(): void
    {
        Schema::table('evaluaciones_nivelacion', function (Blueprint $table) {
            $table->dropForeign(['matricula_examen_id']);
            $table->dropUnique(['matricula_examen_id']);
            $table->dropForeign(['oferta_examen_id']);
            $table->dropForeign(['nivel_recomendado_id']);
            $table->dropForeign(['evaluado_por']);
            $table->dropColumn([
                'matricula_examen_id', 'oferta_examen_id', 'nivel_recomendado_id',
                'evaluado_por', 'evaluado_en', 'motivo_anulacion',
            ]);
        });
    }
};
