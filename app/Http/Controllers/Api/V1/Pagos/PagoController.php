<?php

namespace App\Http\Controllers\Api\V1\Pagos;

use App\Http\Controllers\Controller;
use App\Models\ConceptoPago;
use App\Models\Estudiante;
use App\Models\ObligacionPagoEstudiante;
use App\Models\Pago;
use App\Modules\Pagos\CasosUso\ActualizarLinkPago;
use App\Modules\Pagos\CasosUso\AprobarPago;
use App\Modules\Pagos\CasosUso\EliminarPagoTotal;
use App\Modules\Pagos\CasosUso\RechazarPago;
use App\Modules\Pagos\CasosUso\RegistrarPago;
use App\Modules\Pagos\CasosUso\SubirComprobantePago;
use App\Modules\Comun\ContextoUsuario;
use App\Services\ResolutorAlcanceDatos;
use App\Services\ResolutorFlujoMatricula;
use App\Services\ServicioNomenclatura;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'estado' => 'nullable|in:pendiente,en_revision,solicita_link,esperando_respuesta,aprobado,rechazado,cancelado',
            'clasificar' => 'nullable|boolean',
            'concepto_pago_id' => 'nullable|exists:conceptos_pago,id',
            'estudiante_id' => 'nullable|exists:estudiantes,id',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = Pago::with([
            'estudiante:id,codigo,nombre,apellido',
            'conceptoPago:id,codigo,nombre',
            'metodoPago:id,codigo,nombre',
            'cuentaBancaria:id,codigo,nombre,banco,numero_cuenta,tipo_cuenta',
            'sucursal:id,codigo,nombre',
            'comprobantes:id,pago_id,nombre_archivo,ruta_archivo,tipo_archivo,tamano_bytes,creado_en',
            'aprobadoPor:id,name',
            'rechazadoPor:id,name',
            'aplicaciones:id,pago_id,obligacion_pago_estudiante_id,monto_aplicado,estado',
            'aplicaciones.obligacion:id,concepto_pago_id,numero_cuota,nombre_cargo,monto,monto_pagado,estado',
            'aplicaciones.obligacion.conceptoPago:id,codigo,nombre',
        ]);
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, $request->user(), 'pagos');

        if ($request->filled('sucursal_id')) {
            $query->where('pagos.sucursal_id', $request->sucursal_id);
        }
        if ($request->filled('estado')) {
            if ($request->estado === 'solicita_link') {
                $query->whereIn('pagos.estado', ['solicita_link', 'esperando_respuesta']);
            } else {
                $query->where('pagos.estado', $request->estado);
            }
        }
        if ($request->filled('concepto_pago_id')) {
            $query->where('pagos.concepto_pago_id', $request->concepto_pago_id);
        }
        if ($request->filled('estudiante_id')) {
            $query->where('pagos.estudiante_id', $request->estudiante_id);
        }

        if ($request->boolean('clasificar')) {
            $pagos = $query->orderByDesc('pagos.id')->get();

            $clasificados = [
                'pagosPendientes' => $pagos->where('estado', 'pendiente')->values(),
                'pagosEnRevision' => $pagos->where('estado', 'en_revision')->values(),
                'pagosSolicitaLink' => $pagos->whereIn('estado', ['solicita_link', 'esperando_respuesta'])->values(),
                'pagosAprobados' => $pagos->where('estado', 'aprobado')->values(),
                'pagosRechazados' => $pagos->where('estado', 'rechazado')->values(),
            ];

            return response()->json([
                'resultado' => 'A',
                'codigo' => 200,
                'mensaje' => 'OK',
                'data' => [
                    ...$clasificados,
                    'resumen' => [
                        'pendientes' => $clasificados['pagosPendientes']->count(),
                        'en_revision' => $clasificados['pagosEnRevision']->count(),
                        'solicita_link' => $clasificados['pagosSolicitaLink']->count(),
                        'aprobados' => $clasificados['pagosAprobados']->count(),
                        'rechazados' => $clasificados['pagosRechazados']->count(),
                    ],
                ],
            ]);
        }

        $pagos = $query->orderByDesc('pagos.id')->paginate($request->get('per_page', 25));

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $pagos,
        ]);
    }

    public function obligacionesEstudiante(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'concepto_pago_id' => 'required|exists:conceptos_pago,id',
            'metodo_pago_id' => 'nullable|exists:metodos_pago,id',
        ]);

        $estudiante = Estudiante::query();
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($estudiante, $request->user(), 'estudiantes');
        $estudiante->findOrFail($datos['estudiante_id']);

        $concepto = ConceptoPago::findOrFail($datos['concepto_pago_id']);
        $configFlujo = app(ResolutorFlujoMatricula::class)->resolver('portal_administrativo', $concepto->id, $datos['metodo_pago_id'] ?? null);

        if (! in_array($concepto->codigo, ['MAT', 'CUO'], true) || empty($configFlujo['habilita_seleccion_obligaciones'])) {
            return response()->json([
                'resultado' => 'A',
                'codigo' => 0,
                'mensaje' => 'No hay obligaciones seleccionables para este concepto',
                'data' => ['habilita_seleccion_obligaciones' => false, 'obligaciones' => []],
            ]);
        }

        $obligaciones = ObligacionPagoEstudiante::with(['matricula:id,codigo', 'conceptoPago:id,codigo,nombre'])
            ->whereHas('matricula', fn ($q) => $q->where('estudiante_id', $datos['estudiante_id']))
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->where('concepto_pago_id', $concepto->id)
            ->orderBy('matricula_id')
            ->orderBy('numero_cuota')
            ->get()
            ->map(fn ($obligacion) => [
                'id' => $obligacion->id,
                'matricula_id' => $obligacion->matricula_id,
                'matricula_codigo' => $obligacion->matricula?->codigo,
                'concepto' => $obligacion->conceptoPago?->codigo,
                'numero_cuota' => $obligacion->numero_cuota,
                'nombre_cargo' => $obligacion->nombre_cargo,
                'monto' => (float) $obligacion->monto,
                'monto_pagado' => (float) $obligacion->monto_pagado,
                'saldo' => $obligacion->saldoPendiente(),
                'fecha_vencimiento' => $obligacion->fecha_vencimiento?->format('d/m/Y'),
            ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => [
                'habilita_seleccion_obligaciones' => true,
                'obligaciones' => $obligaciones,
            ],
        ]);
    }

    public function registrar(Request $request): JsonResponse
    {
        $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'matricula_id' => 'nullable|exists:matriculas,id',
            'concepto_pago_id' => 'required|exists:conceptos_pago,id',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
            'monto' => 'required|numeric|min:0.01',
            'monto_recibido' => 'nullable|numeric|min:0.01',
            'vuelto' => 'nullable|numeric|min:0',
            'fecha_proceso' => 'nullable|date',
            'referencia_externa' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:500',
            'solicitar_link' => 'nullable|boolean',
            'obligaciones' => 'nullable|array',
            'obligaciones.*.obligacion_id' => 'required_with:obligaciones|exists:obligaciones_pago_estudiante,id',
            'obligaciones.*.monto_aplicado' => 'required_with:obligaciones|numeric|min:0.01',
            'inventario_libro_id' => 'nullable|exists:inventario_libros,id',
            'cantidad_libro' => 'required_with:inventario_libro_id|integer|min:1',
            'codigo_recibo' => 'nullable|string|max:50',
        ]);

        $resultado = app(RegistrarPago::class)->ejecutar(
            [...$request->all(), 'solicitar_link' => $request->boolean('solicitar_link')],
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
            'data' => $resultado->data()['pago'],
        ], 201);
    }

    public function actualizarLink(Request $request, int $id): JsonResponse
    {
        $query = Pago::query();
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, $request->user(), 'pagos');
        $query->findOrFail($id);

        $request->validate([
            'link_pago_url' => 'required|string|max:500',
        ]);

        $resultado = app(ActualizarLinkPago::class)->ejecutar($id, $request->input('link_pago_url'), ContextoUsuario::desdeRequest());

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
            'codigo' => 0,
            'mensaje' => $resultado->mensaje(),
            'data' => $resultado->data()['pago'],
        ]);
    }

    public function subirComprobante(Request $request, int $pagoId): JsonResponse
    {
        $query = Pago::query();
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, $request->user(), 'pagos');
        $query->findOrFail($pagoId);

        $request->validate([
            'archivo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $resultado = app(SubirComprobantePago::class)->ejecutar($pagoId, $request->file('archivo'), ContextoUsuario::desdeRequest());

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
            'data' => $resultado->data()['comprobante'],
        ], 201);
    }

    public function aprobar(int $id): JsonResponse
    {
        $query = Pago::query();
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, request()->user(), 'pagos');
        $query->findOrFail($id);

        $resultado = app(AprobarPago::class)->ejecutar($id, ContextoUsuario::desdeRequest());

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
            'data' => $resultado->data(),
        ]);
    }

    public function rechazar(int $id, Request $request): JsonResponse
    {
        $query = Pago::query();
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, $request->user(), 'pagos');
        $query->findOrFail($id);

        $request->validate([
            'motivo_rechazo' => 'required|string|max:500',
        ]);

        $resultado = app(RechazarPago::class)->ejecutar($id, $request->motivo_rechazo, ContextoUsuario::desdeRequest());

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
            'data' => $resultado->data()['pago'],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $pago = Pago::with([
            'estudiante:id,codigo,nombre,apellido',
            'conceptoPago:id,codigo,nombre',
            'metodoPago:id,codigo,nombre',
            'cuentaBancaria:id,codigo,nombre,banco,numero_cuenta,tipo_cuenta',
            'sucursal:id,codigo,nombre',
            'comprobantes:id,nombre_archivo,ruta_archivo,tipo_archivo,estado',
            'aplicaciones:id,pago_id,obligacion_pago_estudiante_id,monto_aplicado,estado',
            'aplicaciones.obligacion:id,concepto_pago_id,numero_cuota,nombre_cargo,monto,monto_pagado,estado',
            'aplicaciones.obligacion.conceptoPago:id,codigo,nombre',
            'reciboCaja:id,codigo,numero_recibo,estado',
        ]);
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($pago, $request->user(), 'pagos');
        $pago = $pago->findOrFail($id);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $pago,
        ]);
    }

    public function eliminarTotal(int $id): JsonResponse
    {
        $query = Pago::query();
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, request()->user(), 'pagos');
        $query->findOrFail($id);

        $resultado = app(EliminarPagoTotal::class)->ejecutar($id);

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
            'data' => $resultado->data()['ok'],
        ]);
    }

    public function siguienteRecibo(): JsonResponse
    {
        $anio = date('Y');
        $entidad = 'recibos_caja_'.$anio;

        $preview = app(ServicioNomenclatura::class)->previewSiguienteCodigo(
            entidad: $entidad,
            formato: 'RC-{ANIO}-{SECUENCIA:6}',
            longitudSecuencia: 6,
            anio: $anio,
        );

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $preview,
        ]);
    }
}
