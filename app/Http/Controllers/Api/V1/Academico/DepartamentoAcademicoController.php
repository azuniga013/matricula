<?php

namespace App\Http\Controllers\Api\V1\Academico;

use App\Http\Controllers\Controller;
use App\Models\DepartamentoAcademico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartamentoAcademicoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DepartamentoAcademico::ordenados()->withCount('planesEstudio');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%");
            });
        }

        $departamentos = $query->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $departamentos,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => 'required|string|max:50|unique:departamentos_academicos,codigo',
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $datos['creado_por'] = $request->user()->id;

        $departamento = DepartamentoAcademico::create($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Departamento académico creado exitosamente',
            'data' => $departamento,
        ], 201);
    }

    public function show(DepartamentoAcademico $departamentoAcademico): JsonResponse
    {
        $departamentoAcademico->loadCount('planesEstudio')->load([
            'planesEstudio' => fn ($q) => $q->activos(),
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $departamentoAcademico,
        ]);
    }

    public function update(Request $request, DepartamentoAcademico $departamentoAcademico): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $datos['actualizado_por'] = $request->user()->id;

        $departamentoAcademico->update($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Departamento académico actualizado exitosamente',
            'data' => $departamentoAcademico,
        ]);
    }
}
