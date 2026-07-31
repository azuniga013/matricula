<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::macro('apiResourceProtegido', function (string $nombre, string $controlador, string $modulo, array $opciones = []) {
            $accionesPermiso = [
                'index' => 'consultar',
                'show' => 'consultar',
                'store' => 'crear',
                'update' => 'modificar',
                'destroy' => 'eliminar',
            ];

            $soloAcciones = $opciones['only'] ?? ['index', 'store', 'show', 'update', 'destroy'];

            $parametros = $opciones['parameters'][$nombre] ?? str($nombre)->singular()->toString();

            foreach ($soloAcciones as $accion) {
                $permiso = $accionesPermiso[$accion] ?? null;
                $mws = $permiso ? ['permission:' . $modulo . '.' . $permiso] : [];

                match ($accion) {
                    'index' => Route::get($nombre, [$controlador, 'index'])
                        ->name("{$nombre}.index")
                        ->middleware($mws),
                    'store' => Route::post($nombre, [$controlador, 'store'])
                        ->name("{$nombre}.store")
                        ->middleware($mws),
                    'show' => Route::get("{$nombre}/{{$parametros}}", [$controlador, 'show'])
                        ->name("{$nombre}.show")
                        ->middleware($mws),
                    'update' => Route::match(['PUT', 'PATCH'], "{$nombre}/{{$parametros}}", [$controlador, 'update'])
                        ->name("{$nombre}.update")
                        ->middleware($mws),
                    'destroy' => Route::delete("{$nombre}/{{$parametros}}", [$controlador, 'destroy'])
                        ->name("{$nombre}.destroy")
                        ->middleware($mws),
                    default => null,
                };
            }
        });

        Route::macro('accionProtegida', function (string $metodo, string $uri, array $accion, string $permiso) {
            return Route::match([$metodo], $uri, $accion)
                ->middleware('permission:' . $permiso);
        });
    }
}
