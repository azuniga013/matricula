<?php

namespace App\Http\Controllers\Api\V1\Estudiantes;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Services\ResolutorAlcanceDatos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstudianteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Estudiante::activos()->ordenados()->with('sucursal');
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, $request->user(), 'estudiantes');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('estudiantes.codigo', 'like', "%{$buscar}%")
                    ->orWhere('estudiantes.nombre', 'like', "%{$buscar}%")
                    ->orWhere('estudiantes.apellido', 'like', "%{$buscar}%")
                    ->orWhere('estudiantes.identidad', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('sucursal_id')) {
            $query->where('estudiantes.sucursal_id', $request->sucursal_id);
        }

        $estudiantes = $query->paginate(25);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $estudiantes,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => 'nullable|string|max:50|unique:estudiantes,codigo',
            'nombre' => 'required|string|max:150',
            'apellido' => 'required|string|max:150',
            'identidad' => 'nullable|string|max:30|unique:estudiantes,identidad',
            'fecha_nacimiento' => 'nullable|date|before:today',
            'sexo' => 'nullable|string|in:M,F,Otro',
            'correo' => 'nullable|email|max:100',
            'telefono' => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:255',
            'sucursal_id' => 'required|exists:sucursales,id',
            'nombre_padre' => 'nullable|string|max:150',
            'telefono_padre' => 'nullable|string|max:30',
            'correo_padre' => 'nullable|email|max:100',
        ]);

        if (empty($datos['codigo'])) {
            $resultadoCodigo = app(\App\Services\ServicioNomenclatura::class)->generarCodigo(
                entidad: 'estudiantes_' . date('Y'),
                formato: 'EST-{ANIO}-{SECUENCIA:8}',
                longitudSecuencia: 8,
                anio: date('Y'),
            );
            $datos['codigo'] = $resultadoCodigo['codigo'];
        }

        $datos['creado_por'] = $request->user()->id;
        $datos['es_primer_ingreso'] = true;
        $datos['estado'] = 'activo';

        $estudiante = Estudiante::create($datos);
        $estudiante->load('sucursal');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Estudiante registrado exitosamente',
            'data' => $estudiante,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $estudiante = Estudiante::query();
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($estudiante, $request->user(), 'estudiantes');
        $estudiante = $estudiante->findOrFail($id);
        $estudiante->load('sucursal', 'acceso');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $estudiante,
        ]);
    }

    public function update(Request $request, Estudiante $estudiante): JsonResponse
    {
        $query = Estudiante::query();
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, $request->user(), 'estudiantes');
        $query->findOrFail($estudiante->id);

        $datos = $request->validate([
            'nombre' => 'required|string|max:150',
            'apellido' => 'required|string|max:150',
            'correo' => 'nullable|email|max:100',
            'telefono' => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:255',
            'nombre_padre' => 'nullable|string|max:150',
            'telefono_padre' => 'nullable|string|max:30',
            'correo_padre' => 'nullable|email|max:100',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $datos['actualizado_por'] = $request->user()->id;

        $estudiante->update($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Estudiante actualizado exitosamente',
            'data' => $estudiante,
        ]);
    }

    public function buscarPorIdentidad(Request $request): JsonResponse
    {
        $request->validate([
            'identidad' => 'required|string|max:30',
        ]);

        $query = Estudiante::where('identidad', $request->identidad);
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, $request->user(), 'estudiantes');
        $estudiante = $query->first();

        if (!$estudiante) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 404,
                'mensaje' => 'Estudiante no encontrado',
            ], 404);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => [
                'id' => $estudiante->id,
                'codigo' => $estudiante->codigo,
                'nombre' => $estudiante->nombre,
                'apellido' => $estudiante->apellido,
                'correo_enmascarado' => $estudiante->correo_enmascarado,
                'telefono_enmascarado' => $estudiante->telefono_enmascarado,
                'estado' => $estudiante->estado,
            ],
        ]);
    }

    public function planActivo(Request $request, int $id): JsonResponse
    {
        $estudiante = Estudiante::query();
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($estudiante, $request->user(), 'estudiantes');
        $estudiante = $estudiante->findOrFail($id);

        $matriculaActiva = Matricula::where('estudiante_id', $estudiante->id)
            ->where('estado', 'matriculado')
            ->with('ofertaAcademica.nivelAcademico.versionPlanEstudio.planEstudio')
            ->latest('fecha_confirmacion')
            ->first();

        if (!$matriculaActiva || !$matriculaActiva->ofertaAcademica?->nivelAcademico?->versionPlanEstudio) {
            return response()->json([
                'resultado' => 'A',
                'codigo' => 0,
                'mensaje' => 'OK',
                'data' => null,
            ]);
        }

        $versionPlan = $matriculaActiva->ofertaAcademica->nivelAcademico->versionPlanEstudio;
        $plan = $versionPlan->planEstudio;

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => [
                'plan_estudio_id' => $plan->id,
                'plan_codigo' => $plan->codigo,
                'plan_nombre' => $plan->nombre,
                'version_plan_estudio_id' => $versionPlan->id,
            ],
        ]);
    }
}
