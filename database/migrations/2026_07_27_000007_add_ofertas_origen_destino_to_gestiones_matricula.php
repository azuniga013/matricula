<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gestiones_matricula', function (Blueprint $table) {
            $table->foreignId('oferta_academica_origen_id')->nullable()->after('oferta_academica_destino_id')->constrained('ofertas_academicas');
            $table->index(['oferta_academica_origen_id', 'oferta_academica_destino_id'], 'gest_matricula_of_origen_dest_idx');
        });
    }

    public function down(): void
    {
        Schema::table('gestiones_matricula', function (Blueprint $table) {
            $table->dropForeign(['oferta_academica_origen_id']);
            $table->dropIndex(['oferta_academica_origen_id', 'oferta_academica_destino_id']);
            $table->dropColumn('oferta_academica_origen_id');
        });
    }
};
