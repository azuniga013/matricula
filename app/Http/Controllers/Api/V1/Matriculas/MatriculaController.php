<?php

namespace App\Http\Controllers\Api\V1\Matriculas;

use App\Http\Controllers\Controller;
use App\Models\{Estudiante, OfertaAcademica, Matricula, ObligacionPagoEstudiante};
use App\Services\ServicioNomenclatura;
use App\Services\ResolutorFlujoMatricula;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatriculaController extends Controller
{
    use \App\Http\Controllers\Concerns\ValidaConflictoHorario;
    use \App\Http\Controllers\Concerns\ValidaPrerrequisitos;
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'periodo_academico_id' => 'nullable|exists:periodos_academicos,id',
            'oferta_academica_id' => 'nullable|exists:ofertas_academicas,id',
            'estudiante_id' => 'nullable|exists:estudiantes,id',
            'estado' => 'nullable|in:reservada,en_revision,matriculado,rechazado,cancelado',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = Matricula::with([
            'estudiante' => fn($q) => $q->select('id','codigo','nombre','apellido'),
            'ofertaAcademica' => fn($q) => $q->select('id','codigo','nivel_academico_id','horario_id','modalidad_id','sucursal_id','periodo_academico_id'),
            'ofertaAcademica.nivelAcademico' => fn($q) => $q->select('id','codigo','nombre','regimen_academico_id'),
            'ofertaAcademica.nivelAcademico.regimenAcademico' => fn($q) => $q->select('id','codigo','nombre'),
            'ofertaAcademica.horario' => fn($q) => $q->select('id','codigo','nombre','hora_inicio','hora_fin'),
            'ofertaAcademica.periodoAcademico' => fn($q) => $q->select('id','codigo','nombre'),
            'ofertaAcademica.modalidad' => fn($q) => $q->select('id','codigo','nombre'),
            'sucursal' => fn($q) => $q->select('id','codigo','nombre'),
        ]);

        if ($request->filled('sucursal_id')) {
            $query->where('matriculas.sucursal_id', $request->sucursal_id);
        }
        if ($request->filled('periodo_academico_id')) {
            $query->whereHas('ofertaAcademica', fn($q) => $q->where('periodo_academico_id', $request->periodo_academico_id));
        }
        if ($request->filled('oferta_academica_id')) {
            $query->where('matriculas.oferta_academica_id', $request->oferta_academica_id);
        }
        if ($request->filled('estudiante_id')) {
            $query->where('matriculas.estudiante_id', $request->estudiante_id);
        }
        if ($request->filled('estado')) {
            $query->where('matriculas.estado', $request->estado);
        }

        $matriculas = $query->orderByDesc('matriculas.id')->paginate($request->get('per_page', 25));

        $matriculas->getCollection()->transform(function ($m) {
            $m->setAttribute('regimen', $m->ofertaAcademica?->nivelAcademico?->regimenAcademico?->nombre);
            $m->setAttribute('regimen_codigo', $m->ofertaAcademica?->nivelAcademico?->regimenAcademico?->codigo);
            return $m;
        });

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $matriculas,
        ]);
    }

    public function reservar(Request $request): JsonResponse
    {
        $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'oferta_academica_id' => 'required|exists:ofertas_academicas,id',
            'plan_estudio_id' => 'nullable|exists:planes_estudio,id',
        ]);

        $resultado = DB::transaction(function () use ($request) {
            $estudiante = Estudiante::findOrFail($request->estudiante_id);
            $oferta = OfertaAcademica::with('nivelAcademico.versionPlanEstudio')
                ->lockForUpdate()
                ->findOrFail($request->oferta_academica_id);
            $planOfertaId = $oferta->nivelAcademico?->versionPlanEstudio?->plan_estudio_id;
            if ($request->filled('plan_estudio_id') && (int) $request->plan_estudio_id !== (int) $planOfertaId) {
                return ['ok' => false, 'codigo' => 422, 'mensaje' => 'La oferta seleccionada no pertenece al plan de estudio indicado'];
            }
            $configFlujo = app(ResolutorFlujoMatricula::class)->resolver('portal_administrativo', $oferta->planCobro?->detalles?->first()?->concepto_pago_id, null);

            if (empty($configFlujo['habilita_reserva_cupo'])) {
                return ['ok' => false, 'codigo' => 422, 'mensaje' => 'La reserva de cupo está deshabilitada para este flujo'];
            }

            if ($oferta->estado !== 'abierto') {
                return ['ok' => false, 'codigo' => 422, 'mensaje' => 'La oferta no está abierta para matrícula'];
            }

            if ($oferta->cuposDisponibles() <= 0) {
                return ['ok' => false, 'codigo' => 422, 'mensaje' => 'No hay cupos disponibles'];
            }

            $yaMatriculado = Matricula::where('estudiante_id', $estudiante->id)
                ->where('oferta_academica_id', $oferta->id)
                ->whereIn('matriculas.estado', ['reservada', 'en_revision', 'matriculado'])
                ->exists();

            if ($yaMatriculado) {
                return ['ok' => false, 'codigo' => 422, 'mensaje' => 'El estudiante ya tiene una matrícula activa en esta oferta'];
            }

            $nuevoPlanId = $oferta->nivelAcademico?->versionPlanEstudio?->plan_estudio_id;
            if ($nuevoPlanId) {
                $matriculaActiva = Matricula::where('estudiante_id', $estudiante->id)
                    ->where('estado', 'matriculado')
                    ->whereHas('ofertaAcademica.nivelAcademico.versionPlanEstudio', function ($q) use ($nuevoPlanId) {
                        $q->where('plan_estudio_id', '!=', $nuevoPlanId);
                    })
                    ->exists();

                if ($matriculaActiva) {
                    return ['ok' => false, 'codigo' => 422, 'mensaje' => 'El estudiante ya tiene un plan de estudios activo. Debe finalizarlo antes de cambiarse a otro plan.'];
                }
            }

            $prerrequisitos = $this->validarPrerrequisitos($estudiante->id, $oferta->id);
            if ($prerrequisitos) {
                return ['ok' => false, 'codigo' => 422, 'mensaje' => $prerrequisitos];
            }

            $codigoMatricula = app(ServicioNomenclatura::class)->generarCodigo(
                entidad: 'matriculas_' . date('Y'),
                formato: 'MAT-{ANIO}-{SECUENCIA:8}',
                longitudSecuencia: 8,
                anio: date('Y'),
            );

            $matricula = Matricula::create([
                'codigo' => $codigoMatricula['codigo'],
                'estudiante_id' => $estudiante->id,
                'oferta_academica_id' => $oferta->id,
                'sucursal_id' => $oferta->sucursal_id,
                'estado' => 'reservada',
                'fecha_reserva' => now(),
                'creado_por' => auth()->id(),
            ]);

            $oferta->increment('cupos_reservados');

            $this->generarObligacionesDesdeOferta($matricula, $oferta);

            return ['ok' => true, 'matricula' => $matricula];
        });

        if (!$resultado['ok']) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => $resultado['codigo'],
                'mensaje' => $resultado['mensaje'],
            ], $resultado['codigo']);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'Matrícula reservada correctamente',
            'data' => $resultado['matricula'],
        ]);
    }

    public function confirmar(int $id): JsonResponse
    {
        $resultado = DB::transaction(function () use ($id) {
            $matricula = Matricula::lockForUpdate()->findOrFail($id);

            if ($matricula->estado !== 'reservada') {
                return ['ok' => false, 'codigo' => 422, 'mensaje' => 'Solo se pueden confirmar matrículas reservadas'];
            }

            $oferta = OfertaAcademica::with('planCobro.detalles.conceptoPago')
                ->lockForUpdate()
                ->findOrFail($matricula->oferta_academica_id);
            $configFlujo = app(ResolutorFlujoMatricula::class)->resolver('portal_administrativo', $oferta->planCobro?->detalles?->first()?->concepto_pago_id, null);

            if (empty($configFlujo['habilita_confirmacion_matricula'])) {
                return ['ok' => false, 'codigo' => 422, 'mensaje' => 'La confirmación de matrícula está deshabilitada para este flujo'];
            }

            if ($oferta->cuposDisponibles() <= 0) {
                return ['ok' => false, 'codigo' => 422, 'mensaje' => 'No hay cupos disponibles para confirmar'];
            }

            $prerrequisitos = $this->validarPrerrequisitos($matricula->estudiante_id, $matricula->oferta_academica_id);
            if ($prerrequisitos) {
                return ['ok' => false, 'codigo' => 422, 'mensaje' => $prerrequisitos];
            }

            $conflicto = $this->validarConflictoHorario($matricula->estudiante_id, $matricula->oferta_academica_id, $matricula->id);
            if ($conflicto) {
                return ['ok' => false, 'codigo' => 422, 'mensaje' => $conflicto];
            }

            $matricula->update([
                'estado' => 'en_revision',
                'fecha_confirmacion' => now(),
                'actualizado_por' => auth()->id(),
            ]);

            $this->generarObligacionesDesdeOferta($matricula, $oferta);

            if ($oferta->cuposDisponibles() <= 0) {
                $oferta->update(['estado' => 'lleno']);
            }

            return ['ok' => true, 'matricula' => $matricula->fresh()];
        });

        if (!$resultado['ok']) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => $resultado['codigo'],
                'mensaje' => $resultado['mensaje'],
            ], $resultado['codigo']);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'Matrícula confirmada y obligaciones generadas',
            'data' => $resultado['matricula'],
        ]);
    }

    private function generarObligacionesDesdeOferta(Matricula $matricula, OfertaAcademica $oferta): void
    {
        $matricula->loadMissing('obligaciones');

        if ($matricula->obligaciones->isNotEmpty()) {
            return;
        }

        $oferta->loadMissing('planCobro.detalles');

        if (!$oferta->planCobro || $oferta->planCobro->detalles->isEmpty()) {
            return;
        }

        $obligaciones = [];
        foreach ($oferta->planCobro->detalles as $detalle) {
            $obligaciones[] = [
                'matricula_id' => $matricula->id,
                'concepto_pago_id' => $detalle->concepto_pago_id,
                'numero_cuota' => $detalle->numero_cuota,
                'nombre_cargo' => $detalle->nombre_cargo,
                'monto' => $detalle->monto,
                'monto_pagado' => 0,
                'fecha_vencimiento' => $detalle->dias_vencimiento > 0 ? now()->addDays($detalle->dias_vencimiento) : now(),
                'estado' => 'pendiente',
                'creado_por' => auth()->id(),
            ];
        }

        if (!empty($obligaciones)) {
            ObligacionPagoEstudiante::insert($obligaciones);
            $matricula->unsetRelation('obligaciones');
        }
    }

    public function cancelar(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        $resultado = DB::transaction(function () use ($id, $request) {
            $matricula = Matricula::lockForUpdate()->findOrFail($id);

            if (in_array($matricula->estado, ['cancelado'])) {
                return ['ok' => false, 'codigo' => 422, 'mensaje' => 'La matrícula ya está cancelada'];
            }

            $oferta = OfertaAcademica::lockForUpdate()->findOrFail($matricula->oferta_academica_id);
            $estadoAnterior = $matricula->estado;

            $matricula->update([
                'estado' => 'rechazado',
                'observaciones' => $request->motivo,
                'actualizado_por' => auth()->id(),
            ]);

            if ($estadoAnterior === 'reservada' || $estadoAnterior === 'en_revision') {
                $oferta->decrement('cupos_reservados');
            } elseif ($estadoAnterior === 'matriculado') {
                $oferta->decrement('cupos_matriculados');
                if ($oferta->estado === 'lleno') {
                    $oferta->update(['estado' => 'abierto']);
                }
            }

            $matricula->obligaciones()->update(['estado' => 'rechazado']);

            return ['ok' => true, 'matricula' => $matricula->fresh()];
        });

        if (!$resultado['ok']) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => $resultado['codigo'],
                'mensaje' => $resultado['mensaje'],
            ], $resultado['codigo']);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'Matrícula cancelada',
            'data' => $resultado['matricula'],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $matricula = Matricula::with([
            'estudiante:id,codigo,nombre,apellido,correo,telefono',
            'ofertaAcademica:id,codigo,nivel_academico_id,periodo_academico_id,horario_id,docente_id,sucursal_id,plan_cobro_id',
            'sucursal:id,codigo,nombre',
            'obligaciones:id,concepto_pago_id,numero_cuota,nombre_cargo,monto,monto_pagado,fecha_vencimiento,estado',
        ])->findOrFail($id);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $matricula,
        ]);
    }
}
