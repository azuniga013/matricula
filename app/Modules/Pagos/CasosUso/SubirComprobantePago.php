<?php

namespace App\Modules\Pagos\CasosUso;

use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;
use App\Modules\Pagos\Repositorios\PagoRepositorio;
use App\Services\ResolutorFlujoMatricula;
use Illuminate\Http\UploadedFile;

final class SubirComprobantePago
{
    public function __construct(
        private readonly PagoRepositorio $repositorio,
        private readonly ResolutorFlujoMatricula $resolutorFlujo,
    ) {}

    public function ejecutar(int $pagoId, UploadedFile $archivo, ContextoUsuario $contexto): ResultadoCasoUso
    {
        $pago = $this->repositorio->buscar($pagoId);
        if (! $pago) {
            return ResultadoCasoUso::error(404, 'Pago no encontrado', '404_PAGO_NO_ENCONTRADO');
        }

        $configFlujo = $this->resolutorFlujo->resolver('portal_administrativo', $pago->concepto_pago_id, $pago->metodo_pago_id);
        if (empty($configFlujo['habilita_carga_comprobante'])) {
            return ResultadoCasoUso::error(422, 'La carga de comprobantes está deshabilitada para este flujo');
        }

        if ($pago->estado !== 'pendiente') {
            return ResultadoCasoUso::error(422, 'Solo se pueden subir comprobantes a pagos pendientes');
        }

        $nombre = $pago->codigo.'_'.time().'.'.$archivo->getClientOriginalExtension();
        $ruta = $archivo->storeAs('comprobantes', $nombre, 'public');

        $comprobante = $this->repositorio->crearComprobante([
            'pago_id' => $pago->id,
            'nombre_archivo' => $archivo->getClientOriginalName(),
            'ruta_archivo' => $ruta,
            'tipo_archivo' => $archivo->getClientOriginalExtension(),
            'tamano_bytes' => $archivo->getSize(),
            'estado' => 'adjuntado',
        ], $contexto->usuarioId());

        return ResultadoCasoUso::exito('Comprobante subido correctamente', ['comprobante' => $comprobante], 201);
    }
}
