<?php

namespace App\Modules\Pagos\Servicios;

use App\Models\AplicacionPago;
use App\Models\InventarioLibro;
use App\Models\Matricula;
use App\Models\MovimientoInventarioLibro;
use App\Models\ObligacionPagoEstudiante;
use App\Models\OfertaAcademica;
use App\Models\Pago;
use App\Models\SesionCaja;
use App\Models\Sucursal;
use App\Modules\Pagos\Exceptions\InventarioInsuficienteException;

final class AplicadorEfectosPago
{
    public function obtenerSesionCajaAbierta(int $sucursalId, int $usuarioId): ?SesionCaja
    {
        return SesionCaja::where('sucursal_id', $sucursalId)
            ->where('usuario_cajero_id', $usuarioId)
            ->where('estado', 'abierta')
            ->latest('id')
            ->first();
    }

    public function mensajeSesionCajaRequerida(int $sucursalId, int $usuarioId, string $accion): string
    {
        $sucursal = Sucursal::find($sucursalId);
        $sesionOtraSucursal = SesionCaja::where('usuario_cajero_id', $usuarioId)
            ->where('estado', 'abierta')
            ->where('sucursal_id', '!=', $sucursalId)
            ->latest('id')
            ->first();

        $nombreSucursal = $sucursal?->codigo
            ? $sucursal->codigo . ' · ' . $sucursal->nombre
            : ($sucursal?->nombre ?? 'la sucursal correspondiente');

        if ($sesionOtraSucursal) {
            $sucursalSesion = Sucursal::find($sesionOtraSucursal->sucursal_id);
            $nombreSesion = $sucursalSesion?->codigo
                ? $sucursalSesion->codigo . ' · ' . $sucursalSesion->nombre
                : ($sucursalSesion?->nombre ?? 'otra sucursal');

            return "Debe abrir una sesión de caja en {$nombreSucursal} para {$accion}. Su sesión abierta actual pertenece a {$nombreSesion}.";
        }

        return "Debe abrir una sesión de caja en {$nombreSucursal} antes de {$accion}.";
    }

    public function asignarSesionCajaSiHaceFalta(Pago $pago, int $usuarioId): void
    {
        if ($pago->sesion_caja_id) {
            return;
        }

        $sesionCaja = $this->obtenerSesionCajaAbierta((int) $pago->sucursal_id, $usuarioId);

        if ($sesionCaja) {
            $pago->update([
                'sesion_caja_id' => $sesionCaja->id,
                'actualizado_por' => $usuarioId,
            ]);
        }
    }

    public function confirmarMatriculaSiCorresponde(Pago $pago, int $usuarioId): void
    {
        if (! $pago->matricula_id) {
            return;
        }

        $this->matricularSiReservada($pago, $usuarioId);
    }

    public function confirmarMatriculaAlRegistrar(Pago $pago, bool $esSolicitudLink, int $usuarioId): void
    {
        if (! $pago->matricula_id || $esSolicitudLink) {
            return;
        }

        $this->matricularSiReservada($pago, $usuarioId);
    }

    private function matricularSiReservada(Pago $pago, int $usuarioId): void
    {
        $matricula = Matricula::lockForUpdate()->find($pago->matricula_id);
        if (! $matricula || ! in_array($matricula->estado, ['reservada', 'en_revision'], true)) {
            return;
        }

        $matricula->update([
            'estado' => 'matriculado',
            'fecha_confirmacion' => now(),
            'actualizado_por' => $usuarioId,
        ]);

        $oferta = OfertaAcademica::lockForUpdate()->find($matricula->oferta_academica_id);
        if ($oferta && $oferta->cupos_reservados > 0) {
            $oferta->decrement('cupos_reservados');
            $oferta->increment('cupos_matriculados');
            if ($oferta->cuposDisponibles() <= 0) {
                $oferta->update(['estado' => 'lleno']);
            }
        }
    }

    public function descontarLibroSiCorresponde(Pago $pago, string $codigoConcepto, ?int $inventarioLibroId, ?int $cantidadLibro, int $usuarioId): void
    {
        if (! $inventarioLibroId || $codigoConcepto !== 'VLI') {
            return;
        }

        $inventario = InventarioLibro::lockForUpdate()->findOrFail($inventarioLibroId);
        if ($inventario->existencia_actual < $cantidadLibro) {
            throw new InventarioInsuficienteException('No hay suficiente existencia. Disponible: '.$inventario->existencia_actual);
        }

        $nuevaExistencia = $inventario->existencia_actual - $cantidadLibro;
        $inventario->update([
            'existencia_actual' => $nuevaExistencia,
            'actualizado_por' => $usuarioId,
        ]);

        MovimientoInventarioLibro::create([
            'inventario_libro_id' => $inventario->id,
            'tipo_movimiento' => 'salida',
            'cantidad' => $cantidadLibro,
            'existencia_antes' => $inventario->existencia_actual,
            'existencia_despues' => $nuevaExistencia,
            'motivo' => 'Venta de libro - Pago '.$pago->codigo,
            'referencia_type' => Pago::class,
            'referencia_id' => $pago->id,
            'creado_por' => $usuarioId,
        ]);
    }

