<?php

namespace App\Modules\Caja\Repositorios;

use App\Models\DetalleCierreCaja;
use App\Models\SesionCaja;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

interface SesionCajaRepositorio
{
    public function existeAbiertaDelCajero(int $sucursalId, int $usuarioCajeroId): bool;

    public function buscarConBloqueo(int $id): ?SesionCaja;

    public function crearSesion(array $atributos): SesionCaja;

    public function pagosAprobadosDeLaSesion(SesionCaja $sesion, Carbon $fechaCierre): Collection;

    public function guardarDetalleCierre(SesionCaja $sesion, array $datos, int $usuarioId): DetalleCierreCaja;

    public function cerrarSesion(SesionCaja $sesion, array $atributos): void;
}
