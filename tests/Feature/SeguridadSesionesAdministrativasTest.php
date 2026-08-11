<?php

namespace Tests\Feature;

use App\Models\Modulo;
use App\Models\OpcionModulo;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\SesionUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeguridadSesionesAdministrativasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearPermisosBase();

        $rolSuperadmin = Rol::create(['codigo' => 'SUPERADMIN', 'nombre' => 'Super Admin', 'estado' => 'activo']);
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $this->admin->roles()->attach($rolSuperadmin->id, ['estado' => 'activo']);
        $rolSuperadmin->permisos()->sync(Permiso::pluck('id')->all());
        $this->adminToken = $this->admin->createToken('admin-token')->plainTextToken;
    }

    private function crearPermisosBase(): void
    {
        $modulo = Modulo::create(['codigo' => 'seguridad', 'nombre' => 'Seguridad', 'estado' => 'activo', 'orden' => 1]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'seguridad.usuarios', 'nombre' => 'Usuarios', 'estado' => 'activo']);

        foreach (['consultar', 'crear', 'modificar', 'configurar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcion->id,
                'codigo' => 'seguridad.'.$accion,
                'nombre' => ucfirst($accion),
                'accion' => $accion,
                'estado' => 'activo',
            ]);
        }
    }

    private function adminHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->adminToken];
    }

    private function crearUsuarioActivo(string $email = 'user@test.com', string $password = 'password'): User
    {
        return User::create([
            'name' => 'Usuario Test',
            'email' => $email,
            'password' => bcrypt($password),
            'estado' => 'activo',
        ]);
    }

    private function loginComo(string $email, string $password = 'password'): string
    {
        return $this->postJson('/api/v1/login', [
            'email' => $email,
            'password' => $password,
        ])->assertOk()->json('data.token');
    }

    public function test_logout_revoca_sesion_y_reusar_token_devuelve_401_sesion_revocada(): void
    {
        $usuario = $this->crearUsuarioActivo('logout@test.com');
        $token = $this->loginComo('logout@test.com');

        $this->assertDatabaseHas('sesiones_usuario', [
            'usuario_id' => $usuario->id,
            'token_hash' => hash('sha256', $token),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/logout')
            ->assertOk();

        $this->assertDatabaseHas('sesiones_usuario', [
            'usuario_id' => $usuario->id,
            'token_hash' => hash('sha256', $token),
        ]);
        $this->assertNotNull(SesionUsuario::where('usuario_id', $usuario->id)->where('token_hash', hash('sha256', $token))->first()->revocado_en);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me')
            ->assertStatus(401)
            ->assertJsonPath('codigo_error', '401_SESION_REVOCADA');
    }

    public function test_inactivar_usuario_revoca_sesiones_y_reusar_token_devuelve_401_sesion_revocada(): void
    {
        $usuario = $this->crearUsuarioActivo('inactivar@test.com');
        $token = $this->loginComo('inactivar@test.com');

        $this->withHeaders($this->adminHeaders())
            ->putJson('/api/v1/seguridad/usuarios/'.$usuario->id, ['estado' => 'inactivo'])
            ->assertOk();

        $this->assertSame(0, $usuario->fresh()->tokens()->count());
        $this->assertNotNull($usuario->fresh()->sesiones()->first()?->revocado_en);
        $this->assertDatabaseHas('bitacora_seguridad', [
            'accion' => 'inactivar_usuario',
            'registro_id' => $usuario->id,
            'resultado' => 'exitoso',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me')
            ->assertStatus(401)
            ->assertJsonPath('codigo_error', '401_SESION_REVOCADA');
    }

    public function test_restablecer_contrasena_revoca_sesiones_y_reusar_token_devuelve_401_sesion_revocada(): void
    {
        $usuario = $this->crearUsuarioActivo('reset@test.com');
        $token = $this->loginComo('reset@test.com');

        $this->withHeaders($this->adminHeaders())
            ->postJson('/api/v1/seguridad/usuarios/'.$usuario->id.'/restablecer-contrasena', [
                'password' => 'nuevaPassword123',
                'password_confirmation' => 'nuevaPassword123',
            ])
            ->assertOk();

        $this->assertSame(0, $usuario->fresh()->tokens()->count());
        $this->assertNotNull($usuario->fresh()->sesiones()->first()?->revocado_en);
        $this->assertDatabaseHas('bitacora_seguridad', [
            'accion' => 'restablecer_contrasena_usuario',
            'registro_id' => $usuario->id,
            'resultado' => 'exitoso',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me')
            ->assertStatus(401)
            ->assertJsonPath('codigo_error', '401_SESION_REVOCADA');

        $this->postJson('/api/v1/login', [
            'email' => 'reset@test.com',
            'password' => 'nuevaPassword123',
        ])->assertOk();
    }

    public function test_bloquea_usuario_al_quinto_intento_fallido_segun_configuracion(): void
    {
        $usuario = $this->crearUsuarioActivo('bloqueo@test.com');

        for ($i = 1; $i <= 4; $i++) {
            $this->postJson('/api/v1/login', [
                'email' => 'bloqueo@test.com',
                'password' => 'incorrecta',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/login', [
            'email' => 'bloqueo@test.com',
            'password' => 'incorrecta',
        ])
            ->assertStatus(423)
            ->assertJsonPath('codigo_error', '423_USUARIO_BLOQUEADO');

        $usuario->refresh();
        $this->assertTrue($usuario->estaBloqueado());
        $this->assertDatabaseHas('bitacora_seguridad', [
            'accion' => 'bloqueo_temporal_login',
            'registro_id' => $usuario->id,
            'resultado' => 'exitoso',
        ]);
    }

    public function test_no_puede_inactivar_ultimo_superadmin_activo(): void
    {
        $this->withHeaders($this->adminHeaders())
            ->putJson('/api/v1/seguridad/usuarios/'.$this->admin->id, ['estado' => 'inactivo'])
            ->assertStatus(422)
            ->assertJsonPath('codigo_error', '422_ULTIMO_SUPERADMIN');

        $this->assertSame('activo', $this->admin->fresh()->estado);
    }

    public function test_no_puede_retirar_superadmin_al_ultimo_superadmin_activo(): void
    {
        Rol::create(['codigo' => 'DOCENTE', 'nombre' => 'Docente', 'estado' => 'activo']);

        $this->withHeaders($this->adminHeaders())
            ->postJson('/api/v1/seguridad/usuarios/'.$this->admin->id.'/roles', [
                'roles' => ['DOCENTE'],
            ])
            ->assertStatus(422)
            ->assertJsonPath('codigo_error', '422_ULTIMO_SUPERADMIN');

        $this->assertTrue($this->admin->fresh()->roles()->where('roles.codigo', 'SUPERADMIN')->exists());
    }

    public function test_revocacion_manual_de_sesion_registra_bitacora_y_bloquea_el_token(): void
    {
        $token = $this->loginComo('admin@test.com');
        $sesion = SesionUsuario::where('usuario_id', $this->admin->id)
            ->where('token_hash', hash('sha256', $token))
            ->latest('id')
            ->firstOrFail();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/seguridad/sesiones/'.$sesion->id)
            ->assertOk();

        $this->assertDatabaseHas('bitacora_seguridad', [
            'accion' => 'revocar_sesion',
            'registro_id' => $sesion->id,
            'resultado' => 'exitoso',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me')
            ->assertStatus(401)
            ->assertJsonPath('codigo_error', '401_SESION_REVOCADA');
    }
}
