<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ofertas_academicas', function (Blueprint $table) {
            $table->string('whatsapp_grupo_nombre', 150)->nullable()->after('whatsapp_link_periodo');
        });

        $ofertas = DB::table('ofertas_academicas')
            ->whereNotNull('grupo_whatsapp_id')
            ->whereNull('whatsapp_grupo_nombre')
            ->get(['id', 'grupo_whatsapp_id']);

        foreach ($ofertas as $oferta) {
            $nombre = DB::table('grupos_whatsapp')
                ->where('id', $oferta->grupo_whatsapp_id)
                ->value('nombre');

            if ($nombre) {
                DB::table('ofertas_academicas')
                    ->where('id', $oferta->id)
                    ->update(['whatsapp_grupo_nombre' => $nombre]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('ofertas_academicas', function (Blueprint $table) {
            $table->dropColumn('whatsapp_grupo_nombre');
        });
    }
};
