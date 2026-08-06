<?php

namespace Tests\Feature;

use App\Models\Rol;
use Database\Seeders\RolSeeder;
use Database\Seeders\SeguridadRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeguridadRbacSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_rol_docente_recibe_permisos_minimos_academicos(): void
    {
        $this->seed([RolSeeder::class, SeguridadRbacSeeder::class]);

        $permisos = Rol::where('codigo', 'DOCENTE')->firstOrFail()
            ->permisos()
            ->pluck('codigo')
            ->all();

        $this->assertEqualsCanonicalizing([
            'asistencias.consultar',
            'asistencias.crear',
            'calificaciones.consultar',
            'calificaciones.crear',
            'calificaciones.modificar',
        ], array_values(array_intersect($permisos, [
            'asistencias.consultar',
            'asistencias.crear',
            'calificaciones.consultar',
            'calificaciones.crear',
            'calificaciones.modificar',
        ])));
    }
}
