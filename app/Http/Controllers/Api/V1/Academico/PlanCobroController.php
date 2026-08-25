<?php

namespace App\Http\Controllers\Api\V1\Academico;

use App\Http\Controllers\Controller;
use App\Models\DetallePlanCobro;
use App\Models\PlanCobro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanCobroController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PlanCobro::with([
            'detalles' => fn ($q) => $q->with('conceptoPago:id,codigo,nombre')->activos()->orderBy('numero_cuota'),
        ]);

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

        $planes = $query->orderBy('codigo')->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $planes,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => 'required|string|max:50|unique:planes_cobro,codigo',
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.concepto_pago_id' => 'required|integer|exists:conceptos_pago,id',
            'detalles.*.numero_cuota' => 'required|integer|min:0|distinct',
            'detalles.*.nombre_cargo' => 'required|string|max:100',
            'detalles.*.monto' => 'required|numeric|min:0',
            'detalles.*.dias_vencimiento' => 'nullable|integer|min:0',
        ]);

        $userId = $request->user()->id;
        $datos['creado_por'] = $userId;
        $ahora = now();

        $plan = PlanCobro::create([
            'codigo' => $datos['codigo'],
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'estado' => 'activo',
            'creado_por' => $userId,
            'actualizado_por' => $userId,
            'creado_en' => $ahora,
            'actualizado_en' => $ahora,
        ]);

        foreach ($datos['detalles'] as $detalle) {
            DetallePlanCobro::create([
                'plan_cobro_id' => $plan->id,
                'concepto_pago_id' => $detalle['concepto_pago_id'],
                'numero_cuota' => $detalle['numero_cuota'],
                'nombre_cargo' => $detalle['nombre_cargo'],
                'monto' => $detalle['monto'],
                'dias_vencimiento' => $detalle['dias_vencimiento'] ?? 0,
                'estado' => 'activo',
                'creado_por' => $userId,
                'actualizado_por' => $userId,
                'creado_en' => $ahora,
                'actualizado_en' => $ahora,
            ]);
        }

        $plan->load(['detalles' => fn ($q) => $q->with('conceptoPago:id,codigo,nombre')]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Plan de cobro creado exitosamente',
            'data' => $plan,
        ], 201);
    }

    public function show(PlanCobro $planCobro): JsonResponse
    {
        $planCobro->load([
            'detalles' => fn ($q) => $q->with('conceptoPago:id,codigo,nombre')->activos()->orderBy('numero_cuota'),
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $planCobro,
        ]);
    }

    public function update(Request $request, PlanCobro $planCobro): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'estado' => 'sometimes|string|in:activo,inactivo',
            'detalles' => 'sometimes|array|min:1',
            'detalles.*.id' => 'nullable|integer|exists:detalle_plan_cobro,id',
            'detalles.*.concepto_pago_id' => 'required|integer|exists:conceptos_pago,id',
            'detalles.*.numero_cuota' => 'required|integer|min:0|distinct',
            'detalles.*.nombre_cargo' => 'required|string|max:100',
            'detalles.*.monto' => 'required|numeric|min:0',
            'detalles.*.dias_vencimiento' => 'nullable|integer|min:0',
            'detalles.*.eliminar' => 'sometimes|boolean',
        ]);

        $userId = $request->user()->id;
        $datos['actualizado_por'] = $userId;
        $ahora = now();

        $planCobro->update([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'estado' => $datos['estado'] ?? $planCobro->estado,
            'actualizado_por' => $userId,
            'actualizado_en' => $ahora,
        ]);

        if ($request->has('detalles')) {
            $existingIds = [];

            foreach ($datos['detalles'] as $detalle) {
                if (!empty($detalle['eliminar'])) {
                    DetallePlanCobro::where('id', $detalle['id'])->update([
                        'estado' => 'inactivo',
                        'actualizado_por' => $userId,
                        'actualizado_en' => $ahora,
                    ]);
                    continue;
                }

                $dataDetalle = [
                    'concepto_pago_id' => $detalle['concepto_pago_id'],
                    'numero_cuota' => $detalle['numero_cuota'],
                    'nombre_cargo' => $detalle['nombre_cargo'],
                    'monto' => $detalle['monto'],
                    'dias_vencimiento' => $detalle['dias_vencimiento'] ?? 0,
                    'actualizado_por' => $userId,
                    'actualizado_en' => $ahora,
                ];

                if (!empty($detalle['id'])) {
                    DetallePlanCobro::where('id', $detalle['id'])->update($dataDetalle);
                    $existingIds[] = $detalle['id'];
                } else {
                    $dataDetalle['plan_cobro_id'] = $planCobro->id;
                    $dataDetalle['estado'] = 'activo';
                    $dataDetalle['creado_por'] = $userId;
                    $dataDetalle['creado_en'] = $ahora;
                    $nuevo = DetallePlanCobro::create($dataDetalle);
                    $existingIds[] = $nuevo->id;
                }
            }
        }

        $planCobro->load(['detalles' => fn ($q) => $q->with('conceptoPago:id,codigo,nombre')->activos()->orderBy('numero_cuota')]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Plan de cobro actualizado exitosamente',
            'data' => $planCobro,
        ]);
    }
}
