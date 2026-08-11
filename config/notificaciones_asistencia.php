<?php

return [
    'activar_envios' => (bool) env('NOTIFICACIONES_ASISTENCIA_ACTIVAR_ENVIOS', false),
    'max_intentos' => (int) env('NOTIFICACIONES_ASISTENCIA_MAX_INTENTOS', 3),
    'lote_procesamiento' => (int) env('NOTIFICACIONES_ASISTENCIA_LOTE', 100),
    'grupo_parametros' => env('NOTIFICACIONES_ASISTENCIA_GRUPO', 'notificaciones_asistencia'),
    'email' => [
        'habilitado' => (bool) env('NOTIFICACIONES_ASISTENCIA_EMAIL_HABILITADO', true),
        'driver' => env('NOTIFICACIONES_ASISTENCIA_EMAIL_DRIVER', 'mail'),
    ],
    'whatsapp' => [
        'habilitado' => (bool) env('NOTIFICACIONES_ASISTENCIA_WHATSAPP_HABILITADO', false),
        'driver' => env('NOTIFICACIONES_ASISTENCIA_WHATSAPP_DRIVER', 'deshabilitado'),
        'remitente' => env('NOTIFICACIONES_ASISTENCIA_WHATSAPP_REMITENTE'),
        'plantilla' => env('NOTIFICACIONES_ASISTENCIA_WHATSAPP_PLANTILLA', 'asistencia_basica'),
    ],
];
