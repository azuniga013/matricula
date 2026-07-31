<?php

namespace App\Http\Middleware;

use App\Models\AccesoEstudiante;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutenticarEstudiante
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 401,
                'mensaje' => 'Token de autenticación requerido',
                'codigo_error' => '401_NO_AUTENTICADO_ESTUDIANTE',
            ], 401);
        }

        $acceso = AccesoEstudiante::where('token', hash('sha256', $token))
            ->where('estado', 'activo')
            ->with('estudiante')
            ->first();

        if (!$acceso) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 401,
                'mensaje' => 'Sesión inválida o expirada',
                'codigo_error' => '401_TOKEN_INVALIDO',
            ], 401);
        }

        if ($acceso->estudiante->estado !== 'activo') {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 403,
                'mensaje' => 'Su cuenta está inactiva',
                'codigo_error' => '403_CUENTA_INACTIVA',
            ], 403);
        }

        $request->attributes->set('acceso_estudiante', $acceso);
        $request->attributes->set('estudiante', $acceso->estudiante);

        return $next($request);
    }
}
