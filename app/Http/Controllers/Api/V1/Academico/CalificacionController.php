<?php

namespace App\Http\Controllers\Api\V1\Academico;

use App\Helpers\RespuestaError;
use App\Http\Controllers\Controller;
use App\Models\Calificacion;
use App\Modules\Calificaciones\CasosUso\ActualizarCalificacion;
use App\Modules\Calificaciones\CasosUso\RegistrarCalificaciones;
use App\Modules\Calificaciones\Servicios\ValidadorAccesoOfertaDocente;
use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;
use App\Services\ServicioBitacora;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalificacionController extends Controller
{
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
        if ($request->user()->docente_id) {
            $query->whereHas('ofertaAcademica', fn ($q) => $q->where('docente_id', $request->user()->docente_id));
        }

        $calificaciones = $query->orderByDesc('calificaciones.id')->paginate($request->get('per_page', 25));
        $calificaciones->getCollection()->transform(function (Calificacion $calificacion) {
            $calificacion->setAttribute('aprobada', $calificacion->estaAprobada());

            return $calificacion;
        });

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

        $resultado = app(RegistrarCalificaciones::class)->ejecutar(
            (int) $request->oferta_academica_id,
            $request->calificaciones,
            $request->user()?->docente_id,
            ContextoUsuario::desdeRequest(),
        );

        if ($resultado->ok()) {
            app(ServicioBitacora::class)->registrarAuditoriaDesdeRequest($request, 'calificaciones', 'registrar', 'ofertas_academicas', (int) $request->oferta_academica_id, null, ['cantidad' => count($request->calificaciones)], 'Registro de calificaciones por oferta');
        }

        return $this->responder($resultado);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $calificacion = Calificacion::with([
            'estudiante:id,codigo,nombre,apellido',
            'ofertaAcademica:id,codigo,nivel_id,horario_id,periodo_academico_id',
            'docente:id,codigo,nombre,apellido',
            'matricula:id,codigo,estado',
        ])->findOrFail($id);
        if (! app(ValidadorAccesoOfertaDocente::class)->puedeGestionar($request->user()?->docente_id, $calificacion->ofertaAcademica)) {
            return RespuestaError::make('403_OFERTA_NO_ASIGNADA', 403, 'No tienes asignada esta oferta académica')->response($request);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $calificacion,
        ]);
    }

    public function actualizar(int $id, Request $request): JsonResponse
    {
        $antes = Calificacion::find($id)?->only(['nota_final', 'faltas', 'observaciones', 'estado']);
        $request->validate([
            'nota_final' => 'nullable|numeric|min:0|max:100',
            'faltas' => 'nullable|integer|min:0',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $resultado = app(ActualizarCalificacion::class)->ejecutar(
            $id,
            $request->only(['nota_final', 'faltas', 'observaciones']),
            $request->user()?->docente_id,
            ContextoUsuario::desdeRequest(),
        );

        if ($resultado->ok()) {
            app(ServicioBitacora::class)->registrarAuditoriaDesdeRequest($request, 'calificaciones', 'actualizar', 'calificaciones', $id, $antes, $resultado->data()['calificacion'] ?? null, 'Actualización de calificación');
        }

        return $this->responder($resultado);
    }

    private function responder(ResultadoCasoUso $resultado): JsonResponse
    {
        if (! $resultado->ok()) {
            return RespuestaError::make(
                $resultado->codigoError() ?? 'ERROR',
                $resultado->codigo(),
                $resultado->mensaje()
            )->response(request());
        }

        $data = $resultado->data();

        return response()->json([
            'resultado' => 'A',
            'codigo' => $resultado->codigo(),
            'mensaje' => $resultado->mensaje(),
            'data' => $data['calificaciones'] ?? $data['calificacion'] ?? null,
        ]);
    }
}
