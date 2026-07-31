<?php

namespace App\Http\Controllers\Api\V1\Academico;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocenteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Docente::activos()->ordenados();

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%")
                    ->orWhere('apellido', 'like', "%{$buscar}%")
                    ->orWhere('correo', 'like', "%{$buscar}%");
            });
        }

        $docentes = $query->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $docentes,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => 'required|string|max:50|unique:docentes,codigo',
            'nombre' => 'required|string|max:150',
            'apellido' => 'required|string|max:150',
            'correo' => 'nullable|email|max:100',
            'telefono' => 'nullable|string|max:30',
            'identidad' => 'nullable|string|max:30|unique:docentes,identidad',
        ]);

        $datos['creado_por'] = $request->user()->id;

        $docente = Docente::create($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Docente creado exitosamente',
            'data' => $docente,
        ], 201);
    }

    public function show(Docente $docente): JsonResponse
    {
        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $docente,
        ]);
    }

    public function update(Request $request, Docente $docente): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:150',
            'apellido' => 'required|string|max:150',
            'correo' => 'nullable|email|max:100',
            'telefono' => 'nullable|string|max:30',
            'identidad' => 'nullable|string|max:30|unique:docentes,identidad,' . $docente->id,
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $datos['actualizado_por'] = $request->user()->id;

        $docente->update($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Docente actualizado exitosamente',
            'data' => $docente,
        ]);
    }
}
