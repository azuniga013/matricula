<?php

namespace Tests\Feature;

use App\Models\Modulo;
use App\Models\OpcionModulo;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\User;
use Tests\TestCase;

class RolTest extends TestCase
{
    protected User $admin;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearPermisosBase();

        $rolAdmin = Rol::create(['codigo' => 'SUPERADMIN', 'nombre' => 'Super Admin', 'estado' => 'activo']);
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $this->admin->roles()->attach($rolAdmin->id, ['estado' => 'activo']);
        $this->admin->roles->first()->permisos()->sync([1, 2, 3, 4]);
        $this->token = $this->admin->createToken('test')->plainTextToken;
    }

    protected function crearPermisosBase(): void
    {
        $modulo = Modulo::create(['codigo' => 'seguridad', 'nombre' => 'Seguridad', 'estado' => 'activo', 'orden' => 1]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'seguridad.roles', 'nombre' => 'Roles', 'estado' => 'activo']);
        Permiso::create(['opcion_modulo_id' => $opcion->id, 'codigo' => 'seguridad.consultar', 'nombre' => 'Consultar', 'accion' => 'consultar', 'estado' => 'activo']);
        Permiso::create(['opcion_modulo_id' => $opcion->id, 'codigo' => 'seguridad.crear', 'nombre' => 'Crear', 'accion' => 'crear', 'estado' => 'activo']);
        Permiso::create(['opcion_modulo_id' => $opcion->id, 'codigo' => 'seguridad.modificar', 'nombre' => 'Modificar', 'accion' => 'modificar', 'estado' => 'activo']);
        Permiso::create(['opcion_modulo_id' => $opcion->id, 'codigo' => 'seguridad.configurar', 'nombre' => 'Configurar', 'accion' => 'configurar', 'estado' => 'activo']);
    }

    public function test_crear_rol(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/seguridad/roles', [
                'codigo' => 'NUEVO_ROL',
                'nombre' => 'Rol Nuevo',
                'descripcion' => 'Un rol de prueba',
            ]);

        $response->assertCreated()
            ->assertJson([
                'resultado' => 'A',
                'mensaje' => 'Rol creado exitosamente',
            ]);

        $this->assertDatabaseHas('roles', ['codigo' => 'NUEVO_ROL']);
    }

    public function test_listar_roles(): void
    {
        Rol::create(['codigo' => 'ROL1', 'nombre' => 'R1', 'estado' => 'activo']);
        Rol::create(['codigo' => 'ROL2', 'nombre' => 'R2', 'estado' => 'activo']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/seguridad/roles');

        $response->assertOk()
            ->assertJson(['resultado' => 'A']);
    }

    public function test_asignar_permisos_a_rol(): void
    {
        $rol = Rol::create(['codigo' => 'ROL_PERMISOS', 'nombre' => 'Rol Permisos', 'estado' => 'activo']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/v1/seguridad/roles/{$rol->id}/permisos", [
                'permisos' => ['seguridad.consultar', 'seguridad.crear'],
            ]);

        $response->assertOk()
            ->assertJson([
                'resultado' => 'A',
                'mensaje' => 'Permisos asignados exitosamente',
            ]);

        $this->assertDatabaseHas('rol_permisos', [
            'rol_id' => $rol->id,
            'permiso_id' => 1,
            'estado' => 'activo',
        ]);
    }
}
