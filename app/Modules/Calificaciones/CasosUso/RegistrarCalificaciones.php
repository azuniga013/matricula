<?php

namespace App\Modules\Calificaciones\CasosUso;

use App\Modules\Calificaciones\Repositorios\CalificacionRepositorio;
use App\Modules\Calificaciones\Servicios\SincronizadorHistorialAcademico;
use App\Modules\Calificaciones\Servicios\ValidadorAccesoOfertaDocente;
use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;
use App\Services\ServicioNomenclatura;
use Illuminate\Support\Facades\DB;

final class RegistrarCalificaciones
{
    public function __construct(
        private readonly CalificacionRepositorio $repositorio,
        private readonly ValidadorAccesoOfertaDocente $validadorAcceso,
        private readonly SincronizadorHistorialAcademico $sincronizadorHistorial,
        private readonly ServicioNomenclatura $nomenclatura,
    ) {}

    /**
     * @param  array<int, array{estudiante_id: int, nota_final: float|int|null, faltas: int|null, observaciones: string|null}>  $calificaciones
     */
    public function ejecutar(int $ofertaAcademicaId, array $calificaciones, ?int $docenteId, ContextoUsuario $contexto): ResultadoCasoUso
    {
        $oferta = $this->repositorio->buscarOferta($ofertaAcademicaId);
        if (! $oferta) {
            return ResultadoCasoUso::error(404, 'Oferta académica no encontrada', '404_OFERTA_NO_ENCONTRADA');
        }

        if (! $this->validadorAcceso->puedeGestionar($docenteId, $oferta)) {
            return ResultadoCasoUso::error(403, 'No tienes asignada esta oferta académica', '403_OFERTA_NO_ASIGNADA');
        }

        $creadas = DB::transaction(function () use ($oferta, $calificaciones, $contexto) {
            $creadas = [];

            foreach ($calificaciones as $item) {
                $matricula = $this->repositorio->matriculaActivaEnOferta($item['estudiante_id'], $oferta->id);
                if (! $matricula) {
                    continue;
                }

                $codigo = $this->nomenclatura->generarCodigo(
                    entidad: 'calificaciones_'.date('Y'),
                    formato: 'CAL-{ANIO}-{SECUENCIA:6}',
                    longitudSecuencia: 6,
                    anio: date('Y'),
                );

                $calificacion = $this->repositorio->crearOActualizar(
                    [
                        'estudiante_id' => $item['estudiante_id'],
                        'oferta_academica_id' => $oferta->id,
                    ],
                    [
                        'codigo' => $codigo['codigo'],
                        'matricula_id' => $matricula->id,
                        'nota_final' => $item['nota_final'] ?? null,
                        'faltas' => $item['faltas'] ?? 0,
                        'docente_id' => $oferta->docente_id,
                        'estado' => 'registrado',
                        'observaciones' => $item['observaciones'] ?? null,
                        'creado_por' => $contexto->usuarioId(),
                    ]
                );

                $creadas[] = $calificacion;
            }

            return $creadas;
        });

        foreach ($creadas as $calificacion) {
            $this->repositorio->cargarRelaciones($calificacion);
            $this->sincronizadorHistorial->sincronizar($calificacion);
        }

        return ResultadoCasoUso::exito(
            count($creadas).' calificaciones registradas',
            ['calificaciones' => $creadas],
        );
    }
}
