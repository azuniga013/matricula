<?php

namespace App\Http\Controllers\Api\V1\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\OpcionModulo;
use App\Models\Permiso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermisoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $permisos = Permiso::query()
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->with('opcionModulo.modulo')
            ->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $permisos,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'opcion_modulo_id' => 'required|exists:opciones_modulo,id',
            'codigo' => 'required|string|max:80|unique:permisos,codigo',
            'nombre' => 'required|string|max:100',
            'accion' => 'required|string|max:30',
        ]);

        $datos['creado_por'] = $request->user()->id;
        $datos['actualizado_por'] = $request->user()->id;
        $datos['creado_en'] = now();
        $datos['actualizado_en'] = now();

        $permiso = Permiso::create($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Permiso creado exitosamente',
            'data' => $permiso->load('opcionModulo'),
        ], 201);
    }

    public function show(Permiso $permiso): JsonResponse
    {
        $permiso->load('opcionModulo.modulo');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $permiso,
        ]);
    }

    public function update(Request $request, Permiso $permiso): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:100',
            'accion' => 'required|string|max:30',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $datos['actualizado_por'] = $request->user()->id;
        $datos['actualizado_en'] = now();

        $permiso->update($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Permiso actualizado exitosamente',
            'data' => $permiso->load('opcionModulo'),
        ]);
    }

    public function destroy(Request $request, Permiso $permiso): JsonResponse
    {
        $permiso->update([
            'estado' => 'inactivo',
            'actualizado_por' => $request->user()->id,
            'actualizado_en' => now(),
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Permiso desactivado exitosamente',
        ]);
    }
}
