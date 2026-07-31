<?php

namespace App\Http\Controllers\Api\V1\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\SesionUsuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SesionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sesiones = SesionUsuario::where('usuario_id', $request->user()->id)
            ->orderByDesc('creado_en')
            ->limit(50)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'ip' => $s->ip,
                'agente' => $s->agente,
                'vencimiento' => $s->vencimiento,
                'revocado_en' => $s->revocado_en,
                'ultimo_acceso' => $s->ultimo_acceso,
                'activa' => $s->estaActiva(),
            ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $sesiones,
        ]);
    }

    public function revocar(Request $request, int $sesionId): JsonResponse
    {
        $sesion = SesionUsuario::where('id', $sesionId)
            ->where('usuario_id', $request->user()->id)
            ->firstOrFail();

        $sesion->update(['revocado_en' => now()]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Sesión revocada exitosamente',
        ]);
    }
}
