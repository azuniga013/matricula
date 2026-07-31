<?php

namespace App\Http\Controllers\Api\V1\Academico;

use App\Http\Controllers\Controller;
use App\Models\OfertaAcademica;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitorCuposController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = OfertaAcademica::query()
            ->select([
                'ofertas_academicas.id',
                'ofertas_academicas.codigo',
                'ofertas_academicas.estado',
                'ofertas_academicas.cupo_maximo',
                'ofertas_academicas.cupos_reservados',
                'ofertas_academicas.cupos_matriculados',
                'ofertas_academicas.acepta_cambios_horario',
                'ofertas_academicas.sucursal_id',
                'ofertas_academicas.nivel_academico_id',
                'ofertas_academicas.modalidad_id',
                'ofertas_academicas.horario_id',
                'ofertas_academicas.docente_id',
                'ofertas_academicas.periodo_academico_id',
            ])
            ->with([
                'sucursal' => fn ($q) => $q->select('id','codigo','nombre'),
                'nivelAcademico' => fn ($q) => $q->select('id','codigo','nombre','version_plan_estudio_id','regimen_academico_id'),
                'nivelAcademico.regimenAcademico' => fn ($q) => $q->select('id','codigo','nombre'),
                'nivelAcademico.versionPlanEstudio' => fn ($q) => $q->select('id','numero_version','plan_estudio_id'),
                'nivelAcademico.versionPlanEstudio.planEstudio' => fn ($q) => $q->select('id','codigo','nombre'),
                'modalidad' => fn ($q) => $q->select('id','codigo','nombre'),
                'horario' => fn ($q) => $q->select('id','codigo','nombre','hora_inicio','hora_fin'),
                'docente' => fn ($q) => $q->select('id','codigo','nombre','apellido'),
            ]);

        if ($request->filled('periodo_academico_id')) {
            $query->porPeriodo($request->periodo_academico_id);
        }

        if ($request->filled('sucursal_id')) {
            $query->porSucursal($request->sucursal_id);
        }

        $ofertas = $query->orderBy('sucursal_id')
            ->orderBy('nivel_academico_id')
            ->get()
            ->map(function ($oferta) {
                $disponibles = $oferta->cupo_maximo - $oferta->cupos_matriculados - $oferta->cupos_reservados;

                $oferta->cupos_disponibles = $disponibles;
                $oferta->color_estado = $this->calcularColor(
                    $oferta->estado,
                    $disponibles,
                    $oferta->cupo_maximo,
                    (bool) $oferta->acepta_cambios_horario
                );

                return $oferta;
            });

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $ofertas,
            'meta' => [
                'refresco_segundos' => config('seguridad.monitor.refresco_segundos', 300),
            ],
        ]);
    }

    /**
     * Colores funcionales del monitor (AGENTS.md §4.25):
     * verde = matrícula abierta con cupos, azul = acepta cambios de horario,
     * amarillo = pocos cupos, rojo = sin cupos o cerrada, gris = cancelada.
     */
    private function calcularColor(string $estado, int $disponibles, int $cupoMaximo, bool $aceptaCambios): string
    {
        return match (true) {
            $estado === 'cancelado' => 'gris',
            $estado === 'cerrado' || ($estado === 'abierto' && $disponibles <= 0) => 'rojo',
            $estado === 'abierto' && $aceptaCambios => 'azul',
            $estado === 'abierto' && $disponibles <= max(1, (int) floor($cupoMaximo * 0.2)) => 'amarillo',
            $estado === 'abierto' && $disponibles > 0 => 'verde',
            default => 'gris',
        };
    }
}
