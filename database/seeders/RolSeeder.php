<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['codigo' => 'SUPERADMIN', 'nombre' => 'Superadministrador', 'descripcion' => 'Acceso total al sistema'],
            ['codigo' => 'ADMIN_GENERAL', 'nombre' => 'Administrador General', 'descripcion' => 'Administrador con acceso amplio'],
            ['codigo' => 'ADMIN_OPERATIVO', 'nombre' => 'Administrador Operativo', 'descripcion' => 'Opera usuarios y procesos diarios sin acceso a configuración sensible'],
            ['codigo' => 'ADMIN_ACADEMICO', 'nombre' => 'Administrador Académico', 'descripcion' => 'Configura catálogos y ofertas académicas sin acceso a seguridad sensible'],
            ['codigo' => 'ADMIN_SUCURSAL', 'nombre' => 'Administrador de Sucursal', 'descripcion' => 'Administrador limitado a su sucursal'],
            ['codigo' => 'CAJA', 'nombre' => 'Caja', 'descripcion' => 'Operaciones de caja y recibos'],
            ['codigo' => 'MATRICULA', 'nombre' => 'Matrícula', 'descripcion' => 'Gestión de matrículas'],
            ['codigo' => 'DOCENTE', 'nombre' => 'Docente', 'descripcion' => 'Acceso a asistencia y calificaciones'],
            ['codigo' => 'ALUMNO', 'nombre' => 'Alumno', 'descripcion' => 'Portal del estudiante'],
            ['codigo' => 'AUDITORIA', 'nombre' => 'Consulta o Auditoría', 'descripcion' => 'Solo consulta y reportes'],
            ['codigo' => 'SUPERVISOR', 'nombre' => 'Supervisor', 'descripcion' => 'Supervisión global de docentes'],
        ];

        foreach ($roles as $datos) {
            Rol::updateOrCreate(
                ['codigo' => $datos['codigo']],
                array_merge($datos, ['estado' => 'activo'])
            );
        }
    }
}
