<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('niveles_academicos', function (Blueprint $table) {
            $table->foreignId('regimen_academico_id')
                ->nullable()
                ->after('version_plan_estudio_id')
                ->constrained('modalidades')
                ->nullOnDelete();
        });

        Schema::table('ofertas_academicas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('regimen_academico_id');
        });
    }

    public function down(): void
    {
        Schema::table('ofertas_academicas', function (Blueprint $table) {
            $table->foreignId('regimen_academico_id')
                ->nullable()
                ->after('nivel_academico_id')
                ->constrained('modalidades')
                ->nullOnDelete();
        });

        Schema::table('niveles_academicos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('regimen_academico_id');
        });
    }
};
