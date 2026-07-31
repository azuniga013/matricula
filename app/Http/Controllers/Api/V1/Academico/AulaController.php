<?php

namespace App\Http\Controllers\Api\V1\Academico;

use App\Http\Controllers\Controller;
use App\Models\Aula;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AulaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Aula::activos()->ordenados()->with('sucursal');

        if ($request->filled('sucursal_id')) {
            $query->where('sucursal_id', $request->sucursal_id);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%");
            });
        }

        $aulas = $query->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $aulas,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'sucursal_id' => 'required|exists:sucursales,id',
            'codigo' => 'required|string|max:50|unique:aulas,codigo',
            'nombre' => 'required|string|max:100',
            'capacidad' => 'required|integer|min:1',
            'descripcion' => 'nullable|string',
        ]);

        $datos['creado_por'] = $request->user()->id;

        $aula = Aula::create($datos);
        $aula->load('sucursal');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Aula creada exitosamente',
            'data' => $aula,
        ], 201);
    }

    public function show(Aula $aula): JsonResponse
    {
        $aula->load('sucursal');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $aula,
        ]);
    }

    public function update(Request $request, Aula $aula): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:100',
            'capacidad' => 'required|integer|min:1',
            'descripcion' => 'nullable|string',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $datos['actualizado_por'] = $request->user()->id;

        $aula->update($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Aula actualizada exitosamente',
            'data' => $aula,
        ]);
    }
}
