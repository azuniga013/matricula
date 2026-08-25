<?php

namespace App\Http\Controllers\Api\V1\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\MetodoPago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class MetodoPagoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MetodoPago::query()->orderBy('codigo');

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
        $normalizado = $request->all();
        foreach (['descripcion'] as $campo) {
            if (($normalizado[$campo] ?? null) === '') {
                $normalizado[$campo] = null;
            }
        }
        if (array_key_exists('portal_disponible', $normalizado) && $normalizado['portal_disponible'] === '') {
            $normalizado['portal_disponible'] = null;
        }
        if (array_key_exists('portal_disponible', $normalizado) && is_string($normalizado['portal_disponible'])) {
            $normalizado['portal_disponible'] = filter_var($normalizado['portal_disponible'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        if (!Schema::hasColumn('metodos_pago', 'permite_link_pago')) {
            unset($normalizado['permite_link_pago']);
        } elseif (array_key_exists('permite_link_pago', $normalizado) && is_string($normalizado['permite_link_pago'])) {
            $normalizado['permite_link_pago'] = filter_var($normalizado['permite_link_pago'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $datos = validator($normalizado, [
            'codigo' => 'required|string|max:50|unique:metodos_pago,codigo',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'permite_link_pago' => 'sometimes|boolean',
            'portal_disponible' => 'nullable|boolean',
        ])->validate();

        $datos['portal_disponible'] = filter_var($datos['portal_disponible'] ?? true, FILTER_VALIDATE_BOOLEAN);
        if (Schema::hasColumn('metodos_pago', 'permite_link_pago')) {
            $datos['permite_link_pago'] = filter_var($datos['permite_link_pago'] ?? false, FILTER_VALIDATE_BOOLEAN);
        } else {
            unset($datos['permite_link_pago']);
        }
        $datos['creado_por'] = $request->user()->id;
        $datos['actualizado_por'] = $request->user()->id;
        $datos['creado_en'] = now();
        $datos['actualizado_en'] = now();

        $metodo = MetodoPago::create($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Método de pago creado exitosamente',
            'data' => $metodo,
        ], 201);
    }

    public function show(MetodoPago $metodoPago): JsonResponse
    {
        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $metodoPago,
        ]);
    }

    public function update(Request $request, MetodoPago $metodoPago): JsonResponse
    {
        $normalizado = $request->all();
        foreach (['descripcion'] as $campo) {
            if (($normalizado[$campo] ?? null) === '') {
                $normalizado[$campo] = null;
            }
        }
        if (array_key_exists('portal_disponible', $normalizado) && $normalizado['portal_disponible'] === '') {
            $normalizado['portal_disponible'] = null;
        }
        if (array_key_exists('portal_disponible', $normalizado) && is_string($normalizado['portal_disponible'])) {
            $normalizado['portal_disponible'] = filter_var($normalizado['portal_disponible'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        if (!Schema::hasColumn('metodos_pago', 'permite_link_pago')) {
            unset($normalizado['permite_link_pago']);
        } elseif (array_key_exists('permite_link_pago', $normalizado) && is_string($normalizado['permite_link_pago'])) {
            $normalizado['permite_link_pago'] = filter_var($normalizado['permite_link_pago'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $datos = validator($normalizado, [
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'permite_link_pago' => 'sometimes|boolean',
            'estado' => 'sometimes|string|in:activo,inactivo',
            'portal_disponible' => 'nullable|boolean',
        ])->validate();

        if (array_key_exists('portal_disponible', $datos)) {
            $datos['portal_disponible'] = filter_var($datos['portal_disponible'], FILTER_VALIDATE_BOOLEAN);
        }
        if (Schema::hasColumn('metodos_pago', 'permite_link_pago') && array_key_exists('permite_link_pago', $datos)) {
            $datos['permite_link_pago'] = filter_var($datos['permite_link_pago'], FILTER_VALIDATE_BOOLEAN);
        } else {
            unset($datos['permite_link_pago']);
        }
        $datos['actualizado_por'] = $request->user()->id;
        $datos['actualizado_en'] = now();

        $metodoPago->update($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Método de pago actualizado exitosamente',
            'data' => $metodoPago,
        ]);
    }
}
