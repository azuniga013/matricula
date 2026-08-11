<?php

namespace App\Modules\Caja\CasosUso;

use App\Modules\Caja\Repositorios\ReciboCajaRepositorio;
use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;

final class AnularRecibo
{
    public function __construct(
        private readonly ReciboCajaRepositorio $repositorio,
    ) {}

    public function ejecutar(int $reciboId, string $motivo, ContextoUsuario $contexto): ResultadoCasoUso
    {
        $recibo = $this->repositorio->buscar($reciboId);
        if (! $recibo) {
            return ResultadoCasoUso::error(404, 'Recibo no encontrado', '404_RECIBO_NO_ENCONTRADO');
        }

        if ($recibo->estado === 'anulado') {
            return ResultadoCasoUso::error(422, 'El recibo ya está anulado');
        }

        $this->repositorio->anular($recibo, $motivo, $contexto->usuarioId());

        return ResultadoCasoUso::exito('Recibo anulado', ['recibo' => $recibo->fresh()]);
    }
}
