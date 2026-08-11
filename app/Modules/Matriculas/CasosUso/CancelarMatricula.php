<?php

namespace App\Modules\Matriculas\CasosUso;

use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;
use App\Modules\Matriculas\Repositorios\MatriculaRepositorio;
use Illuminate\Support\Facades\DB;

final class CancelarMatricula
{
    public function __construct(
        private readonly MatriculaRepositorio $repositorio,
    ) {}

    public function ejecutar(int $matriculaId, string $motivo, ContextoUsuario $contexto): ResultadoCasoUso
    {
        return DB::transaction(function () use ($matriculaId, $motivo, $contexto) {
            $matricula = $this->repositorio->buscarConBloqueo($matriculaId);
            if (! $matricula) {
                return ResultadoCasoUso::error(404, 'Matrícula no encontrada', '404_MATRICULA_NO_ENCONTRADA');
            }

            if (in_array($matricula->estado, ['cancelado'])) {
                return ResultadoCasoUso::error(422, 'La matrícula ya está cancelada');
            }

            $oferta = $this->repositorio->ofertaParaBloqueo($matricula->oferta_academica_id);
            if (! $oferta) {
                return ResultadoCasoUso::error(404, 'Oferta académica no encontrada', '404_OFERTA_NO_ENCONTRADA');
            }

            $estadoAnterior = $matricula->estado;

            $this->repositorio->cancelarMatricula($matricula, $motivo, $contexto->usuarioId());
            $this->repositorio->liberarCupos($oferta, $estadoAnterior);
            $this->repositorio->rechazarObligaciones($matricula);

            return ResultadoCasoUso::exito(
                'Matrícula cancelada',
                ['matricula' => $matricula->fresh()],
            );
        });
    }
}
