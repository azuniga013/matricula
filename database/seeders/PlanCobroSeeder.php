<?php

namespace Database\Seeders;

use App\Models\DetallePlanCobro;
use App\Models\OfertaAcademica;
use App\Models\PlanCobro;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanCobroSeeder extends Seeder
{
    public function run(): void
    {
        $matId = DB::table('conceptos_pago')->where('codigo', 'MAT')->first()->id;
        $cuoId = DB::table('conceptos_pago')->where('codigo', 'CUO')->first()->id;

        $planIntensivo = PlanCobro::updateOrCreate(
            ['codigo' => 'PLN-INT-2026'],
            [
                'nombre' => 'Plan Intensivo 2026',
                'descripcion' => 'Plan de cobro intensivo: Matrícula + 1 cuota',
                'estado' => 'activo',
                'creado_por' => null,
                'actualizado_por' => null,
            ]
        );

        $planSemi = PlanCobro::updateOrCreate(
            ['codigo' => 'PLN-SEMI-2026'],
            [
                'nombre' => 'Plan Semi Intensivo 2026',
                'descripcion' => 'Plan de cobro semi intensivo: Matrícula + 1 cuota',
                'estado' => 'activo',
                'creado_por' => null,
                'actualizado_por' => null,
            ]
        );

        $this->sincronizarDetalles($planIntensivo->id, [
            ['concepto_pago_id' => $matId, 'numero_cuota' => 0, 'nombre_cargo' => 'Matrícula', 'monto' => 1200.00, 'dias_vencimiento' => 0],
            ['concepto_pago_id' => $cuoId, 'numero_cuota' => 1, 'nombre_cargo' => 'Cuota 1', 'monto' => 1100.00, 'dias_vencimiento' => 30],
        ]);

        $this->sincronizarDetalles($planSemi->id, [
            ['concepto_pago_id' => $matId, 'numero_cuota' => 0, 'nombre_cargo' => 'Matrícula', 'monto' => 600.00, 'dias_vencimiento' => 0],
            ['concepto_pago_id' => $cuoId, 'numero_cuota' => 1, 'nombre_cargo' => 'Cuota 1', 'monto' => 700.00, 'dias_vencimiento' => 30],
        ]);

        OfertaAcademica::query()
            ->with('nivelAcademico.versionPlanEstudio.planEstudio')
            ->get()
            ->each(function (OfertaAcademica $oferta) use ($planIntensivo, $planSemi) {
                $planNombre = $oferta->nivelAcademico?->versionPlanEstudio?->planEstudio?->nombre;
                if (! $planNombre) {
                    return;
                }

                $esSemi = str_contains($planNombre, 'Semi');
                $oferta->update(['plan_cobro_id' => $esSemi ? $planSemi->id : $planIntensivo->id]);
            });
    }

    private function sincronizarDetalles(int $planCobroId, array $detalles): void
    {
        foreach ($detalles as $detalle) {
            DetallePlanCobro::updateOrCreate(
                [
                    'plan_cobro_id' => $planCobroId,
                    'numero_cuota' => $detalle['numero_cuota'],
                ],
                [
                    'concepto_pago_id' => $detalle['concepto_pago_id'],
                    'nombre_cargo' => $detalle['nombre_cargo'],
                    'monto' => $detalle['monto'],
                    'dias_vencimiento' => $detalle['dias_vencimiento'],
                    'estado' => 'activo',
                    'creado_por' => null,
                    'actualizado_por' => null,
                ]
            );
        }
    }
}
