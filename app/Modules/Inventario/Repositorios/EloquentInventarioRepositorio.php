<?php

namespace App\Modules\Inventario\Repositorios;

use App\Models\InventarioLibro;
use App\Models\MovimientoInventarioLibro;

final class EloquentInventarioRepositorio implements InventarioRepositorio
{
    public function buscar(int $id): ?InventarioLibro
    {
        return InventarioLibro::find($id);
    }

    public function buscarConBloqueo(int $id): ?InventarioLibro
    {
        return InventarioLibro::lockForUpdate()->find($id);
    }

    public function existeParaLibroYSucursal(int $libroId, int $sucursalId): bool
    {
        return InventarioLibro::where('libro_id', $libroId)
            ->where('sucursal_id', $sucursalId)
            ->exists();
    }

    public function crear(array $atributos): InventarioLibro
    {
        return InventarioLibro::create($atributos);
    }

    public function actualizarExistencia(InventarioLibro $inventario, int $existencia, int $usuarioId): void
    {
        $inventario->update([
            'existencia_actual' => $existencia,
            'actualizado_por' => $usuarioId,
            'actualizado_en' => now(),
        ]);
    }

    public function crearMovimiento(array $atributos, int $usuarioId): MovimientoInventarioLibro
    {
        return MovimientoInventarioLibro::create([
            ...$atributos,
            'creado_por' => $usuarioId,
            'creado_en' => now(),
        ]);
    }

    public function cargarRelaciones(InventarioLibro $inventario): void
    {
        $inventario->load('libro:id,codigo,titulo,precio_venta', 'sucursal:id,codigo,nombre');
    }
}
