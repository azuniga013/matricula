<?php

namespace App\Modules\Calificaciones\CasosUso;

use App\Modules\Calificaciones\Repositorios\CalificacionRepositorio;
use App\Modules\Calificaciones\Servicios\SincronizadorHistorialAcademico;
use App\Modules\Calificaciones\Servicios\ValidadorAccesoOfertaDocente;
use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;

final class ActualizarCalificacion
{
    public function __construct(
        private readonly CalificacionRepositorio $repositorio,
        private readonly ValidadorAccesoOfertaDocente $validadorAcceso,
        private readonly SincronizadorHistorialAcademico $sincronizadorHistorial,
    ) {}

    /**
     * @param  array{nota_final?: float|int|null, faltas?: int|null, observaciones?: string|null}  $datos
     */
    public function ejecutar(int $id, array $datos, ?int $docenteId, ContextoUsuario $contexto): ResultadoCasoUso
    {
        $calificacion = $this->repositorio->buscarConOferta($id);
        if (! $calificacion) {
            return ResultadoCasoUso::error(404, 'Calificación no encontrada', '404_CALIFICACION_NO_ENCONTRADA');
        }

        if (! $this->validadorAcceso->puedeGestionar($docenteId, $calificacion->ofertaAcademica)) {
            return ResultadoCasoUso::error(403, 'No tienes asignada esta oferta académica', '403_OFERTA_NO_ASIGNADA');
        }

        $this->repositorio->actualizar($calificacion, [
            'nota_final' => $datos['nota_final'] ?? $calificacion->nota_final,
            'faltas' => $datos['faltas'] ?? $calificacion->faltas,
            'observaciones' => $datos['observaciones'] ?? $calificacion->observaciones,
            'estado' => 'corregido',
            'actualizado_por' => $contexto->usuarioId(),
            'actualizado_en' => now(),
        ]);

        $this->repositorio->cargarRelaciones($calificacion);
        $this->sincronizadorHistorial->sincronizar($calificacion);

        return ResultadoCasoUso::exito(
            'Calificación actualizada',
            ['calificacion' => $calificacion],
        );
    }
}
