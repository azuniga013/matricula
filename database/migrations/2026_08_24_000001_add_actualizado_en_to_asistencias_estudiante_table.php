<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencias_estudiante', function (Blueprint $table) {
            $table->timestamp('actualizado_en')->nullable()->after('creado_en');
        });

        DB::table('asistencias_estudiante')
            ->whereNull('actualizado_en')
            ->update(['actualizado_en' => DB::raw('creado_en')]);
    }

    public function down(): void
    {
        Schema::table('asistencias_estudiante', function (Blueprint $table) {
            $table->dropColumn('actualizado_en');
        });
    }
};
