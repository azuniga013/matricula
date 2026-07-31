<?php

namespace App\Http\Controllers\Api\V1\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\Modulo;
use App\Models\OpcionModulo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpcionModuloController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $opciones = OpcionModulo::query()
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->with('modulo')
            ->withCount('permisos')
            ->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $opciones,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'modulo_id' => 'required|exists:modulos,id',
            'codigo' => 'required|string|max:80|unique:opciones_modulo,codigo',
            'nombre' => 'required|string|max:100',
            'ruta' => 'nullable|string|max:255',
            'orden' => 'nullable|integer|min:0',
        ]);

        $datos['creado_por'] = $request->user()->id;

        $opcion = OpcionModulo::create($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Opción creada exitosamente',
            'data' => $opcion->load('modulo'),
        ], 201);
    }

    public function show(OpcionModulo $opcionModulo): JsonResponse
    {
        $opcionModulo->load('modulo')->loadCount('permisos');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $opcionModulo,
        ]);
    }

    public function update(Request $request, OpcionModulo $opcionModulo): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:100',
            'ruta' => 'nullable|string|max:255',
            'orden' => 'nullable|integer|min:0',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $datos['actualizado_por'] = $request->user()->id;

        $opcionModulo->update($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Opción actualizada exitosamente',
            'data' => $opcionModulo->load('modulo'),
        ]);
    }

    public function destroy(Request $request, OpcionModulo $opcionModulo): JsonResponse
    {
        $opcionModulo->update([
            'estado' => 'inactivo',
            'actualizado_por' => $request->user()->id,
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Opción desactivada exitosamente',
        ]);
    }
}
