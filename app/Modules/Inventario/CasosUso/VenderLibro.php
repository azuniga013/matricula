<?php

namespace App\Modules\Inventario\CasosUso;

use App\Models\Pago;
use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;
use App\Modules\Inventario\Repositorios\InventarioRepositorio;
use App\Modules\Inventario\Servicios\RegistradorMovimientoInventario;
use Illuminate\Support\Facades\DB;

final class VenderLibro
{
    public function __construct(
        private readonly InventarioRepositorio $repositorio,
        private readonly RegistradorMovimientoInventario $registradorMovimiento,
    ) {}

    /**
     * @param  array{inventario_libro_id: int, cantidad: int, motivo?: string|null, pago_id?: int|null}  $datos
     */
    public function ejecutar(array $datos, ContextoUsuario $contexto): ResultadoCasoUso
    {
        return DB::transaction(function () use ($datos, $contexto) {
            $inventario = $this->repositorio->buscarConBloqueo($datos['inventario_libro_id']);
            if (! $inventario) {
                return ResultadoCasoUso::error(404, 'Inventario no encontrado', '404_INVENTARIO_NO_ENCONTRADO');
            }

            if ($inventario->existencia_actual < $datos['cantidad']) {
                return ResultadoCasoUso::error(422, 'No hay suficiente existencia. Disponible: '.$inventario->existencia_actual);
            }

            $usuarioId = $contexto->usuarioId();
            $nuevaExistencia = $inventario->existencia_actual - $datos['cantidad'];
            $existenciaAntes = $inventario->existencia_actual;

            $this->repositorio->actualizarExistencia($inventario, $nuevaExistencia, $usuarioId);

            $this->registradorMovimiento->registrar(
                $inventario,
                'salida',
                $datos['cantidad'],
                $existenciaAntes,
                $nuevaExistencia,
                $datos['motivo'] ?? 'Venta de libro',
                ! empty($datos['pago_id']) ? Pago::class : null,
                $datos['pago_id'] ?? null,
                $usuarioId,
            );

            $this->repositorio->cargarRelaciones($inventario);

            return ResultadoCasoUso::exito('Venta registrada', [
                'venta' => [
                    'inventario' => $inventario,
                    'total_venta' => $inventario->libro->precio_venta * $datos['cantidad'],
                ],
            ]);
        });
    }
}
