<?php

namespace App\Contracts;

class ResultadoProcesamiento
{
    public function __construct(
        public readonly bool $exitoso,
        public readonly ?string $transaccionId = null,
        public readonly ?string $redirectUrl = null,
        public readonly ?array $datosCliente = null,
        public readonly ?string $error = null,
    ) {}
}
