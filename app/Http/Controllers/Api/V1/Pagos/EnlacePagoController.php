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
        $query = EnlacePago::with(['conceptoPago', 'metodoPago', 'cuentaBancaria']);

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
            'enlace_url' => 'required|url|max:500',
            'monto' => 'nullable|numeric|min:0',
            'monto_objetivo' => 'nullable|numeric|min:0',
            'concepto_pago_id' => 'nullable|exists:conceptos_pago,id',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
            'fecha_vencimiento' => 'nullable|date',
            'usos_maximos' => 'nullable|integer|min:1',
            'observaciones' => 'nullable|string|max:500',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $datos['creado_por'] = $request->user()->id;
        $datos['actualizado_por'] = $request->user()->id;
        $datos['creado_en'] = now();
        $datos['actualizado_en'] = now();
        $datos['usos_actuales'] = 0;

        $enlace = EnlacePago::create($datos);
        $enlace->load(['conceptoPago', 'metodoPago', 'cuentaBancaria']);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Enlace de pago creado exitosamente',
            'data' => $enlace,
        ], 201);
    }

    public function show(EnlacePago $enlacePago): JsonResponse
    {
        $enlacePago->load(['conceptoPago', 'metodoPago', 'cuentaBancaria']);

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
            'enlace_url' => 'sometimes|url|max:500',
            'monto' => 'nullable|numeric|min:0',
            'monto_objetivo' => 'nullable|numeric|min:0',
            'concepto_pago_id' => 'nullable|exists:conceptos_pago,id',
            'metodo_pago_id' => 'sometimes|exists:metodos_pago,id',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
            'fecha_vencimiento' => 'nullable|date',
            'usos_maximos' => 'nullable|integer|min:1',
            'observaciones' => 'nullable|string|max:500',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $datos['actualizado_por'] = $request->user()->id;
        $datos['actualizado_en'] = now();

        $enlacePago->update($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Enlace de pago actualizado exitosamente',
            'data' => $enlacePago->load(['conceptoPago', 'metodoPago', 'cuentaBancaria']),
        ]);
    }

    public function destroy(Request $request, EnlacePago $enlacePago): JsonResponse
    {
        $enlacePago->update([
            'estado' => 'inactivo',
            'actualizado_por' => $request->user()->id,
            'actualizado_en' => now(),
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Enlace de pago desactivado correctamente.',
        ]);
    }

    public function disponibles(Request $request): JsonResponse
    {
        $query = EnlacePago::with(['conceptoPago', 'metodoPago', 'cuentaBancaria'])
            ->where('estado', 'activo')
            ->where('estado_operativo', 'disponible')
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
            $query->where(function ($q) use ($request) {
                $q->where('monto_objetivo', $request->monto)
                    ->orWhereNull('monto_objetivo');
            });
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

        $enlacePago->update([
            'usos_actuales' => $enlacePago->usos_actuales + 1,
            'estado_operativo' => 'reservado',
            'asignado_a_pago_id' => $request->input('pago_id'),
            'asignado_a_estudiante_id' => $request->input('estudiante_id'),
            'fecha_asignacion' => now(),
            'actualizado_por' => $request->user()->id,
            'actualizado_en' => now(),
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Enlace asignado correctamente.',
            'data' => $enlacePago,
        ]);
    }
}
