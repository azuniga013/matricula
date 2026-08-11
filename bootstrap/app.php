<?php

use App\Http\Middleware\AutenticarEstudiante;
use App\Http\Middleware\RegistrarPeticion;
use App\Http\Middleware\ValidarSesionAdministrativa;
use App\Http\Middleware\VerificarPermiso;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

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
            'admin.session' => ValidarSesionAdministrativa::class,
            'permission' => VerificarPermiso::class,
            'log.peticion' => RegistrarPeticion::class,
            'auth.estudiante' => AutenticarEstudiante::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (Throwable $e) {
            if ($e instanceof NotFoundHttpException) {
                return new JsonResponse([
                    'resultado' => 'R',
                    'codigo' => 404,
                    'mensaje' => 'Recurso no encontrado',
                ], 404);
            }
            if ($e instanceof AccessDeniedHttpException) {
                return new JsonResponse([
                    'resultado' => 'R',
                    'codigo' => 403,
                    'mensaje' => 'No tiene permiso para realizar esta acción',
                ], 403);
            }
            if ($e instanceof MethodNotAllowedHttpException) {
                return new JsonResponse([
                    'resultado' => 'R',
                    'codigo' => 405,
                    'mensaje' => 'Método no permitido',
                ], 405);
            }
            if ($e instanceof ValidationException) {
                return new JsonResponse([
                    'resultado' => 'R',
                    'codigo' => 422,
                    'mensaje' => 'Error de validación',
                    'errores' => $e->errors(),
                ], 422);
            }
            if ($e instanceof AuthenticationException) {
                return new JsonResponse([
                    'resultado' => 'R',
                    'codigo' => 401,
                    'mensaje' => 'No autenticado',
                ], 401);
            }
            if ($e instanceof TooManyRequestsHttpException) {
                return new JsonResponse([
                    'resultado' => 'R',
                    'codigo' => 429,
                    'mensaje' => 'Demasiadas peticiones. Intente más tarde',
                ], 429);
            }

            return null;
        });
    })->create();
