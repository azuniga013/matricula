<?php

namespace App\Modules\Pagos\CasosUso;

use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;
use App\Modules\Pagos\Repositorios\PagoRepositorio;
use App\Modules\Pagos\Servicios\AplicadorEfectosPago;
use Illuminate\Support\Facades\DB;

final class RechazarPago
{
    public function __construct(
        private readonly PagoRepositorio $repositorio,
        private readonly AplicadorEfectosPago $efectos,
    ) {}

    public function ejecutar(int $pagoId, string $motivo, ContextoUsuario $contexto): ResultadoCasoUso
    {
        return DB::transaction(function () use ($pagoId, $motivo, $contexto) {
            $pago = $this->repositorio->buscarConBloqueo($pagoId);
            if (! $pago) {
                return ResultadoCasoUso::error(404, 'Pago no encontrado', '404_PAGO_NO_ENCONTRADO');
            }

            if (! in_array($pago->estado, ['pendiente', 'solicita_link', 'en_revision'], true)) {
                return ResultadoCasoUso::error(422, 'El pago no está en un estado válido para rechazo. Estado actual: '.$pago->estado);
            }

            $usuarioId = $contexto->usuarioId();
            $this->repositorio->marcarRechazado($pago, $motivo, $usuarioId);
            $this->efectos->cancelarAplicacionesPendientes($pagoId);
            $this->efectos->revertirMatriculaAlRechazar($pago, $usuarioId);

            return ResultadoCasoUso::exito('Pago rechazado', ['pago' => $pago], 200);
        });
    }
}
