<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->alias([
            'permission' => \App\Http\Middleware\VerificarPermiso::class,
            'log.peticion' => \App\Http\Middleware\RegistrarPeticion::class,
            'auth.estudiante' => \App\Http\Middleware\AutenticarEstudiante::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (\Throwable $e) {
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return new JsonResponse([
                    'resultado' => 'R',
                    'codigo' => 404,
                    'mensaje' => 'Recurso no encontrado',
                ], 404);
            }
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
                return new JsonResponse([
                    'resultado' => 'R',
                    'codigo' => 403,
                    'mensaje' => 'No tiene permiso para realizar esta acción',
                ], 403);
            }
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                return new JsonResponse([
                    'resultado' => 'R',
                    'codigo' => 405,
                    'mensaje' => 'Método no permitido',
                ], 405);
            }
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return new JsonResponse([
                    'resultado' => 'R',
                    'codigo' => 422,
                    'mensaje' => 'Error de validación',
                    'errores' => $e->errors(),
                ], 422);
            }
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return new JsonResponse([
                    'resultado' => 'R',
                    'codigo' => 401,
                    'mensaje' => 'No autenticado',
                ], 401);
            }
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException) {
                return new JsonResponse([
                    'resultado' => 'R',
                    'codigo' => 429,
                    'mensaje' => 'Demasiadas peticiones. Intente más tarde',
                ], 429);
            }
            return null;
        });
    })->create();
