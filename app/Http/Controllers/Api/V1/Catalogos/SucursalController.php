<?php

namespace App\Http\Controllers\Api\V1\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Sucursal::query()->orderBy('codigo');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $query->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => 'required|string|max:50|unique:sucursales,codigo',
            'nombre' => 'required|string|max:150',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:30',
            'correo' => 'nullable|email|max:150',
        ]);

        $datos['creado_por'] = $request->user()->id;

        $sucursal = Sucursal::create($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Sucursal creada exitosamente',
            'data' => $sucursal,
        ], 201);
    }

    public function show(Sucursal $sucursal): JsonResponse
    {
        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $sucursal,
        ]);
    }

    public function update(Request $request, Sucursal $sucursal): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:150',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:30',
            'correo' => 'nullable|email|max:150',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $datos['actualizado_por'] = $request->user()->id;

        $sucursal->update($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Sucursal actualizada exitosamente',
            'data' => $sucursal,
        ]);
    }
}
