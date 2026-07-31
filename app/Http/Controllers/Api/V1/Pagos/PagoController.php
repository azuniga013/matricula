<?php

namespace App\Http\Controllers\Api\V1\Pagos;

use App\Http\Controllers\Controller;
use App\Models\{Pago, ComprobantePago, ObligacionPagoEstudiante, AplicacionPago, ReciboCaja, ConceptoPago, Matricula, OfertaAcademica, SesionCaja};
use App\Services\ServicioNomenclatura;
use App\Services\ResolutorFlujoMatricula;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PagoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'estado' => 'nullable|in:pendiente,en_revision,solicita_link,aprobado,rechazado,cancelado',
            'clasificar' => 'nullable|boolean',
            'concepto_pago_id' => 'nullable|exists:conceptos_pago,id',
            'estudiante_id' => 'nullable|exists:estudiantes,id',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = Pago::with([
            'estudiante:id,codigo,nombre,apellido',
            'conceptoPago:id,codigo,nombre',
            'metodoPago:id,codigo,nombre',
            'sucursal:id,codigo,nombre',
            'comprobantes:id,pago_id,nombre_archivo,ruta_archivo,tipo_archivo,tamano_bytes,creado_en',
            'aprobadoPor:id,name',
            'rechazadoPor:id,name',
            'aplicaciones:id,pago_id,obligacion_pago_estudiante_id,monto_aplicado,estado',
            'aplicaciones.obligacion:id,concepto_pago_id,numero_cuota,nombre_cargo,monto,monto_pagado,estado',
            'aplicaciones.obligacion.conceptoPago:id,codigo,nombre',
        ]);

        if ($request->filled('sucursal_id')) {
            $query->where('pagos.sucursal_id', $request->sucursal_id);
        }
        if ($request->filled('estado')) {
            if ($request->estado === 'solicita_link') {
                $query->where('pagos.estado', 'solicita_link');
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
                'pagosSolicitaLink' => $pagos->where('estado', 'solicita_link')->values(),
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

    public function registrar(Request $request): JsonResponse
    {
        $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'matricula_id' => 'nullable|exists:matriculas,id',
            'concepto_pago_id' => 'required|exists:conceptos_pago,id',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'monto' => 'required|numeric|min:0.01',
            'fecha_proceso' => 'nullable|date',
            'referencia_externa' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:500',
            'obligaciones' => 'nullable|array',
            'obligaciones.*.obligacion_id' => 'required_with:obligaciones|exists:obligaciones_pago_estudiante,id',
            'obligaciones.*.monto_aplicado' => 'required_with:obligaciones|numeric|min:0.01',
            'inventario_libro_id' => 'nullable|exists:inventario_libros,id',
            'cantidad_libro' => 'required_with:inventario_libro_id|integer|min:1',
            'codigo_recibo' => 'nullable|string|max:50',
        ]);

        $metodoPagoId = $request->metodo_pago_id ? (int) $request->metodo_pago_id : null;
        $metodo = $metodoPagoId ? \App\Models\MetodoPago::find($metodoPagoId) : null;
        $solicitaLink = $request->boolean('solicitar_link') || ($metodo?->permite_link_pago ?? false);

        $resultado = DB::transaction(function () use ($request, $metodo, $solicitaLink) {
            $estudiante = \App\Models\Estudiante::findOrFail($request->estudiante_id);
            $concepto = ConceptoPago::findOrFail($request->concepto_pago_id);
            $fechaProceso = Carbon::parse($request->fecha_proceso ?? now());
            $configFlujo = app(ResolutorFlujoMatricula::class)->resolver('portal_administrativo', $concepto->id, $request->metodo_pago_id ? (int) $request->metodo_pago_id : null);

            $resultadoCodigo = app(ServicioNomenclatura::class)->generarCodigo(
                entidad: 'pagos_' . date('Y'),
                formato: 'PAG-{ANIO}-{SECUENCIA:6}',
                longitudSecuencia: 6,
                anio: date('Y'),
            );

            $pago = Pago::create([
                'codigo' => $resultadoCodigo['codigo'],
                'estudiante_id' => $estudiante->id,
                'matricula_id' => $request->matricula_id,
                'concepto_pago_id' => $concepto->id,
                'metodo_pago_id' => $request->metodo_pago_id,
                'sucursal_id' => $estudiante->sucursal_id,
                'monto' => $request->monto,
                'estado' => $solicitaLink ? 'solicita_link' : 'aprobado',
                'referencia_externa' => $request->referencia_externa,
                'observaciones' => $request->observaciones,
                'aprobado_por' => $solicitaLink ? null : auth()->id(),
                'fecha_aprobacion' => $solicitaLink ? null : $fechaProceso,
                'creado_por' => auth()->id(),
                'creado_en' => $fechaProceso,
            ]);

            $sesionCaja = \App\Models\SesionCaja::where('sucursal_id', $pago->sucursal_id)
                ->where('usuario_cajero_id', auth()->id())
                ->where('estado', 'abierta')
                ->latest('id')
                ->first();

            if ($sesionCaja) {
                $pago->update([
                    'sesion_caja_id' => $sesionCaja->id,
                    'actualizado_por' => auth()->id(),
                ]);
            }

            if ($request->filled('inventario_libro_id') && $concepto->codigo === 'VLI') {
                $inventario = \App\Models\InventarioLibro::lockForUpdate()->findOrFail($request->inventario_libro_id);
                if ($inventario->existencia_actual < $request->cantidad_libro) {
                    throw new \RuntimeException('No hay suficiente existencia. Disponible: ' . $inventario->existencia_actual);
                }
                $nuevaExistencia = $inventario->existencia_actual - $request->cantidad_libro;
                $inventario->update([
                    'existencia_actual' => $nuevaExistencia,
                    'actualizado_por' => auth()->id(),
                ]);
                \App\Models\MovimientoInventarioLibro::create([
                    'inventario_libro_id' => $inventario->id,
                    'tipo_movimiento' => 'salida',
                    'cantidad' => $request->cantidad_libro,
                    'existencia_antes' => $inventario->existencia_actual,
                    'existencia_despues' => $nuevaExistencia,
                    'motivo' => 'Venta de libro - Pago ' . $pago->codigo,
                    'referencia_type' => Pago::class,
                    'referencia_id' => $pago->id,
                    'creado_por' => auth()->id(),
                ]);
            }

            $esSolicitudLink = $solicitaLink;

            if ($pago->matricula_id) {
                $matricula = \App\Models\Matricula::lockForUpdate()->find($pago->matricula_id);
                if ($matricula && !$esSolicitudLink && in_array($matricula->estado, ['reservada', 'en_revision'])) {
                    $matricula->update([
                        'estado' => 'matriculado',
                        'fecha_confirmacion' => now(),
                        'actualizado_por' => auth()->id(),
                    ]);

                    $oferta = \App\Models\OfertaAcademica::lockForUpdate()->find($matricula->oferta_academica_id);
                    if ($oferta && $oferta->cupos_reservados > 0) {
                        $oferta->decrement('cupos_reservados');
                        $oferta->increment('cupos_matriculados');
                        if ($oferta->cuposDisponibles() <= 0) {
                            $oferta->update(['estado' => 'lleno']);
                        }
                    }
                }

                $obligaciones = ObligacionPagoEstudiante::where('matricula_id', $pago->matricula_id)
                    ->where('estado', 'pendiente')
                    ->orderBy('numero_cuota')
                    ->get();

                $montoRestante = $pago->monto;

                foreach ($obligaciones as $obligacion) {
                    if ($montoRestante <= 0) break;

                    $saldo = $obligacion->monto - $obligacion->monto_pagado;
                    $montoAplicar = min($montoRestante, $saldo);

                    $obligacion->update([
                        'monto_pagado' => $obligacion->monto_pagado + $montoAplicar,
                        'estado' => ($obligacion->monto_pagado + $montoAplicar) >= $obligacion->monto ? 'pagado' : 'parcial',
                    ]);

                    AplicacionPago::create([
                        'pago_id' => $pago->id,
                        'obligacion_pago_estudiante_id' => $obligacion->id,
                        'estudiante_id' => $pago->estudiante_id,
                        'monto_aplicado' => $montoAplicar,
                        'estado' => 'activo',
                        'creado_por' => auth()->id(),
                    ]);

                    $montoRestante -= $montoAplicar;
                }
            }

            $recibo = $esSolicitudLink || empty($configFlujo['habilita_generacion_recibo']) ? null : $this->generarRecibo($pago);

            return ['pago' => $pago, 'recibo' => $recibo];
        });

        if (!empty($resultado['ok']) && $resultado['ok'] === false) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => $resultado['codigo'] ?? 422,
                'mensaje' => $resultado['mensaje'] ?? 'No se pudo registrar el pago',
            ], $resultado['codigo'] ?? 422);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 201,
            'mensaje' => $solicitaLink ? 'Pago registrado en solicitud de link' : 'Pago registrado y aprobado',
            'data' => $resultado['pago'],
        ], 201);
    }

    public function actualizarLink(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'link_pago_url' => 'required|string|max:500',
        ]);

        $pago = Pago::findOrFail($id);
        if ($pago->estado !== 'solicita_link') {
            return response()->json(['resultado' => 'R', 'codigo' => 422, 'mensaje' => 'El pago no está en solicitud de link'], 422);
        }

        $link = trim($request->input('link_pago_url'));
        if (!preg_match('/^https?:\/\//i', $link)) {
            $link = 'https://' . $link;
        }

        if (!filter_var($link, FILTER_VALIDATE_URL)) {
            return response()->json(['resultado' => 'R', 'codigo' => 422, 'mensaje' => 'El link de pago no tiene un formato válido'], 422);
        }

        $datos = [
            'link_pago_url' => $link,
            'link_generado_por' => auth()->id(),
            'link_generado_en' => now(),
            'estado' => 'solicita_link',
            'actualizado_por' => auth()->id(),
        ];
        if (Schema::hasColumn('pagos', 'link_pago_estado')) {
            $datos['link_pago_estado'] = 'enviado';
        }

        $pago->update($datos);

        return response()->json(['resultado' => 'A', 'codigo' => 0, 'mensaje' => 'Link guardado correctamente', 'data' => $pago->fresh()]);
    }

    public function subirComprobante(Request $request, int $pagoId): JsonResponse
    {
        $request->validate([
            'archivo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $pago = Pago::findOrFail($pagoId);
        $configFlujo = app(ResolutorFlujoMatricula::class)->resolver('portal_administrativo', $pago->concepto_pago_id, $pago->metodo_pago_id);

        if (empty($configFlujo['habilita_carga_comprobante'])) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'La carga de comprobantes está deshabilitada para este flujo',
            ], 422);
        }

        if ($pago->estado !== 'pendiente') {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'Solo se pueden subir comprobantes a pagos pendientes',
            ], 422);
        }

        $archivo = $request->file('archivo');
        $nombre = $pago->codigo . '_' . time() . '.' . $archivo->getClientOriginalExtension();
        $ruta = $archivo->storeAs('comprobantes', $nombre, 'public');

        $comprobante = ComprobantePago::create([
            'pago_id' => $pago->id,
            'nombre_archivo' => $archivo->getClientOriginalName(),
            'ruta_archivo' => $ruta,
            'tipo_archivo' => $archivo->getClientOriginalExtension(),
            'tamano_bytes' => $archivo->getSize(),
            'estado' => 'adjuntado',
            'creado_por' => auth()->id(),
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 201,
            'mensaje' => 'Comprobante subido correctamente',
            'data' => $comprobante,
        ], 201);
    }

    public function aprobar(int $id): JsonResponse
    {
        $resultado = DB::transaction(function () use ($id) {
            $pago = Pago::lockForUpdate()->findOrFail($id);

            if (!in_array($pago->estado, ['pendiente', 'en_revision'])) {
                return ['ok' => false, 'codigo' => 422, 'mensaje' => 'El pago no está pendiente ni en revisión'];
            }

            $pago->update([
                'estado' => 'aprobado',
                'aprobado_por' => auth()->id(),
                'fecha_aprobacion' => now(),
                'actualizado_por' => auth()->id(),
            ]);

            if (!$pago->sesion_caja_id) {
                $sesionCaja = \App\Models\SesionCaja::where('sucursal_id', $pago->sucursal_id)
                    ->where('usuario_cajero_id', auth()->id())
                    ->where('estado', 'abierta')
                    ->latest('id')
                    ->first();

                if ($sesionCaja) {
                    $pago->update([
                        'sesion_caja_id' => $sesionCaja->id,
                        'actualizado_por' => auth()->id(),
                    ]);
                }
            }

            if ($pago->matricula_id) {
                $matricula = \App\Models\Matricula::lockForUpdate()->find($pago->matricula_id);
                if ($matricula && in_array($matricula->estado, ['reservada', 'en_revision'])) {
                    $matricula->update([
                        'estado' => 'matriculado',
                        'fecha_confirmacion' => now(),
                        'actualizado_por' => auth()->id(),
                    ]);

                    $oferta = \App\Models\OfertaAcademica::lockForUpdate()->find($matricula->oferta_academica_id);
                    if ($oferta && $oferta->cupos_reservados > 0) {
                        $oferta->decrement('cupos_reservados');
                        $oferta->increment('cupos_matriculados');
                        if ($oferta->cuposDisponibles() <= 0) {
                            $oferta->update(['estado' => 'lleno']);
                        }
                    }
                }

                $obligaciones = ObligacionPagoEstudiante::where('matricula_id', $pago->matricula_id)
                    ->where('estado', 'pendiente')
                    ->orderBy('numero_cuota')
                    ->get();

                $montoRestante = $pago->monto;

                foreach ($obligaciones as $obligacion) {
                    if ($montoRestante <= 0) break;

                    $saldo = $obligacion->monto - $obligacion->monto_pagado;
                    $montoAplicar = min($montoRestante, $saldo);

                    $obligacion->update([
                        'monto_pagado' => $obligacion->monto_pagado + $montoAplicar,
                        'estado' => ($obligacion->monto_pagado + $montoAplicar) >= $obligacion->monto ? 'pagado' : 'parcial',
                    ]);

                    AplicacionPago::create([
                        'pago_id' => $pago->id,
                        'obligacion_pago_estudiante_id' => $obligacion->id,
                        'estudiante_id' => $pago->estudiante_id,
                        'monto_aplicado' => $montoAplicar,
                        'estado' => 'activo',
                        'creado_por' => auth()->id(),
                    ]);

                    $montoRestante -= $montoAplicar;
                }
            }

            $recibo = $this->generarRecibo($pago);

            return ['ok' => true, 'pago' => $pago->fresh(), 'recibo' => $recibo];
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
            'mensaje' => 'Pago aprobado y recibo generado',
            'data' => [
                'pago' => $resultado['pago'],
                'recibo' => $resultado['recibo'],
            ],
        ]);
    }

    public function rechazar(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'motivo_rechazo' => 'required|string|max:500',
        ]);

        $pago = Pago::lockForUpdate()->findOrFail($id);

        if (!in_array($pago->estado, ['pendiente', 'solicita_link', 'en_revision'])) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'El pago no está en un estado válido para rechazo. Estado actual: ' . $pago->estado,
            ], 422);
        }

        DB::transaction(function () use ($pago, $request) {
            $datos = [
                'estado' => 'rechazado',
                'rechazado_por' => auth()->id(),
                'fecha_rechazo' => now(),
                'motivo_rechazo' => $request->motivo_rechazo,
                'actualizado_por' => auth()->id(),
            ];
            if (Schema::hasColumn('pagos', 'link_pago_estado')) {
                $datos['link_pago_estado'] = $pago->estado === 'solicita_link' ? 'rechazado' : $pago->link_pago_estado;
            }

            $pago->update($datos);

            AplicacionPago::where('pago_id', $pago->id)
                ->where('estado', 'pendiente')
                ->update([
                    'estado' => 'cancelado',
                    'actualizado_en' => now(),
                ]);

            if ($pago->matricula_id) {
                $matricula = Matricula::lockForUpdate()->find($pago->matricula_id);
                if ($matricula && in_array($matricula->estado, ['reservada', 'en_revision'])) {
                    $matricula->update([
                        'estado' => 'rechazado',
                        'actualizado_por' => auth()->id(),
                    ]);

                    $oferta = OfertaAcademica::lockForUpdate()->find($matricula->oferta_academica_id);
                    if ($oferta && $oferta->cupos_reservados > 0) {
                        $oferta->decrement('cupos_reservados');
                        $oferta->update(['estado' => 'abierto']);
                    }

                    $obligacionIds = $pago->aplicaciones()->pluck('obligacion_pago_estudiante_id');
                    ObligacionPagoEstudiante::whereIn('id', $obligacionIds)
                        ->where('estado', 'pendiente')
                        ->update(['estado' => 'rechazado']);
                }
            }
        });

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'Pago rechazado',
            'data' => $pago,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $pago = Pago::with([
            'estudiante:id,codigo,nombre,apellido',
            'conceptoPago:id,codigo,nombre',
            'metodoPago:id,codigo,nombre',
            'sucursal:id,codigo,nombre',
            'comprobantes:id,nombre_archivo,ruta_archivo,tipo_archivo,estado',
            'aplicaciones:id,pago_id,obligacion_pago_estudiante_id,monto_aplicado,estado',
            'aplicaciones.obligacion:id,concepto_pago_id,numero_cuota,nombre_cargo,monto,monto_pagado,estado',
            'aplicaciones.obligacion.conceptoPago:id,codigo,nombre',
            'reciboCaja:id,codigo,numero_recibo,estado',
        ])->findOrFail($id);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $pago,
        ]);
    }

    public function eliminarTotal(int $id): JsonResponse
    {
        $resultado = DB::transaction(function () use ($id) {
            $pago = Pago::with(['reciboCaja', 'comprobantes', 'aplicaciones'])->lockForUpdate()->findOrFail($id);

            if ($pago->reciboCaja) {
                $pago->reciboCaja()->delete();
            }

            if ($pago->comprobantes->isNotEmpty()) {
                $pago->comprobantes()->delete();
            }

            if ($pago->aplicaciones->isNotEmpty()) {
                $pago->aplicaciones()->delete();
            }

            $pago->delete();

            return true;
        });

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'Pago eliminado por completo',
            'data' => $resultado,
        ]);
    }

    public function siguienteRecibo(): JsonResponse
    {
        $anio = date('Y');
        $entidad = 'recibos_caja_' . $anio;

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

    private function generarRecibo(Pago $pago, ?string $codigoRecibo = null): ReciboCaja
    {
        $reciboExistente = ReciboCaja::where('pago_id', $pago->id)->first();
        if ($reciboExistente) {
            return $reciboExistente;
        }

        $anio = date('Y');
        $servicio = app(ServicioNomenclatura::class);

        if ($codigoRecibo) {
            $recibo = $this->intentarCrearRecibo($pago, $codigoRecibo, 0, $anio);
            if ($recibo) return $recibo;
        }

        for ($intento = 0; $intento < 5; $intento++) {
            $resultado = $servicio->generarCodigo(
                entidad: 'recibos_caja_' . $anio,
                formato: 'RC-{ANIO}-{SECUENCIA:6}',
                longitudSecuencia: 6,
                anio: $anio,
            );
            $recibo = $this->intentarCrearRecibo($pago, $resultado['codigo'], $resultado['secuencia'], $anio);
            if ($recibo) return $recibo;
        }

        throw new \RuntimeException('No se pudo generar el recibo después de varios intentos');
    }

    private function intentarCrearRecibo(Pago $pago, string $codigo, int $secuencia, string $anio): ?ReciboCaja
    {
        try {
            $fechaLocal = now(config('app.timezone'));

            $recibo = ReciboCaja::create([
                'codigo' => $codigo,
                'numero_recibo' => $secuencia,
                'pago_id' => $pago->id,
                'estudiante_id' => $pago->estudiante_id,
                'sucursal_id' => $pago->sucursal_id,
                'concepto_pago_id' => $pago->concepto_pago_id,
                'metodo_pago_id' => $pago->metodo_pago_id,
                'monto_total' => $pago->monto,
                'estado' => 'emitido',
                'anio' => $anio,
                'fecha_proceso' => $pago->fecha_proceso ?? $pago->fecha_aprobacion ?? $pago->creado_en ?? $fechaLocal,
                'fecha_recibo' => $pago->fecha_proceso ?? $pago->fecha_aprobacion ?? $pago->creado_en ?? $fechaLocal,
                'creado_por' => auth()->id(),
                'creado_en' => $fechaLocal,
            ]);

            return $recibo;
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return null;
        }
    }
}
