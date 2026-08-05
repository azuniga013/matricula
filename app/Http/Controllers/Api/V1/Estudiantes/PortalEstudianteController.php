<?php

namespace App\Http\Controllers\Api\V1\Estudiantes;

use App\Http\Controllers\Controller;
use App\Models\EnlacePago;
use App\Models\Calificacion;
use App\Models\CertificadoElectronico;
use App\Models\Matricula;
use App\Models\HistorialAcademico;
use App\Models\ObligacionPagoEstudiante;
use App\Models\OfertaAcademica;
use App\Models\Pago;
use App\Models\AplicacionPago;
use App\Models\ConceptoPago;
use App\Services\ServicioNomenclatura;
use App\Services\ResolutorFlujoMatricula;
use App\Services\DetectorPagoDuplicado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PortalEstudianteController extends Controller
{
    use \App\Http\Controllers\Concerns\ValidaConflictoHorario;
    use \App\Http\Controllers\Concerns\ValidaPrerrequisitos;
    public function misOfertas(Request $request): JsonResponse
    {
        $estudiante = $request->attributes->get('estudiante');

        $periodoActivo = \App\Models\PeriodoAcademico::abierto()->orderByDesc('fecha_inicio')->first();

        $nivelesAprobados = \App\Models\HistorialAcademico::where('estudiante_id', $estudiante->id)
            ->where('estado', 'aprobado')
            ->pluck('nivel_academico_id')
            ->unique()
            ->values();

        $nivelesAprobadosPorCalificacion = Calificacion::with('matricula.ofertaAcademica:nivel_academico_id,id')
            ->where('estudiante_id', $estudiante->id)
            ->get()
            ->filter(fn ($c) => $c->estaAprobada())
            ->pluck('matricula.ofertaAcademica.nivel_academico_id')
            ->filter()
            ->unique()
            ->values();

        $nivelesAprobados = $nivelesAprobados
            ->merge($nivelesAprobadosPorCalificacion)
            ->unique()
            ->values();

        $nivelesYaMatriculadosEnPeriodo = Matricula::where('estudiante_id', $estudiante->id)
            ->whereIn('estado', ['reservada', 'en_revision', 'matriculado'])
            ->whereHas('ofertaAcademica', function ($q) use ($periodoActivo) {
                $q->when($periodoActivo, fn ($sub) => $sub->where('periodo_academico_id', $periodoActivo->id));
            })
            ->pluck('oferta_academica_id')
            ->unique()
            ->values();

        $ofertas = OfertaAcademica::where('sucursal_id', $estudiante->sucursal_id)
            ->where('estado', 'abierto')
            ->whereRaw('cupo_maximo - cupos_matriculados - cupos_reservados > 0')
            ->when($periodoActivo, fn ($q) => $q->where('periodo_academico_id', $periodoActivo->id))
            ->when($nivelesAprobados->isNotEmpty(), function ($q) use ($nivelesAprobados) {
                $q->whereHas('nivelAcademico', function ($nivelQuery) use ($nivelesAprobados) {
                    $nivelQuery->whereNotIn('id', $nivelesAprobados);
                });
            })
            ->when($nivelesYaMatriculadosEnPeriodo->isNotEmpty(), function ($q) use ($nivelesYaMatriculadosEnPeriodo) {
                $q->whereNotIn('ofertas_academicas.id', $nivelesYaMatriculadosEnPeriodo);
            })
            ->with([
                'nivelAcademico.regimenAcademico',
                'modalidad',
                'horario',
                'docente',
                'planCobro.detalles',
            ])
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'codigo' => $o->codigo,
                'nivel' => $o->nivelAcademico->nombre,
                'nivel_codigo' => $o->nivelAcademico->codigo,
                'regimen' => $o->nivelAcademico->regimenAcademico->nombre ?? null,
                'modalidad' => $o->modalidad->nombre,
                'horario' => $o->horario ? $o->horario->hora_inicio . ' - ' . $o->horario->hora_fin : null,
                'horario_detalle' => $o->horario?->nombre,
                'docente' => $o->docente ? trim($o->docente->nombre . ' ' . $o->docente->apellido) : null,
                'cupos_disponibles' => $o->cupos_disponibles,
                'monto_total' => $o->planCobro ? $o->planCobro->detalles->sum('monto') : null,
                'periodo' => $o->periodoAcademico->nombre ?? null,
            ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
                'mensaje' => 'OK',
            'data' => [
                'periodo_actual' => $periodoActivo ? [
                    'id' => $periodoActivo->id,
                    'codigo' => $periodoActivo->codigo,
                    'nombre' => $periodoActivo->nombre,
                ] : null,
                'ofertas' => $ofertas,
            ],
        ]);
    }

    public function reservarMatricula(Request $request): JsonResponse
    {
        $estudiante = $request->attributes->get('estudiante');

        $datos = $request->validate([
            'oferta_academica_id' => 'required|exists:ofertas_academicas,id',
        ]);

        $oferta = OfertaAcademica::findOrFail($datos['oferta_academica_id']);

        if ($oferta->sucursal_id !== $estudiante->sucursal_id) {
            return \App\Helpers\RespuestaError::make('422_OFERTA_NO_PERTENECE_SUCURSAL', 422, 'La oferta no pertenece a su sucursal')
                ->response($request);
        }

        if ($oferta->estado !== 'abierto') {
            return \App\Helpers\RespuestaError::make('422_OFERTA_NO_ABIERTA', 422, 'La oferta no está abierta para matrícula')
                ->response($request);
        }

        if ($oferta->cupos_disponibles <= 0) {
            return \App\Helpers\RespuestaError::make('422_SIN_CUPO', 422, 'No hay cupos disponibles en esta oferta')
                ->response($request);
        }

        $configFlujo = app(ResolutorFlujoMatricula::class)->resolver('portal_estudiante', $oferta->planCobro?->detalles?->first()?->concepto_pago_id, null);
        if (empty($configFlujo['habilita_reserva_cupo'])) {
            return \App\Helpers\RespuestaError::make('422_FLUJO_MATRICULA_DESHABILITADO', 422, 'La reserva de cupo está deshabilitada para este proceso')
                ->response($request);
        }

        $yaMatriculadoEnPeriodo = Matricula::where('estudiante_id', $estudiante->id)
            ->whereIn('estado', ['reservada', 'en_revision', 'matriculado'])
            ->whereHas('ofertaAcademica', function ($q) use ($oferta) {
                $q->where('periodo_academico_id', $oferta->periodo_academico_id)
                  ->where('nivel_academico_id', $oferta->nivel_academico_id);
            })
            ->exists();

        if ($yaMatriculadoEnPeriodo) {
            return \App\Helpers\RespuestaError::make('422_MATRICULA_DUPLICADA_PERIODO', 422, 'Ya tiene ese nivel matriculado en el mismo período')
                ->response($request);
        }

        $matriculaExistente = Matricula::where('estudiante_id', $estudiante->id)
            ->where('oferta_academica_id', $oferta->id)
            ->first();

        if ($matriculaExistente && in_array($matriculaExistente->estado, ['reservada', 'en_revision', 'matriculado'])) {
            return \App\Helpers\RespuestaError::make('422_MATRICULA_DUPLICADA', 422, 'Ya tiene una matrícula activa en esta oferta')
                ->response($request);
        }

        $prerrequisitos = $this->validarPrerrequisitos($estudiante->id, $oferta->id);
        if ($prerrequisitos) {
            return \App\Helpers\RespuestaError::make('422_PRERREQUISITOS_NO_CUMPLIDOS', 422, $prerrequisitos)
                ->response($request);
        }

        $conflicto = $this->validarConflictoHorario($estudiante->id, $oferta->id);
        if ($conflicto) {
            return \App\Helpers\RespuestaError::make('422_CONFLICTO_HORARIO', 422, $conflicto)
                ->response($request);
        }

        $matriculaResultado = \Illuminate\Support\Facades\DB::transaction(function () use ($estudiante, $oferta, $matriculaExistente) {
            if ($matriculaExistente && in_array($matriculaExistente->estado, ['cancelado', 'rechazado'])) {
                $matriculaExistente = Matricula::lockForUpdate()->find($matriculaExistente->id);
                $tienePagos = $matriculaExistente->obligaciones()
                    ->whereHas('aplicaciones')
                    ->exists();

                if (!$tienePagos) {
                    $matriculaExistente->obligaciones()->delete();
                    $matriculaExistente->update([
                        'estado' => 'reservada',
                        'fecha_reserva' => now(),
                    ]);
                    $matricula = $matriculaExistente;
                } else {
                    $codigo = app(ServicioNomenclatura::class)->generarCodigo(
                        entidad: 'matriculas_' . date('Y'),
                        formato: 'MAT-{ANIO}-{SECUENCIA:8}',
                        longitudSecuencia: 8,
                        anio: date('Y'),
                    )['codigo'];
                    $matricula = Matricula::create([
                        'codigo' => $codigo,
                        'estudiante_id' => $estudiante->id,
                        'oferta_academica_id' => $oferta->id,
                        'sucursal_id' => $estudiante->sucursal_id,
                        'estado' => 'reservada',
                        'fecha_reserva' => now(),
                    ]);
                }
            } else {
                $codigo = app(ServicioNomenclatura::class)->generarCodigo(
                    entidad: 'matriculas_' . date('Y'),
                    formato: 'MAT-{ANIO}-{SECUENCIA:8}',
                    longitudSecuencia: 8,
                    anio: date('Y'),
                )['codigo'];
                $matricula = Matricula::create([
                    'codigo' => $codigo,
                    'estudiante_id' => $estudiante->id,
                    'oferta_academica_id' => $oferta->id,
                    'sucursal_id' => $estudiante->sucursal_id,
                    'estado' => 'reservada',
                    'fecha_reserva' => now(),
                ]);
            }

            $oferta->increment('cupos_reservados');

            if ($oferta->planCobro) {
                $detalles = $oferta->planCobro->detalles()->get();
                foreach ($detalles as $detalle) {
                    ObligacionPagoEstudiante::create([
                        'matricula_id' => $matricula->id,
                        'concepto_pago_id' => $detalle->concepto_pago_id,
                        'numero_cuota' => $detalle->numero_cuota,
                        'nombre_cargo' => $detalle->nombre_cargo,
                        'monto' => $detalle->monto,
                        'monto_pagado' => 0,
                        'fecha_vencimiento' => now()->addDays($detalle->dias_vencimiento)->toDateString(),
                        'estado' => 'pendiente',
                    ]);
                }
            }

            return $matricula;
        });

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Matrícula reservada exitosamente. Realice su pago para confirmar.',
            'data' => [
                'matricula_codigo' => $matriculaResultado->codigo,
                'estado' => $matriculaResultado->estado,
                'obligaciones_total' => $matriculaResultado->obligaciones()->sum('monto'),
                'obligaciones_cantidad' => $matriculaResultado->obligaciones()->count(),
            ],
        ], 201);
    }

    public function misMatriculas(Request $request): JsonResponse
    {
        $estudiante = $request->attributes->get('estudiante');

        $matriculas = $estudiante->matriculas()
            ->with([
                'ofertaAcademica.nivelAcademico',
                'ofertaAcademica.horario',
                'ofertaAcademica.nivelAcademico.regimenAcademico',
                'ofertaAcademica.modalidad',
                'ofertaAcademica.docente',
            ])
            ->latest('fecha_reserva')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'codigo' => $m->codigo,
                'estado' => $m->estado,
                'fecha_reserva' => $m->fecha_reserva?->format('d/m/Y'),
                'fecha_confirmacion' => $m->fecha_confirmacion?->format('d/m/Y'),
                'nivel' => $m->ofertaAcademica->nivelAcademico->nombre ?? null,
                'horario' => $m->ofertaAcademica->horario
                    ? $m->ofertaAcademica->horario->hora_inicio . ' - ' . $m->ofertaAcademica->horario->hora_fin
                    : null,
                'regimen' => $m->ofertaAcademica->nivelAcademico->regimenAcademico->nombre ?? null,
                'modalidad' => $m->ofertaAcademica->modalidad->nombre ?? null,
                'docente' => $m->ofertaAcademica->docente
                    ? trim($m->ofertaAcademica->docente->nombre . ' ' . $m->ofertaAcademica->docente->apellido)
                    : null,
            ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $matriculas,
        ]);
    }

    public function registrarPago(Request $request): JsonResponse
    {
        $estudiante = $request->attributes->get('estudiante');

        $datos = $request->validate([
            'matricula_id' => 'required|exists:matriculas,id',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'referencia' => 'nullable|string|max:100',
            'fecha_pago' => 'nullable|date',
            'solicitar_link' => 'nullable|boolean',
            'obligacion_ids' => 'nullable|array',
            'obligacion_ids.*' => 'integer|exists:obligaciones_pago_estudiante,id',
        ]);

        $validacion = $this->validarReferenciaFechaMetodo($datos, (int) $datos['metodo_pago_id']);
        if (!$validacion['ok']) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'codigo_error' => '422_VALIDACION',
                'mensaje' => $validacion['error'],
            ], 422);
        }

        $matricula = Matricula::where('estudiante_id', $estudiante->id)
            ->with('ofertaAcademica.planCobro.detalles.conceptoPago')
            ->findOrFail($datos['matricula_id']);
        $metodo = \App\Models\MetodoPago::findOrFail($datos['metodo_pago_id']);

        if (!in_array($matricula->estado, ['reservada', 'matriculado'])) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'La matrícula no está en un estado válido para registrar un pago',
            ], 422);
        }

        $obligacionesQuery = $matricula->obligaciones()->where('estado', 'pendiente');

        if (!empty($datos['obligacion_ids'])) {
            $matConceptoId = ConceptoPago::where('codigo', 'MAT')->value('id');
            $tieneMatPendiente = $matricula->obligaciones()
                ->where('estado', 'pendiente')
                ->where('concepto_pago_id', $matConceptoId)
                ->exists();

            if ($tieneMatPendiente) {
                $matIncluida = $matricula->obligaciones()
                    ->whereIn('id', $datos['obligacion_ids'])
                    ->where('concepto_pago_id', $matConceptoId)
                    ->exists();

                if (!$matIncluida) {
                    return response()->json([
                        'resultado' => 'R',
                        'codigo' => 422,
                        'mensaje' => 'Debe incluir el pago de matrícula antes de pagar cuotas',
                    ], 422);
                }
            }

            $obligacionesQuery->whereIn('id', $datos['obligacion_ids']);
        }

        if ($metodo->permite_link_pago && empty($datos['obligacion_ids'])) {
            $obligacionesQuery = $matricula->obligaciones()->where('estado', 'pendiente');
        }

        $obligaciones = $obligacionesQuery->get();

        if ($obligaciones->isEmpty()) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'No hay obligaciones pendientes para esta matrícula',
            ], 422);
        }

        $montoTotal = $obligaciones->sum(fn($o) => $o->saldoPendiente());
        $primerConcepto = $obligaciones->first()->conceptoPago;
        $obligacionIds = $obligaciones->pluck('id')->toArray();

        $configFlujo = app(ResolutorFlujoMatricula::class)->resolver('portal_estudiante', $primerConcepto->id, $metodo->id);

        $referenciaLimpia = $validacion['referencia'];
        $fechaProcesoCarbon = $validacion['fecha_carbon'];

        $resultado = DB::transaction(function () use ($estudiante, $matricula, $datos, $montoTotal, $primerConcepto, $obligacionIds, $obligaciones, $metodo, $configFlujo, $referenciaLimpia, $fechaProcesoCarbon) {
            $codigoPago = app(ServicioNomenclatura::class)->generarCodigo(
                entidad: 'pagos_' . date('Y'),
                formato: 'PAG-{ANIO}-{SECUENCIA:6}',
                longitudSecuencia: 6,
                anio: date('Y'),
            );

            $pago = Pago::create([
                'codigo' => $codigoPago['codigo'],
                'estudiante_id' => $estudiante->id,
                'matricula_id' => $matricula->id,
                'concepto_pago_id' => $primerConcepto->id,
                'metodo_pago_id' => $datos['metodo_pago_id'],
                'sucursal_id' => $estudiante->sucursal_id,
                'monto' => $montoTotal,
                'estado' => (!empty($datos['solicitar_link']) || $metodo->permite_link_pago || $metodo->codigo === 'LNK') ? 'solicita_link' : 'pendiente',
                'referencia_externa' => $referenciaLimpia ?? ($datos['referencia'] ?? null),
                'fecha_deposito' => $fechaProcesoCarbon,
                'creado_en' => now(),
            ]);

            $obligacionesMap = $obligaciones->keyBy('id');
            foreach ($obligacionIds as $oid) {
                $obligacion = $obligacionesMap->get($oid);
                AplicacionPago::create([
                    'pago_id' => $pago->id,
                    'obligacion_pago_estudiante_id' => $oid,
                    'estudiante_id' => $estudiante->id,
                    'monto_aplicado' => $obligacion ? $obligacion->monto : 0,
                    'estado' => 'pendiente',
                    'creado_por' => null,
                ]);
            }

            return $pago;
        });

        $duplicado = app(DetectorPagoDuplicado::class)->aplicar(
            $resultado,
            $resultado->referencia_externa,
            $resultado->fecha_deposito ? \Illuminate\Support\Carbon::instance($resultado->fecha_deposito) : null
        );

        return response()->json([
            'resultado' => 'A',
            'codigo' => 201,
            'mensaje' => (!empty($datos['solicitar_link']) || $metodo->permite_link_pago) ? 'Solicitud de link enviada a contabilidad.' : 'Pago registrado. Ahora puede subir su comprobante.',
            'data' => [
                'pago_id' => $resultado->id,
                'codigo' => $resultado->codigo,
                'monto' => $resultado->monto,
                'obligaciones_seleccionadas' => $obligacionIds,
                'estado' => $resultado->estado,
                'estado_pago' => $resultado->estado,
                'estado_matricula' => $matricula->estado,
                'alerta_duplicado' => (bool) $resultado->fresh()->alerta_duplicado,
            ],
        ], 201);
    }

    public function subirComprobante(Request $request): JsonResponse
    {
        $estudiante = $request->attributes->get('estudiante');

        $datos = $request->validate([
            'pago_id' => 'required|exists:pagos,id',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'referencia' => 'nullable|string|max:100',
            'fecha_pago' => 'nullable|date',
            'comprobante' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $pago = Pago::findOrFail($datos['pago_id']);

        if ($pago->estudiante_id !== $estudiante->id) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'El pago no pertenece a su cuenta',
            ], 422);
        }

        if (!in_array($pago->estado, ['pendiente', 'rechazado'])) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'Este pago ya fue procesado',
            ], 422);
        }

        $metodo = \App\Models\MetodoPago::findOrFail($datos['metodo_pago_id']);
        $validacion = $this->validarReferenciaFechaMetodo($datos, $metodo->id);
        if (!$validacion['ok']) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'codigo_error' => '422_VALIDACION',
                'mensaje' => $validacion['error'],
            ], 422);
        }

        $configFlujo = app(ResolutorFlujoMatricula::class)->resolver('portal_estudiante', $pago->concepto_pago_id, $pago->metodo_pago_id);
        if (empty($configFlujo['habilita_carga_comprobante'])) {
            return response()->json(['resultado' => 'R', 'codigo' => 422, 'mensaje' => 'La carga de comprobante está deshabilitada para este proceso'], 422);
        }
        if (!empty($configFlujo['requiere_comprobante']) && !$request->hasFile('comprobante')) {
            return response()->json(['resultado' => 'R', 'codigo' => 422, 'mensaje' => 'Este proceso requiere comprobante'], 422);
        }

        $archivo = $request->file('comprobante');
        $nombreArchivo = time() . '_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();
        $ruta = $archivo->storeAs('comprobantes', $nombreArchivo, 'public');

        \App\Models\ComprobantePago::create([
            'pago_id' => $pago->id,
            'nombre_archivo' => $archivo->getClientOriginalName(),
            'ruta_archivo' => $ruta,
            'tipo_archivo' => $archivo->getMimeType(),
            'tamano_bytes' => $archivo->getSize(),
            'estado' => 'pendiente',
        ]);

        $actualizar = [
            'estado' => 'en_revision',
            'motivo_rechazo' => null,
            'rechazado_por' => null,
            'fecha_rechazo' => null,
        ];

        if ($validacion['referencia'] !== null) {
            $actualizar['referencia_externa'] = $validacion['referencia'];
        }
        if ($validacion['fecha_carbon'] !== null) {
            $actualizar['fecha_deposito'] = $validacion['fecha_carbon'];
        }

        $pago->update($actualizar);

        app(DetectorPagoDuplicado::class)->aplicar(
            $pago->fresh(),
            $pago->fresh()->referencia_externa,
            $pago->fresh()->fecha_deposito ? \Illuminate\Support\Carbon::instance($pago->fresh()->fecha_deposito) : null
        );

        if ($pago->matricula_id) {
            Matricula::where('id', $pago->matricula_id)
                ->where('estado', 'reservada')
                ->update(['estado' => 'en_revision']);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Comprobante subido exitosamente. Será revisado por contabilidad.',
        ]);
    }

    public function misPagos(Request $request): JsonResponse
    {
        $estudiante = $request->attributes->get('estudiante');

        $pagos = $estudiante->pagos()
            ->with(['conceptoPago', 'metodoPago', 'comprobantes', 'reciboCaja', 'matricula.ofertaAcademica.nivelAcademico', 'matricula.ofertaAcademica.grupoWhatsapp'])
            ->latest('creado_en')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'estudiante_id' => $p->estudiante_id,
                'codigo' => $p->codigo,
                'monto' => $p->monto,
                'estado' => $p->estado,
                'concepto' => $p->conceptoPago->nombre ?? null,
                'metodo_pago_id' => $p->metodo_pago_id,
                'metodo' => $p->metodoPago->nombre ?? null,
                'metodo_pago' => $p->metodoPago ? [
                    'id' => $p->metodoPago->id,
                    'codigo' => $p->metodoPago->codigo,
                    'nombre' => $p->metodoPago->nombre,
                    'permite_link_pago' => $p->metodoPago->permite_link_pago,
                    'requiere_proveedor' => $p->metodoPago->requiere_proveedor ?? false,
                ] : null,
                'matricula_id' => $p->matricula_id,
                'matricula_codigo' => $p->matricula?->codigo,
                'matricula_estado' => $p->matricula?->estado,
                'matricula_nivel' => $p->matricula?->ofertaAcademica?->nivelAcademico?->nombre,
                'obligaciones_total' => $p->aplicaciones->sum(fn ($a) => $a->monto_aplicado),
                'fecha' => $p->creado_en?->format('d/m/Y H:i'),
                'motivo_rechazo' => $p->motivo_rechazo,
                'link_pago_url' => $p->link_pago_url,
                'link_pago_estado' => $p->link_pago_estado,
                'whatsapp_link' => $p->estado === 'aprobado' && $p->matricula?->ofertaAcademica?->grupoWhatsapp
                    ? $p->matricula->ofertaAcademica->grupoWhatsapp->link : null,
                'whatsapp_grupo' => $p->estado === 'aprobado' && $p->matricula?->ofertaAcademica?->grupoWhatsapp
                    ? $p->matricula->ofertaAcademica->grupoWhatsapp->nombre : null,
                'recibo_id' => $p->reciboCaja?->id,
                'numero_recibo' => $p->reciboCaja?->numero_recibo,
                'fecha_recibo' => $p->reciboCaja?->fecha_recibo?->format('d/m/Y H:i') ?? $p->reciboCaja?->fecha_proceso?->format('d/m/Y H:i') ?? null,
                'tiene_comprobante' => $p->comprobantes->isNotEmpty(),
                'comprobantes' => $p->comprobantes->map(fn ($c) => [
                    'id' => $c->id,
                    'nombre_archivo' => $c->nombre_archivo,
                    'tipo_archivo' => $c->tipo_archivo,
                    'estado' => $c->estado,
                    'fecha' => $c->creado_en?->format('d/m/Y H:i'),
                    'ruta_descarga' => $c->ruta_archivo ? \Illuminate\Support\Facades\Storage::url($c->ruta_archivo) : null,
                ])->values(),
            ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $pagos,
        ]);
    }

    public function eliminarPago(Request $request, Pago $pago): JsonResponse
    {
        $estudiante = $request->attributes->get('estudiante');

        if ((int) $pago->estudiante_id !== (int) $estudiante->id) {
            return \App\Helpers\RespuestaError::noEncontrado('Pago')->response($request);
        }

        DB::transaction(function () use ($pago) {
            $matricula = null;
            if ($pago->matricula_id) {
                $matricula = Matricula::where('id', $pago->matricula_id)->lockForUpdate()->first();
                if ($matricula) {
                    $matricula->gestiones()->delete();
                    Calificacion::where('matricula_id', $matricula->id)->delete();
                    HistorialAcademico::where('matricula_id', $matricula->id)->delete();
                }
            }

            $pago->comprobantes()->delete();
            $pago->aplicaciones()->delete();
            $pago->reciboCaja()?->delete();

            if ($pago->matricula_id) {
                $pago->update(['matricula_id' => null]);
            }

            if ($matricula) {
                $matricula->obligaciones()->delete();
                $matricula->delete();
            }

            $pago->delete();
        });

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Pago eliminado correctamente',
        ]);
    }

    public function misRecibos(Request $request): JsonResponse
    {
        $estudiante = $request->attributes->get('estudiante');

        $recibos = $estudiante->recibos()
            ->with(['conceptoPago', 'metodoPago', 'pago:id,codigo'])
            ->where('estado', '!=', 'anulado')
            ->latest('fecha_recibo')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'codigo_pago' => $r->pago?->codigo,
                'numero_recibo' => $r->numero_recibo,
                'monto_total' => $r->monto_total,
                'concepto_origen' => $r->conceptoPago?->nombre,
                'metodo' => $r->metodoPago->nombre ?? null,
                'veces_reimpreso' => (int) ($r->veces_reimpreso ?? 0),
                'fecha' => ($r->fecha_recibo ?? $r->fecha_proceso ?? $r->creado_en)?->format('d/m/Y'),
                'hora' => ($r->fecha_recibo ?? $r->fecha_proceso ?? $r->creado_en)?->format('H:i'),
                'estado' => $r->estado,
            ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $recibos,
        ]);
    }

    public function miNivel(Request $request): JsonResponse
    {
        $estudiante = $request->attributes->get('estudiante');

        $matricula = $estudiante->matriculas()
            ->where('estado', 'matriculado')
            ->with([
                'ofertaAcademica.nivelAcademico',
                'ofertaAcademica.horario',
                'ofertaAcademica.periodoAcademico',
                'ofertaAcademica.nivelAcademico.regimenAcademico',
                'ofertaAcademica.modalidad',
                'ofertaAcademica.docente',
            ])
            ->latest('fecha_confirmacion')
            ->first();

        if (!$matricula || !$matricula->ofertaAcademica) {
            return response()->json([
                'resultado' => 'A',
                'codigo' => 0,
                'mensaje' => 'OK',
                'data' => null,
            ]);
        }

        $o = $matricula->ofertaAcademica;

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => [
                'nivel_codigo' => $o->nivelAcademico->codigo,
                'nivel_nombre' => $o->nivelAcademico->nombre,
                'periodo' => $o->periodoAcademico->nombre ?? null,
                'horario' => $o->horario ? $o->horario->hora_inicio . ' - ' . $o->horario->hora_fin : null,
                'regimen' => $o->nivelAcademico->regimenAcademico->nombre ?? null,
                'modalidad' => $o->modalidad->nombre ?? null,
                'docente' => $o->docente ? trim($o->docente->nombre . ' ' . $o->docente->apellido) : null,
            ],
        ]);
    }

    public function misCalificaciones(Request $request): JsonResponse
    {
        $estudiante = $request->attributes->get('estudiante');

        $calificaciones = Calificacion::with([
                'matricula.ofertaAcademica.nivelAcademico:id,codigo,nombre',
                'matricula.ofertaAcademica.periodoAcademico:id,codigo,nombre',
                'matricula.ofertaAcademica.horario:id,codigo,nombre,hora_inicio,hora_fin',
                'matricula.ofertaAcademica.docente:id,codigo,nombre,apellido',
            ])
            ->where('estudiante_id', $estudiante->id)
            ->orderByDesc('calificaciones.id')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'codigo' => $c->codigo,
                'nivel' => $c->matricula?->ofertaAcademica?->nivelAcademico?->nombre,
                'periodo' => $c->matricula?->ofertaAcademica?->periodoAcademico?->nombre,
                'horario' => $c->matricula?->ofertaAcademica?->horario
                    ? $c->matricula->ofertaAcademica->horario->hora_inicio . ' - ' . $c->matricula->ofertaAcademica->horario->hora_fin
                    : null,
                'docente' => $c->matricula?->ofertaAcademica?->docente
                    ? trim($c->matricula->ofertaAcademica->docente->nombre . ' ' . $c->matricula->ofertaAcademica->docente->apellido)
                    : null,
                'nota_final' => $c->nota_final,
                'faltas' => $c->faltas,
                'estado' => $c->estado,
                'aprobada' => $c->estaAprobada(),
                'observaciones' => $c->observaciones,
            ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $calificaciones,
        ]);
    }

    public function enlacesPago(Request $request): JsonResponse
    {
        $estudiante = $request->attributes->get('estudiante');

        $conceptoIds = ObligacionPagoEstudiante::where('estudiante_id', $estudiante->id)
            ->where('estado', 'pendiente')
            ->whereHas('pagoConcepto', fn ($q) => $q->where('codigo', 'MAT'))
            ->pluck('concepto_pago_id')
            ->unique()
            ->values()
            ->toArray();

        $query = EnlacePago::with('cuentaBancaria')
            ->where('estado', 'activo')
            ->where(function ($q) {
                $q->whereNull('fecha_vencimiento')
                    ->orWhere('fecha_vencimiento', '>=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('usos_maximos')
                    ->orWhereColumn('usos_actuales', '<', 'usos_maximos');
            });

        if (!empty($conceptoIds)) {
            $query->whereIn('concepto_pago_id', $conceptoIds);
        }

        if ($request->filled('monto')) {
            $query->where('monto', $request->monto);
        }

        $enlaces = $query->orderBy('creado_en', 'desc')->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $enlaces,
        ]);
    }

    public function confirmarLinkPago(Request $request): JsonResponse
    {
        $estudiante = $request->attributes->get('estudiante');

        $datos = $request->validate([
            'pago_id' => 'required|exists:pagos,id',
        ]);

        $pago = Pago::where('estudiante_id', $estudiante->id)->findOrFail($datos['pago_id']);

        if (!in_array($pago->estado, ['solicita_link', 'esperando_respuesta']) || empty($pago->link_pago_url)) {
            return response()->json(['resultado' => 'R', 'codigo' => 422, 'mensaje' => 'El enlace de pago todavía no está disponible o ya fue respondido'], 422);
        }

        $pago->update([
            'estado' => 'en_revision',
            'link_pago_estado' => 'ejecutado',
            'confirmado_por_estudiante_id' => $estudiante->id,
            'confirmado_por_estudiante_en' => now(),
        ]);

        if ($pago->matricula_id) {
            Matricula::where('id', $pago->matricula_id)->where('estado', 'reservada')->update(['estado' => 'en_revision']);
        }

        return response()->json(['resultado' => 'A', 'codigo' => 0, 'mensaje' => 'Gracias. Su pago quedó en revisión.']);
    }

    public function reengancharFlujoPago(Request $request): JsonResponse
    {
        $estudiante = $request->attributes->get('estudiante');

        $datos = $request->validate([
            'pago_id' => 'required|exists:pagos,id',
        ]);

        $resultado = DB::transaction(function () use ($estudiante, $datos) {
            $pago = Pago::with(['matricula.ofertaAcademica', 'aplicaciones'])
                ->where('estudiante_id', $estudiante->id)
                ->lockForUpdate()
                ->findOrFail($datos['pago_id']);

            if ($pago->estado === 'aprobado') {
                return ['ok' => false, 'codigo' => 422, 'mensaje' => 'El pago ya fue aprobado y no requiere reenganche.'];
            }

            if (!$pago->matricula_id || !$pago->matricula) {
                return ['ok' => false, 'codigo' => 422, 'mensaje' => 'El pago no tiene matrícula asociada.'];
            }

            $matricula = Matricula::lockForUpdate()->find($pago->matricula_id);
            if (!$matricula) {
                return ['ok' => false, 'codigo' => 404, 'mensaje' => 'La matrícula ya no existe.'];
            }

            $estadoPagoAntes = $pago->estado;
            $estadoMatriculaAntes = $matricula->estado;
            $nuevoEstadoPago = 'solicita_link';

            $pago->update([
                'estado' => $nuevoEstadoPago,
                'link_pago_estado' => $pago->link_pago_url ? 'enviado' : null,
                'confirmado_por_estudiante_id' => null,
                'confirmado_por_estudiante_en' => null,
                'motivo_rechazo' => null,
                'rechazado_por' => null,
                'fecha_rechazo' => null,
                'actualizado_por' => null,
            ]);

            if (in_array($matricula->estado, ['en_revision', 'rechazado'])) {
                $matricula->update([
                    'estado' => 'reservada',
                    'fecha_reserva' => $matricula->fecha_reserva ?? now(),
                    'fecha_confirmacion' => null,
                    'actualizado_por' => null,
                ]);
            }

            if ($pago->aplicaciones()->exists()) {
                $pago->aplicaciones()->whereIn('estado', ['pendiente', 'cancelado'])->update([
                    'estado' => 'pendiente',
                    'actualizado_en' => now(),
                ]);
            }

            return [
                'ok' => true,
                'pago' => $pago->fresh(['matricula.ofertaAcademica', 'aplicaciones']),
                'matricula' => $matricula->fresh(),
                'antes' => [
                    'estado_pago' => $estadoPagoAntes,
                    'estado_matricula' => $estadoMatriculaAntes,
                    'link_pago_estado' => $pago->link_pago_estado,
                ],
            ];
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
            'codigo' => 0,
            'mensaje' => 'El flujo fue reencauzado correctamente.',
            'data' => [
                'pago_id' => $resultado['pago']->id,
                'estado_pago' => $resultado['pago']->estado,
                'matricula_id' => $resultado['matricula']->id,
                'estado_matricula' => $resultado['matricula']->estado,
                'antes' => $resultado['antes'],
                'despues' => [
                    'estado_pago' => $resultado['pago']->estado,
                    'estado_matricula' => $resultado['matricula']->estado,
                    'link_pago_estado' => $resultado['pago']->link_pago_estado,
                    'siguiente_paso' => $resultado['pago']->link_pago_url ? 'contabilidad_revisar_link' : 'contabilidad_publicar_link',
                ],
            ],
        ]);
    }

    public function whatsapp(Request $request): JsonResponse
    {
        $estudiante = $request->attributes->get('estudiante');

        $matricula = $estudiante->matriculas()
            ->where('estado', 'matriculado')
            ->with('ofertaAcademica.grupoWhatsapp')
            ->latest('fecha_confirmacion')
            ->first();

        if (!$matricula || !$matricula->ofertaAcademica || !$matricula->ofertaAcademica->grupoWhatsapp) {
            return response()->json([
                'resultado' => 'A',
                'codigo' => 0,
                'mensaje' => 'OK',
                'data' => ['whatsapp_link' => null],
            ]);
        }

        $tienePagoAprobado = $estudiante->pagos()
            ->where('matricula_id', $matricula->id)
            ->where('estado', 'aprobado')
            ->exists();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => [
                'whatsapp_link' => $tienePagoAprobado ? $matricula->ofertaAcademica->grupoWhatsapp->link : null,
            ],
        ]);
    }

    public function misCertificados(Request $request): JsonResponse
    {
        $estudiante = $request->attributes->get('estudiante');

        $certificados = CertificadoElectronico::with([
                'nivelAcademico:id,codigo,nombre',
                'historialAcademico.ofertaAcademica.periodoAcademico:id,codigo,nombre',
                'historialAcademico.ofertaAcademica.sucursal:id,codigo,nombre',
            ])
            ->where('estudiante_id', $estudiante->id)
            ->orderByDesc('emitido_en')
            ->get()
            ->map(fn ($cert) => [
                'id' => $cert->id,
                'codigo' => $cert->codigo,
                'nivel' => $cert->nivelAcademico?->nombre,
                'periodo' => $cert->historialAcademico?->ofertaAcademica?->periodoAcademico?->nombre,
                'sucursal' => $cert->historialAcademico?->ofertaAcademica?->sucursal?->nombre,
                'nota_final' => $cert->nota_final !== null ? number_format((float) $cert->nota_final, 2) : null,
                'emitido_en' => optional($cert->emitido_en)->format('d/m/Y H:i'),
                'codigo_verificacion' => $cert->codigo_verificacion,
                'estado' => $cert->estado,
                'vista_url' => route('certificados.validar', $cert->token_validacion),
                'pdf_url' => route('certificados.pdf', $cert->token_validacion),
            ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $certificados,
        ]);
    }

    private function validarReferenciaFechaMetodo(array $datos, int $metodoId): array
    {
        $metodo = \App\Models\MetodoPago::find($metodoId);
        $requerido = $metodo && in_array($metodo->codigo, DetectorPagoDuplicado::METODOS_VALIDABLES, true);

        $referencia = isset($datos['referencia']) ? trim((string) $datos['referencia']) : '';
        $fechaPago = $datos['fecha_pago'] ?? null;
        $fechaCarbon = null;

        if ($fechaPago) {
            try {
                $fechaCarbon = \Illuminate\Support\Carbon::parse($fechaPago)->startOfDay();
            } catch (\Throwable $e) {
                $fechaCarbon = null;
            }
        }

        if ($requerido) {
            if ($referencia === '') {
                return [
                    'ok' => false,
                    'error' => 'El número de referencia es obligatorio para ' . $metodo->nombre . '.',
                ];
            }
            if ($fechaCarbon === null) {
                return [
                    'ok' => false,
                    'error' => 'La fecha de pago es obligatoria para ' . $metodo->nombre . '.',
                ];
            }
        }

        return [
            'ok' => true,
            'metodo' => $metodo,
            'referencia' => $referencia !== '' ? $referencia : null,
            'fecha_carbon' => $fechaCarbon,
        ];
    }
}
