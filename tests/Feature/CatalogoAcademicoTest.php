<?php

namespace Tests\Feature;

use App\Models\DepartamentoAcademico;
use App\Models\GrupoWhatsapp;
use App\Models\Modalidad;
use App\Models\Modulo;
use App\Models\OpcionModulo;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoAcademicoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearPermisosBase();

        $rol = Rol::create(['codigo' => 'TEST_ADMIN', 'nombre' => 'Test Admin', 'estado' => 'activo']);

        $permisos = Permiso::where('codigo', 'like', 'catalogos_academicos.%')->get();
        $rol->permisos()->attach($permisos->pluck('id')->toArray(), ['estado' => 'activo']);

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $this->admin->roles()->attach($rol->id, ['estado' => 'activo']);

        $this->token = $this->admin->createToken('test')->plainTextToken;
    }

    private function crearPermisosBase(): void
    {
        $modulo = Modulo::create(['codigo' => 'catalogos_academicos', 'nombre' => 'Catálogos Académicos', 'estado' => 'activo', 'orden' => 2]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'catalogos.general', 'nombre' => 'General', 'estado' => 'activo']);

        foreach (['consultar', 'crear', 'modificar', 'eliminar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcion->id,
                'codigo' => 'catalogos_academicos.' . $accion,
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

    public function test_listar_departamentos_academicos(): void
    {
        DepartamentoAcademico::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/catalogos-academicos/departamentos-academicos', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(3, 'data');
    }

    public function test_crear_departamento_academico(): void
    {
        $payload = [
            'codigo' => 'CMP',
            'nombre' => 'Computación',
            'descripcion' => 'Área de formación en computación',
        ];

        $response = $this->postJson('/api/v1/catalogos-academicos/departamentos-academicos', $payload, $this->headers());

        $response->assertCreated()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.codigo', 'CMP');

        $this->assertDatabaseHas('departamentos_academicos', ['codigo' => 'CMP']);
    }

    public function test_validar_codigo_unico_departamento(): void
    {
        DepartamentoAcademico::factory()->create(['codigo' => 'ING']);

        $payload = ['codigo' => 'ING', 'nombre' => 'Duplicado'];

        $response = $this->postJson('/api/v1/catalogos-academicos/departamentos-academicos', $payload, $this->headers());

        $response->assertUnprocessable();
    }

    public function test_crear_plan_estudio(): void
    {
        $departamento = DepartamentoAcademico::factory()->create();

        $payload = [
            'departamento_academico_id' => $departamento->id,
            'codigo' => 'CMP-GEN',
            'nombre' => 'Computación General',
        ];

        $response = $this->postJson('/api/v1/catalogos-academicos/planes-estudio', $payload, $this->headers());

        $response->assertCreated()
            ->assertJsonPath('data.codigo', 'CMP-GEN');
    }

    public function test_crear_modalidad(): void
    {
        $payload = [
            'codigo' => 'INT',
            'nombre' => 'Intensivo',
            'tipo' => 'regimen_academico',
        ];

        $response = $this->postJson('/api/v1/catalogos-academicos/modalidades', $payload, $this->headers());

        $response->assertCreated()
            ->assertJsonPath('data.tipo', 'regimen_academico');
    }

    public function test_validar_tipo_modalidad(): void
    {
        $payload = [
            'codigo' => 'BAD',
            'nombre' => 'Malo',
            'tipo' => 'invalido',
        ];

        $response = $this->postJson('/api/v1/catalogos-academicos/modalidades', $payload, $this->headers());

        $response->assertUnprocessable();
    }

    public function test_listar_modalidades_por_tipo(): void
    {
        Modalidad::factory()->regimenAcademico()->create(['codigo' => 'INT']);
        Modalidad::factory()->atencion()->create(['codigo' => 'PRES']);

        $response = $this->getJson('/api/v1/catalogos-academicos/modalidades?tipo=regimen_academico', $this->headers());

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_crear_horario(): void
    {
        $payload = [
            'codigo' => 'M1',
            'nombre' => 'Matutino 1',
            'hora_inicio' => '07:00',
            'hora_fin' => '09:00',
            'lunes' => true,
            'miercoles' => true,
            'viernes' => true,
        ];

        $response = $this->postJson('/api/v1/catalogos-academicos/horarios', $payload, $this->headers());

        $response->assertCreated()
            ->assertJsonPath('data.codigo', 'M1');

        $this->assertDatabaseHas('horarios', ['id' => $response->json('data.id'), 'lunes' => true]);
    }

    public function test_validar_hora_fin_mayor_que_hora_inicio(): void
    {
        $payload = [
            'codigo' => 'BAD',
            'nombre' => 'Malo',
            'hora_inicio' => '09:00',
            'hora_fin' => '07:00',
            'lunes' => true,
        ];

        $response = $this->postJson('/api/v1/catalogos-academicos/horarios', $payload, $this->headers());

        $response->assertUnprocessable();
    }

    public function test_crear_docente(): void
    {
        $payload = [
            'codigo' => 'DOC001',
            'nombre' => 'María',
            'apellido' => 'López',
            'correo' => 'maria@example.com',
        ];

        $response = $this->postJson('/api/v1/catalogos-academicos/docentes', $payload, $this->headers());

        $response->assertCreated()
            ->assertJsonPath('data.codigo', 'DOC001');
    }

    public function test_crear_periodo_academico(): void
    {
        $payload = [
            'codigo' => '2026-I',
            'nombre' => 'Primer Semestre 2026',
            'fecha_inicio' => '2026-01-15',
            'fecha_fin' => '2026-06-30',
        ];

        $response = $this->postJson('/api/v1/catalogos-academicos/periodos-academicos', $payload, $this->headers());

        $response->assertCreated()
            ->assertJsonPath('data.codigo', '2026-I');
    }

    public function test_crear_grupo_whatsapp(): void
    {
        $sucursal = Sucursal::factory()->create();

        $payload = [
            'sucursal_id' => $sucursal->id,
            'codigo' => 'GRP-001',
            'nombre' => 'Inglés Básico Matutino',
            'link' => 'https://chat.whatsapp.com/example',
        ];

        $response = $this->postJson('/api/v1/catalogos-academicos/grupos-whatsapp', $payload, $this->headers());

        $response->assertCreated()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.codigo', 'GRP-001');
    }

    public function test_eliminar_grupo_whatsapp(): void
    {
        $sucursal = Sucursal::factory()->create();
        $grupo = GrupoWhatsapp::create([
            'sucursal_id' => $sucursal->id,
            'codigo' => 'GRP-002',
            'nombre' => 'Grupo Test',
            'link' => 'https://chat.whatsapp.com/test',
            'creado_por' => $this->admin->id,
        ]);

        $response = $this->deleteJson("/api/v1/catalogos-academicos/grupos-whatsapp/{$grupo->id}", [], $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('mensaje', 'Grupo de WhatsApp desactivado correctamente.');

        $this->assertDatabaseHas('grupos_whatsapp', ['id' => $grupo->id, 'estado' => 'inactivo']);
    }

    public function test_requiere_permiso_para_crear(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $payload = ['codigo' => 'X', 'nombre' => 'X'];

        $response = $this->postJson('/api/v1/catalogos-academicos/departamentos-academicos', $payload, [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertForbidden();
    }
}
