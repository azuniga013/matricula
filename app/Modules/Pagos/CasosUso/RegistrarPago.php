<?php

namespace App\Modules\Pagos\CasosUso;

use App\Models\ConceptoPago;
use App\Models\CuentaBancaria;
use App\Models\Estudiante;
use App\Models\MetodoPago;
use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;
use App\Modules\Pagos\Exceptions\InventarioInsuficienteException;
use App\Modules\Pagos\Repositorios\PagoRepositorio;
use App\Modules\Pagos\Servicios\AplicadorEfectosPago;
use App\Modules\Pagos\Servicios\GeneradorReciboCaja;
use App\Services\Pagos\ValidadorReglasPago;
use App\Services\ResolutorFlujoMatricula;
use App\Services\ServicioNomenclatura;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class RegistrarPago
{
    public function __construct(
        private readonly PagoRepositorio $repositorio,
        private readonly AplicadorEfectosPago $efectos,
        private readonly GeneradorReciboCaja $generadorRecibo,
        private readonly ResolutorFlujoMatricula $resolutorFlujo,
        private readonly ValidadorReglasPago $validadorReglas,
        private readonly ServicioNomenclatura $nomenclatura,
    ) {}

    public function ejecutar(array $datos, ContextoUsuario $contexto): ResultadoCasoUso
    {
        $metodoPagoId = isset($datos['metodo_pago_id']) ? (int) $datos['metodo_pago_id'] : null;
        $metodo = $metodoPagoId ? MetodoPago::find($metodoPagoId) : null;
        $cuentaBancaria = $this->validarCuentaBancaria($metodo, $datos['cuenta_bancaria_id'] ?? null);
        if ($cuentaBancaria === false) {
            return ResultadoCasoUso::error(422, 'Debe seleccionar una cuenta bancaria activa para pagos por depósito o transferencia.', '422_CUENTA_BANCARIA_REQUERIDA');
        }

        $monto = (float) ($datos['monto'] ?? 0);
        [$montoRecibido, $vuelto, $errorEfectivo] = $this->validarMontosEfectivo($metodo, $monto, $datos);
        if ($errorEfectivo) {
            return ResultadoCasoUso::error(422, $errorEfectivo['mensaje'], $errorEfectivo['codigo_error']);
        }

        $concepto = ConceptoPago::findOrFail($datos['concepto_pago_id']);
        $configFlujo = $this->resolutorFlujo->resolver(
            'portal_administrativo',
            $concepto->id,
            $metodoPagoId,
        );
        $solicitaLink = $this->validadorReglas->debeSolicitarLink((bool) ($datos['solicitar_link'] ?? false), $metodo);

        $errorLink = $this->validadorReglas->validarSolicitudLink($configFlujo, $metodo, $solicitaLink);
        if ($errorLink) {
            return ResultadoCasoUso::error(422, $errorLink['mensaje'], $errorLink['codigo_error']);
        }

        if (! $solicitaLink && empty($configFlujo['habilita_aprobacion_pago'])) {
            return ResultadoCasoUso::error(
                422,
                'La aprobación inmediata de pago está deshabilitada para este flujo.',
                '422_APROBACION_DESHABILITADA',
            );
        }

        if (
            ! empty($datos['matricula_id'])
            && $this->validadorReglas->seleccionExcluyeMatriculaPendiente(
                (int) $datos['matricula_id'],
                collect($datos['obligaciones'] ?? [])->pluck('obligacion_id')->all(),
            )
        ) {
            return ResultadoCasoUso::error(
                422,
                'Debe incluir la obligación de matrícula antes de registrar cuotas.',
                '422_MATRICULA_OBLIGATORIA',
            );
        }

        $usuarioId = $contexto->usuarioId();

        try {
            $guardado = DB::transaction(function () use ($datos, $cuentaBancaria, $solicitaLink, $usuarioId, $concepto, $configFlujo, $monto, $montoRecibido, $vuelto) {
                $estudiante = Estudiante::findOrFail($datos['estudiante_id']);
                $fechaProceso = Carbon::parse($datos['fecha_proceso'] ?? now());

                if (! $solicitaLink && ! $this->efectos->obtenerSesionCajaAbierta((int) $estudiante->sucursal_id, $usuarioId)) {
                    return ResultadoCasoUso::error(
                        422,
                        $this->efectos->mensajeSesionCajaRequerida((int) $estudiante->sucursal_id, $usuarioId, 'registrar pagos administrativos'),
                        '422_SESION_CAJA_REQUERIDA'
                    );
                }

                $resultadoCodigo = $this->nomenclatura->generarCodigo(
                    entidad: 'pagos_'.date('Y'),
                    formato: 'PAG-{ANIO}-{SECUENCIA:6}',
                    longitudSecuencia: 6,
                    anio: date('Y'),
                );

                $pago = $this->repositorio->crearPago([
                    'codigo' => $resultadoCodigo['codigo'],
                    'estudiante_id' => $estudiante->id,
                    'matricula_id' => $datos['matricula_id'] ?? null,
                    'concepto_pago_id' => $concepto->id,
                    'metodo_pago_id' => $datos['metodo_pago_id'] ?? null,
                    'cuenta_bancaria_id' => $cuentaBancaria?->id,
                    'sucursal_id' => $estudiante->sucursal_id,
                    'monto' => $monto,
                    'monto_recibido' => $montoRecibido,
                    'vuelto' => $vuelto,
                    'estado' => $solicitaLink ? 'solicita_link' : 'aprobado',
                    'referencia_externa' => $datos['referencia_externa'] ?? null,
                    'observaciones' => $datos['observaciones'] ?? null,
                    'aprobado_por' => $solicitaLink ? null : $usuarioId,
                    'fecha_aprobacion' => $solicitaLink ? null : $fechaProceso,
                    'creado_por' => $usuarioId,
                    'creado_en' => $fechaProceso,
                ]);

                $this->efectos->asignarSesionCajaSiHaceFalta($pago, $usuarioId);
                $this->efectos->descontarLibroSiCorresponde(
                    $pago,
                    $concepto->codigo,
                    $datos['inventario_libro_id'] ?? null,
                    isset($datos['cantidad_libro']) ? (int) $datos['cantidad_libro'] : null,
                    $usuarioId,
                );
                $this->efectos->confirmarMatriculaAlRegistrar($pago, $solicitaLink, $usuarioId);
                $this->efectos->aplicarAObligacionesConSeleccion($pago, $datos['obligaciones'] ?? [], $usuarioId);

                $recibo = $solicitaLink || empty($configFlujo['habilita_generacion_recibo'])
                    ? null
                    : $this->generadorRecibo->generar($pago, null, $usuarioId);

                return ['pago' => $pago, 'recibo' => $recibo];
            });
        } catch (InventarioInsuficienteException $e) {
            return ResultadoCasoUso::error(422, $e->getMessage(), '422_INVENTARIO_INSUFICIENTE');
        }

        if ($guardado instanceof ResultadoCasoUso) {
            return $guardado;
        }

        return ResultadoCasoUso::exito(
            $solicitaLink ? 'Pago registrado en solicitud de link' : 'Pago registrado y aprobado',
            $guardado,
            201,
        );
    }

    private function validarCuentaBancaria(?MetodoPago $metodo, mixed $cuentaBancariaId): CuentaBancaria|false|null
    {
        if (! $metodo || ! in_array($metodo->codigo, ['DEP', 'TRA'], true)) {
            return null;
        }

        if (! $cuentaBancariaId) {
            return false;
        }

        return CuentaBancaria::activas()->find($cuentaBancariaId) ?: false;
    }

    private function validarMontosEfectivo(?MetodoPago $metodo, float $monto, array $datos): array
    {
        if (! $metodo || $metodo->codigo !== 'EFE') {
            return [null, null, null];
        }

        if (! array_key_exists('monto_recibido', $datos) || $datos['monto_recibido'] === null || $datos['monto_recibido'] === '') {
            return [null, null, ['mensaje' => 'Debe ingresar el monto recibido para pagos en efectivo.', 'codigo_error' => '422_MONTO_RECIBIDO_REQUERIDO']];
        }

        $montoRecibido = (float) $datos['monto_recibido'];
        if ($montoRecibido < $monto) {
            return [null, null, ['mensaje' => 'El monto recibido no puede ser menor al total del pago.', 'codigo_error' => '422_MONTO_RECIBIDO_INSUFICIENTE']];
        }

        $vueltoCalculado = round($montoRecibido - $monto, 2);

        return [$montoRecibido, $vueltoCalculado, null];
    }
}
