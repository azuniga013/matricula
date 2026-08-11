<?php

namespace App\Modules\Caja\CasosUso;

use App\Modules\Caja\Repositorios\SesionCajaRepositorio;
use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;
use App\Services\ServicioNomenclatura;

final class AbrirSesionCaja
{
    public function __construct(
        private readonly SesionCajaRepositorio $repositorio,
        private readonly ServicioNomenclatura $nomenclatura,
    ) {}

    public function ejecutar(array $datos, ContextoUsuario $contexto): ResultadoCasoUso
    {
        $usuarioId = $contexto->usuarioId();

        $sesionAbierta = $this->repositorio->existeAbiertaDelCajero((int) $datos['sucursal_id'], $usuarioId);
        if ($sesionAbierta) {
            return ResultadoCasoUso::error(422, 'Ya tiene una sesión de caja abierta en esta sucursal');
        }

        $codigoSesion = $this->nomenclatura->generarCodigo(
            entidad: 'sesiones_caja_'.date('Y'),
            formato: 'SCA-{ANIO}-{SECUENCIA:6}',
            longitudSecuencia: 6,
            anio: date('Y'),
        );

        $sesion = $this->repositorio->crearSesion([
            'codigo' => $codigoSesion['codigo'],
            'sucursal_id' => $datos['sucursal_id'],
            'usuario_cajero_id' => $usuarioId,
            'monto_inicial' => $datos['monto_inicial'],
            'estado' => 'abierta',
            'fecha_apertura' => now(),
            'observaciones' => $datos['observaciones'] ?? null,
            'creado_por' => $usuarioId,
        ]);

        return ResultadoCasoUso::exito('Sesión de caja abierta', ['sesion' => $sesion], 201);
    }
}
