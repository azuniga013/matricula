<?php

namespace App\Http\Middleware;

use App\Services\CachePermisosService;
use App\Services\ServicioBitacora;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermiso
{
    public function __construct(
        protected CachePermisosService $cachePermisos,
        protected ServicioBitacora $bitacora,
    ) {}

    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 401,
                'mensaje' => 'No autenticado',
            ], 401);
        }

        if ($usuario->estado !== 'activo') {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 403,
                'mensaje' => 'Usuario inactivo',
            ], 403);
        }

        if ($usuario->estaBloqueado()) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 423,
                'mensaje' => 'Usuario bloqueado temporalmente',
            ], 423);
        }

        $permisos = $this->cachePermisos->obtenerPermisos($usuario);

        if (!$permisos->contains('codigo', $permiso)) {
            $this->bitacora->registrarDenegacion(
                $usuario->id,
                $permiso,
                $request->ip(),
                $request->userAgent()
            );

            return response()->json([
                'resultado' => 'R',
                'codigo' => 403,
                'mensaje' => 'No tiene permiso para realizar esta acción',
            ], 403);
        }

        return $next($request);
    }
}
