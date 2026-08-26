<?php

namespace App\Modules\Pagos\CasosUso;

use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;
use App\Modules\Pagos\Repositorios\PagoRepositorio;
use App\Modules\Pagos\Servicios\AplicadorEfectosPago;
use App\Modules\Pagos\Servicios\GeneradorReciboCaja;
use App\Models\EnlacePago;
use App\Services\ResolverEnlacePagoDisponible;
use Illuminate\Support\Facades\DB;

final class AprobarPago
{
    public function __construct(
        private readonly PagoRepositorio $repositorio,
        private readonly AplicadorEfectosPago $efectos,
        private readonly GeneradorReciboCaja $generadorRecibo,
        private readonly ResolverEnlacePagoDisponible $resolverEnlace,
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

            if (! $this->efectos->obtenerSesionCajaAbierta((int) $pago->sucursal_id, $usuarioId)) {
                return ResultadoCasoUso::error(
                    422,
                    $this->efectos->mensajeSesionCajaRequerida((int) $pago->sucursal_id, $usuarioId, 'aprobar pagos administrativos'),
                    '422_SESION_CAJA_REQUERIDA'
                );
            }

            $this->repositorio->aprobar($pago, $usuarioId);
            if ($pago->link_pago_url) {
                $enlace = EnlacePago::where('enlace_url', $pago->link_pago_url)->first();
                if ($enlace) {
                    $this->resolverEnlace->marcarUsado($enlace);
                }
            }
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
