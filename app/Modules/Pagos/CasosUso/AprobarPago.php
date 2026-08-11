<?php

namespace App\Modules\Pagos\CasosUso;

use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;
use App\Modules\Pagos\Repositorios\PagoRepositorio;
use App\Modules\Pagos\Servicios\AplicadorEfectosPago;
use App\Modules\Pagos\Servicios\GeneradorReciboCaja;
use Illuminate\Support\Facades\DB;

final class AprobarPago
{
    public function __construct(
        private readonly PagoRepositorio $repositorio,
        private readonly AplicadorEfectosPago $efectos,
        private readonly GeneradorReciboCaja $generadorRecibo,
    ) {}

    public function ejecutar(int $pagoId, ContextoUsuario $contexto): ResultadoCasoUso
    {
        return DB::transaction(function () use ($pagoId, $contexto) {
            $pago = $this->repositorio->buscarConBloqueo($pagoId);
            if (! $pago) {
                return ResultadoCasoUso::error(404, 'Pago no encontrado', '404_PAGO_NO_ENCONTRADO');
            }

            if (! in_array($pago->estado, ['pendiente', 'en_revision'], true)) {
                return ResultadoCasoUso::error(422, 'El pago no está pendiente ni en revisión');
            }

            $usuarioId = $contexto->usuarioId();

            $this->repositorio->aprobar($pago, $usuarioId);
            $this->efectos->asignarSesionCajaSiHaceFalta($pago, $usuarioId);
            $this->efectos->confirmarMatriculaSiCorresponde($pago, $usuarioId);
            $this->efectos->aplicarAObligacionesPendientes($pago, $usuarioId);

            $recibo = $this->generadorRecibo->generar($pago, null, $usuarioId);

            return ResultadoCasoUso::exito(
                'Pago aprobado y recibo generado',
                ['pago' => $pago->fresh(), 'recibo' => $recibo],
            );
        });
    }
}
