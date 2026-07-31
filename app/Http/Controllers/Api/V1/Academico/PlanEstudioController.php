<?php

namespace App\Http\Controllers\Api\V1\Academico;

use App\Http\Controllers\Controller;
use App\Models\PlanEstudio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanEstudioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PlanEstudio::ordenados()
            ->with('departamentoAcademico')
            ->withCount('versiones');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('departamento_academico_id')) {
            $query->where('departamento_academico_id', $request->departamento_academico_id);
        }

        $planes = $query->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $planes,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'departamento_academico_id' => 'required|exists:departamentos_academicos,id',
            'codigo' => 'required|string|max:50|unique:planes_estudio,codigo',
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
        ]);

        $datos['creado_por'] = $request->user()->id;

        $plan = PlanEstudio::create($datos);
        $plan->load('departamentoAcademico');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Plan de estudio creado exitosamente',
            'data' => $plan,
        ], 201);
    }

    public function show(PlanEstudio $planEstudio): JsonResponse
    {
        $planEstudio->load('departamentoAcademico')
            ->loadCount('versiones')
            ->load(['versiones' => fn ($q) => $q->activos()->orderByDesc('numero_version')]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $planEstudio,
        ]);
    }

    public function update(Request $request, PlanEstudio $planEstudio): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $datos['actualizado_por'] = $request->user()->id;

        $planEstudio->update($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Plan de estudio actualizado exitosamente',
            'data' => $planEstudio,
        ]);
    }
}
