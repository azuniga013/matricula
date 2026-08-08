<?php

namespace Tests\Feature;

use App\Models\Docente;
use App\Models\Modulo;
use App\Models\OpcionModulo;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\SesionUsuario;
use App\Models\User;
use Tests\TestCase;

class UsuarioTest extends TestCase
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
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'seguridad.usuarios', 'nombre' => 'Usuarios', 'estado' => 'activo']);
        Permiso::create(['opcion_modulo_id' => $opcion->id, 'codigo' => 'seguridad.consultar', 'nombre' => 'Consultar', 'accion' => 'consultar', 'estado' => 'activo']);
        Permiso::create(['opcion_modulo_id' => $opcion->id, 'codigo' => 'seguridad.crear', 'nombre' => 'Crear', 'accion' => 'crear', 'estado' => 'activo']);
        Permiso::create(['opcion_modulo_id' => $opcion->id, 'codigo' => 'seguridad.modificar', 'nombre' => 'Modificar', 'accion' => 'modificar', 'estado' => 'activo']);
        Permiso::create(['opcion_modulo_id' => $opcion->id, 'codigo' => 'seguridad.configurar', 'nombre' => 'Configurar', 'accion' => 'configurar', 'estado' => 'activo']);
    }

    public function test_listar_usuarios(): void
    {
        for ($i = 0; $i < 3; $i++) {
            User::create([
                'name' => "User $i",
                'email' => "user{$i}@test.com",
                'password' => bcrypt('password'),
                'estado' => 'activo',
            ]);
        }

        SesionUsuario::create([
            'usuario_id' => $this->admin->id,
            'token_hash' => hash('sha256', 'abc'),
            'ip' => '127.0.0.1',
            'vencimiento' => now()->addMinutes(30),
            'ultimo_acceso' => now()->subMinutes(5),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/seguridad/usuarios');

        $response->assertOk()
            ->assertJson(['resultado' => 'A']);

        $usuarios = $response->json('data.data');
        $adminEnLista = collect($usuarios)->firstWhere('id', $this->admin->id);
        $this->assertNotNull($adminEnLista['ultimo_acceso'] ?? null);
        $this->assertNull(collect($usuarios)->firstWhere('email', 'user0@test.com')['ultimo_acceso'] ?? null);
    }

    public function test_crear_usuario(): void
    {
        Rol::create(['codigo' => 'DOCENTE', 'nombre' => 'Docente', 'estado' => 'activo']);
        $docente = Docente::create([
            'codigo' => 'DOC-TEST', 'nombre' => 'Juan', 'apellido' => 'Pérez',
            'correo' => 'juan.docente@test.com', 'estado' => 'activo',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/seguridad/usuarios', [
                'name' => 'Juan Pérez',
                'email' => 'juan@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'roles' => ['DOCENTE'],
                'docente_id' => $docente->id,
            ]);

        $response->assertCreated()
            ->assertJson([
                'resultado' => 'A',
                'mensaje' => 'Usuario creado exitosamente',
            ]);

        $this->assertDatabaseHas('users', ['email' => 'juan@test.com', 'docente_id' => $docente->id]);
        $this->assertDatabaseHas('usuario_roles', ['usuario_id' => User::where('email', 'juan@test.com')->value('id'), 'rol_id' => Rol::where('codigo', 'DOCENTE')->value('id'), 'estado' => 'activo']);
    }

    public function test_inactivar_usuario_revoca_tokens(): void
    {
        $usuario = User::create([
            'name' => 'Token User',
            'email' => 'token@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $usuario->createToken('test-token');

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson("/api/v1/seguridad/usuarios/{$usuario->id}", [
                'estado' => 'inactivo',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
            'estado' => 'inactivo',
        ]);

        $this->assertEquals(0, $usuario->fresh()->tokens()->count());
    }
}
