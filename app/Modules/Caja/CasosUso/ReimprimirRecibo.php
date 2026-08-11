<?php

namespace App\Modules\Caja\CasosUso;

use App\Modules\Caja\Repositorios\ReciboCajaRepositorio;
use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;

final class ReimprimirRecibo
{
    public function __construct(
        private readonly ReciboCajaRepositorio $repositorio,
    ) {}

    public function ejecutar(int $reciboId, ContextoUsuario $contexto): ResultadoCasoUso
    {
        $recibo = $this->repositorio->buscar($reciboId);
        if (! $recibo) {
            return ResultadoCasoUso::error(404, 'Recibo no encontrado', '404_RECIBO_NO_ENCONTRADO');
        }

        if ($recibo->estado === 'anulado') {
            return ResultadoCasoUso::error(422, 'No se puede reimprimir un recibo anulado');
        }

        $this->repositorio->registrarReimpresion($recibo, $contexto->usuarioId());

        return ResultadoCasoUso::exito('Reimpresión registrada', ['recibo' => $recibo->fresh()]);
    }
}
