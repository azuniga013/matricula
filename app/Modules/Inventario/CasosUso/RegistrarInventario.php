<?php

namespace App\Modules\Inventario\CasosUso;

use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;
use App\Modules\Inventario\Repositorios\InventarioRepositorio;
use App\Modules\Inventario\Servicios\RegistradorMovimientoInventario;
use Illuminate\Support\Facades\DB;

final class RegistrarInventario
{
    public function __construct(
        private readonly InventarioRepositorio $repositorio,
        private readonly RegistradorMovimientoInventario $registradorMovimiento,
    ) {}

    /**
     * @param  array{libro_id: int, sucursal_id: int, existencia_actual: int, existencia_minima?: int|null}  $datos
     */
    public function ejecutar(array $datos, ContextoUsuario $contexto): ResultadoCasoUso
    {
        if ($this->repositorio->existeParaLibroYSucursal($datos['libro_id'], $datos['sucursal_id'])) {
            return ResultadoCasoUso::error(422, 'El libro ya tiene inventario registrado en esta sucursal');
        }

        $usuarioId = $contexto->usuarioId();

        $guardado = DB::transaction(function () use ($datos, $usuarioId) {
            $inventario = $this->repositorio->crear([
                'libro_id' => $datos['libro_id'],
                'sucursal_id' => $datos['sucursal_id'],
                'existencia_actual' => $datos['existencia_actual'],
                'existencia_minima' => $datos['existencia_minima'] ?? 0,
                'creado_por' => $usuarioId,
            ]);

            if ($inventario->existencia_actual > 0) {
                $this->registradorMovimiento->registrar(
                    $inventario,
                    'entrada',
                    $inventario->existencia_actual,
                    0,
                    $inventario->existencia_actual,
                    'Registro inicial de inventario',
                    null,
                    null,
                    $usuarioId,
                );
            }

            $this->repositorio->cargarRelaciones($inventario);

            return $inventario;
        });

        return ResultadoCasoUso::exito('Inventario registrado exitosamente', ['inventario' => $guardado], 201);
    }
}
