<?php

namespace App\Http\Controllers\Api\V1\Academico;

use App\Http\Controllers\Controller;
use App\Models\PeriodoAcademico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PeriodoAcademicoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PeriodoAcademico::ordenados();

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%");
            });
        }

        $periodos = $query->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $periodos,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => 'required|string|max:50|unique:periodos_academicos,codigo',
            'nombre' => 'required|string|max:150',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
        ]);

        $datos['creado_por'] = $request->user()->id;
        $datos['actualizado_por'] = $request->user()->id;
        $datos['creado_en'] = now();
        $datos['actualizado_en'] = now();

        $periodo = PeriodoAcademico::create($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Período académico creado exitosamente',
            'data' => $periodo,
        ], 201);
    }

    public function show(PeriodoAcademico $periodoAcademico): JsonResponse
    {
        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $periodoAcademico,
        ]);
    }

    public function update(Request $request, PeriodoAcademico $periodoAcademico): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:150',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'estado' => 'sometimes|string|in:activo,cerrado,inactivo',
        ]);

        $datos['actualizado_por'] = $request->user()->id;
        $datos['actualizado_en'] = now();

        $periodoAcademico->update($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Período académico actualizado exitosamente',
            'data' => $periodoAcademico,
        ]);
    }
}
