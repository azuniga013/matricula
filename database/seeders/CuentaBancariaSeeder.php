<?php

namespace Database\Seeders;

use App\Models\CuentaBancaria;
use Illuminate\Database\Seeder;

class CuentaBancariaSeeder extends Seeder
{
    public function run(): void
    {
        CuentaBancaria::firstOrCreate(
            ['codigo' => 'BAC-001'],
            [
                'nombre' => 'Cuenta Principal BAC',
                'banco' => 'BAC Honduras',
                'numero_cuenta' => '743806641',
                'tipo_cuenta' => 'ahorro',
                'estado' => 'activo',
                'creado_por' => null,
                'actualizado_por' => null,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]
        );

        CuentaBancaria::firstOrCreate(
            ['codigo' => 'OCC-001'],
            [
                'nombre' => 'Cuenta Principal Occidente',
                'banco' => 'Banco de Occidente Honduras',
                'numero_cuenta' => '2111011420',
                'tipo_cuenta' => 'ahorro',
                'estado' => 'activo',
                'creado_por' => null,
                'actualizado_por' => null,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]
        );
    }
}
