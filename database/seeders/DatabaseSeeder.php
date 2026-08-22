<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SucursalSeeder::class,
            RolSeeder::class,
            SeguridadRbacSeeder::class,
            UsuarioAdminSeeder::class,
            ModalidadSeeder::class,
            CatalogoAcademicoSeeder::class,
            ConceptoPagoSeeder::class,
            ProveedorPagoSeeder::class,
            MetodoPagoSeeder::class,
            PlanCobroSeeder::class,
            OfertaAcademicaSeeder::class,
            EstudianteSeeder::class,
            TipoGestionMatriculaSeeder::class,
            CuentaBancariaSeeder::class,
            EnlacePagoSeeder::class,
            ReglaAprobacionSeeder::class,
            LibroSeeder::class,
            ConfiguracionFlujoMatriculaSeeder::class,
            ParametroGlobalSeeder::class,
        ]);
    }
}
