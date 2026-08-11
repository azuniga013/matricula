<?php

namespace App\Modules\Pagos\CasosUso;

use App\Modules\Comun\ResultadoCasoUso;
use App\Modules\Pagos\Repositorios\PagoRepositorio;
use Illuminate\Support\Facades\DB;

final class EliminarPagoTotal
{
    public function __construct(
        private readonly PagoRepositorio $repositorio,
    ) {}

    public function ejecutar(int $pagoId): ResultadoCasoUso
    {
        return DB::transaction(function () use ($pagoId) {
            $pago = $this->repositorio->buscarConBloqueo($pagoId);
            if (! $pago) {
                return ResultadoCasoUso::error(404, 'Pago no encontrado', '404_PAGO_NO_ENCONTRADO');
            }

            $this->repositorio->eliminarDependenciasYRegistro($pago);

            return ResultadoCasoUso::exito('Pago eliminado por completo', ['ok' => true], 200);
        });
    }
}
