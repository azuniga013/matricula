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
            $table->string('whatsapp_link_periodo', 500)->nullable()->after('grupo_whatsapp_id');
        });

        $ofertas = DB::table('ofertas_academicas')
            ->whereNotNull('grupo_whatsapp_id')
            ->whereNull('whatsapp_link_periodo')
            ->get(['id', 'grupo_whatsapp_id']);

        foreach ($ofertas as $oferta) {
            $link = DB::table('grupos_whatsapp')
                ->where('id', $oferta->grupo_whatsapp_id)
                ->value('link');

            if ($link) {
                DB::table('ofertas_academicas')
                    ->where('id', $oferta->id)
                    ->update(['whatsapp_link_periodo' => $link]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('ofertas_academicas', function (Blueprint $table) {
            $table->dropColumn('whatsapp_link_periodo');
        });
    }
};
