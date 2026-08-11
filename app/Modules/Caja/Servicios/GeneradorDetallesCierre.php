<?php

namespace App\Modules\Caja\Servicios;

use Illuminate\Database\Eloquent\Collection;

final class GeneradorDetallesCierre
{
    /**
     * @return array<int, array{concepto_pago_id: int, metodo_pago_id: int, cantidad_transacciones: int, monto_total: float}>
     */
    public function generar(Collection $pagos): array
    {
        return $pagos->groupBy(function ($pago) {
            return $pago->concepto_pago_id.'_'.$pago->metodo_pago_id;
        })->map(function ($grupo) {
            $primero = $grupo->first();

            return [
                'concepto_pago_id' => $primero->concepto_pago_id,
                'metodo_pago_id' => $primero->metodo_pago_id,
                'cantidad_transacciones' => $grupo->count(),
                'monto_total' => $grupo->sum('monto'),
            ];
        })->values()->all();
    }
}
