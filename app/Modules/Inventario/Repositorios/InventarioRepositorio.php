<?php

namespace App\Modules\Inventario\Repositorios;

use App\Models\InventarioLibro;
use App\Models\MovimientoInventarioLibro;

interface InventarioRepositorio
{
    public function buscar(int $id): ?InventarioLibro;

    public function buscarConBloqueo(int $id): ?InventarioLibro;

    public function existeParaLibroYSucursal(int $libroId, int $sucursalId): bool;

    public function crear(array $atributos): InventarioLibro;

    public function actualizarExistencia(InventarioLibro $inventario, int $existencia, int $usuarioId): void;

    public function crearMovimiento(array $atributos, int $usuarioId): MovimientoInventarioLibro;

    public function cargarRelaciones(InventarioLibro $inventario): void;
}
