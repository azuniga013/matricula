<?php

namespace App\Http\Middleware;

use App\Models\SesionUsuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidarSesionAdministrativa
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token) {
            $tokenHash = hash('sha256', $token);
            $sesionRevocada = SesionUsuario::where('token_hash', $tokenHash)
                ->whereNotNull('revocado_en')
                ->latest('id')
                ->first();

            if ($sesionRevocada) {
                return response()->json([
                    'resultado' => 'R',
                    'codigo' => 401,
                    'codigo_error' => '401_SESION_REVOCADA',
                    'mensaje' => 'La sesión fue revocada. Inicie sesión nuevamente.',
                ], 401);
            }
        }

        $response = $next($request);

        $usuario = $request->user();
        if (! $usuario || ! $token || $response->getStatusCode() >= 400) {
            return $response;
        }

        $tokenHash = hash('sha256', $token);
        $sesion = SesionUsuario::firstOrNew([
            'usuario_id' => $usuario->id,
            'token_hash' => $tokenHash,
        ]);

        if (! $sesion->exists) {
            $sesion->fill([
                'ip' => $request->ip(),
                'agente' => $request->userAgent(),
                'vencimiento' => now()->addMinutes(config('seguridad.sesiones.duracion_minutos', 480)),
            ]);
        }

        $sesion->fill([
            'ip' => $request->ip(),
            'agente' => $request->userAgent(),
            'ultimo_acceso' => now(),
        ]);
        $sesion->save();

        return $response;
    }
}
