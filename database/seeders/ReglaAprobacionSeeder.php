<?php

namespace Database\Seeders;

use App\Models\ReglaAprobacion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReglaAprobacionSeeder extends Seeder
{
    public function run(): void
    {
        $modalidades = DB::table('modalidades')->pluck('id', 'codigo');

        $reglas = [
            ['codigo' => 'RA-INT', 'nombre' => 'Regla Aprobación Intensivo', 'modalidad_key' => 'INT', 'nota_minima_aprobar' => 80, 'faltas_maximas_permitidas' => 7, 'descripcion' => 'Nota mínima 80% y máximo 7 faltas para Intensivo'],
            ['codigo' => 'RA-SEMI', 'nombre' => 'Regla Aprobación Semi Intensivo', 'modalidad_key' => 'SEMI', 'nota_minima_aprobar' => 80, 'faltas_maximas_permitidas' => 3, 'descripcion' => 'Nota mínima 80% y máximo 3 faltas para Semi Intensivo'],
        ];

        foreach ($reglas as $regla) {
            $modalidadId = $modalidades[$regla['modalidad_key']] ?? null;

            ReglaAprobacion::updateOrCreate(
                ['codigo' => $regla['codigo']],
                [
                    'nombre' => $regla['nombre'],
                    'modalidad_id' => $modalidadId,
                    'nota_minima_aprobar' => $regla['nota_minima_aprobar'],
                    'faltas_maximas_permitidas' => $regla['faltas_maximas_permitidas'],
                    'descripcion' => $regla['descripcion'],
                    'estado' => 'activo',
                    'creado_por' => null,
                ]
            );
        }
    }
}