    public function aplicarAObligacionesPendientes(Pago $pago, int $usuarioId): void
    {
        if (! $pago->matricula_id) {
            return;
        }

        $obligaciones = ObligacionPagoEstudiante::where('matricula_id', $pago->matricula_id)
            ->where('estado', 'pendiente')
            ->orderBy('numero_cuota')
            ->get();

        $montoRestante = (float) $pago->monto;

        foreach ($obligaciones as $obligacion) {
            if ($montoRestante <= 0) {
                break;
            }

            $saldo = $obligacion->monto - $obligacion->monto_pagado;
            $montoAplicar = min($montoRestante, $saldo);

            $this->aplicarMontoAObligacion($pago, $obligacion, $montoAplicar, $usuarioId);
            $montoRestante -= $montoAplicar;
        }
    }

    public function aplicarAObligacionesConSeleccion(Pago $pago, array $seleccion, int $usuarioId): void
    {
        if (! $pago->matricula_id) {
            return;
        }

        $obligaciones = ObligacionPagoEstudiante::where('matricula_id', $pago->matricula_id)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->orderBy('numero_cuota');

        if (! empty($seleccion)) {
            $ids = collect($seleccion)
                ->pluck('obligacion_id')
                ->map(fn ($id) => (int) $id)
                ->values();
            $obligaciones->whereIn('id', $ids);
        }

        $montoRestante = (float) $pago->monto;

        foreach ($obligaciones->get() as $obligacion) {
            if ($montoRestante <= 0) {
                break;
            }

            $saldo = $obligacion->monto - $obligacion->monto_pagado;
            $seleccionada = collect($seleccion)->firstWhere('obligacion_id', $obligacion->id);
            $montoAplicar = $seleccionada
                ? min((float) $seleccionada['monto_aplicado'], $saldo, $montoRestante)
                : min($montoRestante, $saldo);

            $this->aplicarMontoAObligacion($pago, $obligacion, $montoAplicar, $usuarioId);
            $montoRestante -= $montoAplicar;
        }
    }

    private function aplicarMontoAObligacion(Pago $pago, ObligacionPagoEstudiante $obligacion, float $montoAplicar, int $usuarioId): void
    {
        $obligacion->update([
            'monto_pagado' => $obligacion->monto_pagado + $montoAplicar,
            'estado' => ($obligacion->monto_pagado + $montoAplicar) >= $obligacion->monto ? 'pagado' : 'parcial',
        ]);

        AplicacionPago::create([
            'pago_id' => $pago->id,
            'obligacion_pago_estudiante_id' => $obligacion->id,
            'estudiante_id' => $pago->estudiante_id,
            'monto_aplicado' => $montoAplicar,
            'estado' => 'activo',
            'creado_por' => $usuarioId,
        ]);
    }

    public function cancelarAplicacionesPendientes(int $pagoId): void
    {
        AplicacionPago::where('pago_id', $pagoId)
            ->where('estado', 'pendiente')
            ->update([
                'estado' => 'cancelado',
                'actualizado_en' => now(),
            ]);
    }

    public function revertirMatriculaAlRechazar(Pago $pago, int $usuarioId): void
    {
        if (! $pago->matricula_id) {
            return;
        }

        $matricula = Matricula::lockForUpdate()->find($pago->matricula_id);
        if (! $matricula || ! in_array($matricula->estado, ['reservada', 'en_revision'], true)) {
            return;
        }

        $matricula->update([
            'estado' => 'rechazado',
            'actualizado_por' => $usuarioId,
        ]);

        $oferta = OfertaAcademica::lockForUpdate()->find($matricula->oferta_academica_id);
        if ($oferta && $oferta->cupos_reservados > 0) {
            $oferta->decrement('cupos_reservados');
            $oferta->update(['estado' => 'abierto']);
        }

        $obligacionIds = $pago->aplicaciones()->pluck('obligacion_pago_estudiante_id');
        ObligacionPagoEstudiante::whereIn('id', $obligacionIds)
            ->where('estado', 'pendiente')
            ->update(['estado' => 'rechazado']);
    }
}
