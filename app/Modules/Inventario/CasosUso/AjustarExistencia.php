<?php

namespace App\Modules\Inventario\CasosUso;

use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;
use App\Modules\Inventario\Repositorios\InventarioRepositorio;
use App\Modules\Inventario\Servicios\RegistradorMovimientoInventario;
use Illuminate\Support\Facades\DB;

final class AjustarExistencia
{
    public function __construct(
        private readonly InventarioRepositorio $repositorio,
        private readonly RegistradorMovimientoInventario $registradorMovimiento,
    ) {}

    /**
     * @param  array{inventario_libro_id: int, cantidad: int, motivo: string}  $datos
     */
    public function ejecutar(array $datos, ContextoUsuario $contexto): ResultadoCasoUso
    {
        return DB::transaction(function () use ($datos, $contexto) {
            $inventario = $this->repositorio->buscarConBloqueo($datos['inventario_libro_id']);
            if (! $inventario) {
                return ResultadoCasoUso::error(404, 'Inventario no encontrado', '404_INVENTARIO_NO_ENCONTRADO');
            }

            $nuevaExistencia = $inventario->existencia_actual + $datos['cantidad'];

            if ($nuevaExistencia < 0) {
                return ResultadoCasoUso::error(422, 'La existencia no puede ser negativa');
            }

            $usuarioId = $contexto->usuarioId();
            $tipo = $datos['cantidad'] >= 0 ? 'entrada' : 'salida';
            $existenciaAntes = $inventario->existencia_actual;

            $this->repositorio->actualizarExistencia($inventario, $nuevaExistencia, $usuarioId);

            $this->registradorMovimiento->registrar(
                $inventario,
                $tipo,
                abs($datos['cantidad']),
                $existenciaAntes,
                $nuevaExistencia,
                $datos['motivo'],
                null,
                null,
                $usuarioId,
            );

            $this->repositorio->cargarRelaciones($inventario);

            return ResultadoCasoUso::exito(
                $tipo === 'entrada' ? 'Entrada registrada' : 'Salida registrada',
                ['inventario' => $inventario],
            );
        });
    }
}
