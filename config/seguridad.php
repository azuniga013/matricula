<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Intentos de acceso
    |--------------------------------------------------------------------------
    */

    'intentos' => [
        'maximos' => (int) env('SEGURIDAD_INTENTOS_MAXIMOS', 5),
        'ventana_minutos' => (int) env('SEGURIDAD_INTENTOS_VENTANA', 15),
        'bloqueo_minutos' => (int) env('SEGURIDAD_BLOQUEO_MINUTOS', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sesiones
    |--------------------------------------------------------------------------
    */

    'sesiones' => [
        'duracion_minutos' => (int) env('SEGURIDAD_SESION_DURACION', 480),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bitácora
    |--------------------------------------------------------------------------
    */

    'bitacora' => [
        'registrar_peticiones' => env('SEGURIDAD_BITACORA_PETICIONES', true),
        'registrar_seguridad' => env('SEGURIDAD_BITACORA_SEGURIDAD', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caché de permisos
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'ttl_minutos' => (int) env('SEGURIDAD_CACHE_PERMISOS_TTL', 60),
        'prefijo' => 'permisos_usuario_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitor de cupos
    |--------------------------------------------------------------------------
    |
    | Intervalo de actualización automática (segundos) de la pantalla del
    | monitor de cupos. La consulta se realiza contra la API; no requiere
    | tarea programada (AGENTS.md §4.25).
    |
    */

    'monitor' => [
        'refresco_segundos' => (int) env('MONITOR_CUPOS_REFRESCO_SEGUNDOS', 300),
    ],

];
