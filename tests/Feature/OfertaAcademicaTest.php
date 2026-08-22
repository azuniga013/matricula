<?php

namespace Tests\Feature;

use App\Models\Aula;
use App\Models\DepartamentoAcademico;
use App\Models\Docente;
use App\Models\Horario;
use App\Models\Modalidad;
use App\Models\Modulo;
use App\Models\NivelAcademico;
use App\Models\OpcionModulo;
use App\Models\OfertaAcademica;
use App\Models\Permiso;
use App\Models\PeriodoAcademico;
use App\Models\PlanCobro;
use App\Models\PlanEstudio;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\VersionPlanEstudio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfertaAcademicaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $token;
    private Sucursal $sucursal;
    private PeriodoAcademico $periodo;
    private NivelAcademico $nivel;
    private Modalidad $regimen;
    private Modalidad $modalidad;
    private Horario $horario;
    private Docente $docente;
    private Aula $aula;
    private PlanCobro $planCobro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearPermisosBase();

        $rol = Rol::create(['codigo' => 'TEST_ADMIN', 'nombre' => 'Test Admin', 'estado' => 'activo']);
        $permisos = Permiso::where('codigo', 'like', 'ofertas.%')->get();
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
        $this->periodo = PeriodoAcademico::create([
            'codigo' => '2026-I',
            'nombre' => 'Semestre 1',
            'fecha_inicio' => '2026-01-15',
            'fecha_fin' => '2026-06-30',
            'estado' => 'activo',
        ]);

        $depto = DepartamentoAcademico::factory()->create(['codigo' => 'ING']);
        $plan = PlanEstudio::create([
            'departamento_academico_id' => $depto->id,
            'codigo' => 'ING-GEN',
            'nombre' => 'Inglés General',
        ]);
        $version = VersionPlanEstudio::create([
            'plan_estudio_id' => $plan->id,
            'numero_version' => 1,
            'vigente_desde' => '2026-01-01',
        ]);

        $this->regimen = Modalidad::create([
            'codigo' => 'INT',
            'nombre' => 'Intensivo',
            'tipo' => 'regimen_academico',
        ]);

        $this->nivel = NivelAcademico::create([
            'version_plan_estudio_id' => $version->id,
            'regimen_academico_id' => $this->regimen->id,
            'codigo' => 'ING-1',
            'nombre' => 'Inglés 1',
            'orden' => 1,
            'nota_minima_aprobar' => 80,
            'faltas_maximas_permitidas' => 7,
        ]);

        $this->modalidad = Modalidad::create([
            'codigo' => 'PRES',
            'nombre' => 'Presencial',
            'tipo' => 'atencion',
        ]);

        $this->horario = Horario::create([
            'codigo' => 'M1',
            'nombre' => 'Matutino',
            'hora_inicio' => '07:00',
            'hora_fin' => '09:00',
        ]);
        $this->horario->update(['lunes' => true, 'miercoles' => true]);

        $this->docente = Docente::factory()->create(['codigo' => 'DOC001']);

        $this->aula = Aula::create([
            'sucursal_id' => $this->sucursal->id,
            'codigo' => 'AUL-01',
            'nombre' => 'Aula 1',
            'capacidad' => 25,
        ]);

        $this->planCobro = PlanCobro::create([
            'codigo' => 'PLN-TEST',
            'nombre' => 'Plan Test',
            'estado' => 'activo',
        ]);
    }

    private function crearPermisosBase(): void
    {
        $modulo = Modulo::create(['codigo' => 'ofertas', 'nombre' => 'Ofertas', 'estado' => 'activo', 'orden' => 3]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'ofertas.general', 'nombre' => 'General', 'estado' => 'activo']);

        foreach (['consultar', 'crear', 'modificar', 'eliminar', 'aprobar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcion->id,
                'codigo' => 'ofertas.' . $accion,
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

    private function ofertaData(array $extra = []): array
    {
        return array_merge([
            'sucursal_id' => $this->sucursal->id,
            'periodo_academico_id' => $this->periodo->id,
            'nivel_academico_id' => $this->nivel->id,
            'modalidad_id' => $this->modalidad->id,
            'horario_id' => $this->horario->id,
            'docente_id' => $this->docente->id,
            'aula_id' => $this->aula->id,
            'plan_cobro_id' => $this->planCobro->id,
            'codigo' => 'SPS-2026I-ING1-INT-MAT',
            'cupo_maximo' => 25,
        ], $extra);
    }

    public function test_crear_oferta_academica(): void
    {
        $response = $this->postJson('/api/v1/ofertas/academicas', $this->ofertaData(), $this->headers());

        $response->assertCreated()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.estado', 'borrador')
            ->assertJsonPath('data.cupo_maximo', 25);

        $this->assertDatabaseHas('ofertas_academicas', ['codigo' => 'SPS-2026I-ING1-INT-MAT']);
    }

    public function test_crear_oferta_academica_requiere_plan_cobro(): void
    {
        $datos = $this->ofertaData();
        unset($datos['plan_cobro_id']);

        $this->postJson('/api/v1/ofertas/academicas', $datos, $this->headers())
            ->assertStatus(422);

        $this->assertDatabaseMissing('ofertas_academicas', ['codigo' => $datos['codigo']]);
    }

    public function test_listar_ofertas_academicas(): void
    {
        OfertaAcademica::factory()->count(3)->create([
            'sucursal_id' => $this->sucursal->id,
            'periodo_academico_id' => $this->periodo->id,
            'nivel_academico_id' => $this->nivel->id,
            'modalidad_id' => $this->modalidad->id,
            'horario_id' => $this->horario->id,
            'docente_id' => $this->docente->id,
            'aula_id' => $this->aula->id,
        ]);

        $response = $this->getJson('/api/v1/ofertas/academicas', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(3, 'data.data');
    }

    public function test_filtrar_ofertas_por_sucursal(): void
    {
        $otraSucursal = Sucursal::factory()->create(['codigo' => 'TGU']);

        OfertaAcademica::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'periodo_academico_id' => $this->periodo->id,
            'nivel_academico_id' => $this->nivel->id,
            'modalidad_id' => $this->modalidad->id,
            'horario_id' => $this->horario->id,
            'docente_id' => $this->docente->id,
            'aula_id' => $this->aula->id,
        ]);

        OfertaAcademica::factory()->create([
            'sucursal_id' => $otraSucursal->id,
            'periodo_academico_id' => $this->periodo->id,
            'nivel_academico_id' => $this->nivel->id,
            'modalidad_id' => $this->modalidad->id,
            'horario_id' => $this->horario->id,
            'docente_id' => $this->docente->id,
            'aula_id' => $this->aula->id,
        ]);

        $response = $this->getJson("/api/v1/ofertas/academicas?sucursal_id={$this->sucursal->id}", $this->headers());

        $response->assertOk()
            ->assertJsonCount(1, 'data.data');
    }

    public function test_cupo_maximo_no_puede_ser_menor_a_ocupados(): void
    {
        $oferta = OfertaAcademica::create($this->ofertaData([
            'cupos_matriculados' => 20,
            'cupos_reservados' => 5,
            'estado' => 'abierto',
        ]));

        $response = $this->postJson("/api/v1/ofertas/academicas/{$oferta->id}", [
            'cupo_maximo' => 20,
        ], $this->headers());

        $response->assertUnprocessable()
            ->assertJsonPath('resultado', 'R');
    }

    public function test_monitor_cupos(): void
    {
        OfertaAcademica::create($this->ofertaData([
            'estado' => 'abierto',
            'cupos_matriculados' => 10,
        ]));

        $response = $this->getJson('/api/v1/ofertas/monitor', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [[
                    'codigo',
                    'estado',
                    'cupo_maximo',
                    'cupos_reservados',
                    'cupos_matriculados',
                    'cupos_disponibles',
                    'color_estado',
                    'sucursal',
                    'nivel_academico',
                    'modalidad',
                    'horario',
                    'docente',
                ]],
                'meta' => ['refresco_segundos'],
            ]);
    }

    public function test_monitor_filtrar_por_periodo(): void
    {
        $periodo2 = PeriodoAcademico::create([
            'codigo' => '2026-II',
            'nombre' => 'Semestre 2',
            'fecha_inicio' => '2026-07-01',
            'fecha_fin' => '2026-12-15',
            'estado' => 'activo',
        ]);

        OfertaAcademica::create($this->ofertaData(['estado' => 'abierto']));
        OfertaAcademica::create($this->ofertaData([
            'periodo_academico_id' => $periodo2->id,
            'codigo' => 'SPS-2026II-ING1-INT-MAT',
            'estado' => 'borrador',
        ]));

        $response = $this->getJson("/api/v1/ofertas/monitor?periodo_academico_id={$this->periodo->id}", $this->headers());

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_requiere_permiso_para_crear(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/v1/ofertas/academicas', $this->ofertaData(), [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertForbidden();
    }

    public function test_validar_codigo_unico(): void
    {
        OfertaAcademica::create($this->ofertaData());

        $response = $this->postJson('/api/v1/ofertas/academicas', $this->ofertaData(), $this->headers());

        $response->assertUnprocessable();
    }

    public function test_autogenerar_codigo_cuando_no_se_proporciona(): void
    {
        $data = $this->ofertaData();
        unset($data['codigo']);

        $response = $this->postJson('/api/v1/ofertas/academicas', $data, $this->headers());

        $response->assertCreated()
            ->assertJsonPath('resultado', 'A');

        $codigo = $response->json('data.codigo');
        $this->assertNotNull($codigo);
        $this->assertStringStartsWith('OF-' . date('Y') . '-', $codigo);
        $this->assertDatabaseHas('ofertas_academicas', ['codigo' => $codigo]);
    }

    public function test_autogenerar_codigo_con_secuencia_incremental(): void
    {
        $data = $this->ofertaData();
        unset($data['codigo']);

        $r1 = $this->postJson('/api/v1/ofertas/academicas', $data, $this->headers());
        $r1->assertCreated();
        $c1 = $r1->json('data.codigo');

        $r2 = $this->postJson('/api/v1/ofertas/academicas', $data, $this->headers());
        $r2->assertCreated();
        $c2 = $r2->json('data.codigo');

        $this->assertNotEquals($c1, $c2);
        $sufijo1 = (int) substr($c1, -6);
        $sufijo2 = (int) substr($c2, -6);
        $this->assertEquals($sufijo1 + 1, $sufijo2);
    }
}
