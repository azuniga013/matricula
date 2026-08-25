<?php

namespace App\Providers;

use App\Database\Schema\Grammars\PostgresSinTransaccionesGrammar;
use App\Events\AsistenciaNotificableRegistrada;
use App\Listeners\CrearNotificacionesAsistencia;
use App\Modules\Caja\Repositorios\EloquentReciboCajaRepositorio;
use App\Modules\Caja\Repositorios\EloquentSesionCajaRepositorio;
use App\Modules\Caja\Repositorios\ReciboCajaRepositorio;
use App\Modules\Caja\Repositorios\SesionCajaRepositorio;
use App\Modules\Calificaciones\Repositorios\CalificacionRepositorio;
use App\Modules\Calificaciones\Repositorios\EloquentCalificacionRepositorio;
use App\Modules\Inventario\Repositorios\EloquentInventarioRepositorio;
use App\Modules\Inventario\Repositorios\InventarioRepositorio;
use App\Modules\Matriculas\Repositorios\EloquentMatriculaRepositorio;
use App\Modules\Matriculas\Repositorios\MatriculaRepositorio;
use App\Modules\Pagos\Repositorios\EloquentPagoRepositorio;
use App\Modules\Pagos\Repositorios\PagoRepositorio;
use App\Services\CachePermisosService;
use App\Services\ConfiguracionBitacorasService;
use App\Services\Pagos\FabricaProcesadorPago;
use App\Services\Pagos\PayPalProcesador;
use App\Services\RegistroBitacoraCorreoService;
use App\Services\ResolutorAlcanceDatos;
use App\Services\ServicioBitacora;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CachePermisosService::class);
        $this->app->singleton(ConfiguracionBitacorasService::class);
        $this->app->singleton(RegistroBitacoraCorreoService::class);
        $this->app->singleton(ServicioBitacora::class);
        $this->app->singleton(ResolutorAlcanceDatos::class, function ($app) {
            return new ResolutorAlcanceDatos(
                $app->make(CachePermisosService::class)
            );
        });
        $this->app->singleton(PagoRepositorio::class, EloquentPagoRepositorio::class);
        $this->app->singleton(MatriculaRepositorio::class, EloquentMatriculaRepositorio::class);
        $this->app->singleton(CalificacionRepositorio::class, EloquentCalificacionRepositorio::class);
        $this->app->singleton(SesionCajaRepositorio::class, EloquentSesionCajaRepositorio::class);
        $this->app->singleton(ReciboCajaRepositorio::class, EloquentReciboCajaRepositorio::class);
        $this->app->singleton(InventarioRepositorio::class, EloquentInventarioRepositorio::class);
    }

    public function boot(): void
    {
        FabricaProcesadorPago::registrar('PAYPAL', PayPalProcesador::class);
        Event::listen(AsistenciaNotificableRegistrada::class, CrearNotificacionesAsistencia::class);

        if ($this->app->runningInConsole() && config('database.default') === 'pgsql') {
            $conexion = DB::connection();
            $conexion->setSchemaGrammar(new PostgresSinTransaccionesGrammar($conexion));
        }
    }
}
