<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create default grupo_whatsapp for existing data
        $sucursalId = DB::table('sucursales')->value('id');
        if ($sucursalId) {
            $rows = DB::table('ofertas_academicas')
                ->whereNotNull('grupo_whatsapp')
                ->where('grupo_whatsapp', '!=', '')
                ->distinct('grupo_whatsapp')
                ->pluck('grupo_whatsapp');

            foreach ($rows as $i => $link) {
                $codigo = 'WS-DEF-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
                $nombre = 'Grupo ' . ($i + 1);
                $existingId = DB::table('grupos_whatsapp')->where('link', $link)->value('id');
                if (!$existingId) {
                    DB::table('grupos_whatsapp')->insert([
                        'sucursal_id' => $sucursalId,
                        'codigo' => $codigo,
                        'nombre' => $nombre,
                        'link' => $link,
                        'estado' => 'activo',
                        'creado_en' => now(),
                    ]);
                }
            }
        }

        Schema::table('ofertas_academicas', function (Blueprint $table) {
            $table->foreignId('grupo_whatsapp_id')->nullable()->after('observaciones')
                ->constrained('grupos_whatsapp')->nullOnDelete();
        });

        // Migrate data: match existing grupo_whatsapp strings to new IDs
        if ($sucursalId) {
            $links = DB::table('grupos_whatsapp')->pluck('id', 'link');
            foreach ($links as $link => $id) {
                DB::table('ofertas_academicas')
                    ->where('grupo_whatsapp', $link)
                    ->update(['grupo_whatsapp_id' => $id]);
            }
        }

        Schema::table('ofertas_academicas', function (Blueprint $table) {
            $table->dropColumn('grupo_whatsapp');
        });
    }

    public function down(): void
    {
        Schema::table('ofertas_academicas', function (Blueprint $table) {
            $table->string('grupo_whatsapp', 255)->nullable()->after('observaciones');
        });

        // Restore data
        $rows = DB::table('ofertas_academicas')
            ->whereNotNull('grupo_whatsapp_id')
            ->join('grupos_whatsapp', 'ofertas_academicas.grupo_whatsapp_id', '=', 'grupos_whatsapp.id')
            ->select('ofertas_academicas.id', 'grupos_whatsapp.link')
            ->get();

        foreach ($rows as $row) {
            DB::table('ofertas_academicas')
                ->where('id', $row->id)
                ->update(['grupo_whatsapp' => $row->link]);
        }

        Schema::table('ofertas_academicas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grupo_whatsapp_id');
        });
    }
};
