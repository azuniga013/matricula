<?php

namespace App\Http\Controllers\Api\V1\Academico;

use App\Http\Controllers\Controller;
use App\Models\{HistorialAcademico, Calificacion, Matricula, NivelAcademico};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistorialAcademicoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'page' => 'nullable|integer|min:1',
        ]);

        $historial = HistorialAcademico::with([
            'nivelAcademico:id,codigo,nombre,orden',
            'periodoAcademico:id,codigo,nombre',
            'ofertaAcademica:id,codigo,modalidad_id,horario_id',
            'ofertaAcademica.regimenAcademico:id,codigo,nombre',
            'ofertaAcademica.modalidad:id,codigo,nombre',
            'ofertaAcademica.horario:id,codigo,nombre',
        ])
        ->where('historial_academico.estudiante_id', $request->estudiante_id)
        ->orderByDesc('historial_academico.id')
        ->paginate($request->get('per_page', 25));

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $historial,
        ]);
    }

    public function nivelActual(int $estudianteId): JsonResponse
    {
        $matriculaActiva = Matricula::with([
            'ofertaAcademica.nivelAcademico:id,codigo,nombre,orden',
            'ofertaAcademica.periodoAcademico:id,codigo,nombre',
            'ofertaAcademica.horario:id,codigo,nombre',
        ])
        ->where('estudiante_id', $estudianteId)
        ->where('estado', 'matriculado')
        ->first();

        if (!$matriculaActiva) {
            return response()->json([
                'resultado' => 'A',
                'codigo' => 200,
                'mensaje' => 'OK',
                'data' => null,
            ]);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => [
                'nivel' => $matriculaActiva->ofertaAcademica->nivelAcademico,
                'periodo' => $matriculaActiva->ofertaAcademica->periodoAcademico,
                'horario' => $matriculaActiva->ofertaAcademica->horario,
                'oferta' => [
                    'codigo' => $matriculaActiva->ofertaAcademica->codigo,
                ],
            ],
        ]);
    }

    public function calificacionesEstudiante(int $estudianteId): JsonResponse
    {
        $calificaciones = Calificacion::with([
            'ofertaAcademica:id,codigo,nivel_id,periodo_academico_id,horario_id',
            'ofertaAcademica.nivelAcademico:id,codigo,nombre',
            'ofertaAcademica.periodoAcademico:id,codigo,nombre',
        ])
        ->where('estudiante_id', $estudianteId)
        ->orderByDesc('calificaciones.id')
        ->get()
        ->map(function ($cal) {
            return [
                'codigo' => $cal->codigo,
                'nota_final' => $cal->nota_final,
                'faltas' => $cal->faltas,
                'estado' => $cal->estado,
                'aprobada' => $cal->estaAprobada(),
                'nivel' => $cal->ofertaAcademica->nivelAcademico->nombre ?? null,
                'periodo' => $cal->ofertaAcademica->periodoAcademico->nombre ?? null,
            ];
        });

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $calificaciones,
        ]);
    }
}
