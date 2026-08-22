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
                  ->orWhere('codigo', 'like', 'distribucion_apk.%')
                  ->orWhere('codigo', 'like', 'seguridad.parametros.%');
            })->get();
            foreach ($permisosAdmin as $permiso) {
                RolPermiso::updateOrCreate(
                    ['rol_id' => $rolAdminGeneral->id, 'permiso_id' => $permiso->id],
                    ['estado' => 'activo']
                );
            }
        }

        $rolAdminOperativo = Rol::where('codigo', 'ADMIN_OPERATIVO')->first();
        if ($rolAdminOperativo) {
            $permisosOperativos = Permiso::where(function ($q) {
                $q->where('codigo', 'catalogos_academicos.consultar')
                  ->orWhere('codigo', 'like', 'seguridad.usuarios.%')
                  ->orWhere('codigo', 'like', 'estudiantes.%')
                  ->orWhere('codigo', 'like', 'matriculas.%')
                  ->orWhere('codigo', 'like', 'pagos.%')
                  ->orWhere('codigo', 'like', 'caja.%')
                  ->orWhere('codigo', 'like', 'reportes.%')
                  ->orWhere('codigo', 'like', 'inventario.%')
                  ->orWhere('codigo', 'calificaciones.consultar')
                  ->orWhere('codigo', 'asistencias.consultar');
            })->get();

            foreach ($permisosOperativos as $permiso) {
                RolPermiso::updateOrCreate(
                    ['rol_id' => $rolAdminOperativo->id, 'permiso_id' => $permiso->id],
                    ['estado' => 'activo']
                );
            }
        }

        $rolAdminAcademico = Rol::where('codigo', 'ADMIN_ACADEMICO')->first();
        if ($rolAdminAcademico) {
            $permisosAcademicos = Permiso::where(function ($q) {
                $q->where('codigo', 'catalogos_academicos.consultar')
                  ->orWhere('codigo', 'like', 'catalogos.%')
                  ->orWhere('codigo', 'like', 'ofertas.%')
                  ->orWhere('codigo', 'like', 'calificaciones.%')
                  ->orWhere('codigo', 'like', 'asistencias.%')
                  ->orWhere('codigo', 'like', 'reportes.academicos.%');
            })->get();

            foreach ($permisosAcademicos as $permiso) {
                RolPermiso::updateOrCreate(
                    ['rol_id' => $rolAdminAcademico->id, 'permiso_id' => $permiso->id],
                    ['estado' => 'activo']
                );
            }
        }

        // El docente gestiona únicamente sus ofertas asignadas; ese alcance se
        // valida además en los controladores académicos. Estos son los permisos
        // mínimos que necesita la APK y el pase de lista administrativo.
        $rolDocente = Rol::where('codigo', 'DOCENTE')->first();
        if ($rolDocente) {
            $permisosDocente = Permiso::whereIn('codigo', [
                'asistencias.consultar',
                'asistencias.crear',
                'calificaciones.consultar',
                'calificaciones.crear',
                'calificaciones.modificar',
            ])->get();

            foreach ($permisosDocente as $permiso) {
                RolPermiso::updateOrCreate(
                    ['rol_id' => $rolDocente->id, 'permiso_id' => $permiso->id],
                    ['estado' => 'activo']
                );
            }
        }
    }
}
