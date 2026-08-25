<?php

namespace App\Modules\Caja\Repositorios;

use App\Models\ReciboCaja;

final class EloquentReciboCajaRepositorio implements ReciboCajaRepositorio
{
    public function buscar(int $id): ?ReciboCaja
    {
        return ReciboCaja::find($id);
    }

    public function registrarReimpresion(ReciboCaja $recibo, int $usuarioId): void
    {
        $recibo->update([
            'veces_reimpreso' => $recibo->veces_reimpreso + 1,
            'actualizado_por' => $usuarioId,
            'actualizado_en' => now(),
        ]);
    }

    public function anular(ReciboCaja $recibo, string $motivo, int $usuarioId): void
    {
        $recibo->update([
            'estado' => 'anulado',
            'anulado_por' => $usuarioId,
            'fecha_anulacion' => now(),
            'motivo_anulacion' => $motivo,
            'actualizado_por' => $usuarioId,
            'actualizado_en' => now(),
        ]);
    }
}
