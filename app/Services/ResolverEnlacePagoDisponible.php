<?php

namespace App\Services;

use App\Models\EnlacePago;
use Illuminate\Support\Facades\DB;

class ResolverEnlacePagoDisponible
{
    public function resolver(int $metodoPagoId, float $monto, ?int $pagoId = null, ?int $estudianteId = null): ?EnlacePago
    {
        return DB::transaction(function () use ($metodoPagoId, $monto, $pagoId, $estudianteId) {
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

            if (empty($enlace->enlace_url) || ! filter_var($enlace->enlace_url, FILTER_VALIDATE_URL)) {
                return null;
            }

            $enlace->update([
                'estado_operativo' => 'reservado',
                'fecha_asignacion' => now(),
                'asignado_a_pago_id' => $pagoId,
                'asignado_a_estudiante_id' => $estudianteId,
            ]);

            return $enlace->fresh();
        });
    }
}
