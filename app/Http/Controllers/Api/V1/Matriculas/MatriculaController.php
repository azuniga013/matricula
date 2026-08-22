<?php

namespace App\Http\Controllers\Api\V1\Matriculas;

use App\Http\Controllers\Controller;
use App\Models\Matricula;
use App\Modules\Comun\ContextoUsuario;
use App\Modules\Matriculas\CasosUso\CancelarMatricula;
use App\Modules\Matriculas\CasosUso\ConfirmarMatricula;
use App\Modules\Matriculas\CasosUso\ReservarMatricula;
use App\Services\ResolutorAlcanceDatos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatriculaController extends Controller
{
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
            'estudiante' => fn ($q) => $q->select('id', 'codigo', 'nombre', 'apellido'),
            'ofertaAcademica' => fn ($q) => $q->select('id', 'codigo', 'nivel_academico_id', 'horario_id', 'modalidad_id', 'sucursal_id', 'periodo_academico_id'),
            'ofertaAcademica.nivelAcademico' => fn ($q) => $q->select('id', 'codigo', 'nombre', 'regimen_academico_id'),
            'ofertaAcademica.nivelAcademico.regimenAcademico' => fn ($q) => $q->select('id', 'codigo', 'nombre'),
            'ofertaAcademica.horario' => fn ($q) => $q->select('id', 'codigo', 'nombre', 'hora_inicio', 'hora_fin'),
            'ofertaAcademica.periodoAcademico' => fn ($q) => $q->select('id', 'codigo', 'nombre'),
            'ofertaAcademica.modalidad' => fn ($q) => $q->select('id', 'codigo', 'nombre'),
            'sucursal' => fn ($q) => $q->select('id', 'codigo', 'nombre'),
        ]);
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, $request->user(), 'matriculas');

        if ($request->filled('sucursal_id')) {
            $query->where('matriculas.sucursal_id', $request->sucursal_id);
        }
        if ($request->filled('periodo_academico_id')) {
            $query->whereHas('ofertaAcademica', fn ($q) => $q->where('periodo_academico_id', $request->periodo_academico_id));
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

        $resultado = app(ReservarMatricula::class)->ejecutar(
            $request->all(),
            ContextoUsuario::desdeRequest(),
        );

        if (! $resultado->ok()) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => $resultado->codigo(),
                'codigo_error' => $resultado->codigoError(),
                'mensaje' => $resultado->mensaje(),
            ], $resultado->codigo());
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => $resultado->mensaje(),
            'data' => $resultado->data()['matricula'],
        ]);
    }

    public function confirmar(int $id): JsonResponse
    {
        $query = Matricula::query();
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, request()->user(), 'matriculas');
        $query->findOrFail($id);

        $resultado = app(ConfirmarMatricula::class)->ejecutar($id, ContextoUsuario::desdeRequest());

        if (! $resultado->ok()) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => $resultado->codigo(),
                'codigo_error' => $resultado->codigoError(),
                'mensaje' => $resultado->mensaje(),
            ], $resultado->codigo());
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => $resultado->mensaje(),
            'data' => $resultado->data()['matricula'],
        ]);
    }

    public function cancelar(int $id, Request $request): JsonResponse
    {
        $query = Matricula::query();
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, $request->user(), 'matriculas');
        $query->findOrFail($id);

        $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        $resultado = app(CancelarMatricula::class)->ejecutar(
            $id,
            $request->input('motivo'),
            ContextoUsuario::desdeRequest(),
        );

        if (! $resultado->ok()) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => $resultado->codigo(),
                'codigo_error' => $resultado->codigoError(),
                'mensaje' => $resultado->mensaje(),
            ], $resultado->codigo());
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => $resultado->mensaje(),
            'data' => $resultado->data()['matricula'],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $matricula = Matricula::with([
            'estudiante:id,codigo,nombre,apellido,correo,telefono',
            'ofertaAcademica:id,codigo,nivel_academico_id,periodo_academico_id,horario_id,docente_id,sucursal_id,plan_cobro_id',
            'sucursal:id,codigo,nombre',
            'obligaciones:id,matricula_id,concepto_pago_id,numero_cuota,nombre_cargo,monto,monto_pagado,fecha_vencimiento,estado',
        ]);
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($matricula, $request->user(), 'matriculas');
        $matricula = $matricula->findOrFail($id);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $matricula,
        ]);
    }
}
