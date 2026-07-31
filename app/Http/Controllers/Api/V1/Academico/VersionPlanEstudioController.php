<?php

namespace App\Http\Controllers\Api\V1\Academico;

use App\Http\Controllers\Controller;
use App\Models\PlanEstudio;
use App\Models\VersionPlanEstudio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VersionPlanEstudioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = VersionPlanEstudio::with('planEstudio');

        if ($request->filled('plan_estudio_id')) {
            $query->where('plan_estudio_id', $request->plan_estudio_id);
        }

        $versiones = $query->orderByDesc('numero_version')->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $versiones,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'plan_estudio_id' => [
                'required', 'integer',
                Rule::exists('planes_estudio', 'id')->where('estado', 'activo'),
            ],
            'numero_version' => [
                'required', 'integer', 'min:1',
                Rule::unique('versiones_plan_estudio', 'numero_version')
                    ->where('plan_estudio_id', $request->plan_estudio_id),
            ],
            'vigente_desde' => 'required|date',
            'vigente_hasta' => 'nullable|date|after_or_equal:vigente_desde',
            'codigo' => 'nullable|string|max:50|unique:versiones_plan_estudio,codigo',
        ]);

        if (empty($datos['codigo'])) {
            $plan = PlanEstudio::find($datos['plan_estudio_id']);
            $codigoBase = 'V' . $datos['plan_estudio_id'] . 'N' . $datos['numero_version'];
            $datos['codigo'] = $codigoBase;
        }

        $datos['creado_por'] = $request->user()->id;

        $version = VersionPlanEstudio::create($datos);
        $version->load('planEstudio');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Versión de plan de estudio creada exitosamente',
            'data' => $version,
        ], 201);
    }

    public function show(VersionPlanEstudio $versionPlanEstudio): JsonResponse
    {
        $versionPlanEstudio->load('planEstudio')->loadCount('niveles');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $versionPlanEstudio,
        ]);
    }

    public function update(Request $request, VersionPlanEstudio $versionPlanEstudio): JsonResponse
    {
        $datos = $request->validate([
            'vigente_desde' => 'nullable|date',
            'vigente_hasta' => 'nullable|date|after_or_equal:vigente_desde',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $datos['actualizado_por'] = $request->user()->id;

        $versionPlanEstudio->update($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Versión actualizada exitosamente',
            'data' => $versionPlanEstudio,
        ]);
    }
}
