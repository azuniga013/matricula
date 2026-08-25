<?php

namespace App\Services;

use App\Models\BitacoraSeguridad;
use App\Models\BitacoraPeticion;
use Illuminate\Http\Request;
use App\Models\BitacoraAuditoria;

class ServicioBitacora
{
    public function __construct(
        private readonly ConfiguracionBitacorasService $configuracion,
    ) {}

    public function registrarPeticion(Request $request, int $estadoHttp, int $duracionMs): void
    {
        if (! $this->configuracion->peticionesHabilitadas()) {
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
        if (! $this->configuracion->seguridadHabilitada()) {
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

    public function registrarAuditoria(array $datos): void
    {
        if (! $this->configuracion->auditoriaCentralHabilitada()) {
            return;
        }

        BitacoraAuditoria::create(array_merge([
            'usuario_id' => null,
            'modulo' => 'general',
            'accion' => 'actualizacion',
            'entidad_tipo' => null,
            'entidad_id' => null,
            'descripcion' => null,
            'valores_antes' => null,
            'valores_despues' => null,
            'ip' => null,
            'agente' => null,
            'creado_en' => now(),
        ], $datos));
    }

    public function registrarAuditoriaDesdeRequest(Request $request, string $modulo, string $accion, ?string $entidadTipo = null, mixed $entidadId = null, mixed $antes = null, mixed $despues = null, ?string $descripcion = null): void
    {
        $this->registrarAuditoria([
            'usuario_id' => $request->user()?->id,
            'modulo' => $modulo,
            'accion' => $accion,
            'entidad_tipo' => $entidadTipo,
            'entidad_id' => $entidadId,
            'descripcion' => $descripcion,
            'valores_antes' => $antes,
            'valores_despues' => $despues,
            'ip' => $request->ip(),
            'agente' => $request->userAgent(),
            'creado_en' => now(),
        ]);
    }
}
