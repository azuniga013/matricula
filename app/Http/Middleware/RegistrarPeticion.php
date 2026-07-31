<?php

namespace App\Http\Middleware;

use App\Services\ServicioBitacora;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegistrarPeticion
{
    public function __construct(
        protected ServicioBitacora $bitacora,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $inicio = microtime(true);

        $response = $next($request);

        $duracionMs = (int) ((microtime(true) - $inicio) * 1000);

        try {
            $this->bitacora->registrarPeticion($request, $response->getStatusCode(), $duracionMs);
        } catch (\Throwable $e) {
            // No falla la petición por un error de bitácora
        }

        return $response;
    }
}
