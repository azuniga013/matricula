<?php

namespace App\Modules\Pagos\Repositorios;

use App\Models\ComprobantePago;
use App\Models\Pago;

interface PagoRepositorio
{
    public function buscar(int $id): ?Pago;

    public function buscarConBloqueo(int $id): ?Pago;

    public function crearPago(array $atributos): Pago;

    public function aprobar(Pago $pago, int $usuarioId): Pago;

    public function actualizarLink(Pago $pago, string $link, int $usuarioId): Pago;

    public function crearComprobante(array $atributos, int $usuarioId): ComprobantePago;

    public function marcarRechazado(Pago $pago, string $motivo, int $usuarioId): void;

    public function eliminarDependenciasYRegistro(Pago $pago): void;
}
