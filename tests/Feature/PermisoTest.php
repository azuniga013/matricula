<?php

namespace Tests\Feature;

use App\Models\Modulo;
use App\Models\OpcionModulo;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\SeguridadRbacSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PermisoTest extends TestCase
{
    protected User $usuario;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearDatosBase();

        $rol = Rol::create(['codigo' => 'TEST_ROL', 'nombre' => 'Test', 'estado' => 'activo']);
        $this->usuario = User::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $this->usuario->roles()->attach($rol->id, ['estado' => 'activo']);
        $this->token = $this->usuario->createToken('test')->plainTextToken;
    }

    protected function crearDatosBase(): void
    {
        $modulo = Modulo::create(['codigo' => 'seguridad', 'nombre' => 'Seguridad', 'estado' => 'activo', 'orden' => 1]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'seguridad.modulos', 'nombre' => 'Módulos', 'estado' => 'activo']);
        Permiso::create(['opcion_modulo_id' => $opcion->id, 'codigo' => 'seguridad.consultar', 'nombre' => 'Consultar', 'accion' => 'consultar', 'estado' => 'activo']);
    }

    public function test_acceso_con_permiso(): void
    {
        $this->usuario->roles->first()->permisos()->attach(1, ['estado' => 'activo']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/seguridad/modulos');

        $response->assertOk();
    }

    public function test_acceso_sin_permiso_devuelve_403(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/seguridad/modulos');

        $response->assertStatus(403)
            ->assertJson([
                'resultado' => 'R',
                'codigo' => 403,
            ]);
    }

    public function test_usuario_inactivo_no_puede_acceder(): void
    {
        $this->usuario->update(['estado' => 'inactivo']);
        $this->usuario->roles->first()->permisos()->attach(1, ['estado' => 'activo']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/seguridad/modulos');

        $response->assertStatus(403)
            ->assertJson([
                'mensaje' => 'Usuario inactivo',
            ]);
    }

    public function test_admin_general_recibe_todos_los_permisos_de_pagos(): void
    {
        Rol::create(['codigo' => 'SUPERADMIN', 'nombre' => 'Superadministrador', 'estado' => 'activo']);
        Rol::create(['codigo' => 'ADMIN_GENERAL', 'nombre' => 'Administrador General', 'estado' => 'activo']);

        $this->seed(SeguridadRbacSeeder::class);

        $rolAdmin = Rol::where('codigo', 'ADMIN_GENERAL')->firstOrFail();
        $permisosPagos = Permiso::where('codigo', 'like', 'pagos.%')->pluck('id');

        $asignados = DB::table('rol_permisos')
            ->where('rol_id', $rolAdmin->id)
            ->whereIn('permiso_id', $permisosPagos)
            ->pluck('permiso_id')
            ->all();

        $this->assertCount($permisosPagos->count(), $asignados);
    }
}
