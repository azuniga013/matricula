<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanCobroSeeder extends Seeder
{
    public function run(): void
    {
        $matId = DB::table('conceptos_pago')->where('codigo', 'MAT')->first()->id;
        $cuoId = DB::table('conceptos_pago')->where('codigo', 'CUO')->first()->id;

        $planIntensivo = DB::table('planes_cobro')->insertGetId([
            'codigo' => 'PLN-INT-2026',
            'nombre' => 'Plan Intensivo 2026',
            'descripcion' => 'Plan de cobro intensivo: Matrícula + 1 cuota',
            'estado' => 'activo',
            'creado_por' => null,
            'actualizado_por' => null,
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);

        DB::table('detalle_plan_cobro')->insert([
            ['plan_cobro_id' => $planIntensivo, 'concepto_pago_id' => $matId, 'numero_cuota' => 0, 'nombre_cargo' => 'Matrícula', 'monto' => 1200.00, 'dias_vencimiento' => 0, 'estado' => 'activo', 'creado_por' => null, 'actualizado_por' => null, 'creado_en' => now(), 'actualizado_en' => now()],
            ['plan_cobro_id' => $planIntensivo, 'concepto_pago_id' => $cuoId, 'numero_cuota' => 1, 'nombre_cargo' => 'Cuota 1', 'monto' => 1100.00, 'dias_vencimiento' => 30, 'estado' => 'activo', 'creado_por' => null, 'actualizado_por' => null, 'creado_en' => now(), 'actualizado_en' => now()],
        ]);

        $planSemi = DB::table('planes_cobro')->insertGetId([
            'codigo' => 'PLN-SEMI-2026',
            'nombre' => 'Plan Semi Intensivo 2026',
            'descripcion' => 'Plan de cobro semi intensivo: Matrícula + 1 cuota',
            'estado' => 'activo',
            'creado_por' => null,
            'actualizado_por' => null,
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);

        DB::table('detalle_plan_cobro')->insert([
            ['plan_cobro_id' => $planSemi, 'concepto_pago_id' => $matId, 'numero_cuota' => 0, 'nombre_cargo' => 'Matrícula', 'monto' => 600.00, 'dias_vencimiento' => 0, 'estado' => 'activo', 'creado_por' => null, 'actualizado_por' => null, 'creado_en' => now(), 'actualizado_en' => now()],
            ['plan_cobro_id' => $planSemi, 'concepto_pago_id' => $cuoId, 'numero_cuota' => 1, 'nombre_cargo' => 'Cuota 1', 'monto' => 700.00, 'dias_vencimiento' => 30, 'estado' => 'activo', 'creado_por' => null, 'actualizado_por' => null, 'creado_en' => now(), 'actualizado_en' => now()],
        ]);

        DB::table('ofertas_academicas')
            ->where('codigo', 'like', '%INT%')
            ->update(['plan_cobro_id' => $planIntensivo]);

        DB::table('ofertas_academicas')
            ->where('codigo', 'like', '%SEMI%')
            ->update(['plan_cobro_id' => $planSemi]);
    }
}
