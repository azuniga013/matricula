<?php

namespace App\Contracts;

use App\Models\Pago;

interface ProcesadorPago
{
    public function procesar(Pago $pago, array $datos): ResultadoProcesamiento;

    public function capturar(string $transaccionId): ResultadoProcesamiento;

    public function verificar(string $transaccionId): EstadoTransaccion;

    public function proveedorCodigo(): string;
}
