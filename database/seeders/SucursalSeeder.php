<?php

namespace Database\Seeders;

use App\Models\Sucursal;
use Illuminate\Database\Seeder;

class SucursalSeeder extends Seeder
{
    public function run(): void
    {
        $sucursales = [
            ['codigo' => 'SPS', 'nombre' => 'San Pedro Sula', 'direccion' => 'San Pedro Sula, Honduras'],
            ['codigo' => 'TGU', 'nombre' => 'Tegucigalpa', 'direccion' => 'Tegucigalpa, Honduras'],
        ];

        foreach ($sucursales as $datos) {
            Sucursal::updateOrCreate(
                ['codigo' => $datos['codigo']],
                array_merge($datos, ['estado' => 'activo'])
            );
        }
    }
}
