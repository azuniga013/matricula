<?php

namespace App\Services;

use App\Models\ConfiguracionFlujoMatricula;

class ResolutorFlujoMatricula
{
    public function resolver(?string $origen, ?int $conceptoPagoId, ?int $metodoPagoId = null): array
    {
        $query = ConfiguracionFlujoMatricula::query()->where('estado', 'activo');

        if ($origen !== null) {
            $query->where('origen', $origen);
        }

        if ($conceptoPagoId !== null) {
            $query->where(function ($q) use ($conceptoPagoId) {
                $q->where('concepto_pago_id', $conceptoPagoId)
                  ->orWhereHas('conceptosPago', function ($sub) use ($conceptoPagoId) {
                      $sub->where('conceptos_pago.id', $conceptoPagoId);
                  });
            });
        }

        if ($metodoPagoId !== null) {
            $query->where(function ($q) use ($metodoPagoId) {
                $q->whereNull('metodo_pago_id')
                  ->orWhere('metodo_pago_id', $metodoPagoId)
                  ->orWhereHas('metodosPago', function ($sub) use ($metodoPagoId) {
                      $sub->where('metodos_pago.id', $metodoPagoId);
                  });
            });
        }

        $configuracion = $query->orderByDesc('id')->first();

        // Las configuraciones antiguas se registraron como tecnico. Se usan como
        // respaldo únicamente para el portal administrativo cuando no existe
        // una configuración explícita para ese origen.
        if (!$configuracion && $origen === 'portal_administrativo') {
            return $this->resolver('tecnico', $conceptoPagoId, $metodoPagoId);
        }

        return $configuracion ? $configuracion->toArray() : [
            'habilita_reserva_cupo' => true,
            'habilita_carga_comprobante' => true,
            'requiere_comprobante' => true,
            'habilita_revision_contable' => true,
            'habilita_aprobacion_pago' => true,
            'habilita_generacion_recibo' => true,
            'habilita_confirmacion_matricula' => true,
            'habilita_seleccion_obligaciones' => true,
            'habilita_whatsapp' => true,
            'habilita_reenganche' => true,
            'habilita_solicitud_link' => true,
        ];
    }
}
