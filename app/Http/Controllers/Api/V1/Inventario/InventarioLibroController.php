<?php

namespace App\Http\Controllers\Api\V1\Inventario;

use App\Http\Controllers\Controller;
use App\Models\InventarioLibro;
use App\Models\MovimientoInventarioLibro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioLibroController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'libro_id' => 'nullable|exists:libros,id',
            'stock_bajo' => 'nullable|boolean',
        ]);

        $query = InventarioLibro::with([
            'libro:id,codigo,titulo,precio_venta',
            'sucursal:id,codigo,nombre',
        ]);

        if ($request->filled('sucursal_id')) {
            $query->where('inventario_libros.sucursal_id', $request->sucursal_id);
        }

        if ($request->filled('libro_id')) {
            $query->where('inventario_libros.libro_id', $request->libro_id);
        }

        if ($request->boolean('stock_bajo')) {
            $query->stockBajo();
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $query->orderBy('inventario_libros.sucursal_id')->orderBy('inventario_libros.existencia_actual')->get(),
        ]);
    }

    public function show(InventarioLibro $inventarioLibro): JsonResponse
    {
        $inventarioLibro->load([
            'libro:id,codigo,titulo,precio_venta',
            'sucursal:id,codigo,nombre',
            'movimientos' => function ($q) {
                $q->orderByDesc('movimientos_inventario_libros.id')->limit(50);
            },
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $inventarioLibro,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'libro_id' => 'required|exists:libros,id',
            'sucursal_id' => 'required|exists:sucursales,id',
            'existencia_actual' => 'required|integer|min:0',
            'existencia_minima' => 'nullable|integer|min:0',
        ]);

        $existe = InventarioLibro::where('libro_id', $datos['libro_id'])
            ->where('sucursal_id', $datos['sucursal_id'])
            ->exists();

        if ($existe) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'El libro ya tiene inventario registrado en esta sucursal',
            ], 422);
        }

        $datos['existencia_minima'] = $datos['existencia_minima'] ?? 0;
        $datos['creado_por'] = $request->user()->id;

        /** @var InventarioLibro $inventario */
        $inventario = InventarioLibro::create($datos);

        if ($inventario->existencia_actual > 0) {
            MovimientoInventarioLibro::create([
                'inventario_libro_id' => $inventario->id,
                'tipo_movimiento' => 'entrada',
                'cantidad' => $inventario->existencia_actual,
                'existencia_antes' => 0,
                'existencia_despues' => $inventario->existencia_actual,
                'motivo' => 'Registro inicial de inventario',
                'creado_por' => $request->user()->id,
            ]);
        }

        $inventario->load('libro:id,codigo,titulo', 'sucursal:id,codigo,nombre');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Inventario registrado exitosamente',
            'data' => $inventario,
        ], 201);
    }

    public function ajustar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'inventario_libro_id' => 'required|exists:inventario_libros,id',
            'cantidad' => 'required|integer',
            'motivo' => 'required|string|max:500',
        ]);

        return DB::transaction(function () use ($datos, $request) {
            /** @var InventarioLibro $inventario */
            $inventario = InventarioLibro::lockForUpdate()->findOrFail($datos['inventario_libro_id']);

            $nuevaExistencia = $inventario->existencia_actual + $datos['cantidad'];

            if ($nuevaExistencia < 0) {
                return response()->json([
                    'resultado' => 'R',
                    'codigo' => 422,
                    'mensaje' => 'La existencia no puede ser negativa',
                ], 422);
            }

            $tipo = $datos['cantidad'] >= 0 ? 'entrada' : 'salida';

            $inventario->update([
                'existencia_actual' => $nuevaExistencia,
                'actualizado_por' => $request->user()->id,
            ]);

            MovimientoInventarioLibro::create([
                'inventario_libro_id' => $inventario->id,
                'tipo_movimiento' => $tipo,
                'cantidad' => abs($datos['cantidad']),
                'existencia_antes' => $inventario->existencia_actual - $datos['cantidad'],
                'existencia_despues' => $nuevaExistencia,
                'motivo' => $datos['motivo'],
                'creado_por' => $request->user()->id,
            ]);

            $inventario->load('libro:id,codigo,titulo', 'sucursal:id,codigo,nombre');

            return response()->json([
                'resultado' => 'A',
                'codigo' => 0,
                'mensaje' => $tipo === 'entrada' ? 'Entrada registrada' : 'Salida registrada',
                'data' => $inventario,
            ]);
        });
    }

    public function vender(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'inventario_libro_id' => 'required|exists:inventario_libros,id',
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'nullable|string|max:500',
            'pago_id' => 'nullable|exists:pagos,id',
        ]);

        return DB::transaction(function () use ($datos, $request) {
            /** @var InventarioLibro $inventario */
            $inventario = InventarioLibro::lockForUpdate()->findOrFail($datos['inventario_libro_id']);

            if ($inventario->existencia_actual < $datos['cantidad']) {
                return response()->json([
                    'resultado' => 'R',
                    'codigo' => 422,
                    'mensaje' => 'No hay suficiente existencia. Disponible: ' . $inventario->existencia_actual,
                ], 422);
            }

            $nuevaExistencia = $inventario->existencia_actual - $datos['cantidad'];

            $inventario->update([
                'existencia_actual' => $nuevaExistencia,
                'actualizado_por' => $request->user()->id,
            ]);

            $movData = [
                'inventario_libro_id' => $inventario->id,
                'tipo_movimiento' => 'salida',
                'cantidad' => $datos['cantidad'],
                'existencia_antes' => $inventario->existencia_actual + $datos['cantidad'],
                'existencia_despues' => $nuevaExistencia,
                'motivo' => $datos['motivo'] ?? 'Venta de libro',
                'creado_por' => $request->user()->id,
            ];

            if (!empty($datos['pago_id'])) {
                $movData['referencia_type'] = \App\Models\Pago::class;
                $movData['referencia_id'] = $datos['pago_id'];
            }

            $movimiento = MovimientoInventarioLibro::create($movData);

            $inventario->load('libro:id,codigo,titulo,precio_venta', 'sucursal:id,codigo,nombre');

            return response()->json([
                'resultado' => 'A',
                'codigo' => 0,
                'mensaje' => 'Venta registrada',
                'data' => [
                    'inventario' => $inventario,
                    'movimiento' => $movimiento,
                    'total_venta' => $inventario->libro->precio_venta * $datos['cantidad'],
                ],
            ]);
        });
    }

    public function kardex(Request $request): JsonResponse
    {
        $request->validate([
            'inventario_libro_id' => 'required|exists:inventario_libros,id',
        ]);

        $inventario = InventarioLibro::with([
            'libro:id,codigo,titulo',
            'sucursal:id,codigo,nombre',
            'movimientos' => function ($q) {
                $q->orderByDesc('movimientos_inventario_libros.id');
            },
        ])->findOrFail($request->inventario_libro_id);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $inventario,
        ]);
    }
}
