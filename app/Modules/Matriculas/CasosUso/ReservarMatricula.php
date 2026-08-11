<?php

namespace App\Modules\Matriculas\CasosUso;

use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;
use App\Modules\Matriculas\Repositorios\MatriculaRepositorio;
use App\Modules\Matriculas\Servicios\GeneradorObligacionesMatricula;
use App\Modules\Matriculas\Servicios\ValidadorPrerrequisitos;
use App\Services\ResolutorFlujoMatricula;
use App\Services\ServicioNomenclatura;
use Illuminate\Support\Facades\DB;

final class ReservarMatricula
{
    public function __construct(
        private readonly MatriculaRepositorio $repositorio,
        private readonly ResolutorFlujoMatricula $resolutorFlujo,
        private readonly ValidadorPrerrequisitos $validadorPrerrequisitos,
        private readonly ServicioNomenclatura $nomenclatura,
        private readonly GeneradorObligacionesMatricula $generadorObligaciones,
    ) {}

    public function ejecutar(array $datos, ContextoUsuario $contexto): ResultadoCasoUso
    {
        return DB::transaction(function () use ($datos, $contexto) {
            $oferta = $this->repositorio->ofertaConDetallesParaBloqueo((int) $datos['oferta_academica_id']);
            if (! $oferta) {
                return ResultadoCasoUso::error(404, 'Oferta académica no encontrada', '404_OFERTA_NO_ENCONTRADA');
            }

            $planOfertaId = $oferta->nivelAcademico?->versionPlanEstudio?->plan_estudio_id;
            if (! empty($datos['plan_estudio_id']) && (int) $datos['plan_estudio_id'] !== (int) $planOfertaId) {
                return ResultadoCasoUso::error(422, 'La oferta seleccionada no pertenece al plan de estudio indicado');
            }

            $configFlujo = $this->resolutorFlujo->resolver('portal_administrativo', $oferta->planCobro?->detalles?->first()?->concepto_pago_id, null);
            if (empty($configFlujo['habilita_reserva_cupo'])) {
                return ResultadoCasoUso::error(422, 'La reserva de cupo está deshabilitada para este flujo');
            }

            if ($oferta->estado !== 'abierto') {
                return ResultadoCasoUso::error(422, 'La oferta no está abierta para matrícula');
            }

            if ($oferta->cuposDisponibles() <= 0) {
                return ResultadoCasoUso::error(422, 'No hay cupos disponibles');
            }

            $estudianteId = (int) $datos['estudiante_id'];

            if ($this->repositorio->existeMatriculaActivaEnOferta($estudianteId, $oferta->id)) {
                return ResultadoCasoUso::error(422, 'El estudiante ya tiene una matrícula activa en esta oferta');
            }

            $planNuevoId = $oferta->nivelAcademico?->versionPlanEstudio?->plan_estudio_id;
            if ($planNuevoId && $this->repositorio->tienePlanActivoDiferente($estudianteId, $planNuevoId)) {
                return ResultadoCasoUso::error(422, 'El estudiante ya tiene un plan de estudios activo. Debe finalizarlo antes de cambiarse a otro plan.');
            }

            $prerrequisitos = $this->validadorPrerrequisitos->validar($estudianteId, $oferta->id);
            if ($prerrequisitos) {
                return ResultadoCasoUso::error(422, $prerrequisitos);
            }

            $codigoMatricula = $this->nomenclatura->generarCodigo(
                entidad: 'matriculas_'.date('Y'),
                formato: 'MAT-{ANIO}-{SECUENCIA:8}',
                longitudSecuencia: 8,
                anio: date('Y'),
            );

            $matricula = $this->repositorio->crearMatricula([
                'codigo' => $codigoMatricula['codigo'],
                'estudiante_id' => $estudianteId,
                'oferta_academica_id' => $oferta->id,
                'sucursal_id' => $oferta->sucursal_id,
                'estado' => 'reservada',
                'fecha_reserva' => now(),
                'creado_por' => $contexto->usuarioId(),
            ]);

            $this->repositorio->reservarCupo($oferta);
            $this->generadorObligaciones->generar($matricula, $oferta, $contexto->usuarioId());

            return ResultadoCasoUso::exito(
                'Matrícula reservada correctamente',
                ['matricula' => $matricula],
            );
        });
    }
}
