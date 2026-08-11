<?php

namespace App\Http\Controllers\Api\V1\Estudiantes;

use App\Contracts\ResultadoProcesamiento;
use App\Http\Controllers\Controller;
use App\Models\AplicacionPago;
use App\Models\Matricula;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Services\Pagos\FabricaProcesadorPago;
use App\Services\Pagos\ValidadorReglasPago;
use App\Services\ServicioNomenclatura;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PagoTarjetaController extends Controller
{
    public function iniciarPago(Request $request): JsonResponse
    {
        $estudiante = $request->attributes->get('estudiante');

        $datos = $request->validate([
            'matricula_id' => 'required|exists:matriculas,id',
            'obligacion_ids' => 'required|array|min:1',
            'obligacion_ids.*' => 'integer|exists:obligaciones_pago_estudiante,id',
        ]);

        $returnUrl = $request->input('return_url', route('portal.pagos.paypal.retorno', [], false).'?pago_id=PLACEHOLDER');
        $cancelUrl = $request->input('cancel_url', route('portal.pagos.paypal.cancelado', [], false).'?pago_id=PLACEHOLDER');

        $metodoTarjeta = MetodoPago::where('codigo', 'TAR')
            ->where('estado', 'activo')
            ->with('proveedorPago')
            ->first();

        if (! $metodoTarjeta || ! $metodoTarjeta->proveedorPago) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'El pago con tarjeta no está disponible en este momento',
            ], 422);
        }

        $proveedor = $metodoTarjeta->proveedorPago;
        if (! $proveedor->activo) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'El procesador de pago no está activo',
            ], 422);
        }

        $matricula = Matricula::where('estudiante_id', $estudiante->id)
            ->findOrFail($datos['matricula_id']);

        $obligaciones = $matricula->obligaciones()
            ->whereIn('id', $datos['obligacion_ids'])
            ->where('estado', 'pendiente')
            ->get();

        if ($obligaciones->isEmpty()) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'Las obligaciones seleccionadas no están pendientes',
            ], 422);
        }

        $montoTotal = $obligaciones->sum(fn ($o) => $o->saldoPendiente());
        $primerConcepto = $obligaciones->first()->conceptoPago;
        $obligacionIds = $obligaciones->pluck('id')->toArray();

        if (app(ValidadorReglasPago::class)->seleccionExcluyeMatriculaPendiente($matricula->id, $obligacionIds)) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'codigo_error' => '422_MATRICULA_OBLIGATORIA',
                'mensaje' => 'Debe incluir la obligación de matrícula antes de pagar cuotas.',
            ], 422);
        }

        $resultado = DB::transaction(function () use (
            $estudiante, $matricula, $metodoTarjeta, $proveedor,
            $montoTotal, $primerConcepto, $obligacionIds, $obligaciones,
            $returnUrl, $cancelUrl
        ) {
            $fechaProceso = now();

            $codigoPago = app(ServicioNomenclatura::class)->generarCodigo(
                entidad: 'pagos_'.date('Y'),
                formato: 'PAG-{ANIO}-{SECUENCIA:6}',
                longitudSecuencia: 6,
                anio: date('Y'),
            );

            $pago = Pago::create([
                'codigo' => $codigoPago['codigo'],
                'estudiante_id' => $estudiante->id,
                'matricula_id' => $matricula->id,
                'concepto_pago_id' => $primerConcepto->id,
                'metodo_pago_id' => $metodoTarjeta->id,
                'proveedor_pago_id' => $proveedor->id,
                'sucursal_id' => $estudiante->sucursal_id,
                'monto' => $montoTotal,
                'estado' => 'pendiente',
                'fecha_proceso' => $fechaProceso,
                'creado_en' => $fechaProceso,
            ]);

            $obligacionesMap = $obligaciones->keyBy('id');
            foreach ($obligacionIds as $oid) {
                $obligacion = $obligacionesMap->get($oid);
                AplicacionPago::create([
                    'pago_id' => $pago->id,
                    'obligacion_pago_estudiante_id' => $oid,
                    'estudiante_id' => $estudiante->id,
                    'monto_aplicado' => $obligacion ? $obligacion->monto : 0,
                    'estado' => 'pendiente',
                    'creado_por' => null,
                ]);
            }

            $procesador = FabricaProcesadorPago::crear($proveedor->codigo);
            $resultadoProc = $procesador->procesar($pago, [
                'return_url' => str_replace('PLACEHOLDER', $pago->id, $returnUrl),
                'cancel_url' => str_replace('PLACEHOLDER', $pago->id, $cancelUrl),
            ]);

            if (! $resultadoProc->exitoso) {
                DB::rollBack();

                return $resultadoProc;
            }

            $pago->update([
                'transaccion_id' => $resultadoProc->transaccionId,
                'procesador_respuesta' => json_encode($resultadoProc->datosCliente),
                'actualizado_en' => now(),
            ]);

            return [
                'pago' => $pago,
                'redirect_url' => $resultadoProc->redirectUrl,
            ];
        });

        if ($resultado instanceof ResultadoProcesamiento) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => $resultado->error ?? 'Error al procesar el pago',
            ], 422);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'Redirigiendo a PayPal...',
            'data' => [
                'pago_id' => $resultado['pago']->id,
                'codigo' => $resultado['pago']->codigo,
                'redirect_url' => $resultado['redirect_url'],
            ],
        ]);
    }

    public function retorno(Request $request): JsonResponse
    {
        $token = $request->input('token', $request->query('token'));
        $pagoId = $request->input('pago_id', $request->query('pago_id'));

        if (! $token) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'Token de pago no recibido',
            ], 422);
        }

        $pago = Pago::with('metodoPago.proveedorPago')->find($pagoId);
        if (! $pago) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 404,
                'mensaje' => 'Pago no encontrado',
            ], 404);
        }

        try {
            $procesador = FabricaProcesadorPago::crear($pago->metodoPago->proveedorPago->codigo);

            if ($pago->transaccion_id) {
                $resultado = $procesador->capturar($pago->transaccion_id);
            } else {
                $resultado = $procesador->capturar($token);
            }

            if (! $resultado->exitoso) {
                $pago->update([
                    'estado' => 'rechazado',
                    'procesador_respuesta' => json_encode(['error' => $resultado->error]),
                    'actualizado_en' => now(),
                ]);

                return response()->json([
                    'resultado' => 'R',
                    'codigo' => 422,
                    'mensaje' => $resultado->error ?? 'Error al capturar el pago',
                ], 422);
            }

            DB::transaction(function () use ($pago, $resultado) {
                $pago->update([
                    'transaccion_id' => $resultado->transaccionId,
                    'estado' => 'aprobado',
                    'aprobado_por' => null,
                    'fecha_aprobacion' => now(),
                    'actualizado_en' => now(),
                ]);

                PagoTarjetaController::confirmarObligaciones($pago);
                PagoTarjetaController::actualizarMatricula($pago);
            });

            return response()->json([
                'resultado' => 'A',
                'codigo' => 200,
                'mensaje' => 'Pago aprobado exitosamente',
                'data' => [
                    'pago_id' => $pago->id,
                    'codigo' => $pago->codigo,
                    'monto' => $pago->monto,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('PayPal return error', [
                'pago_id' => $pago->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'resultado' => 'R',
                'codigo' => 500,
                'mensaje' => 'Error al confirmar el pago',
            ], 500);
        }
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('PayPal webhook received', ['payload' => $payload]);

        $eventType = $payload['event_type'] ?? '';
        $resource = $payload['resource'] ?? [];

        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $transaccionId = $resource['id'] ?? null;
            $customId = $resource['custom_id'] ?? null;

            if ($transaccionId) {
                $pago = Pago::where('transaccion_id', $transaccionId)
                    ->where('estado', 'pendiente')
                    ->first();

                if ($pago) {
                    DB::transaction(function () use ($pago) {
                        $pago->update([
                            'estado' => 'aprobado',
                            'aprobado_por' => null,
                            'fecha_aprobacion' => now(),
                            'actualizado_en' => now(),
                        ]);
                        self::confirmarObligaciones($pago);
                        self::actualizarMatricula($pago);
                    });
                }
            }
        }

        return response()->json(['resultado' => 'A', 'codigo' => 200]);
    }

    public function cancelado(Request $request): JsonResponse
    {
        $pagoId = $request->input('pago_id', $request->query('pago_id'));

        if ($pagoId) {
            Pago::where('id', $pagoId)
                ->where('estado', 'pendiente')
                ->update([
                    'estado' => 'cancelado',
                    'actualizado_en' => now(),
                ]);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'Pago cancelado por el usuario',
        ]);
    }

    public static function confirmarObligaciones(Pago $pago): void
    {
        $aplicaciones = $pago->aplicaciones;

        foreach ($aplicaciones as $aplicacion) {
            $obligacion = $aplicacion->obligacion;
            if ($obligacion) {
                $nuevoPagado = $obligacion->monto_pagado + $aplicacion->monto_aplicado;
                $nuevoEstado = $nuevoPagado >= $obligacion->monto ? 'pagado' : 'parcial';
                $obligacion->update([
                    'monto_pagado' => $nuevoPagado,
                    'estado' => $nuevoEstado,
                    'actualizado_en' => now(),
                ]);
            }

            $aplicacion->update([
                'estado' => 'activo',
                'actualizado_en' => now(),
            ]);
        }
    }

    public static function actualizarMatricula(Pago $pago): void
    {
        if (! $pago->matricula_id) {
            return;
        }

        $matricula = $pago->matricula;
        if ($matricula && $matricula->estado === 'reservada') {
            $matricula->update([
                'estado' => 'matriculado',
                'fecha_confirmacion' => now(),
                'actualizado_en' => now(),
            ]);

            $oferta = $matricula->ofertaAcademica;
            if ($oferta) {
                $oferta->increment('cupos_matriculados');
                $oferta->decrement('cupos_reservados');
            }
        }
    }
}
