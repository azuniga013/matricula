<?php

namespace App\Modules\Caja\CasosUso;

use App\Modules\Caja\Repositorios\SesionCajaRepositorio;
use App\Modules\Caja\Servicios\GeneradorDetallesCierre;
use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;
use Illuminate\Support\Facades\DB;

final class CerrarSesionCaja
{
    public function __construct(
        private readonly SesionCajaRepositorio $repositorio,
        private readonly GeneradorDetallesCierre $generadorDetalles,
    ) {}

    public function ejecutar(int $sesionId, array $datos, ContextoUsuario $contexto): ResultadoCasoUso
    {
        return DB::transaction(function () use ($sesionId, $datos, $contexto) {
            $usuarioId = $contexto->usuarioId();

            $sesion = $this->repositorio->buscarConBloqueo($sesionId);
            if (! $sesion) {
                return ResultadoCasoUso::error(404, 'Sesión de caja no encontrada', '404_SESION_NO_ENCONTRADA');
            }

            if ($sesion->estado !== 'abierta') {
                return ResultadoCasoUso::error(422, 'La sesión ya está cerrada');
            }

            if ($sesion->usuario_cajero_id !== $usuarioId) {
                return ResultadoCasoUso::error(403, 'Solo el cajero que abrió la sesión puede cerrarla');
            }

            $fechaCierre = $sesion->fecha_cierre ?? now();

            $pagos = $this->repositorio->pagosAprobadosDeLaSesion($sesion, $fechaCierre);

            foreach ($this->generadorDetalles->generar($pagos) as $detalle) {
                $this->repositorio->guardarDetalleCierre($sesion, $detalle, $usuarioId);
            }

            $this->repositorio->cerrarSesion($sesion, [
                'estado' => 'cerrada',
                'monto_final' => $datos['monto_final'],
                'fecha_cierre' => now(),
                'observaciones' => $datos['observaciones'] ?? null,
                'cerrado_por' => $usuarioId,
                'actualizado_por' => $usuarioId,
            ]);

            return ResultadoCasoUso::exito(
                'Sesión de caja cerrada con éxito',
                ['sesion' => $sesion->fresh('detalles')],
            );
        });
    }
}
