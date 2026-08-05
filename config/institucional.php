<?php

return [

    'moneda' => [
        'codigo' => 'HNL',
        'simbolo' => 'L',
        'decimales' => 2,
        'separador_decimales' => '.',
        'separador_miles' => ',',
    ],

    'fecha' => [
        'zona_horaria' => env('APP_TIMEZONE', 'America/Tegucigalpa'),
        'formato_corto' => 'd/m/Y',
        'formato_largo' => 'd/m/Y H:i',
        'formato_hora' => 'H:i',
    ],

];