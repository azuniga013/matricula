<?php

namespace Tests\Feature;

use App\Models\{Aula, DepartamentoAcademico, Docente, Estudiante, Horario, Modalidad, Modulo, NivelAcademico, ObligacionPagoEstudiante, OpcionModulo, OfertaAcademica, Permiso, PeriodoAcademico, PlanCobro, PlanEstudio, Rol, Sucursal, User, VersionPlanEstudio};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatriculaTest extends TestCase
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
    private OfertaAcademica $oferta;
    private Estudiante $estudiante;
    private PlanCobro $planCobro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearPermisosBase();

        $rol = Rol::create(['codigo' => 'TEST_ADMIN', 'nombre' => 'Test Admin', 'estado' => 'activo']);
        $permisos = Permiso::where('codigo', 'like', 'matriculas.%')->get();
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

        $matId = \DB::table('conceptos_pago')->insertGetId([
            'codigo' => 'MAT', 'nombre' => 'Matrícula', 'tipo_monto' => 'por_oferta',
            'requiere_autorizacion_monto' => false, 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);
        $cuoId = \DB::table('conceptos_pago')->insertGetId([
            'codigo' => 'CUO', 'nombre' => 'Cuota', 'tipo_monto' => 'por_oferta',
            'requiere_autorizacion_monto' => false, 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);

        $this->planCobro = PlanCobro::create([
            'codigo' => 'PLN-TEST-INT',
            'nombre' => 'Plan Test Intensivo',
            'estado' => 'activo',
        ]);

        \DB::table('detalle_plan_cobro')->insert([
            ['plan_cobro_id' => $this->planCobro->id, 'concepto_pago_id' => $matId, 'numero_cuota' => 0, 'nombre_cargo' => 'Matrícula', 'monto' => 1200.00, 'dias_vencimiento' => 0, 'estado' => 'activo', 'creado_en' => now(), 'actualizado_en' => now()],
            ['plan_cobro_id' => $this->planCobro->id, 'concepto_pago_id' => $cuoId, 'numero_cuota' => 1, 'nombre_cargo' => 'Cuota 1', 'monto' => 1100.00, 'dias_vencimiento' => 30, 'estado' => 'activo', 'creado_en' => now(), 'actualizado_en' => now()],
        ]);

        $this->oferta = OfertaAcademica::create([
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
            'estado' => 'abierto',
        ]);

        $this->estudiante = Estudiante::factory()->create(['sucursal_id' => $this->sucursal->id]);
    }

    private function crearPermisosBase(): void
    {
        $modulo = Modulo::create(['codigo' => 'matriculas', 'nombre' => 'Matrículas', 'estado' => 'activo', 'orden' => 5]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'matriculas.general', 'nombre' => 'General', 'estado' => 'activo']);

        foreach (['consultar', 'crear', 'modificar', 'eliminar', 'aprobar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcion->id,
                'codigo' => 'matriculas.' . $accion,
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

    public function test_reservar_matricula(): void
    {
        $response = $this->postJson('/api/v1/matriculas/reservar', [
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
        ], $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.estado', 'reservada');

        $this->assertDatabaseHas('matriculas', [
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'estado' => 'reservada',
        ]);

        $this->oferta->refresh();
        $this->assertEquals(1, $this->oferta->cupos_reservados);
    }

    public function test_no_reservar_si_no_hay_cupo(): void
    {
        $this->oferta->update([
            'cupo_maximo' => 1,
            'cupos_matriculados' => 1,
        ]);

        $response = $this->postJson('/api/v1/matriculas/reservar', [
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
        ], $this->headers());

        $response->assertUnprocessable()
            ->assertJsonPath('resultado', 'R')
            ->assertJsonFragment(['mensaje' => 'No hay cupos disponibles']);
    }

    public function test_no_duplicar_matricula_en_misma_oferta(): void
    {
        $this->postJson('/api/v1/matriculas/reservar', [
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
        ], $this->headers())->assertOk();

        $response = $this->postJson('/api/v1/matriculas/reservar', [
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
        ], $this->headers());

        $response->assertUnprocessable()
            ->assertJsonPath('resultado', 'R');
    }

    public function test_confirmar_matricula_genera_obligaciones(): void
    {
        $reserva = $this->postJson('/api/v1/matriculas/reservar', [
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
        ], $this->headers())->json('data');

        $response = $this->postJson("/api/v1/matriculas/{$reserva['id']}/confirmar", [], $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.estado', 'en_revision');

        $obligaciones = ObligacionPagoEstudiante::where('matricula_id', $reserva['id'])->get();
        $this->assertCount(2, $obligaciones);
        $this->assertEquals(1200.00, $obligaciones->first()->monto);
        $this->assertEquals(1100.00, $obligaciones->last()->monto);

        $this->oferta->refresh();
        $this->assertEquals(0, $this->oferta->cupos_matriculados);
        $this->assertEquals(1, $this->oferta->cupos_reservados);
    }

    public function test_cancelar_matricula_libera_cupo(): void
    {
        $reserva = $this->postJson('/api/v1/matriculas/reservar', [
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
        ], $this->headers())->json('data');

        $response = $this->postJson("/api/v1/matriculas/{$reserva['id']}/cancelar", [
            'motivo' => 'El estudiante ya no puede asistir',
        ], $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.estado', 'rechazado');

        $this->oferta->refresh();
        $this->assertEquals(0, $this->oferta->cupos_reservados);
    }

    public function test_cancelar_en_revision_libera_cupo_reservado(): void
    {
        $reserva = $this->postJson('/api/v1/matriculas/reservar', [
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
        ], $this->headers())->json('data');

        $this->postJson("/api/v1/matriculas/{$reserva['id']}/confirmar", [], $this->headers())->assertOk();

        $this->oferta->refresh();
        $this->assertEquals(0, $this->oferta->cupos_matriculados);
        $this->assertEquals(1, $this->oferta->cupos_reservados);

        $response = $this->postJson("/api/v1/matriculas/{$reserva['id']}/cancelar", [
            'motivo' => 'Retiro del estudiante',
        ], $this->headers());

        $response->assertOk();

        $this->oferta->refresh();
        $this->assertEquals(0, $this->oferta->cupos_reservados);
    }

    public function test_listar_matriculas(): void
    {
        $reserva = $this->postJson('/api/v1/matriculas/reservar', [
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
        ], $this->headers())->json('data');

        $response = $this->getJson('/api/v1/matriculas', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(1, 'data.data');
    }

    public function test_ver_detalle_matricula(): void
    {
        $reserva = $this->postJson('/api/v1/matriculas/reservar', [
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
        ], $this->headers())->json('data');

        $response = $this->getJson("/api/v1/matriculas/{$reserva['id']}", $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonStructure([
                'data' => ['id', 'codigo', 'estudiante', 'oferta_academica', 'sucursal', 'obligaciones'],
            ]);
    }

    public function test_requiere_permiso_para_reservar(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/v1/matriculas/reservar', [
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertForbidden();
    }

    public function test_reservar_requiere_estudiante_y_oferta(): void
    {
        $response = $this->postJson('/api/v1/matriculas/reservar', [], $this->headers());

        $response->assertUnprocessable();
    }
}
