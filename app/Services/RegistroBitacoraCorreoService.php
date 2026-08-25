<?php

namespace App\Services;

use App\Models\BitacoraCorreo;

class RegistroBitacoraCorreoService
{
    public function __construct(
        private readonly ConfiguracionBitacorasService $configuracion,
    ) {}

    public function registrar(array $datos): void
    {
        if (! $this->configuracion->correosHabilitada()) {
            return;
        }

        BitacoraCorreo::create([
            ...$datos,
            'creado_en' => $datos['creado_en'] ?? now(),
        ]);
    }
}
