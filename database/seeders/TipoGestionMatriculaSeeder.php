<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoGestionMatriculaSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['codigo' => 'CAM', 'nombre' => 'Cambio de horario', 'descripcion' => 'Cambio de horario del estudiante', 'estado' => 'activo', 'creado_por' => null, 'actualizado_por' => null, 'creado_en' => now(), 'actualizado_en' => now()],
            ['codigo' => 'RET', 'nombre' => 'Retiro', 'descripcion' => 'Retiro del estudiante del grupo', 'estado' => 'activo', 'creado_por' => null, 'actualizado_por' => null, 'creado_en' => now(), 'actualizado_en' => now()],
            ['codigo' => 'CAN', 'nombre' => 'Cancelación', 'descripcion' => 'Cancelación de la matrícula', 'estado' => 'activo', 'creado_por' => null, 'actualizado_por' => null, 'creado_en' => now(), 'actualizado_en' => now()],
            ['codigo' => 'CTR', 'nombre' => 'Cambio de modalidad', 'descripcion' => 'Cambio de modalidad académica', 'estado' => 'activo', 'creado_por' => null, 'actualizado_por' => null, 'creado_en' => now(), 'actualizado_en' => now()],
            ['codigo' => 'TSU', 'nombre' => 'Traslado de sucursal', 'descripcion' => 'Traslado a otra sucursal', 'estado' => 'activo', 'creado_por' => null, 'actualizado_por' => null, 'creado_en' => now(), 'actualizado_en' => now()],
            ['codigo' => 'EXM', 'nombre' => 'Excepción de matrícula', 'descripcion' => 'Excepción administrativa de matrícula', 'estado' => 'activo', 'creado_por' => null, 'actualizado_por' => null, 'creado_en' => now(), 'actualizado_en' => now()],
        ];

        DB::table('tipos_gestion_matricula')->insert($tipos);
    }
}
