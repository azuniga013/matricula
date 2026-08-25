<?php

namespace App\Http\Controllers\Api\V1\Matriculas;

use App\Http\Controllers\Controller;
use App\Models\{GestionMatricula, Matricula, OfertaAcademica, TipoGestionMatricula};
use App\Services\ServicioBitacora;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GestionMatriculaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'matricula_id' => 'nullable|exists:matriculas,id',
            'estudiante_id' => 'nullable|exists:estudiantes,id',
            'periodo_academico_id' => 'nullable|exists:periodos_academicos,id',
            'plan_estudio_id' => 'nullable|exists:planes_estudio,id',
            'nivel_academico_id' => 'nullable|exists:niveles_academicos,id',
            'oferta_academica_id' => 'nullable|exists:ofertas_academicas,id',
            'tipo_gestion_matricula_id' => 'nullable|exists:tipos_gestion_matricula,id',
            'estado' => 'nullable|in:pendiente,aprobado,rechazado,ejecutado',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = GestionMatricula::with([
            'matricula:id,codigo,estudiante_id,oferta_academica_id,estado',
            'matricula.estudiante:id,codigo,nombre,apellido',
            'tipoGestion:id,codigo,nombre',
            'ofertaOrigen:id,codigo,nivel_academico_id,horario_id',
            'ofertaDestino:id,codigo,nivel_academico_id,horario_id',
        ]);

        if ($request->filled('matricula_id')) {
            $query->where('gestiones_matricula.matricula_id', $request->matricula_id);
        }
        if ($request->filled('estudiante_id')) {
            $query->whereHas('matricula', fn($q) => $q->where('estudiante_id', $request->estudiante_id));
        }
        if ($request->filled('periodo_academico_id')) {
            $query->whereHas('matricula.ofertaAcademica', fn($q) => $q->where('periodo_academico_id', $request->periodo_academico_id));
        }
        if ($request->filled('plan_estudio_id')) {
            $query->whereHas('matricula.ofertaAcademica.nivelAcademico.versionPlanEstudio', fn($q) => $q->where('plan_estudio_id', $request->plan_estudio_id));
        }
        if ($request->filled('nivel_academico_id')) {
            $query->whereHas('matricula.ofertaAcademica', fn($q) => $q->where('nivel_academico_id', $request->nivel_academico_id));
        }
        if ($request->filled('oferta_academica_id')) {
            $query->whereHas('matricula', fn($q) => $q->where('oferta_academica_id', $request->oferta_academica_id));
        }
        if ($request->filled('tipo_gestion_matricula_id')) {
            $query->where('gestiones_matricula.tipo_gestion_matricula_id', $request->tipo_gestion_matricula_id);
        }
        if ($request->filled('estado')) {
            $query->where('gestiones_matricula.estado', $request->estado);
        }

        $gestiones = $query->orderByDesc('gestiones_matricula.id')->paginate($request->get('per_page', 25));

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $gestiones,
        ]);
    }

    public function solicitar(Request $request): JsonResponse
    {
        $usuarioId = (int) Auth::id();
        $request->validate([
            'matricula_id' => 'required|exists:matriculas,id',
            'tipo_gestion_matricula_id' => 'required|exists:tipos_gestion_matricula,id',
            'motivo' => 'required|string|max:500',
            'oferta_academica_destino_id' => 'nullable|exists:ofertas_academicas,id',
        ]);

        $gestion = GestionMatricula::create([
            'matricula_id' => $request->matricula_id,
            'tipo_gestion_matricula_id' => $request->tipo_gestion_matricula_id,
            'motivo' => $request->motivo,
            'oferta_academica_destino_id' => $request->oferta_academica_destino_id,
            'oferta_academica_origen_id' => Matricula::find($request->matricula_id)->oferta_academica_id,
            'estado' => 'pendiente',
            'solicitado_por' => $usuarioId,
            'fecha_solicitud' => now(),
            'datos_antes' => Matricula::find($request->matricula_id)->toArray(),
            'creado_por' => $usuarioId,
            'actualizado_por' => $usuarioId,
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);

        app(ServicioBitacora::class)->registrarAuditoriaDesdeRequest($request, 'gestiones_matricula', 'solicitar', 'gestiones_matricula', $gestion->id, null, $gestion->toArray(), 'Solicitud de gestión de matrícula');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 201,
            'mensaje' => 'Gestión de matrícula solicitada',
            'data' => $gestion,
        ], 201);
    }

    public function aprobar(int $id): JsonResponse
    {
        $request = request();
        $usuarioId = (int) Auth::id();
        $resultado = DB::transaction(function () use ($id, $usuarioId) {
            $gestion = GestionMatricula::lockForUpdate()->findOrFail($id);

            if ($gestion->estado !== 'pendiente') {
                return ['ok' => false, 'codigo' => 422, 'mensaje' => 'Solo se pueden aprobar gestiones pendientes'];
            }

            $tipo = TipoGestionMatricula::find($gestion->tipo_gestion_matricula_id);
            $matricula = Matricula::lockForUpdate()->findOrFail($gestion->matricula_id);

            $gestion->update([
                'estado' => 'aprobado',
                'decidido_por' => $usuarioId,
                'fecha_decision' => now(),
                'actualizado_por' => $usuarioId,
                'actualizado_en' => now(),
            ]);

            match ($tipo->codigo) {
                'CAM' => $this->aplicarCambioHorario($gestion, $matricula),
                'CTR' => $this->aplicarCambioModalidad($gestion, $matricula),
                'RET' => $this->aplicarRetiro($gestion, $matricula),
                'CAN' => $this->aplicarCancelacion($gestion, $matricula),
                default => null,
            };

            $gestion->update([
                'despues' => $matricula->fresh()->toArray(),
                'estado' => 'ejecutado',
                'actualizado_por' => $usuarioId,
                'actualizado_en' => now(),
            ]);

            return ['ok' => true, 'gestion' => $gestion->fresh()];
        });

        if (!$resultado['ok']) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => $resultado['codigo'],
                'mensaje' => $resultado['mensaje'],
            ], $resultado['codigo']);
        }

        app(ServicioBitacora::class)->registrarAuditoriaDesdeRequest($request, 'gestiones_matricula', 'aprobar', 'gestiones_matricula', $resultado['gestion']->id, null, $resultado['gestion']->toArray(), 'Aprobación y ejecución de gestión de matrícula');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'Gestión aprobada y ejecutada',
            'data' => $resultado['gestion'],
        ]);
    }

    public function rechazar(int $id, Request $request): JsonResponse
    {
        $usuarioId = (int) Auth::id();
        $request->validate([
            'motivo_decision' => 'required|string|max:500',
        ]);

        $gestion = GestionMatricula::findOrFail($id);

        if ($gestion->estado !== 'pendiente') {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'Solo se pueden rechazar gestiones pendientes',
            ], 422);
        }

        $gestion->update([
            'estado' => 'rechazado',
            'decidido_por' => $usuarioId,
            'fecha_decision' => now(),
            'motivo_decision' => $request->motivo_decision,
            'actualizado_por' => $usuarioId,
            'actualizado_en' => now(),
        ]);

        app(ServicioBitacora::class)->registrarAuditoriaDesdeRequest($request, 'gestiones_matricula', 'rechazar', 'gestiones_matricula', $gestion->id, null, $gestion->fresh()->toArray(), 'Rechazo de gestión de matrícula');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'Gestión rechazada',
            'data' => $gestion,
        ]);
    }

    public function tipos(): JsonResponse
    {
        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => TipoGestionMatricula::where('estado', 'activo')->orderBy('nombre')->get(['id', 'codigo', 'nombre']),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $gestion = GestionMatricula::with([
            'matricula:id,codigo,estudiante_id,oferta_academica_id,estado',
            'matricula.estudiante:id,codigo,nombre,apellido',
            'tipoGestion:id,codigo,nombre',
            'ofertaOrigen:id,codigo,nivel_academico_id,horario_id',
            'ofertaDestino:id,codigo,nivel_academico_id,horario_id',
        ])->findOrFail($id);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $gestion,
        ]);
    }

    private function aplicarCambioHorario(GestionMatricula $gestion, Matricula $matricula): void
    {
        $nuevaOferta = OfertaAcademica::lockForUpdate()->findOrFail($gestion->oferta_academica_destino_id);
        $ofertaAnterior = OfertaAcademica::lockForUpdate()->findOrFail($matricula->oferta_academica_id);
        $nivelAnterior = $ofertaAnterior->nivelAcademico()->with('versionPlanEstudio')->first();
        $nivelDestino = $nuevaOferta->nivelAcademico()->with('versionPlanEstudio')->first();

        if ($nuevaOferta->estado !== 'abierto') {
            throw new \Exception('La oferta destino no está abierta');
        }
        if ($nuevaOferta->periodo_academico_id !== $ofertaAnterior->periodo_academico_id) {
            throw new \Exception('La oferta destino debe pertenecer al mismo período');
        }
        if ($nuevaOferta->nivel_academico_id !== $ofertaAnterior->nivel_academico_id) {
            throw new \Exception('La oferta destino debe mantener el mismo nivel académico');
        }
        if ($nivelAnterior?->versionPlanEstudio?->plan_estudio_id !== $nivelDestino?->versionPlanEstudio?->plan_estudio_id) {
            throw new \Exception('La oferta destino debe mantener el mismo plan de estudio');
        }
        if ($nuevaOferta->horario_id === $ofertaAnterior->horario_id) {
            throw new \Exception('La oferta destino debe tener un horario diferente');
        }

        $this->validarYAplicarCambioOferta($gestion, $matricula, $ofertaAnterior, $nuevaOferta);
    }

    private function aplicarCambioModalidad(GestionMatricula $gestion, Matricula $matricula): void
    {
        $nuevaOferta = OfertaAcademica::lockForUpdate()->findOrFail($gestion->oferta_academica_destino_id);
        $ofertaAnterior = OfertaAcademica::lockForUpdate()->findOrFail($matricula->oferta_academica_id);
        $nivelAnterior = $ofertaAnterior->nivelAcademico()->with('versionPlanEstudio')->first();
        $nivelDestino = $nuevaOferta->nivelAcademico()->with('versionPlanEstudio')->first();

        if ($nuevaOferta->estado !== 'abierto') {
            throw new \Exception('La oferta destino no está abierta');
        }
        if ($nuevaOferta->periodo_academico_id !== $ofertaAnterior->periodo_academico_id) {
            throw new \Exception('La oferta destino debe pertenecer al mismo período');
        }
        if ($nuevaOferta->nivel_academico_id !== $ofertaAnterior->nivel_academico_id) {
            throw new \Exception('La oferta destino debe mantener el mismo nivel académico');
        }
        if ($nivelAnterior?->versionPlanEstudio?->plan_estudio_id !== $nivelDestino?->versionPlanEstudio?->plan_estudio_id) {
            throw new \Exception('La oferta destino debe mantener el mismo plan de estudio');
        }
        if ($nuevaOferta->horario_id !== $ofertaAnterior->horario_id) {
            throw new \Exception('La oferta destino debe mantener el mismo horario');
        }
        if ($nuevaOferta->modalidad_id === $ofertaAnterior->modalidad_id) {
            throw new \Exception('La oferta destino debe tener una modalidad diferente');
        }

        $this->validarYAplicarCambioOferta($gestion, $matricula, $ofertaAnterior, $nuevaOferta);
    }

    private function validarYAplicarCambioOferta(GestionMatricula $gestion, Matricula $matricula, OfertaAcademica $ofertaAnterior, OfertaAcademica $nuevaOferta): void
    {
        $usuarioId = (int) Auth::id();

        if ($nuevaOferta->cuposDisponibles() <= 0) {
            throw new \Exception('No hay cupos en la oferta destino');
        }

        $gestion->update([
            'oferta_academica_origen_id' => $ofertaAnterior->id,
            'datos_antes' => $this->snapshotMatricula($matricula, $ofertaAnterior),
            'actualizado_por' => $usuarioId,
            'actualizado_en' => now(),
        ]);
        $ofertaAnterior->decrement('cupos_matriculados');
        if ($ofertaAnterior->estado === 'lleno') {
            $ofertaAnterior->update(['estado' => 'abierto']);
        }

        $matricula->update([
            'oferta_academica_id' => $nuevaOferta->id,
            'sucursal_id' => $nuevaOferta->sucursal_id,
            'actualizado_por' => $usuarioId,
            'actualizado_en' => now(),
        ]);

        $nuevaOferta->increment('cupos_matriculados');

        $gestion->update([
            'despues' => $this->snapshotMatricula($matricula->fresh(), $nuevaOferta),
            'actualizado_por' => $usuarioId,
            'actualizado_en' => now(),
        ]);
    }

    private function snapshotMatricula(Matricula $matricula, OfertaAcademica $oferta): array
    {
        $nivel = $oferta->nivelAcademico()->with('versionPlanEstudio')->first();

        return [
            'matricula_id' => $matricula->id,
            'codigo' => $matricula->codigo,
            'estudiante_id' => $matricula->estudiante_id,
            'oferta_academica_id' => $oferta->id,
            'oferta_codigo' => $oferta->codigo,
            'periodo_academico_id' => $oferta->periodo_academico_id,
            'nivel_academico_id' => $oferta->nivel_academico_id,
            'nivel_codigo' => $nivel?->codigo,
            'nivel_nombre' => $nivel?->nombre,
            'plan_estudio_id' => $nivel?->versionPlanEstudio?->plan_estudio_id,
            'horario_id' => $oferta->horario_id,
            'horario_codigo' => $oferta->horario?->codigo,
            'horario_nombre' => $oferta->horario?->nombre,
            'modalidad_id' => $oferta->modalidad_id,
            'modalidad_codigo' => $oferta->modalidad?->codigo,
            'modalidad_nombre' => $oferta->modalidad?->nombre,
            'sucursal_id' => $oferta->sucursal_id,
            'estado' => $matricula->estado,
        ];
    }

    private function aplicarRetiro(GestionMatricula $gestion, Matricula $matricula): void
    {
        $usuarioId = (int) Auth::id();
        $matricula->update(['estado' => 'cancelado', 'actualizado_por' => $usuarioId, 'actualizado_en' => now()]);
        $oferta = OfertaAcademica::lockForUpdate()->findOrFail($matricula->oferta_academica_id);
        $oferta->decrement('cupos_matriculados');
        if ($oferta->estado === 'lleno') {
            $oferta->update(['estado' => 'abierto']);
        }
        $matricula->obligaciones()->update(['estado' => 'cancelado', 'actualizado_por' => $usuarioId, 'actualizado_en' => now()]);
    }

    private function aplicarCancelacion(GestionMatricula $gestion, Matricula $matricula): void
    {
        $this->aplicarRetiro($gestion, $matricula);
    }
}
