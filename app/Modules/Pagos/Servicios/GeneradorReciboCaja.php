<?php

namespace App\Modules\Pagos\Servicios;

use App\Models\Pago;
use App\Models\ReciboCaja;
use App\Services\ServicioNomenclatura;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;

final class GeneradorReciboCaja
{
    public function __construct(
        private readonly ServicioNomenclatura $nomenclatura,
    ) {}

    public function generar(Pago $pago, ?string $codigoRecibo = null, ?int $usuarioId = null): ReciboCaja
    {
        $reciboExistente = ReciboCaja::where('pago_id', $pago->id)->first();
        if ($reciboExistente) {
            return $reciboExistente;
        }

        $anio = date('Y');
        $creadoPor = $usuarioId ?? Auth::id();

        if ($codigoRecibo) {
            $recibo = $this->intentarCrear($pago, $codigoRecibo, 0, $anio, $creadoPor);
            if ($recibo) {
                return $recibo;
            }
        }

        for ($intento = 0; $intento < 5; $intento++) {
            $resultado = $this->nomenclatura->generarCodigo(
                entidad: 'recibos_caja_'.$anio,
                formato: 'RC-{ANIO}-{SECUENCIA:6}',
                longitudSecuencia: 6,
                anio: $anio,
            );
            $recibo = $this->intentarCrear($pago, $resultado['codigo'], $resultado['secuencia'], $anio, $creadoPor);
            if ($recibo) {
                return $recibo;
            }
        }

        throw new \RuntimeException('No se pudo generar el recibo después de varios intentos');
    }

    private function intentarCrear(Pago $pago, string $codigo, int $secuencia, string $anio, ?int $creadoPor): ?ReciboCaja
    {
        try {
            $fechaLocal = now(config('app.timezone'));
            $fechaRecibo = $pago->fecha_proceso ?? $fechaLocal;

            return ReciboCaja::create([
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
                'fecha_proceso' => $fechaRecibo,
                'fecha_recibo' => $fechaRecibo,
                'creado_por' => $creadoPor,
                'creado_en' => $fechaRecibo,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            return null;
        }
    }
}
