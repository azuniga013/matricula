<?php

namespace App\Http\Controllers\Api\V1\Academico;

use App\Http\Controllers\Controller;
use App\Models\Horario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HorarioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Horario::activos()->ordenados();

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%");
            });
        }

        $horarios = $query->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $horarios,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => 'required|string|max:50|unique:horarios,codigo',
            'nombre' => 'required|string|max:100',
            'hora_inicio' => 'required|string|regex:/^\d{2}:\d{2}$/',
            'hora_fin' => 'required|string|regex:/^\d{2}:\d{2}$/',
            'es_24_horas' => 'nullable|boolean',
            'lunes' => 'nullable|boolean',
            'martes' => 'nullable|boolean',
            'miercoles' => 'nullable|boolean',
            'jueves' => 'nullable|boolean',
            'viernes' => 'nullable|boolean',
            'sabado' => 'nullable|boolean',
            'domingo' => 'nullable|boolean',
            'descripcion' => 'nullable|string',
        ]);

        $es24Horas = $datos['es_24_horas'] ?? false;

        if (!$es24Horas && $datos['hora_inicio'] >= $datos['hora_fin']) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'La hora fin debe ser mayor que la hora inicio',
                'errores' => ['hora_fin' => ['La hora fin debe ser posterior a la hora inicio']],
            ], 422);
        }

        if (!($datos['lunes'] ?? false) && !($datos['martes'] ?? false) && !($datos['miercoles'] ?? false)
            && !($datos['jueves'] ?? false) && !($datos['viernes'] ?? false) && !($datos['sabado'] ?? false)
            && !($datos['domingo'] ?? false)) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'Debe seleccionar al menos un día',
                'errores' => ['lunes' => ['Debe seleccionar al menos un día']],
            ], 422);
        }

        $datos['creado_por'] = $request->user()->id;
        $datos['lunes'] = $datos['lunes'] ?? false;
        $datos['martes'] = $datos['martes'] ?? false;
        $datos['miercoles'] = $datos['miercoles'] ?? false;
        $datos['jueves'] = $datos['jueves'] ?? false;
        $datos['viernes'] = $datos['viernes'] ?? false;
        $datos['sabado'] = $datos['sabado'] ?? false;
        $datos['domingo'] = $datos['domingo'] ?? false;

        $horario = Horario::create($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Horario creado exitosamente',
            'data' => $horario,
        ], 201);
    }

    public function show(Horario $horario): JsonResponse
    {
        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $horario,
        ]);
    }

    public function update(Request $request, Horario $horario): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => [
                'sometimes', 'string', 'max:50',
                Rule::unique('horarios', 'codigo')->ignore($horario),
            ],
            'nombre' => 'required|string|max:100',
            'hora_inicio' => 'required|string|regex:/^\d{2}:\d{2}$/',
            'hora_fin' => 'required|string|regex:/^\d{2}:\d{2}$/',
            'es_24_horas' => 'nullable|boolean',
            'lunes' => 'nullable|boolean',
            'martes' => 'nullable|boolean',
            'miercoles' => 'nullable|boolean',
            'jueves' => 'nullable|boolean',
            'viernes' => 'nullable|boolean',
            'sabado' => 'nullable|boolean',
            'domingo' => 'nullable|boolean',
            'descripcion' => 'nullable|string',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $es24Horas = $datos['es_24_horas'] ?? $horario->es_24_horas;

        if (!$es24Horas && $datos['hora_inicio'] >= $datos['hora_fin']) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'La hora fin debe ser mayor que la hora inicio',
                'errores' => ['hora_fin' => ['La hora fin debe ser posterior a la hora inicio']],
            ], 422);
        }

        $datos['actualizado_por'] = $request->user()->id;
        $datos['lunes'] = $datos['lunes'] ?? false;
        $datos['martes'] = $datos['martes'] ?? false;
        $datos['miercoles'] = $datos['miercoles'] ?? false;
        $datos['jueves'] = $datos['jueves'] ?? false;
        $datos['viernes'] = $datos['viernes'] ?? false;
        $datos['sabado'] = $datos['sabado'] ?? false;
        $datos['domingo'] = $datos['domingo'] ?? false;

        $horario->update($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Horario actualizado exitosamente',
            'data' => $horario,
        ]);
    }
}
