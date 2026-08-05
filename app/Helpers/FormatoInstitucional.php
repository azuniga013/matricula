<?php

if (!function_exists('formatear_moneda')) {
    function formatear_moneda($monto, ?string $simbolo = null, ?int $decimales = null, ?string $sepDecimales = null, ?string $sepMiles = null): string
    {
        $config = config('institucional.moneda', []);
        $simbolo = $simbolo ?? ($config['simbolo'] ?? 'L');
        $decimales = $decimales ?? ($config['decimales'] ?? 2);
        $sepDecimales = $sepDecimales ?? ($config['separador_decimales'] ?? '.');
        $sepMiles = $sepMiles ?? ($config['separador_miles'] ?? ',');

        $montoNumerico = is_numeric($monto) ? (float) $monto : 0.0;

        return $simbolo . ' ' . number_format($montoNumerico, $decimales, $sepDecimales, $sepMiles);
    }
}

if (!function_exists('formatear_numero')) {
    function formatear_numero($valor, ?int $decimales = null, ?string $sepDecimales = null, ?string $sepMiles = null): string
    {
        $config = config('institucional.moneda', []);
        $decimales = $decimales ?? ($config['decimales'] ?? 2);
        $sepDecimales = $sepDecimales ?? ($config['separador_decimales'] ?? '.');
        $sepMiles = $sepMiles ?? ($config['separador_miles'] ?? ',');

        $valorNumerico = is_numeric($valor) ? (float) $valor : 0.0;

        return number_format($valorNumerico, $decimales, $sepDecimales, $sepMiles);
    }
}