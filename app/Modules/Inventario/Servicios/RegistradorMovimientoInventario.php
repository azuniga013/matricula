<?php

namespace App\Modules\Inventario\Servicios;

use App\Models\InventarioLibro;
use App\Modules\Inventario\Repositorios\InventarioRepositorio;

final class RegistradorMovimientoInventario
{
    public function __construct(
        private readonly InventarioRepositorio $repositorio,
    ) {}

    public function registrar(
        InventarioLibro $inventario,
        string $tipoMovimiento,
        int $cantidad,
        int $existenciaAntes,
        int $existenciaDespues,
        string $motivo,
        ?string $referenciaType,
        ?int $referenciaId,
        int $usuarioId,
    ): void {
        $this->repositorio->crearMovimiento([
            'inventario_libro_id' => $inventario->id,
            'tipo_movimiento' => $tipoMovimiento,
            'cantidad' => $cantidad,
            'existencia_antes' => $existenciaAntes,
            'existencia_despues' => $existenciaDespues,
            'motivo' => $motivo,
            'referencia_type' => $referenciaType,
            'referencia_id' => $referenciaId,
        ], $usuarioId);
    }
}
