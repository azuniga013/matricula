<?php

namespace App\Http\Controllers\Api\V1\Academico;

use App\Http\Controllers\Controller;
use App\Models\Modalidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModalidadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Modalidad::activos()->ordenados();

        if ($request->filled('tipo')) {
            $query->porTipo($request->tipo);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%");
            });
        }

        $modalidades = $query->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $modalidades,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => 'required|string|max:50|unique:modalidades,codigo',
            'nombre' => 'required|string|max:100',
            'tipo' => 'required|string|in:regimen_academico,atencion',
            'descripcion' => 'nullable|string',
        ]);

        $datos['creado_por'] = $request->user()->id;

        $modalidad = Modalidad::create($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Modalidad creada exitosamente',
            'data' => $modalidad,
        ], 201);
    }

    public function show(Modalidad $modalidad): JsonResponse
    {
        $modalidad->loadCount('niveles');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $modalidad,
        ]);
    }

    public function update(Request $request, Modalidad $modalidad): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $datos['actualizado_por'] = $request->user()->id;

        $modalidad->update($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Modalidad actualizada exitosamente',
            'data' => $modalidad,
        ]);
    }
}
