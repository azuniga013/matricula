<?php

namespace App\Http\Controllers\Api\V1\Caja;

use App\Http\Controllers\Controller;
use App\Models\SesionCaja;
use App\Models\Sucursal;
use App\Modules\Caja\CasosUso\AbrirSesionCaja;
use App\Modules\Caja\CasosUso\CerrarSesionCaja;
use App\Modules\Comun\ContextoUsuario;
use App\Services\ResolutorAlcanceDatos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, $request->user(), 'sesiones_caja');

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

        $sucursal = Sucursal::query();
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($sucursal, $request->user(), 'sucursales');
        $sucursal->findOrFail((int) $request->input('sucursal_id'));

        $resultado = app(AbrirSesionCaja::class)->ejecutar(
            $request->all(),
            ContextoUsuario::desdeRequest(),
        );

        if (! $resultado->ok()) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => $resultado->codigo(),
                'codigo_error' => $resultado->codigoError(),
                'mensaje' => $resultado->mensaje(),
            ], $resultado->codigo());
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 201,
            'mensaje' => $resultado->mensaje(),
            'data' => $resultado->data()['sesion'],
        ], 201);
    }

    public function cerrar(int $id, Request $request): JsonResponse
    {
        $query = SesionCaja::query();
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, $request->user(), 'sesiones_caja');
        $query->findOrFail($id);

        $request->validate([
            'monto_final' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $resultado = app(CerrarSesionCaja::class)->ejecutar(
            $id,
            $request->all(),
            ContextoUsuario::desdeRequest(),
        );

        if (! $resultado->ok()) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => $resultado->codigo(),
                'codigo_error' => $resultado->codigoError(),
                'mensaje' => $resultado->mensaje(),
            ], $resultado->codigo());
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => $resultado->mensaje(),
            'data' => $resultado->data()['sesion'],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $sesion = SesionCaja::with([
            'sucursal:id,codigo,nombre',
            'cajero:id,name',
            'pagos:id,sesion_caja_id,monto,estado',
            'detalles:concepto_pago_id,metodo_pago_id,cantidad_transacciones,monto_total',
            'detalles.conceptoPago:id,codigo,nombre',
            'detalles.metodoPago:id,codigo,nombre',
        ]);
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($sesion, $request->user(), 'sesiones_caja');
        $sesion = $sesion->findOrFail($id);

        $sesion->setAttribute('total_ingresos', $sesion->detalles?->sum('monto_total') ?? $sesion->pagos?->sum('monto') ?? 0);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $sesion,
        ]);
    }
}
