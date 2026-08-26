<?php

namespace App\Modules\Pagos\CasosUso;

use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;
use App\Modules\Pagos\Repositorios\PagoRepositorio;
use App\Modules\Pagos\Servicios\AplicadorEfectosPago;
use App\Models\EnlacePago;
use App\Services\ResolverEnlacePagoDisponible;
use Illuminate\Support\Facades\DB;

final class RechazarPago
{
    public function __construct(
        private readonly PagoRepositorio $repositorio,
        private readonly AplicadorEfectosPago $efectos,
        private readonly ResolverEnlacePagoDisponible $resolverEnlace,
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
            if ($pago->link_pago_url) {
                $enlace = EnlacePago::where('enlace_url', $pago->link_pago_url)->first();
                if ($enlace) {
                    $this->resolverEnlace->marcarDesuso($enlace);
                }
            }
            $this->efectos->cancelarAplicacionesPendientes($pagoId);
            $this->efectos->revertirMatriculaAlRechazar($pago, $usuarioId);

            return ResultadoCasoUso::exito('Pago rechazado', ['pago' => $pago], 200);
        });
    }
}
