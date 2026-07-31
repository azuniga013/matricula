<?php

namespace App\Http\Controllers\Api\V1\Caja;

use App\Http\Controllers\Controller;
use App\Models\{SesionCaja, DetalleCierreCaja, Pago, ConceptoPago, MetodoPago};
use App\Services\ServicioNomenclatura;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SesionCajaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'estado' => 'nullable|in:abierta,cerrada',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = SesionCaja::with([
            'sucursal:id,codigo,nombre',
            'cajero:id,name',
        ]);

        if ($request->filled('sucursal_id')) {
            $query->where('sesiones_caja.sucursal_id', $request->sucursal_id);
        }
        if ($request->filled('estado')) {
            $query->where('sesiones_caja.estado', $request->estado);
        }

        $sesiones = $query->orderByDesc('sesiones_caja.id')->paginate($request->get('per_page', 25));

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $sesiones,
        ]);
    }

    public function abrir(Request $request): JsonResponse
    {
        $request->validate([
            'sucursal_id' => 'required|exists:sucursales,id',
            'monto_inicial' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $sesionAbierta = SesionCaja::where('sucursal_id', $request->sucursal_id)
            ->where('usuario_cajero_id', auth()->id())
            ->where('estado', 'abierta')
            ->exists();

        if ($sesionAbierta) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'Ya tiene una sesión de caja abierta en esta sucursal',
            ], 422);
        }

        $codigoSesion = app(ServicioNomenclatura::class)->generarCodigo(
            entidad: 'sesiones_caja_' . date('Y'),
            formato: 'SCA-{ANIO}-{SECUENCIA:6}',
            longitudSecuencia: 6,
            anio: date('Y'),
        );

        $sesion = SesionCaja::create([
            'codigo' => $codigoSesion['codigo'],
            'sucursal_id' => $request->sucursal_id,
            'usuario_cajero_id' => auth()->id(),
            'monto_inicial' => $request->monto_inicial,
            'estado' => 'abierta',
            'fecha_apertura' => now(),
            'observaciones' => $request->observaciones,
            'creado_por' => auth()->id(),
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 201,
            'mensaje' => 'Sesión de caja abierta',
            'data' => $sesion,
        ], 201);
    }

    public function cerrar(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'monto_final' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $resultado = DB::transaction(function () use ($id, $request) {
            $sesion = SesionCaja::lockForUpdate()->findOrFail($id);

            if ($sesion->estado !== 'abierta') {
                return ['ok' => false, 'codigo' => 422, 'mensaje' => 'La sesión ya está cerrada'];
            }

            if ($sesion->usuario_cajero_id !== auth()->id()) {
                return ['ok' => false, 'codigo' => 403, 'mensaje' => 'Solo el cajero que abrió la sesión puede cerrarla'];
            }

            $fechaCierre = $sesion->fecha_cierre ?? now();

            $pagos = Pago::where('estado', 'aprobado')
                ->where('sucursal_id', $sesion->sucursal_id)
                ->where(function ($query) use ($sesion, $fechaCierre) {
                    $query->where('sesion_caja_id', $sesion->id)
                        ->orWhereDate('fecha_aprobacion', $fechaCierre->toDateString());
                })
                ->get();

            $totalesPorConceptoMetodo = $pagos->groupBy(function ($pago) {
                return $pago->concepto_pago_id . '_' . $pago->metodo_pago_id;
            })->map(function ($grupo, $key) {
                $primero = $grupo->first();
                return [
                    'concepto_pago_id' => $primero->concepto_pago_id,
                    'metodo_pago_id' => $primero->metodo_pago_id,
                    'cantidad_transacciones' => $grupo->count(),
                    'monto_total' => $grupo->sum('monto'),
                ];
            });

            foreach ($totalesPorConceptoMetodo as $detalle) {
                DetalleCierreCaja::create([
                    'sesion_caja_id' => $sesion->id,
                    'concepto_pago_id' => $detalle['concepto_pago_id'],
                    'metodo_pago_id' => $detalle['metodo_pago_id'],
                    'cantidad_transacciones' => $detalle['cantidad_transacciones'],
                    'monto_total' => $detalle['monto_total'],
                    'estado' => 'activo',
                    'creado_por' => auth()->id(),
                ]);
            }

            $sesion->update([
                'estado' => 'cerrada',
                'monto_final' => $request->monto_final,
                'fecha_cierre' => now(),
                'observaciones' => $request->observaciones,
                'cerrado_por' => auth()->id(),
                'actualizado_por' => auth()->id(),
            ]);

            return ['ok' => true, 'sesion' => $sesion->fresh('detalles')];
        });

        if (!$resultado['ok']) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => $resultado['codigo'],
                'mensaje' => $resultado['mensaje'],
            ], $resultado['codigo']);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'Sesión de caja cerrada con éxito',
            'data' => $resultado['sesion'],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $sesion = SesionCaja::with([
            'sucursal:id,codigo,nombre',
            'cajero:id,name',
            'pagos:id,sesion_caja_id,monto,estado',
            'detalles:concepto_pago_id,metodo_pago_id,cantidad_transacciones,monto_total',
            'detalles.conceptoPago:id,codigo,nombre',
            'detalles.metodoPago:id,codigo,nombre',
        ])->findOrFail($id);

        $sesion->setAttribute('total_ingresos', $sesion->detalles?->sum('monto_total') ?? $sesion->pagos?->sum('monto') ?? 0);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $sesion,
        ]);
    }
}
