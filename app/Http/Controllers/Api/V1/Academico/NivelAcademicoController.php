<?php

namespace App\Http\Controllers\Api\V1\Academico;

use App\Http\Controllers\Controller;
use App\Models\NivelAcademico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NivelAcademicoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = NivelAcademico::ordenados()
            ->with(['versionPlanEstudio.planEstudio.departamentoAcademico', 'regimenAcademico', 'modalidades', 'prerrequisitos']);

        if ($request->filled('version_plan_estudio_id')) {
            $query->where('version_plan_estudio_id', $request->version_plan_estudio_id);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%");
            });
        }

        $niveles = $query->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $niveles,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $versionId = $request->input('version_plan_estudio_id');

        $validados = $request->validate([
            'version_plan_estudio_id' => [
                'required', 'integer',
                Rule::exists('versiones_plan_estudio', 'id')->where('estado', 'activo'),
            ],
            'regimen_academico_id' => 'required|exists:modalidades,id',
            'codigo' => [
                'required', 'string', 'max:50',
                Rule::unique('niveles_academicos', 'codigo')
                    ->where('version_plan_estudio_id', $versionId),
            ],
            'orden' => [
                'required', 'integer', 'min:1',
                Rule::unique('niveles_academicos', 'orden')
                    ->where('version_plan_estudio_id', $versionId),
            ],
            'nombre' => 'required|string|max:150',
            'nota_minima_aprobar' => 'required|integer|min:0|max:100',
            'faltas_maximas_permitidas' => 'required|integer|min:0',
            'modalidades' => 'nullable|array|min:1',
            'modalidades.*' => [
                'integer', 'distinct',
                Rule::exists('modalidades', 'id')->where('estado', 'activo'),
            ],
            'prerrequisitos' => 'nullable|array',
            'prerrequisitos.*' => [
                'integer', 'distinct',
                Rule::exists('niveles_academicos', 'id'),
            ],
        ]);

        $errores = $this->validarPrerrequisitos($validados, $request->user());
        if (!empty($errores)) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'Error en los prerrequisitos',
                'errores' => $errores,
            ], 422);
        }

        $nivel = DB::transaction(function () use ($validados, $request) {
            $datos = $validados;
            $modalidades = $datos['modalidades'] ?? [];
            $prerrequisitos = $datos['prerrequisitos'] ?? [];
            unset($datos['modalidades'], $datos['prerrequisitos']);

            $datos['creado_por'] = $request->user()->id;

            $nivel = NivelAcademico::create($datos);

            if (!empty($modalidades)) {
                $nivel->modalidades()->sync($modalidades);
            }

            if (!empty($prerrequisitos)) {
                $nivel->prerrequisitos()->sync($prerrequisitos);
            }

            return $nivel;
        });

        $nivel->load(['versionPlanEstudio.planEstudio.departamentoAcademico', 'regimenAcademico', 'modalidades', 'prerrequisitos']);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Nivel académico creado exitosamente',
            'data' => $nivel,
        ], 201);
    }

    public function show(NivelAcademico $nivelAcademico): JsonResponse
    {
        $nivelAcademico->load([
            'versionPlanEstudio.planEstudio.departamentoAcademico',
            'regimenAcademico',
            'modalidades',
            'prerrequisitos',
            'esPrerequisitoDe',
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $nivelAcademico,
        ]);
    }

    public function update(Request $request, NivelAcademico $nivelAcademico): JsonResponse
    {
        $versionId = $nivelAcademico->version_plan_estudio_id;

        if ($request->has('codigo') && $request->codigo !== $nivelAcademico->codigo) {
            $tieneHistorial = \App\Models\HistorialAcademico::where('nivel_academico_id', $nivelAcademico->id)->exists();
            if ($tieneHistorial) {
                return response()->json([
                    'resultado' => 'R',
                    'codigo' => 422,
                    'mensaje' => 'No se puede cambiar el código porque el nivel tiene historial académico asociado',
                    'errores' => ['codigo' => ['El código no puede modificarse si ya existen registros académicos']],
                ], 422);
            }
        }

        $validados = $request->validate([
            'regimen_academico_id' => 'sometimes|required|exists:modalidades,id',
            'codigo' => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('niveles_academicos', 'codigo')
                    ->where('version_plan_estudio_id', $versionId)
                    ->ignore($nivelAcademico),
            ],
            'nombre' => 'required|string|max:150',
            'orden' => [
                'required', 'integer', 'min:1',
                Rule::unique('niveles_academicos', 'orden')
                    ->where('version_plan_estudio_id', $versionId)
                    ->ignore($nivelAcademico),
            ],
            'nota_minima_aprobar' => 'required|integer|min:0|max:100',
            'faltas_maximas_permitidas' => 'required|integer|min:0',
            'estado' => 'sometimes|string|in:activo,inactivo',
            'modalidades' => 'nullable|array',
            'modalidades.*' => [
                'integer', 'distinct',
                Rule::exists('modalidades', 'id')->where('estado', 'activo'),
            ],
            'prerrequisitos' => 'nullable|array',
            'prerrequisitos.*' => [
                'integer', 'distinct',
                Rule::exists('niveles_academicos', 'id'),
            ],
        ]);

        $errores = $this->validarPrerrequisitos($validados + ['version_plan_estudio_id' => $versionId], $request->user(), $nivelAcademico);
        if (!empty($errores)) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'Error en los prerrequisitos',
                'errores' => $errores,
            ], 422);
        }

        DB::transaction(function () use ($validados, $nivelAcademico, $request) {
            $datos = $validados;
            $modalidades = $datos['modalidades'] ?? null;
            $prerrequisitos = $datos['prerrequisitos'] ?? null;
            unset($datos['modalidades'], $datos['prerrequisitos']);

            $datos['actualizado_por'] = $request->user()->id;

            $nivelAcademico->update($datos);

            if ($modalidades !== null) {
                $nivelAcademico->modalidades()->sync($modalidades);
            }

            if ($prerrequisitos !== null) {
                $nivelAcademico->prerrequisitos()->sync($prerrequisitos);
            }
        });

        $nivelAcademico->load(['versionPlanEstudio.planEstudio.departamentoAcademico', 'regimenAcademico', 'modalidades', 'prerrequisitos']);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Nivel académico actualizado exitosamente',
            'data' => $nivelAcademico,
        ]);
    }

    public function destroy(NivelAcademico $nivelAcademico): JsonResponse
    {
        $nivelAcademico->update([
            'estado' => 'inactivo',
            'actualizado_por' => request()->user()->id,
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Nivel académico desactivado correctamente',
        ]);
    }

    private function validarPrerrequisitos(array $validados, $usuario, ?NivelAcademico $nivelActual = null): array
    {
        $prerrequisitoIds = $validados['prerrequisitos'] ?? [];
        if (empty($prerrequisitoIds)) {
            return [];
        }

        $errores = [];
        $versionId = $validados['version_plan_estudio_id'];
        $nivelId = $nivelActual?->id;
        $ordenActual = $validados['orden'] ?? $nivelActual?->orden ?? 0;

        $prerrequisitos = NivelAcademico::whereIn('id', $prerrequisitoIds)->get();

        if ($nivelId && $prerrequisitos->contains('id', $nivelId)) {
            $errores['prerrequisitos'][] = 'Un nivel no puede ser prerrequisito de sí mismo.';
        }

        if ($prerrequisitos->contains(fn($n) => $n->version_plan_estudio_id !== (int) $versionId)) {
            $errores['prerrequisitos'][] = 'Los prerrequisitos deben pertenecer a la misma versión del plan de estudio.';
        }

        if ($prerrequisitos->contains(fn($n) => $n->orden >= (int) $ordenActual)) {
            $errores['prerrequisitos'][] = 'Todo prerrequisito debe tener un orden académico menor al nivel actual.';
        }

        return $errores;
    }
}
