<?php

namespace App\Http\Controllers\Api\V1\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\ParametroGlobal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParametroGlobalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ParametroGlobal::query();

        if ($request->filled('grupo')) {
            $query->where('grupo', $request->grupo);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                  ->orWhere('nombre', 'like', "%{$buscar}%");
            });
        }

        $parametros = $query->orderBy('grupo')->orderBy('codigo')->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $parametros,
        ]);
    }

    public function grupos(): JsonResponse
    {
        $grupos = ParametroGlobal::select('grupo')
            ->distinct()
            ->orderBy('grupo')
            ->pluck('grupo');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $grupos,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'grupo' => 'required|string|max:50',
            'codigo' => 'required|string|max:100',
            'nombre' => 'required|string|max:150',
            'valor' => 'nullable|string',
            'tipo' => 'required|in:texto,numero,booleano,seleccion',
            'opciones' => 'nullable|array',
            'descripcion' => 'nullable|string|max:255',
            'estado' => 'boolean',
        ]);

        $existente = ParametroGlobal::where('grupo', $datos['grupo'])
            ->where('codigo', $datos['codigo'])
            ->exists();

        if ($existente) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'Ya existe un parámetro con ese código en el grupo especificado',
            ], 422);
        }

        $datos['creado_por'] = $request->user()->id;
        $datos['actualizado_por'] = $request->user()->id;
        $datos['estado'] = $datos['estado'] ?? true;

        $parametro = ParametroGlobal::create($datos);
        ParametroGlobal::invalidarCache();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 201,
            'mensaje' => 'Parámetro creado exitosamente',
            'data' => $parametro,
        ], 201);
    }

    public function update(Request $request, ParametroGlobal $parametroGlobal): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => 'sometimes|string|max:150',
            'valor' => 'nullable|string',
            'tipo' => 'sometimes|in:texto,numero,booleano,seleccion',
            'opciones' => 'nullable|array',
            'descripcion' => 'nullable|string|max:255',
            'estado' => 'boolean',
        ]);

        $datos['actualizado_por'] = $request->user()->id;

        $parametroGlobal->update($datos);
        ParametroGlobal::invalidarCache();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Parámetro actualizado exitosamente',
            'data' => $parametroGlobal->fresh(),
        ]);
    }

    public function destroy(ParametroGlobal $parametroGlobal): JsonResponse
    {
        $parametroGlobal->delete();
        ParametroGlobal::invalidarCache();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Parámetro eliminado exitosamente',
        ]);
    }
}
