<?php

namespace Tests\Feature;

use App\Models\AccesoEstudiante;
use App\Models\Estudiante;
use App\Models\Modulo;
use App\Models\OpcionModulo;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstudianteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $token;
    private Sucursal $sucursal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearPermisosBase();

        $rol = Rol::create(['codigo' => 'TEST_ADMIN', 'nombre' => 'Test Admin', 'estado' => 'activo']);
        $permisos = Permiso::where('codigo', 'like', 'estudiantes.%')->get();
        $rol->permisos()->attach($permisos->pluck('id')->toArray(), ['estado' => 'activo']);

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $this->admin->roles()->attach($rol->id, ['estado' => 'activo']);
        $this->token = $this->admin->createToken('test')->plainTextToken;

        $this->sucursal = Sucursal::factory()->create(['codigo' => 'SPS']);
    }

    private function crearPermisosBase(): void
    {
        $modulo = Modulo::create(['codigo' => 'estudiantes', 'nombre' => 'Estudiantes', 'estado' => 'activo', 'orden' => 4]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'estudiantes.general', 'nombre' => 'General', 'estado' => 'activo']);

        foreach (['consultar', 'crear', 'modificar', 'aprobar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcion->id,
                'codigo' => 'estudiantes.' . $accion,
                'nombre' => ucfirst($accion),
                'accion' => $accion,
                'estado' => 'activo',
            ]);
        }
    }

    private function headers(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_crear_estudiante(): void
    {
        $payload = [
            'codigo' => 'EST-001',
            'nombre' => 'Ana',
            'apellido' => 'Martínez',
            'identidad' => '0801-1995-12345',
            'correo' => 'ana@test.com',
            'sucursal_id' => $this->sucursal->id,
        ];

        $response = $this->postJson('/api/v1/estudiantes', $payload, $this->headers());

        $response->assertCreated()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.codigo', 'EST-001');

        $this->assertDatabaseHas('estudiantes', ['codigo' => 'EST-001']);
    }

    public function test_listar_estudiantes(): void
    {
        Estudiante::factory()->count(3)->create(['sucursal_id' => $this->sucursal->id]);

        $response = $this->getJson('/api/v1/estudiantes', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(3, 'data.data');
    }

    public function test_buscar_por_identidad(): void
    {
        $estudiante = Estudiante::factory()->create(['identidad' => '0801-1995-12345', 'sucursal_id' => $this->sucursal->id]);

        $response = $this->getJson('/api/v1/estudiantes/buscar-identidad?identidad=0801-1995-12345', $this->headers());

        $response->assertOk()
            ->assertJsonPath('data.codigo', $estudiante->codigo)
            ->assertJsonStructure(['data' => ['correo_enmascarado', 'telefono_enmascarado']]);
    }

    public function test_registro_primer_ingreso(): void
    {
        $payload = [
            'identidad' => '0801-2000-99999',
            'nombre' => 'Pedro',
            'apellido' => 'Sánchez',
            'correo' => 'pedro@test.com',
            'sucursal_id' => $this->sucursal->id,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];

        $response = $this->postJson('/api/v1/estudiantes/registro', $payload);

        $response->assertCreated()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonStructure(['data' => ['estudiante_id', 'codigo']]);

        $this->assertDatabaseHas('estudiantes', ['correo' => 'pedro@test.com']);
        $this->assertDatabaseHas('accesos_estudiante', ['email' => 'pedro@test.com']);
    }

    public function test_login_estudiante(): void
    {
        $estudiante = Estudiante::factory()->create(['sucursal_id' => $this->sucursal->id, 'estado' => 'activo']);
        AccesoEstudiante::create([
            'estudiante_id' => $estudiante->id,
            'email' => 'test@test.com',
            'password' => 'password',
            'estado' => 'activo',
        ]);

        $response = $this->postJson('/api/v1/estudiantes/iniciar-sesion', [
            'email' => 'test@test.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonStructure(['data' => ['token', 'estudiante']]);
    }

    public function test_login_credenciales_invalidas(): void
    {
        $response = $this->postJson('/api/v1/estudiantes/iniciar-sesion', [
            'email' => 'noexiste@test.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(401);
    }

    public function test_activar_estudiante_existente(): void
    {
        $estudiante = Estudiante::factory()->create([
            'identidad' => '0801-1995-55555',
            'codigo' => 'EST-ACT',
            'sucursal_id' => $this->sucursal->id,
        ]);

        $response = $this->postJson('/api/v1/estudiantes/activar', [
            'identidad' => '0801-1995-55555',
            'codigo' => 'EST-ACT',
        ]);

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');

        $this->assertDatabaseHas('accesos_estudiante', ['estudiante_id' => $estudiante->id]);
    }

    public function test_reenviar_credenciales_estudiante(): void
    {
        $estudiante = Estudiante::factory()->create([
            'correo' => 'reenviar@test.com',
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'activo',
        ]);

        AccesoEstudiante::create([
            'estudiante_id' => $estudiante->id,
            'email' => 'reenviar@test.com',
            'password' => 'password',
            'estado' => 'activo',
        ]);

        $response = $this->postJson('/api/v1/estudiantes/reenviar-credenciales', [
            'email' => 'reenviar@test.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');
    }

    public function test_activar_estudiante_inexistente(): void
    {
        $response = $this->postJson('/api/v1/estudiantes/activar', [
            'identidad' => '0801-1995-00000',
            'codigo' => 'NO-EXISTE',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('resultado', 'R');
    }

    public function test_requiere_permiso_para_crear(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/v1/estudiantes', [
            'codigo' => 'X',
            'nombre' => 'X',
            'apellido' => 'X',
            'sucursal_id' => $this->sucursal->id,
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertForbidden();
    }
}
