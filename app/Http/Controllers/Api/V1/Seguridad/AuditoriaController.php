<?php

namespace App\Http\Controllers\Api\V1\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\BitacoraCorreo;
use App\Models\BitacoraAuditoria;
use App\Models\BitacoraPeticion;
use App\Models\BitacoraSeguridad;
use Symfony\Component\HttpFoundation\StreamedResponse;
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

    public function operaciones(Request $request): JsonResponse
    {
        $query = BitacoraAuditoria::with('usuario');

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }
        if ($request->filled('modulo')) {
            $query->where('modulo', $request->modulo);
        }
        if ($request->filled('accion')) {
            $query->where('accion', $request->accion);
        }
        if ($request->filled('entidad_tipo')) {
            $query->where('entidad_tipo', $request->entidad_tipo);
        }
        if ($request->filled('entidad_id')) {
            $query->where('entidad_id', $request->entidad_id);
        }
        if ($request->filled('fecha_desde')) {
            $query->where('creado_en', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->where('creado_en', '<=', $request->fecha_hasta);
        }

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $registros = $query->orderByDesc('creado_en')->paginate($perPage);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $registros,
        ]);
    }

    public function entidad(Request $request): JsonResponse
    {
        $request->validate([
            'entidad_tipo' => 'required|string|max:120',
            'entidad_id' => 'required|integer|min:1',
        ]);

        $registros = BitacoraAuditoria::with('usuario')
            ->where('entidad_tipo', $request->entidad_tipo)
            ->where('entidad_id', $request->entidad_id)
            ->orderByDesc('creado_en')
            ->limit(20)
            ->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $registros,
        ]);
    }

    public function exportarOperaciones(Request $request): StreamedResponse
    {
        $query = BitacoraAuditoria::with('usuario');

        if ($request->filled('usuario_id')) $query->where('usuario_id', $request->usuario_id);
        if ($request->filled('modulo')) $query->where('modulo', $request->modulo);
        if ($request->filled('accion')) $query->where('accion', $request->accion);
        if ($request->filled('entidad_tipo')) $query->where('entidad_tipo', $request->entidad_tipo);
        if ($request->filled('entidad_id')) $query->where('entidad_id', $request->entidad_id);
        if ($request->filled('fecha_desde')) $query->where('creado_en', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta')) $query->where('creado_en', '<=', $request->fecha_hasta);

        $registros = $query->orderByDesc('creado_en')->limit(5000)->get();

        return response()->streamDownload(function () use ($registros) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['fecha', 'usuario', 'modulo', 'accion', 'entidad_tipo', 'entidad_id', 'descripcion', 'valores_antes', 'valores_despues']);
            foreach ($registros as $item) {
                fputcsv($handle, [
                    optional($item->creado_en)?->format('Y-m-d H:i:s'),
                    $item->usuario?->name ?? 'Sistema',
                    $item->modulo,
                    $item->accion,
                    $item->entidad_tipo,
                    $item->entidad_id,
                    $item->descripcion,
                    $item->valores_antes ? json_encode($item->valores_antes, JSON_UNESCAPED_UNICODE) : '',
                    $item->valores_despues ? json_encode($item->valores_despues, JSON_UNESCAPED_UNICODE) : '',
                ]);
            }
            fclose($handle);
        }, 'bitacora_auditoria.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
