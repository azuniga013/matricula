<?php

namespace App\Http\Controllers\Api\V1\Caja;

use App\Http\Controllers\Controller;
use App\Models\{SesionCaja, Pago};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CierreCajaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
            'usuario_cajero_id' => 'nullable|exists:users,id',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = SesionCaja::with([
            'sucursal:id,codigo,nombre',
            'cajero:id,name',
            'detalles.conceptoPago:id,codigo,nombre',
            'detalles.metodoPago:id,codigo,nombre',
        ])
        ->where('sesiones_caja.estado', 'cerrada')
        ->whereBetween('sesiones_caja.fecha_cierre', [
            $request->fecha_desde . ' 00:00:00',
            $request->fecha_hasta . ' 23:59:59',
        ]);

        if ($request->filled('sucursal_id')) {
            $query->where('sesiones_caja.sucursal_id', $request->sucursal_id);
        }
        if ($request->filled('usuario_cajero_id')) {
            $query->where('sesiones_caja.usuario_cajero_id', $request->usuario_cajero_id);
        }

        $sesiones = $query->orderByDesc('sesiones_caja.fecha_cierre')
            ->paginate($request->get('per_page', 25));

        $sesiones->getCollection()->transform(function ($sesion) {
            $totalIngresos = $sesion->detalles?->sum('monto_total') ?? 0;
            if (!$totalIngresos && method_exists($sesion, 'pagos') && $sesion->pagos?->isNotEmpty()) {
                $totalIngresos = $sesion->pagos->sum('monto');
            }

            $sesion->setAttribute('total_ingresos', $totalIngresos);
            return $sesion;
        });

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $sesiones,
        ]);
    }

    public function resumen(Request $request): JsonResponse
    {
        $request->validate([
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'fecha' => 'required|date',
        ]);

        $pagosQuery = Pago::where('pagos.estado', 'aprobado')
            ->whereDate('pagos.fecha_aprobacion', $request->fecha);

        if ($request->filled('sucursal_id')) {
            $pagosQuery->where('pagos.sucursal_id', $request->sucursal_id);
        }

        $pagos = $pagosQuery->with(['conceptoPago:id,codigo,nombre', 'metodoPago:id,codigo,nombre'])->get();

        $resumen = [
            'fecha' => $request->fecha,
            'total_ingresos' => $pagos->sum('monto'),
            'cantidad_pagos' => $pagos->count(),
            'por_concepto' => $pagos->groupBy('concepto_pago_id')->map(function ($grupo) {
                return [
                    'concepto' => $grupo->first()->conceptoPago->nombre,
                    'cantidad' => $grupo->count(),
                    'total' => $grupo->sum('monto'),
                ];
            })->values(),
            'por_metodo' => $pagos->groupBy('metodo_pago_id')->map(function ($grupo) {
                return [
                    'metodo' => $grupo->first()->metodoPago->nombre,
                    'cantidad' => $grupo->count(),
                    'total' => $grupo->sum('monto'),
                ];
            })->values(),
        ];

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $resumen,
        ]);
    }
}
