<?php

namespace App\Modules\Pagos\CasosUso;

use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;
use App\Modules\Pagos\Repositorios\PagoRepositorio;

final class ActualizarLinkPago
{
    public function __construct(
        private readonly PagoRepositorio $repositorio,
    ) {}

    public function ejecutar(int $pagoId, string $linkUrl, ContextoUsuario $contexto): ResultadoCasoUso
    {
        $pago = $this->repositorio->buscar($pagoId);
        if (! $pago) {
            return ResultadoCasoUso::error(404, 'Pago no encontrado', '404_PAGO_NO_ENCONTRADO');
        }

        if ($pago->estado !== 'solicita_link') {
            return ResultadoCasoUso::error(422, 'El pago no está en solicitud de link');
        }

        $link = trim($linkUrl);
        if (! preg_match('/^https?:\/\//i', $link)) {
            $link = 'https://'.$link;
        }

        if (! filter_var($link, FILTER_VALIDATE_URL)) {
            return ResultadoCasoUso::error(422, 'El link de pago no tiene un formato válido');
        }

        $actualizado = $this->repositorio->actualizarLink($pago, $link, $contexto->usuarioId());

        return ResultadoCasoUso::exito('Link guardado correctamente', ['pago' => $actualizado->fresh()], 0);
    }
}
