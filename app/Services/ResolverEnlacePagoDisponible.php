<?php

namespace App\Services;

use App\Models\EnlacePago;
use Illuminate\Support\Facades\DB;

class ResolverEnlacePagoDisponible
{
    public function resolver(int $metodoPagoId, float $monto): ?EnlacePago
    {
        return DB::transaction(function () use ($metodoPagoId, $monto) {
            $enlace = EnlacePago::query()
                ->where('metodo_pago_id', $metodoPagoId)
                ->where('estado', 'activo')
                ->where('estado_operativo', 'disponible')
                ->where(function ($q) {
                    $q->whereNull('fecha_vencimiento')
                        ->orWhere('fecha_vencimiento', '>=', now()->toDateString());
                })
                ->where(function ($q) use ($monto) {
                    $q->where('monto_objetivo', $monto)
                        ->orWhereNull('monto_objetivo');
                })
                ->orderByDesc('monto_objetivo')
                ->lockForUpdate()
                ->first();

            if (! $enlace) {
                return null;
            }

            $enlace->update([
                'estado_operativo' => 'reservado',
                'fecha_asignacion' => now(),
            ]);

            return $enlace->fresh();
        });
    }
}
