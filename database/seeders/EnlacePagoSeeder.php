<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnlacePagoSeeder extends Seeder
{
    public function run(): void
    {
        $cuentaId = DB::table('cuentas_bancarias')->where('codigo', 'BAC-001')->first()?->id;
        $conceptoMatId = DB::table('conceptos_pago')->where('codigo', 'MAT')->first()?->id;
        $conceptoCuoId = DB::table('conceptos_pago')->where('codigo', 'CUO')->first()?->id;

        if (!$cuentaId) return;

        DB::table('enlaces_pago')->insert([
            [
                'codigo' => 'LNK-MAT-INT-2026',
                'nombre' => 'Pago Matrícula Intensivo 2026',
                'monto' => 1200.00,
                'concepto_pago_id' => $conceptoMatId,
                'cuenta_bancaria_id' => $cuentaId,
                'fecha_vencimiento' => '2026-12-31',
                'usos_maximos' => 100,
                'usos_actuales' => 0,
                'estado' => 'activo',
                'creado_por' => null,
                'actualizado_por' => null,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
            [
                'codigo' => 'LNK-CUO-INT-2026',
                'nombre' => 'Pago Cuota Intensivo 2026',
                'monto' => 1100.00,
                'concepto_pago_id' => $conceptoCuoId,
                'cuenta_bancaria_id' => $cuentaId,
                'fecha_vencimiento' => '2026-12-31',
                'usos_maximos' => 100,
                'usos_actuales' => 0,
                'estado' => 'activo',
                'creado_por' => null,
                'actualizado_por' => null,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
        ]);
    }
}
