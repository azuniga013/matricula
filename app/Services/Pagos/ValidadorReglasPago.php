<?php

namespace App\Services\Pagos;

use App\Models\MetodoPago;
use App\Models\ObligacionPagoEstudiante;

final class ValidadorReglasPago
{
    public function debeSolicitarLink(bool $solicitudExplicita, ?MetodoPago $metodo): bool
    {
        return $solicitudExplicita || (bool) ($metodo?->permite_link_pago) || $metodo?->codigo === 'LNK';
    }

    /**
     * @return array{mensaje: string, codigo_error: string}|null
     */
    public function validarSolicitudLink(array $configFlujo, ?MetodoPago $metodo, bool $solicitaLink): ?array
    {
        if (! $solicitaLink) {
            return null;
        }

        if (empty($configFlujo['habilita_solicitud_link'])) {
            return [
                'mensaje' => 'La solicitud de link de pago está deshabilitada para este flujo.',
                'codigo_error' => '422_SOLICITUD_LINK_DESHABILITADA',
            ];
        }

        if (! $metodo || (! $metodo->permite_link_pago && $metodo->codigo !== 'LNK')) {
            return [
                'mensaje' => 'El método de pago seleccionado no permite solicitar link de pago.',
                'codigo_error' => '422_METODO_NO_PERMITE_LINK',
            ];
        }

        return null;
    }

    public function seleccionExcluyeMatriculaPendiente(int $matriculaId, array $obligacionIds): bool
    {
        $obligacionIds = collect($obligacionIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($obligacionIds->isEmpty()) {
            return false;
        }

        $tieneMatriculaPendiente = ObligacionPagoEstudiante::query()
            ->where('matricula_id', $matriculaId)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->whereHas('conceptoPago', fn ($q) => $q->where('codigo', 'MAT'))
            ->exists();

        if (! $tieneMatriculaPendiente) {
            return false;
        }

        $incluyeMatricula = ObligacionPagoEstudiante::query()
            ->where('matricula_id', $matriculaId)
            ->whereIn('id', $obligacionIds)
            ->whereHas('conceptoPago', fn ($q) => $q->where('codigo', 'MAT'))
            ->exists();

        return ! $incluyeMatricula;
    }
}
