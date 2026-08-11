<?php

namespace App\Modules\Caja\Repositorios;

use App\Models\ReciboCaja;

interface ReciboCajaRepositorio
{
    public function buscar(int $id): ?ReciboCaja;

    public function registrarReimpresion(ReciboCaja $recibo, int $usuarioId): void;

    public function anular(ReciboCaja $recibo, string $motivo, int $usuarioId): void;
}
