<?php

namespace App\Http\Controllers\Api\V1\Pagos;

use App\Http\Controllers\Controller;
use App\Models\EnlacePago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnlacePagoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = EnlacePago::with(['conceptoPago', 'cuentaBancaria']);

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

        if ($request->filled('concepto_pago_id')) {
            $query->where('concepto_pago_id', $request->concepto_pago_id);
        }

        $enlaces = $query->orderBy('creado_en', 'desc')->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $enlaces,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => 'required|string|max:50|unique:enlaces_pago,codigo',
            'nombre' => 'required|string|max:150',
            'monto' => 'nullable|numeric|min:0',
            'concepto_pago_id' => 'nullable|exists:conceptos_pago,id',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
            'fecha_vencimiento' => 'nullable|date',
            'usos_maximos' => 'nullable|integer|min:1',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $datos['creado_por'] = $request->user()->id;
        $datos['usos_actuales'] = 0;

        $enlace = EnlacePago::create($datos);
        $enlace->load(['conceptoPago', 'cuentaBancaria']);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Enlace de pago creado exitosamente',
            'data' => $enlace,
        ], 201);
    }

    public function show(EnlacePago $enlacePago): JsonResponse
    {
        $enlacePago->load(['conceptoPago', 'cuentaBancaria']);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $enlacePago,
        ]);
    }

    public function update(Request $request, EnlacePago $enlacePago): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => 'sometimes|string|max:50|unique:enlaces_pago,codigo,' . $enlacePago->id,
            'nombre' => 'sometimes|string|max:150',
            'monto' => 'nullable|numeric|min:0',
            'concepto_pago_id' => 'nullable|exists:conceptos_pago,id',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
            'fecha_vencimiento' => 'nullable|date',
            'usos_maximos' => 'nullable|integer|min:1',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $datos['actualizado_por'] = $request->user()->id;

        $enlacePago->update($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Enlace de pago actualizado exitosamente',
            'data' => $enlacePago,
        ]);
    }

    public function destroy(Request $request, EnlacePago $enlacePago): JsonResponse
    {
        $enlacePago->update([
            'estado' => 'inactivo',
            'actualizado_por' => $request->user()->id,
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Enlace de pago desactivado correctamente.',
        ]);
    }

    public function disponibles(Request $request): JsonResponse
    {
        $query = EnlacePago::with(['conceptoPago', 'cuentaBancaria'])
            ->where('estado', 'activo')
            ->where(function ($q) {
                $q->whereNull('fecha_vencimiento')
                    ->orWhere('fecha_vencimiento', '>=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('usos_maximos')
                    ->orWhereColumn('usos_actuales', '<', 'usos_maximos');
            });

        if ($request->filled('concepto_pago_id')) {
            $query->where('concepto_pago_id', $request->concepto_pago_id);
        }

        if ($request->filled('monto')) {
            $query->where('monto', $request->monto);
        }

        $enlaces = $query->orderBy('creado_en', 'desc')->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $enlaces,
        ]);
    }

    public function usar(Request $request, EnlacePago $enlacePago): JsonResponse
    {
        if (!$enlacePago->estaDisponible()) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'Este enlace de pago ya no está disponible.',
            ], 422);
        }

        $enlacePago->increment('usos_actuales');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Enlace asignado correctamente.',
            'data' => $enlacePago,
        ]);
    }
}
