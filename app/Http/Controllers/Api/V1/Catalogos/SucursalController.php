<?php

namespace App\Http\Controllers\Api\V1\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\Modalidad;
use App\Models\Sucursal;
use App\Services\ResolutorAlcanceDatos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Sucursal::query()->orderBy('codigo');
        if ($request->user()) {
            app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, $request->user(), 'sucursales');
        }

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

        $sucursales = $query->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $sucursales,
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
            'modalidades_atencion' => 'nullable|array',
            'modalidades_atencion.*' => 'integer|exists:modalidades,id',
        ]);

        $datos['creado_por'] = $request->user()->id;
        $datos['actualizado_por'] = $request->user()->id;
        $datos['creado_en'] = now();
        $datos['actualizado_en'] = now();

        $sucursal = Sucursal::create($datos);
        $this->sincronizarModalidadesAtencion($sucursal, $request->input('modalidades_atencion', []), $request->user()->id);
        $sucursal->load(['modalidadesAtencion:id,codigo,nombre,tipo']);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Sucursal creada exitosamente',
            'data' => $sucursal,
        ], 201);
    }

    public function show(Sucursal $sucursal): JsonResponse
    {
        $sucursal->load(['modalidadesAtencion:id,codigo,nombre,tipo']);

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
            'modalidades_atencion' => 'nullable|array',
            'modalidades_atencion.*' => 'integer|exists:modalidades,id',
        ]);

        $datos['actualizado_por'] = $request->user()->id;
        $datos['actualizado_en'] = now();

        $sucursal->update($datos);
        if ($request->has('modalidades_atencion')) {
            $this->sincronizarModalidadesAtencion($sucursal, $request->input('modalidades_atencion', []), $request->user()->id);
        }

        $sucursal->load(['modalidadesAtencion:id,codigo,nombre,tipo']);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Sucursal actualizada exitosamente',
            'data' => $sucursal,
        ]);
    }

    private function sincronizarModalidadesAtencion(Sucursal $sucursal, array $modalidadesIds, int $usuarioId): void
    {
        $modalidadesIds = array_values(array_filter(array_map('intval', $modalidadesIds)));
        $modalidadesIds = Modalidad::query()->whereIn('id', $modalidadesIds)->where('tipo', 'atencion')->pluck('id')->all();

        $sucursal->modalidadesAtencion()->syncWithPivotValues($modalidadesIds, [
            'estado' => 'activo',
            'actualizado_por' => $usuarioId,
        ]);
    }
}
