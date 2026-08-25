<?php

namespace App\Http\Controllers\Api\V1\Academico;

use App\Events\AsistenciaNotificableRegistrada;
use App\Http\Controllers\Controller;
use App\Models\AsistenciaEstudiante;
use App\Models\Matricula;
use App\Models\OfertaAcademica;
use App\Services\ResolutorAlcanceDatos;
use App\Services\ServicioBitacora;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AsistenciaController extends Controller
{
    public function ofertasDisponibles(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = OfertaAcademica::with([
            'sucursal:id,codigo,nombre',
            'periodoAcademico:id,codigo,nombre',
            'nivelAcademico:id,codigo,nombre,regimen_academico_id',
            'nivelAcademico.regimenAcademico:id,codigo,nombre',
            'modalidad:id,codigo,nombre',
            'horario:id,codigo,nombre,hora_inicio,hora_fin',
            'docente:id,codigo,nombre,apellido',
        ])
            // Una oferta puede estar cerrada o llena para nuevas matrículas y
            // aún requerir el pase de lista de sus estudiantes matriculados.
            ->whereIn('estado', ['abierto', 'cerrado', 'lleno'])
            ->orderByDesc('id');

        app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, $user, 'ofertas_academicas');

        if ($user->docente_id) {
            $query->where('docente_id', $user->docente_id);
        }

        if ($request->filled('periodo_academico_id')) {
            $query->where('periodo_academico_id', $request->periodo_academico_id);
        }

        if ($request->filled('sucursal_id')) {
            $query->where('sucursal_id', $request->sucursal_id);
        }

        if ($request->filled('nivel_academico_id')) {
            $query->where('nivel_academico_id', $request->nivel_academico_id);
        }

        $ofertas = $query->limit(200)->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $ofertas,
        ]);
    }

    public function estudiantesPorOferta(Request $request): JsonResponse
    {
        $request->validate([
            'oferta_academica_id' => 'required|exists:ofertas_academicas,id',
        ]);

        $user = $request->user();

        $oferta = OfertaAcademica::findOrFail($request->oferta_academica_id);

        if ($user->docente_id && $oferta->docente_id !== $user->docente_id) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 403,
                'mensaje' => 'No tienes asignada esta oferta académica',
            ], 403);
        }

        $matriculas = Matricula::with([
            'estudiante:id,codigo,nombre,apellido',
        ])
            ->where('oferta_academica_id', $request->oferta_academica_id)
            ->where('estado', 'matriculado')
            ->orderBy('id')
            ->get()
            ->map(function ($m) {
                return [
                    'matricula_id' => $m->id,
                    'estudiante_id' => $m->estudiante_id,
                    'codigo' => $m->estudiante->codigo,
                    'nombre' => $m->estudiante->nombre,
                    'apellido' => $m->estudiante->apellido,
                ];
            });

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $matriculas,
        ]);
    }

    public function registrar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'oferta_academica_id' => 'required|exists:ofertas_academicas,id',
            'fecha' => 'required|date',
            'asistencias' => 'nullable|array',
            'asistencias.*.matricula_id' => 'required|exists:matriculas,id',
            'asistencias.*.estado' => 'required|in:presente,falta,justificada,tardanza',
            'asistencias.*.cuenta_como_falta' => 'nullable|boolean',
            'asistencias.*.observacion' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        $oferta = OfertaAcademica::findOrFail($datos['oferta_academica_id']);

        if ($user->docente_id && $oferta->docente_id !== $user->docente_id) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 403,
                'mensaje' => 'No tienes asignada esta oferta académica',
            ], 403);
        }

        return DB::transaction(function () use ($datos, $user, $request) {
            $registradas = 0;
            $asistenciasNotificables = [];

            $asistencias = $datos['asistencias'] ?? [];
            foreach ($asistencias as $item) {
                $cuentaFalta = $item['cuenta_como_falta'] ?? in_array($item['estado'], ['falta'], true);

                $asistencia = AsistenciaEstudiante::query()
                    ->where('matricula_id', $item['matricula_id'])
                    ->whereDate('fecha', $datos['fecha'])
                    ->first();

                if ($asistencia) {
                    $asistencia->update([
                        'oferta_academica_id' => $datos['oferta_academica_id'],
                        'estado' => $item['estado'],
                        'cuenta_como_falta' => $cuentaFalta,
                        'observacion' => $item['observacion'] ?? null,
                        'registrado_por' => $user->id,
                        'actualizado_en' => now(),
                    ]);
                } else {
                    $asistencia = AsistenciaEstudiante::create([
                        'matricula_id' => $item['matricula_id'],
                        'fecha' => $datos['fecha'],
                        'oferta_academica_id' => $datos['oferta_academica_id'],
                        'estado' => $item['estado'],
                        'cuenta_como_falta' => $cuentaFalta,
                        'observacion' => $item['observacion'] ?? null,
                        'registrado_por' => $user->id,
                        'creado_por' => $user->id,
                        'actualizado_en' => now(),
                    ]);
                }

                if (in_array($asistencia->estado, ['falta', 'tardanza'], true)) {
                    $asistenciasNotificables[] = $asistencia->id;
                }

                $registradas++;
            }

            if (! empty($asistenciasNotificables)) {
                DB::afterCommit(function () use ($asistenciasNotificables) {
                    event(new AsistenciaNotificableRegistrada(array_values(array_unique($asistenciasNotificables))));
                });
            }

            app(ServicioBitacora::class)->registrarAuditoriaDesdeRequest($request, 'asistencias', 'registrar', 'ofertas_academicas', $datos['oferta_academica_id'], null, ['fecha' => $datos['fecha'], 'registradas' => $registradas], 'Registro de asistencias por oferta');

            return response()->json([
                'resultado' => 'A',
                'codigo' => 0,
                'mensaje' => "Se registraron {$registradas} asistencias",
                'data' => ['registradas' => $registradas],
            ]);
        });
    }

    public function porOferta(Request $request): JsonResponse
    {
        $request->validate([
            'oferta_academica_id' => 'required|exists:ofertas_academicas,id',
            'fecha' => 'required|date',
        ]);

        $asistencias = AsistenciaEstudiante::with([
            'matricula.estudiante:id,codigo,nombre,apellido',
        ])
            ->where('oferta_academica_id', $request->oferta_academica_id)
            ->whereDate('fecha', $request->fecha)
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'matricula_id' => $a->matricula_id,
                    'codigo' => $a->matricula?->estudiante?->codigo,
                    'nombre' => $a->matricula?->estudiante?->nombre,
                    'apellido' => $a->matricula?->estudiante?->apellido,
                    'estado' => $a->estado,
                    'cuenta_como_falta' => $a->cuenta_como_falta,
                    'observacion' => $a->observacion,
                ];
            });

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $asistencias,
        ]);
    }

    public function faltasPorOferta(Request $request): JsonResponse
    {
        $request->validate([
            'oferta_academica_id' => 'required|exists:ofertas_academicas,id',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $user = $request->user();
        $oferta = OfertaAcademica::with('periodoAcademico')->findOrFail($request->oferta_academica_id);

        if ($user->docente_id && $oferta->docente_id !== $user->docente_id) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 403,
                'mensaje' => 'No tienes asignada esta oferta académica',
            ], 403);
        }

        $fechaInicio = $request->filled('fecha_inicio') ? $request->fecha_inicio : optional($oferta->periodoAcademico)?->fecha_inicio;
        $fechaFin = $request->filled('fecha_fin') ? $request->fecha_fin : optional($oferta->periodoAcademico)?->fecha_fin;

        $fechaInicio = $fechaInicio ? Carbon::parse($fechaInicio)->format('Y-m-d') : null;
        $fechaFin = $fechaFin ? Carbon::parse($fechaFin)->format('Y-m-d') : null;

        $faltas = Matricula::query()
            ->where('matriculas.oferta_academica_id', $request->oferta_academica_id)
            ->where('matriculas.estado', 'matriculado')
            ->leftJoin('asistencias_estudiante', function ($j) use ($fechaInicio, $fechaFin) {
                $j->on('asistencias_estudiante.matricula_id', '=', 'matriculas.id')
                    ->when($fechaInicio, fn ($q, $fi) => $q->whereDate('asistencias_estudiante.fecha', '>=', $fi))
                    ->when($fechaFin, fn ($q, $ff) => $q->whereDate('asistencias_estudiante.fecha', '<=', $ff))
                    ->where('asistencias_estudiante.cuenta_como_falta', true);
            })
            ->select(
                'matriculas.id as matricula_id',
                'matriculas.estudiante_id',
                DB::raw('COUNT(asistencias_estudiante.id) as faltas')
            )
            ->groupBy('matriculas.id', 'matriculas.estudiante_id')
            ->get()
            ->map(fn ($r) => [
                'matricula_id' => $r->matricula_id,
                'estudiante_id' => $r->estudiante_id,
                'faltas' => (int) $r->faltas,
            ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $faltas,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ]);
    }

    public function resumenFaltas(Request $request): JsonResponse
    {
        $request->validate([
            'oferta_academica_id' => 'required|exists:ofertas_academicas,id',
        ]);

        $resumen = DB::table('asistencias_estudiante')
            ->join('matriculas', 'asistencias_estudiante.matricula_id', '=', 'matriculas.id')
            ->join('estudiantes', 'matriculas.estudiante_id', '=', 'estudiantes.id')
            ->where('asistencias_estudiante.oferta_academica_id', $request->oferta_academica_id)
            ->where('asistencias_estudiante.cuenta_como_falta', true)
            ->select(
                'matriculas.id as matricula_id',
                'estudiantes.codigo',
                'estudiantes.nombre',
                'estudiantes.apellido',
                DB::raw('COUNT(*) as total_faltas')
            )
            ->groupBy('matriculas.id', 'estudiantes.codigo', 'estudiantes.nombre', 'estudiantes.apellido')
            ->orderByDesc('total_faltas')
            ->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $resumen,
        ]);
    }
}
