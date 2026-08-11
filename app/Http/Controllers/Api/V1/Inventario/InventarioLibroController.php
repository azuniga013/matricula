<?php

namespace App\Http\Controllers\Api\V1\Inventario;

use App\Helpers\RespuestaError;
use App\Http\Controllers\Controller;
use App\Models\InventarioLibro;
use App\Models\Sucursal;
use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;
use App\Modules\Inventario\CasosUso\AjustarExistencia;
use App\Modules\Inventario\CasosUso\RegistrarInventario;
use App\Modules\Inventario\CasosUso\VenderLibro;
use App\Services\ResolutorAlcanceDatos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, $request->user(), 'inventario_libros');

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

    public function show(Request $request, int $id): JsonResponse
    {
        $inventarioLibro = InventarioLibro::query();
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($inventarioLibro, $request->user(), 'inventario_libros');
        $inventarioLibro = $inventarioLibro->findOrFail($id);

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

        $sucursal = Sucursal::query();
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($sucursal, $request->user(), 'sucursales');
        $sucursal->findOrFail((int) $datos['sucursal_id']);

        $resultado = app(RegistrarInventario::class)->ejecutar($datos, ContextoUsuario::desdeRequest());

        return $this->responder($resultado);
    }

    public function ajustar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'inventario_libro_id' => 'required|exists:inventario_libros,id',
            'cantidad' => 'required|integer',
            'motivo' => 'required|string|max:500',
        ]);

        $inventario = InventarioLibro::query();
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($inventario, $request->user(), 'inventario_libros');
        $inventario->findOrFail((int) $datos['inventario_libro_id']);

        $resultado = app(AjustarExistencia::class)->ejecutar($datos, ContextoUsuario::desdeRequest());

        return $this->responder($resultado);
    }

    public function vender(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'inventario_libro_id' => 'required|exists:inventario_libros,id',
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'nullable|string|max:500',
            'pago_id' => 'nullable|exists:pagos,id',
        ]);

        $inventario = InventarioLibro::query();
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($inventario, $request->user(), 'inventario_libros');
        $inventario->findOrFail((int) $datos['inventario_libro_id']);

        $resultado = app(VenderLibro::class)->ejecutar($datos, ContextoUsuario::desdeRequest());

        return $this->responder($resultado);
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
        ]);
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($inventario, $request->user(), 'inventario_libros');
        $inventario = $inventario->findOrFail((int) $request->input('inventario_libro_id'));

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $inventario,
        ]);
    }

    private function responder(ResultadoCasoUso $resultado): JsonResponse
    {
        if (! $resultado->ok()) {
            return RespuestaError::make(
                $resultado->codigoError() ?? 'ERROR',
                $resultado->codigo(),
                $resultado->mensaje()
            )->response(request());
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => $resultado->codigo(),
            'mensaje' => $resultado->mensaje(),
            'data' => $resultado->data()['venta'] ?? $resultado->data()['inventario'] ?? $resultado->data() ?? null,
        ], $resultado->codigo() === 201 ? 201 : 200);
    }
}
