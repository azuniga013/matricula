<?php

namespace App\Http\Controllers\Api\V1\Academico;

use App\Helpers\RespuestaError;
use App\Http\Controllers\Controller;
use App\Models\EvaluacionNivelacion;
use App\Models\Matricula;
use App\Modules\Comun\ContextoUsuario;
use App\Modules\Nivelaciones\CasosUso\RegistrarResultadoNivelacion;
use App\Services\ResolutorAlcanceDatos;
use App\Services\ServicioBitacora;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NivelacionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = EvaluacionNivelacion::with([
            'estudiante:id,codigo,nombre,apellido', 'matriculaExamen:id,codigo,estado,sucursal_id',
            'ofertaExamen:id,codigo,nivel_academico_id,sucursal_id,periodo_academico_id,tipo_oferta',
            'nivelAcademico:id,codigo,nombre,orden', 'nivelRecomendado:id,codigo,nombre,orden',
            'evaluador:id,name',
        ])->whereHas('ofertaExamen', function ($ofertas) use ($request) {
            app(ResolutorAlcanceDatos::class)->aplicarAlcance($ofertas, $request->user(), 'ofertas_academicas');
        });

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        return response()->json(['resultado' => 'A', 'codigo' => 0, 'mensaje' => 'OK', 'data' => $query->latest('id')->paginate($request->integer('per_page', 25))]);
    }

    public function pendientes(Request $request): JsonResponse
    {
        $query = Matricula::with(['estudiante:id,codigo,nombre,apellido', 'ofertaAcademica.nivelAcademico', 'ofertaAcademica.periodoAcademico'])
            ->where('estado', 'matriculado')
            ->whereHas('ofertaAcademica', function ($ofertas) use ($request) {
                $ofertas->where('tipo_oferta', 'nivelacion');
                app(ResolutorAlcanceDatos::class)->aplicarAlcance($ofertas, $request->user(), 'ofertas_academicas');
            })
            ->whereDoesntHave('evaluacionNivelacion');

        return response()->json(['resultado' => 'A', 'codigo' => 0, 'mensaje' => 'OK', 'data' => $query->latest('id')->paginate($request->integer('per_page', 25))]);
    }

    public function registrar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'matricula_examen_id' => 'required|exists:matriculas,id',
            'nota_obtenida' => 'required|numeric|min:0|max:100',
            'aprobado' => 'required|boolean',
            'nivel_acreditado_id' => 'nullable|required_if:aprobado,true|exists:niveles_academicos,id',
            'observaciones' => 'nullable|string|max:2000',
        ]);
        $matricula = Matricula::with('ofertaAcademica')->findOrFail($datos['matricula_examen_id']);
        if (! $matricula->ofertaAcademica || ! app(ResolutorAlcanceDatos::class)->aplicarAlcance($matricula->ofertaAcademica->newQuery(), $request->user(), 'ofertas_academicas')->whereKey($matricula->ofertaAcademica->id)->exists()) {
            return RespuestaError::make('403_SIN_ALCANCE', 403, 'No tiene acceso a esta oferta de nivelación')->response($request);
        }
        $resultado = app(RegistrarResultadoNivelacion::class)->ejecutar($matricula, $datos, ContextoUsuario::desdeRequest());
        if (! $resultado->ok()) {
            return RespuestaError::make($resultado->codigoError() ?? 'ERROR', $resultado->codigo(), $resultado->mensaje())->response($request);
        }
        app(ServicioBitacora::class)->registrarAuditoriaDesdeRequest($request, 'nivelaciones', 'registrar_resultado', 'evaluaciones_nivelacion', $resultado->data()['evaluacion']->id, null, $resultado->data()['evaluacion']->toArray(), 'Resultado de nivelación registrado');

        return response()->json(['resultado' => 'A', 'codigo' => 0, 'mensaje' => $resultado->mensaje(), 'data' => $resultado->data()['evaluacion']], $resultado->codigo());
    }
}
