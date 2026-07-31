<?php

use App\Models\Modalidad;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ofertas_academicas', function (Blueprint $table) {
            $table->foreignId('regimen_academico_id')
                ->nullable()
                ->after('nivel_academico_id')
                ->constrained('modalidades');
        });

        $presencial = Modalidad::where('codigo', 'PRES')->value('id');

        DB::table('ofertas_academicas')
            ->whereNull('regimen_academico_id')
            ->update([
                'regimen_academico_id' => DB::raw('modalidad_id'),
                'modalidad_id' => $presencial ?? DB::raw('modalidad_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('ofertas_academicas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('regimen_academico_id');
        });
    }
};
