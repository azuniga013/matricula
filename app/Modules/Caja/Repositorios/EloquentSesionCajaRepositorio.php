<?php

namespace App\Modules\Caja\Repositorios;

use App\Models\DetalleCierreCaja;
use App\Models\Pago;
use App\Models\SesionCaja;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

final class EloquentSesionCajaRepositorio implements SesionCajaRepositorio
{
    public function existeAbiertaDelCajero(int $sucursalId, int $usuarioCajeroId): bool
    {
        return SesionCaja::where('sucursal_id', $sucursalId)
            ->where('usuario_cajero_id', $usuarioCajeroId)
            ->where('estado', 'abierta')
            ->exists();
    }

    public function buscarConBloqueo(int $id): ?SesionCaja
    {
        return SesionCaja::lockForUpdate()->find($id);
    }

    public function crearSesion(array $atributos): SesionCaja
    {
        return SesionCaja::create($atributos);
    }

    public function pagosAprobadosDeLaSesion(SesionCaja $sesion, Carbon $fechaCierre): Collection
    {
        return Pago::where('estado', 'aprobado')
            ->where('sucursal_id', $sesion->sucursal_id)
            ->where(function ($query) use ($sesion, $fechaCierre) {
                $query->where('sesion_caja_id', $sesion->id)
                    ->orWhereDate('fecha_aprobacion', $fechaCierre->toDateString());
            })
            ->get();
    }

    public function guardarDetalleCierre(SesionCaja $sesion, array $datos, int $usuarioId): DetalleCierreCaja
    {
        return DetalleCierreCaja::create([
            'sesion_caja_id' => $sesion->id,
            'concepto_pago_id' => $datos['concepto_pago_id'],
            'metodo_pago_id' => $datos['metodo_pago_id'],
            'cantidad_transacciones' => $datos['cantidad_transacciones'],
            'monto_total' => $datos['monto_total'],
            'estado' => 'activo',
            'creado_por' => $usuarioId,
        ]);
    }

    public function cerrarSesion(SesionCaja $sesion, array $atributos): void
    {
        $sesion->update($atributos);
    }
}
