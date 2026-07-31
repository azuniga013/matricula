<?php

namespace App\Contracts;

class EstadoTransaccion
{
    public function __construct(
        public readonly string $estado,
        public readonly array $datos = [],
        public readonly ?string $error = null,
    ) {}
}
