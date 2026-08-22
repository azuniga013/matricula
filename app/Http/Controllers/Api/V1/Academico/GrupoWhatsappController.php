<?php

namespace App\Http\Controllers\Api\V1\Academico;

use App\Http\Controllers\Controller;
use App\Models\GrupoWhatsapp;
use App\Services\ResolutorAlcanceDatos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GrupoWhatsappController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = GrupoWhatsapp::ordenados()->with('sucursal');
        if ($request->user()) {
            app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, $request->user(), 'grupos_whatsapp');
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('sucursal_id')) {
            $query->where('sucursal_id', $request->sucursal_id);
        }

        if ($request->filled('periodo_academico_id')) {
            $query->whereHas('ofertasAcademicas', fn ($q) => $q->where('periodo_academico_id', $request->periodo_academico_id));
        }

        $grupos = $query->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $grupos,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'sucursal_id' => 'required|exists:sucursales,id',
            'codigo' => 'required|string|max:50|unique:grupos_whatsapp,codigo',
            'nombre' => 'required|string|max:150',
            'link' => 'nullable|string|max:500',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $datos['creado_por'] = $request->user()->id;

        $grupo = GrupoWhatsapp::create($datos);
        $grupo->load('sucursal');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Grupo de WhatsApp creado exitosamente',
            'data' => $grupo,
        ], 201);
    }

    public function show(GrupoWhatsapp $grupoWhatsapp): JsonResponse
    {
        $grupoWhatsapp->load('sucursal')->loadCount('ofertasAcademicas');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $grupoWhatsapp,
        ]);
    }

    public function update(Request $request, GrupoWhatsapp $grupoWhatsapp): JsonResponse
    {
        $datos = $request->validate([
            'sucursal_id' => 'sometimes|exists:sucursales,id',
            'codigo' => 'sometimes|string|max:50|unique:grupos_whatsapp,codigo,' . $grupoWhatsapp->id,
            'nombre' => 'sometimes|string|max:150',
            'link' => 'nullable|string|max:500',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $datos['actualizado_por'] = $request->user()->id;

        $grupoWhatsapp->update($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Grupo de WhatsApp actualizado exitosamente',
            'data' => $grupoWhatsapp,
        ]);
    }

    public function destroy(Request $request, GrupoWhatsapp $grupoWhatsapp): JsonResponse
    {
        if ($grupoWhatsapp->ofertasAcademicas()->exists()) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'No se puede eliminar el grupo porque está siendo utilizado en una o más ofertas académicas.',
            ], 422);
        }

        $grupoWhatsapp->update([
            'estado' => 'inactivo',
            'actualizado_por' => $request->user()->id,
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Grupo de WhatsApp desactivado correctamente.',
        ]);
    }
}
