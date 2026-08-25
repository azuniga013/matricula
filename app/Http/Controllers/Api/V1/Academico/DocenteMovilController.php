<?php

namespace App\Http\Controllers\Api\V1\Academico;

use App\Http\Controllers\Controller;
use App\Models\AsistenciaEstudiante;
use App\Models\Calificacion;
use App\Models\Matricula;
use App\Models\OfertaAcademica;
use App\Models\SincronizacionDocenteMovil;
use App\Modules\Calificaciones\CasosUso\ActualizarCalificacion;
use App\Modules\Calificaciones\CasosUso\RegistrarCalificaciones;
use App\Modules\Comun\ContextoUsuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DocenteMovilController extends Controller
{
    public function sincronizar(Request $request): JsonResponse
    {
        $usuario = $request->user();
        if (! $usuario?->docente_id) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 403,
                'mensaje' => 'La cuenta no esta vinculada a un docente',
            ], 403);
        }

        $desde = $request->filled('desde') ? Carbon::parse($request->query('desde')) : null;
        $servidorEn = now()->toIso8601String();

        $ofertas = OfertaAcademica::with([
            'periodoAcademico:id,codigo,nombre',
            'nivelAcademico:id,codigo,nombre',
            'horario:id,codigo,nombre,hora_inicio,hora_fin',
            'modalidad:id,codigo,nombre',
        ])
            ->where('docente_id', $usuario->docente_id)
            ->orderByDesc('id')
            ->get()
            ->map(fn (OfertaAcademica $oferta) => $this->mapearOferta($oferta, $desde));

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => [
                'servidor_en' => $servidorEn,
                'siguiente_desde' => $servidorEn,
                'ofertas' => $ofertas,
            ],
        ]);
    }

    public function oferta(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();
        if (! $usuario?->docente_id) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 403,
                'mensaje' => 'La cuenta no esta vinculada a un docente',
            ], 403);
        }

        $oferta = OfertaAcademica::with([
            'periodoAcademico:id,codigo,nombre',
            'nivelAcademico:id,codigo,nombre',
            'horario:id,codigo,nombre,hora_inicio,hora_fin',
            'modalidad:id,codigo,nombre',
        ])
            ->where('docente_id', $usuario->docente_id)
            ->findOrFail($id);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $this->mapearOferta($oferta),
        ]);
    }

    public function actualizarWhatsappPeriodo(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();
        if (! $usuario?->docente_id) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 403,
                'mensaje' => 'La cuenta no esta vinculada a un docente',
            ], 403);
        }

        $oferta = OfertaAcademica::where('docente_id', $usuario->docente_id)->findOrFail($id);
        $datos = $request->validate([
            'whatsapp_grupo_nombre' => 'nullable|string|max:150',
            'whatsapp_link_periodo' => 'nullable|string|max:500',
        ]);

        $nombre = trim((string) ($datos['whatsapp_grupo_nombre'] ?? $oferta->whatsapp_grupo_nombre ?? ''));
        if ($nombre === '') {
            $nombre = null;
        }

        $link = trim((string) ($datos['whatsapp_link_periodo'] ?? ''));
        if ($link !== '' && ! preg_match('/^https?:\/\//i', $link)) {
            $link = 'https://' . $link;
        }
        if ($link !== '' && ! filter_var($link, FILTER_VALIDATE_URL)) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'El link de WhatsApp no tiene un formato válido.',
            ], 422);
        }

        $oferta->update([
            'whatsapp_grupo_nombre' => $nombre,
            'whatsapp_link_periodo' => $link !== '' ? $link : null,
            'actualizado_por' => $usuario->id,
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'Configuración de WhatsApp actualizada correctamente.',
            'data' => $this->mapearOferta($oferta->fresh(['periodoAcademico:id,codigo,nombre','nivelAcademico:id,codigo,nombre','horario:id,codigo,nombre,hora_inicio,hora_fin','modalidad:id,codigo,nombre'])),
        ]);
    }

    public function aplicarCola(Request $request): JsonResponse
    {
        $usuario = $request->user();
        if (! $usuario?->docente_id) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 403,
                'mensaje' => 'La cuenta no esta vinculada a un docente',
            ], 403);
        }

        $datos = $request->validate([
            'operaciones' => 'required|array|max:100',
            'operaciones.*.uuid' => 'required|uuid',
            'operaciones.*.tipo' => 'required|in:asistencia,calificacion',
            'operaciones.*.oferta_academica_id' => 'required|integer|exists:ofertas_academicas,id',
            'operaciones.*.fecha' => 'nullable|date',
            'operaciones.*.version_base' => 'nullable|date',
            'operaciones.*.datos' => 'required|array',
        ]);

        $resultados = collect($datos['operaciones'])->map(function (array $operacion) use ($usuario, $request) {
            $existente = SincronizacionDocenteMovil::where('uuid', $operacion['uuid'])->first();
            if ($existente) {
                return json_decode($existente->respuesta_json, true);
            }

            $resultado = $operacion['tipo'] === 'asistencia'
                ? $this->procesarAsistencia($operacion, $usuario)
                : $this->procesarCalificacion($operacion, $request);

            SincronizacionDocenteMovil::create([
                'uuid' => $operacion['uuid'],
                'usuario_id' => $usuario->id,
                'docente_id' => $usuario->docente_id,
                'tipo' => $operacion['tipo'],
                'oferta_academica_id' => $operacion['oferta_academica_id'],
                'estado' => $resultado['estado'],
                'respuesta_json' => json_encode($resultado, JSON_UNESCAPED_UNICODE),
            ]);

            return $resultado;
        })->values();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => ['operaciones' => $resultados],
        ]);
    }

    private function mapearOferta(OfertaAcademica $oferta, ?Carbon $desde = null): array
    {
        $matriculas = Matricula::with('estudiante:id,codigo,nombre,apellido')
            ->where('oferta_academica_id', $oferta->id)
            ->where('estado', 'matriculado')
            ->orderBy('id')
            ->get();

        $calificaciones = Calificacion::where('oferta_academica_id', $oferta->id)
            ->when($desde, function ($query, Carbon $desde) {
                $query->where(function ($inner) use ($desde) {
                    $inner->where('calificaciones.actualizado_en', '>=', $desde)
                        ->orWhere('calificaciones.creado_en', '>=', $desde);
                });
            })
            ->orderBy('id')
            ->get();

        $asistencias = AsistenciaEstudiante::where('oferta_academica_id', $oferta->id)
            ->when($desde, fn ($query, Carbon $desde) => $query->where('creado_en', '>=', $desde))
            ->orderByDesc('fecha')
            ->get();

        return [
            'id' => $oferta->id,
            'codigo' => $oferta->codigo,
            'estado' => $oferta->estado,
            'docente_id' => $oferta->docente_id,
            'whatsapp_grupo_nombre' => $oferta->whatsapp_grupo_nombre,
            'whatsapp_link_periodo' => $oferta->whatsapp_link_periodo,
            'periodo_academico_id' => $oferta->periodo_academico_id,
            'periodo_academico' => $oferta->periodoAcademico,
            'nivel_academico_id' => $oferta->nivel_academico_id,
            'nivel_academico' => $oferta->nivelAcademico,
            'horario_id' => $oferta->horario_id,
            'horario' => $oferta->horario,
            'modalidad_id' => $oferta->modalidad_id,
            'modalidad' => $oferta->modalidad,
            'actualizado_en' => optional($oferta->actualizado_en)->toIso8601String(),
            'alumnos' => $matriculas->map(fn (Matricula $matricula) => [
                'matricula_id' => $matricula->id,
                'estudiante_id' => $matricula->estudiante_id,
                'codigo' => $matricula->estudiante?->codigo,
                'nombre' => $matricula->estudiante?->nombre,
                'apellido' => $matricula->estudiante?->apellido,
                'estado' => $matricula->estado,
                'actualizado_en' => optional($matricula->actualizado_en ?? $matricula->creado_en)->toIso8601String(),
            ])->values(),
            'calificaciones' => $calificaciones->map(fn (Calificacion $calificacion) => [
                'id' => $calificacion->id,
                'estudiante_id' => $calificacion->estudiante_id,
                'matricula_id' => $calificacion->matricula_id,
                'nota_final' => $calificacion->nota_final,
                'faltas' => $calificacion->faltas,
                'observaciones' => $calificacion->observaciones,
                'estado' => $calificacion->estado,
                'version_servidor' => optional($calificacion->actualizado_en ?? $calificacion->creado_en)->toIso8601String(),
            ])->values(),
            'asistencias' => $asistencias->map(fn (AsistenciaEstudiante $asistencia) => [
                'id' => $asistencia->id,
                'matricula_id' => $asistencia->matricula_id,
                'fecha' => optional($asistencia->fecha)->format('Y-m-d'),
                'estado' => $asistencia->estado,
                'cuenta_como_falta' => $asistencia->cuenta_como_falta,
                'observacion' => $asistencia->observacion,
                'version_servidor' => optional($asistencia->creado_en)->toIso8601String(),
            ])->values(),
        ];
    }

    private function procesarAsistencia(array $operacion, $usuario): array
    {
        if (! $usuario->tienePermiso('asistencias.crear')) {
            return $this->resultadoOperacion($operacion, 'rechazada', 403, 'No tiene permiso para registrar asistencias');
        }

        $oferta = OfertaAcademica::where('docente_id', $usuario->docente_id)->find($operacion['oferta_academica_id']);
        if (! $oferta) {
            return $this->resultadoOperacion($operacion, 'rechazada', 404, 'Oferta académica no encontrada o fuera de alcance');
        }

        $matriculaId = (int) ($operacion['datos']['matricula_id'] ?? 0);
        $matricula = Matricula::where('oferta_academica_id', $oferta->id)->find($matriculaId);
        if (! $matricula) {
            return $this->resultadoOperacion($operacion, 'rechazada', 404, 'Matrícula no encontrada en la oferta');
        }

        $asistenciaExistente = AsistenciaEstudiante::where('matricula_id', $matriculaId)
            ->whereDate('fecha', $operacion['fecha'])
            ->first();

        if ($this->hayConflicto($asistenciaExistente?->creado_en, $operacion['version_base'] ?? null)) {
            return $this->resultadoOperacion($operacion, 'conflicto', 409, 'La asistencia cambió en el servidor después de la versión base');
        }

        $estado = $operacion['datos']['estado'] ?? 'presente';
        $asistencia = AsistenciaEstudiante::updateOrCreate(
            [
                'matricula_id' => $matriculaId,
                'fecha' => $operacion['fecha'],
            ],
            [
                'oferta_academica_id' => $oferta->id,
                'estado' => $estado,
                'cuenta_como_falta' => $operacion['datos']['cuenta_como_falta'] ?? ($estado === 'falta'),
                'observacion' => $operacion['datos']['observacion'] ?? null,
                'registrado_por' => $usuario->id,
                'creado_por' => $usuario->id,
            ]
        );

        return $this->resultadoOperacion($operacion, 'aplicada', 200, 'Asistencia aplicada', [
            'id' => $asistencia->id,
            'matricula_id' => $asistencia->matricula_id,
            'fecha' => optional($asistencia->fecha)->format('Y-m-d'),
            'version_servidor' => optional($asistencia->creado_en)->toIso8601String(),
        ]);
    }

    private function procesarCalificacion(array $operacion, Request $request): array
    {
        $usuario = $request->user();
        if (! $usuario->tienePermiso('calificaciones.crear') && ! $usuario->tienePermiso('calificaciones.modificar')) {
            return $this->resultadoOperacion($operacion, 'rechazada', 403, 'No tiene permiso para registrar calificaciones');
        }

        $oferta = OfertaAcademica::where('docente_id', $usuario->docente_id)->find($operacion['oferta_academica_id']);
        if (! $oferta) {
            return $this->resultadoOperacion($operacion, 'rechazada', 404, 'Oferta académica no encontrada o fuera de alcance');
        }

        $estudianteId = (int) ($operacion['datos']['estudiante_id'] ?? 0);
        $calificacionExistente = Calificacion::where('oferta_academica_id', $oferta->id)
            ->where('estudiante_id', $estudianteId)
            ->first();

        $versionActual = $calificacionExistente?->actualizado_en ?? $calificacionExistente?->creado_en;
        if ($this->hayConflicto($versionActual, $operacion['version_base'] ?? null)) {
            return $this->resultadoOperacion($operacion, 'conflicto', 409, 'La calificación cambió en el servidor después de la versión base');
        }

        $datos = [
            'nota_final' => $operacion['datos']['nota_final'] ?? null,
            'faltas' => $operacion['datos']['faltas'] ?? 0,
            'observaciones' => $operacion['datos']['observaciones'] ?? null,
        ];

        if ($calificacionExistente) {
            $resultado = app(ActualizarCalificacion::class)->ejecutar(
                $calificacionExistente->id,
                $datos,
                $usuario->docente_id,
                ContextoUsuario::desdeRequest(),
            );
            if (! $resultado->ok()) {
                return $this->resultadoOperacion($operacion, 'rechazada', $resultado->codigo(), $resultado->mensaje());
            }

            $calificacion = $resultado->data()['calificacion'];
        } else {
            $resultado = app(RegistrarCalificaciones::class)->ejecutar(
                $oferta->id,
                [[
                    'estudiante_id' => $estudianteId,
                    'nota_final' => $datos['nota_final'],
                    'faltas' => $datos['faltas'],
                    'observaciones' => $datos['observaciones'],
                ]],
                $usuario->docente_id,
                ContextoUsuario::desdeRequest(),
            );
            if (! $resultado->ok() || empty($resultado->data()['calificaciones'][0])) {
                return $this->resultadoOperacion($operacion, 'rechazada', $resultado->codigo(), $resultado->mensaje());
            }

            $calificacion = $resultado->data()['calificaciones'][0];
        }

        return $this->resultadoOperacion($operacion, 'aplicada', 200, 'Calificación aplicada', [
            'id' => $calificacion->id,
            'estudiante_id' => $calificacion->estudiante_id,
            'version_servidor' => optional($calificacion->actualizado_en ?? $calificacion->creado_en)->toIso8601String(),
        ]);
    }

    private function hayConflicto($versionActual, ?string $versionBase): bool
    {
        if (! $versionActual || ! $versionBase) {
            return false;
        }

        return Carbon::parse($versionActual)->greaterThan(Carbon::parse($versionBase));
    }

    private function resultadoOperacion(array $operacion, string $estado, int $codigo, string $mensaje, ?array $data = null): array
    {
        return array_filter([
            'uuid' => $operacion['uuid'],
            'tipo' => $operacion['tipo'],
            'oferta_academica_id' => $operacion['oferta_academica_id'],
            'estado' => $estado,
            'codigo' => $codigo,
            'mensaje' => $mensaje,
            'data' => $data,
        ], fn ($valor) => ! is_null($valor));
    }
}
