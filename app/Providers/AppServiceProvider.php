<?php

namespace App\Providers;

use App\Services\Pagos\FabricaProcesadorPago;
use App\Services\Pagos\PayPalProcesador;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\CachePermisosService::class);
        $this->app->singleton(\App\Services\ServicioBitacora::class);
        $this->app->singleton(\App\Services\ResolutorAlcanceDatos::class, function ($app) {
            return new \App\Services\ResolutorAlcanceDatos(
                $app->make(\App\Services\CachePermisosService::class)
            );
        });
    }

    public function boot(): void
    {
        FabricaProcesadorPago::registrar('PAYPAL', PayPalProcesador::class);
    }
}
