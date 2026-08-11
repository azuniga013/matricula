<?php

namespace App\Modules\Matriculas\CasosUso;

use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;
use App\Modules\Matriculas\Repositorios\MatriculaRepositorio;
use App\Modules\Matriculas\Servicios\GeneradorObligacionesMatricula;
use App\Modules\Matriculas\Servicios\ValidadorConflictoHorario;
use App\Modules\Matriculas\Servicios\ValidadorPrerrequisitos;
use App\Services\ResolutorFlujoMatricula;
use Illuminate\Support\Facades\DB;

final class ConfirmarMatricula
{
    public function __construct(
        private readonly MatriculaRepositorio $repositorio,
        private readonly ResolutorFlujoMatricula $resolutorFlujo,
        private readonly ValidadorPrerrequisitos $validadorPrerrequisitos,
        private readonly ValidadorConflictoHorario $validadorConflictoHorario,
        private readonly GeneradorObligacionesMatricula $generadorObligaciones,
    ) {}

    public function ejecutar(int $matriculaId, ContextoUsuario $contexto): ResultadoCasoUso
    {
        return DB::transaction(function () use ($matriculaId, $contexto) {
            $matricula = $this->repositorio->buscarConBloqueo($matriculaId);
            if (! $matricula) {
                return ResultadoCasoUso::error(404, 'Matrícula no encontrada', '404_MATRICULA_NO_ENCONTRADA');
            }

            if ($matricula->estado !== 'reservada') {
                return ResultadoCasoUso::error(422, 'Solo se pueden confirmar matrículas reservadas');
            }

            $oferta = $this->repositorio->ofertaConDetallesParaBloqueo($matricula->oferta_academica_id);
            if (! $oferta) {
                return ResultadoCasoUso::error(404, 'Oferta académica no encontrada', '404_OFERTA_NO_ENCONTRADA');
            }

            $configFlujo = $this->resolutorFlujo->resolver('portal_administrativo', $oferta->planCobro?->detalles?->first()?->concepto_pago_id, null);
            if (empty($configFlujo['habilita_confirmacion_matricula'])) {
                return ResultadoCasoUso::error(422, 'La confirmación de matrícula está deshabilitada para este flujo');
            }

            if ($oferta->cuposDisponibles() <= 0) {
                return ResultadoCasoUso::error(422, 'No hay cupos disponibles para confirmar');
            }

            $prerrequisitos = $this->validadorPrerrequisitos->validar($matricula->estudiante_id, $matricula->oferta_academica_id);
            if ($prerrequisitos) {
                return ResultadoCasoUso::error(422, $prerrequisitos);
            }

            $conflicto = $this->validadorConflictoHorario->validar($matricula->estudiante_id, $matricula->oferta_academica_id, $matricula->id);
            if ($conflicto) {
                return ResultadoCasoUso::error(422, $conflicto);
            }

            $this->repositorio->confirmarMatricula($matricula, $contexto->usuarioId());
            $this->generadorObligaciones->generar($matricula, $oferta, $contexto->usuarioId());
            $this->repositorio->marcarOfertaLlenaSiCorresponde($oferta);

            return ResultadoCasoUso::exito(
                'Matrícula confirmada y obligaciones generadas',
                ['matricula' => $matricula->fresh()],
            );
        });
    }
}
