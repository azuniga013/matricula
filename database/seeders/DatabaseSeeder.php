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
            OfertaAcademicaSeeder::class,
            EstudianteSeeder::class,
            ConceptoPagoSeeder::class,
            ProveedorPagoSeeder::class,
            MetodoPagoSeeder::class,
            PlanCobroSeeder::class,
            TipoGestionMatriculaSeeder::class,
            CuentaBancariaSeeder::class,
            EnlacePagoSeeder::class,
            ReglaAprobacionSeeder::class,
            LibroSeeder::class,
            GrupoWhatsappSeeder::class,
            ConfiguracionFlujoMatriculaSeeder::class,
            ParametroGlobalSeeder::class,
        ]);
    }
}
