<?php

namespace App\Http\Controllers\Api\V1\Academico;

use App\Http\Controllers\Controller;
use App\Models\{Calificacion, HistorialAcademico, Matricula, OfertaAcademica, NivelAcademico};
use App\Services\{ServicioNomenclatura, ServicioBitacora};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalificacionController extends Controller
{
    public function __construct(protected ?ServicioBitacora $bitacora = null) {}
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'oferta_academica_id' => 'nullable|exists:ofertas_academicas,id',
            'estudiante_id' => 'nullable|exists:estudiantes,id',
            'estado' => 'nullable|in:pendiente,registrado,corregido,anulado',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = Calificacion::with([
            'estudiante:id,codigo,nombre,apellido',
            'ofertaAcademica:id,codigo,nivel_academico_id,horario_id,periodo_academico_id',
            'ofertaAcademica.nivelAcademico:id,codigo,nombre',
            'ofertaAcademica.horario:id,codigo,nombre,hora_inicio,hora_fin',
            'ofertaAcademica.periodoAcademico:id,codigo,nombre',
            'docente:id,codigo,nombre,apellido',
        ]);

        if ($request->filled('oferta_academica_id')) {
            $query->where('calificaciones.oferta_academica_id', $request->oferta_academica_id);
        }
        if ($request->filled('estudiante_id')) {
            $query->where('calificaciones.estudiante_id', $request->estudiante_id);
        }
        if ($request->filled('estado')) {
            $query->where('calificaciones.estado', $request->estado);
        }

        $calificaciones = $query->orderByDesc('calificaciones.id')->paginate($request->get('per_page', 25));

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $calificaciones,
        ]);
    }

    public function registrar(Request $request): JsonResponse
    {
        $request->validate([
            'oferta_academica_id' => 'required|exists:ofertas_academicas,id',
            'calificaciones' => 'required|array|min:1',
            'calificaciones.*.estudiante_id' => 'required|exists:estudiantes,id',
            'calificaciones.*.nota_final' => 'nullable|numeric|min:0|max:100',
            'calificaciones.*.faltas' => 'nullable|integer|min:0',
            'calificaciones.*.observaciones' => 'nullable|string|max:500',
        ]);

        $resultado = DB::transaction(function () use ($request) {
            $oferta = OfertaAcademica::findOrFail($request->oferta_academica_id);
            $docenteId = $oferta->docente_id;
            $creadas = [];

            foreach ($request->calificaciones as $item) {
                $matricula = Matricula::where('estudiante_id', $item['estudiante_id'])
                    ->where('oferta_academica_id', $oferta->id)
                    ->where('estado', 'matriculado')
                    ->first();

                if (!$matricula) continue;

                $codigoCalificacion = app(ServicioNomenclatura::class)->generarCodigo(
                    entidad: 'calificaciones_' . date('Y'),
                    formato: 'CAL-{ANIO}-{SECUENCIA:6}',
                    longitudSecuencia: 6,
                    anio: date('Y'),
                );

                $calificacion = Calificacion::updateOrCreate(
                    [
                        'estudiante_id' => $item['estudiante_id'],
                        'oferta_academica_id' => $oferta->id,
                    ],
                    [
                        'codigo' => $codigoCalificacion['codigo'],
                        'matricula_id' => $matricula->id,
                        'nota_final' => $item['nota_final'] ?? null,
                        'faltas' => $item['faltas'] ?? 0,
                        'docente_id' => $docenteId,
                        'estado' => 'registrado',
                        'observaciones' => $item['observaciones'] ?? null,
                        'creado_por' => $request->user()?->id,
                    ]
                );

                $creadas[] = $calificacion;
            }

            return $creadas;
        });

        foreach ($resultado as $cal) {
            $cal->load('matricula', 'ofertaAcademica.nivelAcademico', 'ofertaAcademica.periodoAcademico', 'ofertaAcademica.modalidad');
            $this->sincronizarHistorialAcademico($cal);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => count($resultado) . ' calificaciones registradas',
            'data' => $resultado,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $calificacion = Calificacion::with([
            'estudiante:id,codigo,nombre,apellido',
            'ofertaAcademica:id,codigo,nivel_id,horario_id,periodo_academico_id',
            'docente:id,codigo,nombre,apellido',
            'matricula:id,codigo,estado',
        ])->findOrFail($id);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $calificacion,
        ]);
    }

    public function actualizar(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'nota_final' => 'nullable|numeric|min:0|max:100',
            'faltas' => 'nullable|integer|min:0',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $calificacion = Calificacion::findOrFail($id);

        $calificacion->update([
            'nota_final' => $request->nota_final ?? $calificacion->nota_final,
            'faltas' => $request->faltas ?? $calificacion->faltas,
            'observaciones' => $request->observaciones ?? $calificacion->observaciones,
            'estado' => 'corregido',
            'actualizado_por' => $request->user()?->id,
        ]);

        $calificacion->load('matricula', 'ofertaAcademica.nivelAcademico', 'ofertaAcademica.periodoAcademico', 'ofertaAcademica.modalidad');
        $this->sincronizarHistorialAcademico($calificacion);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'Calificación actualizada',
            'data' => $calificacion,
        ]);
    }

    private function sincronizarHistorialAcademico(Calificacion $calificacion): void
    {
        $matricula = $calificacion->matricula;
        $oferta = $calificacion->ofertaAcademica()->with(['nivelAcademico', 'periodoAcademico', 'modalidad'])->first();

        if (!$matricula || !$oferta) {
            return;
        }

        $estado = 'matriculado';
        if ($calificacion->nota_final !== null) {
            // Usa el método que ya evalúa nota + faltas según modalidad
            $estado = $calificacion->estaAprobada() ? 'aprobado' : 'reprobado';
        }

        $codigoHistorial = 'HIS-' . $calificacion->codigo;
        $codigoHistorial = substr($codigoHistorial, 0, 50);

        $historial = HistorialAcademico::where('estudiante_id', $calificacion->estudiante_id)
            ->where('matricula_id', $calificacion->matricula_id)
            ->first();

        if ($historial) {
            $historial->update([
                'oferta_academica_id' => $oferta->id,
                'nivel_academico_id' => $oferta->nivel_academico_id,
                'periodo_academico_id' => $oferta->periodo_academico_id,
                'estado' => $estado,
                'nota_final' => $calificacion->nota_final,
                'faltas' => $calificacion->faltas ?? 0,
                'observaciones' => $calificacion->observaciones,
            ]);
        } else {
            HistorialAcademico::create([
                'codigo' => $codigoHistorial,
                'estudiante_id' => $calificacion->estudiante_id,
                'matricula_id' => $calificacion->matricula_id,
                'oferta_academica_id' => $oferta->id,
                'nivel_academico_id' => $oferta->nivel_academico_id,
                'periodo_academico_id' => $oferta->periodo_academico_id,
                'estado' => $estado,
                'nota_final' => $calificacion->nota_final,
                'faltas' => $calificacion->faltas ?? 0,
                'observaciones' => $calificacion->observaciones,
            ]);
        }
    }
}
