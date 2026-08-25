<?php

namespace App\Http\Controllers\Api\V1\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\Modulo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModuloController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $modulos = Modulo::query()
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->ordenados()
            ->withCount('opciones')
            ->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $modulos,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => 'required|string|max:50|unique:modulos,codigo',
            'nombre' => 'required|string|max:100',
            'orden' => 'nullable|integer|min:0',
        ]);

        $datos['creado_por'] = $request->user()->id;
        $datos['actualizado_por'] = $request->user()->id;
        $datos['creado_en'] = now();
        $datos['actualizado_en'] = now();

        $modulo = Modulo::create($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Módulo creado exitosamente',
            'data' => $modulo,
        ], 201);
    }

    public function show(Modulo $modulo): JsonResponse
    {
        $modulo->loadCount('opciones');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $modulo,
        ]);
    }

    public function update(Request $request, Modulo $modulo): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:100',
            'orden' => 'nullable|integer|min:0',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $datos['actualizado_por'] = $request->user()->id;
        $datos['actualizado_en'] = now();

        $modulo->update($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Módulo actualizado exitosamente',
            'data' => $modulo,
        ]);
    }

    public function destroy(Request $request, Modulo $modulo): JsonResponse
    {
        $modulo->update([
            'estado' => 'inactivo',
            'actualizado_por' => $request->user()->id,
            'actualizado_en' => now(),
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Módulo desactivado exitosamente',
        ]);
    }
}
