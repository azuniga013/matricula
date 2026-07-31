<?php

namespace Database\Seeders;

use App\Models\RolPermiso;
use App\Models\Rol;
use App\Models\Permiso;
use App\Services\RegistroPermisosService;
use Illuminate\Database\Seeder;

class SeguridadRbacSeeder extends Seeder
{
    public function run(): void
    {
        $service = new RegistroPermisosService();
        $service->registrarModulosDesdeConfig();

        $rolSuperadmin = Rol::where('codigo', 'SUPERADMIN')->first();
        if ($rolSuperadmin) {
            $todosPermisos = Permiso::activos()->get();
            foreach ($todosPermisos as $permiso) {
                RolPermiso::updateOrCreate(
                    ['rol_id' => $rolSuperadmin->id, 'permiso_id' => $permiso->id],
                    ['estado' => 'activo']
                );
            }
        }

        foreach (['SUPERADMIN', 'ADMIN_GENERAL'] as $codigoRol) {
            $rol = Rol::where('codigo', $codigoRol)->first();
            $permisoEliminarPago = Permiso::where('codigo', 'pagos.eliminar')->first();
            if ($rol && $permisoEliminarPago) {
                RolPermiso::updateOrCreate(
                    ['rol_id' => $rol->id, 'permiso_id' => $permisoEliminarPago->id],
                    ['estado' => 'activo']
                );
            }
        }

        $rolAdminGeneral = Rol::where('codigo', 'ADMIN_GENERAL')->first();
        if ($rolAdminGeneral) {
            $permisosAdmin = Permiso::where(function ($q) {
                $q->where('codigo', 'like', 'pagos.%')
                  ->orWhere('codigo', 'like', 'reportes.%')
                  ->orWhere('codigo', 'like', 'inventario.%')
                  ->orWhere('codigo', 'like', 'configuracion.%')
                  ->orWhere('codigo', 'like', 'seguridad.parametros.%');
            })->get();
            foreach ($permisosAdmin as $permiso) {
                RolPermiso::updateOrCreate(
                    ['rol_id' => $rolAdminGeneral->id, 'permiso_id' => $permiso->id],
                    ['estado' => 'activo']
                );
            }
        }
    }
}
