<?php

namespace App\Modules\Pagos\Repositorios;

use App\Models\ComprobantePago;
use App\Models\Pago;
use Illuminate\Support\Facades\Schema;

final class EloquentPagoRepositorio implements PagoRepositorio
{
    public function buscar(int $id): ?Pago
    {
        return Pago::find($id);
    }

    public function buscarConBloqueo(int $id): ?Pago
    {
        return Pago::lockForUpdate()->find($id);
    }

    public function crearPago(array $atributos): Pago
    {
        return Pago::create($atributos);
    }

    public function aprobar(Pago $pago, int $usuarioId): Pago
    {
        $pago->update([
            'estado' => 'aprobado',
            'aprobado_por' => $usuarioId,
            'fecha_aprobacion' => now(),
            'actualizado_por' => $usuarioId,
            'actualizado_en' => now(),
        ]);

        return $pago;
    }

    public function actualizarLink(Pago $pago, string $link, int $usuarioId): Pago
    {
        $datos = [
            'link_pago_url' => $link,
            'link_generado_por' => $usuarioId,
            'link_generado_en' => now(),
            'estado' => 'esperando_respuesta',
            'actualizado_por' => $usuarioId,
            'actualizado_en' => now(),
        ];

        if (Schema::hasColumn('pagos', 'link_pago_estado')) {
            $datos['link_pago_estado'] = 'enviado';
        }

        $pago->update($datos);

        return $pago;
    }

    public function crearComprobante(array $atributos, int $usuarioId): ComprobantePago
    {
        return ComprobantePago::create([
            ...$atributos,
            'creado_por' => $usuarioId,
        ]);
    }

    public function marcarRechazado(Pago $pago, string $motivo, int $usuarioId): void
    {
        $datos = [
            'estado' => 'rechazado',
            'rechazado_por' => $usuarioId,
            'fecha_rechazo' => now(),
            'motivo_rechazo' => $motivo,
            'actualizado_por' => $usuarioId,
            'actualizado_en' => now(),
        ];

        if (Schema::hasColumn('pagos', 'link_pago_estado')) {
            $datos['link_pago_estado'] = $pago->estado === 'solicita_link' ? 'rechazado' : $pago->link_pago_estado;
        }

        $pago->update($datos);
    }

    public function eliminarDependenciasYRegistro(Pago $pago): void
    {
        $pago->load(['reciboCaja', 'comprobantes', 'aplicaciones']);

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
    }
}
