<?php

namespace App\Services\Pagos;

use App\Contracts\ProcesadorPago;
use RuntimeException;

class FabricaProcesadorPago
{
    private static array $procesadores = [];

    public static function registrar(string $codigoProveedor, string $clase): void
    {
        static::$procesadores[$codigoProveedor] = $clase;
    }

    public static function crear(string $codigoProveedor): ProcesadorPago
    {
        $clase = static::$procesadores[$codigoProveedor] ?? null;

        if (!$clase) {
            throw new RuntimeException("No hay procesador registrado para el proveedor: {$codigoProveedor}");
        }

        return app($clase);
    }

    public static function tieneProcesador(string $codigoProveedor): bool
    {
        return isset(static::$procesadores[$codigoProveedor]);
    }
}
