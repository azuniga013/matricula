<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles') || !Schema::hasTable('permisos') || !Schema::hasTable('rol_permisos')) {
            return;
        }

        $rolDocenteId = DB::table('roles')->where('codigo', 'DOCENTE')->value('id');
        if (!$rolDocenteId) {
            return;
        }

        $permisos = DB::table('permisos')->whereIn('codigo', [
            'asistencias.consultar',
            'asistencias.crear',
            'calificaciones.consultar',
            'calificaciones.crear',
            'calificaciones.modificar',
        ])->pluck('id');

        foreach ($permisos as $permisoId) {
            DB::table('rol_permisos')->updateOrInsert(
                ['rol_id' => $rolDocenteId, 'permiso_id' => $permisoId],
                ['estado' => 'activo', 'actualizado_en' => now()]
            );
        }
    }

    public function down(): void
    {
        // Los permisos pueden haber sido administrados manualmente; no se revocan al revertir.
    }
};
