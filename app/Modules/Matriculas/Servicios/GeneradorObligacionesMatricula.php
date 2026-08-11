<?php

namespace App\Modules\Matriculas\Servicios;

use App\Models\Matricula;
use App\Models\ObligacionPagoEstudiante;
use App\Models\OfertaAcademica;

final class GeneradorObligacionesMatricula
{
    public function generar(Matricula $matricula, OfertaAcademica $oferta, ?int $usuarioId = null): void
    {
        $matricula->loadMissing('obligaciones');

        if ($matricula->obligaciones->isNotEmpty()) {
            return;
        }

        $oferta->loadMissing('planCobro.detalles');

        if (! $oferta->planCobro || $oferta->planCobro->detalles->isEmpty()) {
            return;
        }

        $obligaciones = [];
        foreach ($oferta->planCobro->detalles as $detalle) {
            $obligaciones[] = [
                'matricula_id' => $matricula->id,
                'concepto_pago_id' => $detalle->concepto_pago_id,
                'numero_cuota' => $detalle->numero_cuota,
                'nombre_cargo' => $detalle->nombre_cargo,
                'monto' => $detalle->monto,
                'monto_pagado' => 0,
                'fecha_vencimiento' => $detalle->dias_vencimiento > 0 ? now()->addDays($detalle->dias_vencimiento) : now(),
                'estado' => 'pendiente',
                'creado_por' => $usuarioId ?? auth()->id(),
            ];
        }

        if (! empty($obligaciones)) {
            ObligacionPagoEstudiante::insert($obligaciones);
            $matricula->unsetRelation('obligaciones');
        }
    }
}
