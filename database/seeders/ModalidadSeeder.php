<?php

namespace Database\Seeders;

use App\Models\Modalidad;
use Illuminate\Database\Seeder;

class ModalidadSeeder extends Seeder
{
    public function run(): void
    {
        $modalidades = [
            ['codigo' => 'INT', 'nombre' => 'Intensivo', 'tipo' => 'regimen_academico', 'descripcion' => 'Régimen intensivo'],
            ['codigo' => 'SEMI', 'nombre' => 'Semi Intensivo', 'tipo' => 'regimen_academico', 'descripcion' => 'Régimen semi intensivo'],
            ['codigo' => 'INF-INT', 'nombre' => 'Infantil Intensivo', 'tipo' => 'regimen_academico', 'descripcion' => 'Régimen infantil intensivo'],
            ['codigo' => 'INF-SEMI', 'nombre' => 'Infantil Semi Intensivo', 'tipo' => 'regimen_academico', 'descripcion' => 'Régimen infantil semi intensivo'],
            ['codigo' => 'PRES', 'nombre' => 'Presencial', 'tipo' => 'atencion', 'descripcion' => 'Modalidad presencial'],
            ['codigo' => 'VIRT', 'nombre' => 'Virtual', 'tipo' => 'atencion', 'descripcion' => 'Modalidad virtual'],
        ];

        foreach ($modalidades as $modalidad) {
            Modalidad::updateOrCreate(
                ['codigo' => $modalidad['codigo']],
                $modalidad
            );
        }
    }
}
