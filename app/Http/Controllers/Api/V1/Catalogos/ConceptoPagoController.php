<?php

namespace App\Http\Controllers\Api\V1\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\ConceptoPago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConceptoPagoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ConceptoPago::query()->orderBy('codigo');

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
        foreach (['monto_fijo', 'monto_minimo', 'monto_maximo', 'descripcion'] as $campo) {
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
        if (array_key_exists('requiere_autorizacion_monto', $normalizado) && is_string($normalizado['requiere_autorizacion_monto'])) {
            $normalizado['requiere_autorizacion_monto'] = filter_var($normalizado['requiere_autorizacion_monto'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $datos = validator($normalizado, [
            'codigo' => 'required|string|max:50|unique:conceptos_pago,codigo',
            'nombre' => 'required|string|max:150',
            'tipo_monto' => 'required|string|in:fijo,manual,por_oferta,por_inventario',
            'monto_fijo' => 'nullable|numeric|min:0',
            'monto_minimo' => 'nullable|numeric|min:0',
            'monto_maximo' => 'nullable|numeric|min:0',
            'requiere_autorizacion_monto' => 'sometimes|boolean',
            'descripcion' => 'nullable|string',
            'portal_disponible' => 'nullable|boolean',
        ])->validate();

        $datos['portal_disponible'] = filter_var($datos['portal_disponible'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $datos['creado_por'] = $request->user()->id;
        $datos['actualizado_por'] = $request->user()->id;
        $datos['creado_en'] = now();
        $datos['actualizado_en'] = now();

        $concepto = ConceptoPago::create($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Concepto de pago creado exitosamente',
            'data' => $concepto,
        ], 201);
    }

    public function show(ConceptoPago $conceptoPago): JsonResponse
    {
        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $conceptoPago,
        ]);
    }

    public function update(Request $request, ConceptoPago $conceptoPago): JsonResponse
    {
        $normalizado = $request->all();
        foreach (['monto_fijo', 'monto_minimo', 'monto_maximo', 'descripcion'] as $campo) {
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
        if (array_key_exists('requiere_autorizacion_monto', $normalizado) && is_string($normalizado['requiere_autorizacion_monto'])) {
            $normalizado['requiere_autorizacion_monto'] = filter_var($normalizado['requiere_autorizacion_monto'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $datos = validator($normalizado, [
            'nombre' => 'required|string|max:150',
            'tipo_monto' => 'required|string|in:fijo,manual,por_oferta,por_inventario',
            'monto_fijo' => 'nullable|numeric|min:0',
            'monto_minimo' => 'nullable|numeric|min:0',
            'monto_maximo' => 'nullable|numeric|min:0',
            'requiere_autorizacion_monto' => 'sometimes|boolean',
            'descripcion' => 'nullable|string',
            'estado' => 'sometimes|string|in:activo,inactivo',
            'portal_disponible' => 'nullable|boolean',
        ])->validate();

        if (array_key_exists('portal_disponible', $datos)) {
            $datos['portal_disponible'] = filter_var($datos['portal_disponible'], FILTER_VALIDATE_BOOLEAN);
        }
        $datos['actualizado_por'] = $request->user()->id;
        $datos['actualizado_en'] = now();

        $conceptoPago->update($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Concepto de pago actualizado exitosamente',
            'data' => $conceptoPago,
        ]);
    }
}
