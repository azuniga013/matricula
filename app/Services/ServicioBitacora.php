<?php

namespace App\Services;

use App\Models\BitacoraSeguridad;
use App\Models\BitacoraPeticion;
use Illuminate\Http\Request;

class ServicioBitacora
{
    public function registrarPeticion(Request $request, int $estadoHttp, int $duracionMs): void
    {
        if (!config('seguridad.bitacora.registrar_peticiones', true)) {
            return;
        }

        BitacoraPeticion::create([
            'usuario_id' => $request->user()?->id,
            'metodo' => $request->method(),
            'ruta' => $request->route()?->getName() ?? $request->path(),
            'estado_http' => $estadoHttp,
            'duracion_ms' => $duracionMs,
            'ip' => $request->ip(),
            'agente' => $request->userAgent(),
        ]);
    }

    public function registrarSeguridad(array $datos): void
    {
        if (!config('seguridad.bitacora.registrar_seguridad', true)) {
            return;
        }

        BitacoraSeguridad::create(array_merge([
            'usuario_id' => null,
            'accion' => '',
            'modulo' => null,
            'registro_id' => null,
            'valores_antes' => null,
            'valores_despues' => null,
            'ip' => null,
            'agente' => null,
            'resultado' => 'exitoso',
            'motivo' => null,
        ], $datos));
    }

    public function registrarDenegacion(int $usuarioId, string $permiso, string $ip, string $agente, string $motivo = ''): void
    {
        $this->registrarSeguridad([
            'usuario_id' => $usuarioId,
            'accion' => 'denegacion_permiso',
            'modulo' => $permiso,
            'ip' => $ip,
            'agente' => $agente,
            'resultado' => 'rechazado',
            'motivo' => $motivo ?: "Permiso {$permiso} no asignado",
        ]);
    }

    public function registrarOperacionPermitida(?int $usuarioId, string $accion, string $modulo, string $ip, string $agente, $registroId = null, $valoresAntes = null, $valoresDespues = null): void
    {
        $this->registrarSeguridad([
            'usuario_id' => $usuarioId ?: null,
            'accion' => $accion,
            'modulo' => $modulo,
            'registro_id' => $registroId,
            'valores_antes' => $valoresAntes,
            'valores_despues' => $valoresDespues,
            'ip' => $ip,
            'agente' => $agente,
            'resultado' => 'exitoso',
        ]);
    }
}
