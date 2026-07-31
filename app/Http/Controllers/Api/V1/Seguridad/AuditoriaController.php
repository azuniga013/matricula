<?php

namespace App\Http\Controllers\Api\V1\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\BitacoraCorreo;
use App\Models\BitacoraPeticion;
use App\Models\BitacoraSeguridad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function peticiones(Request $request): JsonResponse
    {
        $query = BitacoraPeticion::with('usuario');

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->filled('metodo')) {
            $query->where('metodo', strtoupper($request->metodo));
        }

        if ($request->filled('estado_http')) {
            $query->where('estado_http', $request->estado_http);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('created_at', '<=', $request->fecha_hasta);
        }

        if ($request->filled('busqueda')) {
            $query->where('ruta', 'like', "%{$request->busqueda}%");
        }

        $peticiones = $query->orderByDesc('created_at')
            ->paginate(50);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $peticiones,
        ]);
    }

    public function seguridad(Request $request): JsonResponse
    {
        $query = BitacoraSeguridad::with('usuario');

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->filled('accion')) {
            $query->where('accion', $request->accion);
        }

        if ($request->filled('resultado')) {
            $query->where('resultado', $request->resultado);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('created_at', '<=', $request->fecha_hasta);
        }

        $registros = $query->orderByDesc('created_at')
            ->paginate(50);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $registros,
        ]);
    }

    public function correos(Request $request): JsonResponse
    {
        $query = BitacoraCorreo::query();

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('destinatario')) {
            $query->where('destinatario', 'like', "%{$request->destinatario}%");
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('creado_en', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('creado_en', '<=', $request->fecha_hasta);
        }

        $correos = $query->orderByDesc('creado_en')
            ->paginate(50);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $correos,
        ]);
    }
}
