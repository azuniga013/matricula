<?php

namespace Tests\Feature;

use App\Models\BitacoraSeguridad;
use App\Models\Modulo;
use App\Models\OpcionModulo;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\User;
use Tests\TestCase;

class AuditoriaTest extends TestCase
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
        $this->admin->roles->first()->permisos()->sync([1]);
        $this->token = $this->admin->createToken('test')->plainTextToken;
    }

    protected function crearPermisosBase(): void
    {
        $modulo = Modulo::create(['codigo' => 'seguridad', 'nombre' => 'Seguridad', 'estado' => 'activo', 'orden' => 1]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'seguridad.auditoria', 'nombre' => 'Auditoría', 'estado' => 'activo']);
        Permiso::create(['opcion_modulo_id' => $opcion->id, 'codigo' => 'seguridad.consultar', 'nombre' => 'Consultar', 'accion' => 'consultar', 'estado' => 'activo']);
    }

    public function test_consultar_bitacora_seguridad(): void
    {
        BitacoraSeguridad::create([
            'usuario_id' => $this->admin->id,
            'accion' => 'login',
            'resultado' => 'exitoso',
            'ip' => '127.0.0.1',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/seguridad/auditoria/seguridad');

        $response->assertOk()
            ->assertJson(['resultado' => 'A']);
    }

    public function test_bitacora_registra_denegacion(): void
    {
        $user = User::create([
            'name' => 'No Perm User',
            'email' => 'noperm@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/seguridad/modulos');

        $response->assertStatus(403);

        $this->assertDatabaseHas('bitacora_seguridad', [
            'usuario_id' => $user->id,
            'accion' => 'denegacion_permiso',
            'resultado' => 'rechazado',
        ]);
    }
}
