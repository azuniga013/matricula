<?php

namespace Tests\Feature;

use App\Models\Aula;
use App\Models\HistorialAcademico;
use App\Models\DepartamentoAcademico;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Horario;
use App\Models\Modalidad;
use App\Models\Modulo;
use App\Models\NivelAcademico;
use App\Models\ObligacionPagoEstudiante;
use App\Models\OfertaAcademica;
use App\Models\OpcionModulo;
use App\Models\PeriodoAcademico;
use App\Models\Permiso;
use App\Models\PlanCobro;
use App\Models\PlanEstudio;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\VersionPlanEstudio;
use App\Modules\Comun\ContextoUsuario;
use App\Modules\Matriculas\CasosUso\CancelarMatricula;
use App\Modules\Matriculas\CasosUso\ConfirmarMatricula;
use App\Modules\Matriculas\CasosUso\ReservarMatricula;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        $matId = DB::table('conceptos_pago')->insertGetId([
            'codigo' => 'MAT', 'nombre' => 'Matrícula', 'tipo_monto' => 'por_oferta',
            'requiere_autorizacion_monto' => false, 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);
        $cuoId = DB::table('conceptos_pago')->insertGetId([
            'codigo' => 'CUO', 'nombre' => 'Cuota', 'tipo_monto' => 'por_oferta',
            'requiere_autorizacion_monto' => false, 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);

        $this->planCobro = PlanCobro::create([
            'codigo' => 'PLN-TEST-INT',
            'nombre' => 'Plan Test Intensivo',
            'estado' => 'activo',
        ]);

        DB::table('detalle_plan_cobro')->insert([
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
                'codigo' => 'matriculas.'.$accion,
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
            'plan_estudio_id' => $this->nivel->versionPlanEstudio->plan_estudio_id,
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

    public function test_reservar_rechaza_una_oferta_que_no_pertenece_al_plan_indicado(): void
    {
        $planDistinto = PlanEstudio::create([
            'departamento_academico_id' => $this->nivel->versionPlanEstudio->planEstudio->departamento_academico_id,
            'codigo' => 'PLAN-DISTINTO',
            'nombre' => 'Plan distinto',
        ]);

        $this->postJson('/api/v1/matriculas/reservar', [
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'plan_estudio_id' => $planDistinto->id,
        ], $this->headers())
            ->assertStatus(422)
            ->assertJsonPath('mensaje', 'La oferta seleccionada no pertenece al plan de estudio indicado');
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

    public function test_no_reserva_siguiente_nivel_solo_por_tener_matricula_previa_sin_aprobacion_academica(): void
    {
        $nivel2 = NivelAcademico::create([
            'version_plan_estudio_id' => $this->nivel->versionPlanEstudio->id,
            'regimen_academico_id' => $this->regimen->id,
            'codigo' => 'ING-2',
            'nombre' => 'Inglés 2',
            'orden' => 2,
            'nota_minima_aprobar' => 80,
            'faltas_maximas_permitidas' => 7,
        ]);
        $nivel2->prerrequisitos()->sync([$this->nivel->id]);

        $oferta2 = OfertaAcademica::create([
            'sucursal_id' => $this->sucursal->id,
            'periodo_academico_id' => $this->periodo->id,
            'nivel_academico_id' => $nivel2->id,
            'modalidad_id' => $this->modalidad->id,
            'horario_id' => $this->horario->id,
            'docente_id' => $this->docente->id,
            'aula_id' => $this->aula->id,
            'plan_cobro_id' => $this->planCobro->id,
            'codigo' => 'SPS-2026I-ING2-INT-MAT',
            'cupo_maximo' => 25,
            'estado' => 'abierto',
        ]);

        DB::table('matriculas')->insert([
            'codigo' => 'MAT-PREVIA-001',
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'matriculado',
            'fecha_reserva' => now(),
            'fecha_confirmacion' => now(),
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);

        $response = $this->postJson('/api/v1/matriculas/reservar', [
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $oferta2->id,
            'plan_estudio_id' => $this->nivel->versionPlanEstudio->plan_estudio_id,
        ], $this->headers());

        $response->assertStatus(422)
            ->assertJsonPath('resultado', 'R')
            ->assertJsonPath('mensaje', 'Debe aprobar primero los siguientes niveles: Inglés 1');
    }

    public function test_no_reservar_si_oferta_no_tiene_plan_de_cobro_activo_con_detalles(): void
    {
        $ofertaSinPlan = OfertaAcademica::create([
            'sucursal_id' => $this->sucursal->id,
            'periodo_academico_id' => $this->periodo->id,
            'nivel_academico_id' => $this->nivel->id,
            'modalidad_id' => $this->modalidad->id,
            'horario_id' => $this->horario->id,
            'docente_id' => $this->docente->id,
            'aula_id' => $this->aula->id,
            'plan_cobro_id' => null,
            'codigo' => 'SPS-2026I-ING1-SIN-PLAN',
            'cupo_maximo' => 25,
            'estado' => 'abierto',
        ]);

        $this->postJson('/api/v1/matriculas/reservar', [
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $ofertaSinPlan->id,
            'plan_estudio_id' => $this->nivel->versionPlanEstudio->plan_estudio_id,
        ], $this->headers())
            ->assertStatus(422)
            ->assertJsonPath('resultado', 'R')
            ->assertJsonPath('mensaje', 'La oferta no tiene un plan de cobro activo con detalles configurados');

        $this->assertDatabaseMissing('matriculas', [
            'oferta_academica_id' => $ofertaSinPlan->id,
            'estudiante_id' => $this->estudiante->id,
        ]);
    }

    public function test_no_reserva_siguiente_nivel_si_prerrequisito_esta_aprobado_pero_no_pagado(): void
    {
        $nivel2 = NivelAcademico::create([
            'version_plan_estudio_id' => $this->nivel->versionPlanEstudio->id,
            'regimen_academico_id' => $this->regimen->id,
            'codigo' => 'ING-2B',
            'nombre' => 'Inglés 2B',
            'orden' => 2,
            'nota_minima_aprobar' => 80,
            'faltas_maximas_permitidas' => 7,
        ]);
        $nivel2->prerrequisitos()->sync([$this->nivel->id]);

        $oferta2 = OfertaAcademica::create([
            'sucursal_id' => $this->sucursal->id,
            'periodo_academico_id' => $this->periodo->id,
            'nivel_academico_id' => $nivel2->id,
            'modalidad_id' => $this->modalidad->id,
            'horario_id' => $this->horario->id,
            'docente_id' => $this->docente->id,
            'aula_id' => $this->aula->id,
            'plan_cobro_id' => $this->planCobro->id,
            'codigo' => 'SPS-2026I-ING2B-INT-MAT',
            'cupo_maximo' => 25,
            'estado' => 'abierto',
        ]);

        $matriculaPreviaId = DB::table('matriculas')->insertGetId([
            'codigo' => 'MAT-PREVIA-002',
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'matriculado',
            'fecha_reserva' => now(),
            'fecha_confirmacion' => now(),
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);

        HistorialAcademico::create([
            'codigo' => 'HIST-PRER-001',
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $matriculaPreviaId,
            'oferta_academica_id' => $this->oferta->id,
            'nivel_academico_id' => $this->nivel->id,
            'periodo_academico_id' => $this->periodo->id,
            'nota_final' => 90,
            'faltas' => 0,
            'estado' => 'aprobado',
        ]);

        ObligacionPagoEstudiante::create([
            'matricula_id' => $matriculaPreviaId,
            'concepto_pago_id' => DB::table('conceptos_pago')->where('codigo', 'CUO')->value('id'),
            'numero_cuota' => 1,
            'nombre_cargo' => 'Cuota pendiente',
            'monto' => 700,
            'monto_pagado' => 0,
            'fecha_vencimiento' => now()->toDateString(),
            'estado' => 'pendiente',
        ]);

        $response = $this->postJson('/api/v1/matriculas/reservar', [
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $oferta2->id,
            'plan_estudio_id' => $this->nivel->versionPlanEstudio->plan_estudio_id,
        ], $this->headers());

        $response->assertStatus(422)
            ->assertJsonPath('resultado', 'R')
            ->assertJsonPath('mensaje', 'Debe finalizar administrativamente y pagar los siguientes niveles: Inglés 1');
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

    public function test_reservar_matricula_mediante_caso_de_uso(): void
    {
        $casoUso = app(ReservarMatricula::class);
        $resultado = $casoUso->ejecutar([
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
        ], new ContextoUsuario($this->admin->id));

        $this->assertTrue($resultado->ok());
        $this->assertSame('reservada', $resultado->data()['matricula']->estado);
        $this->assertDatabaseHas('matriculas', [
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'estado' => 'reservada',
        ]);

        $this->oferta->refresh();
        $this->assertEquals(1, $this->oferta->cupos_reservados);
    }

    public function test_reservar_endpoint_sigue_delegando_al_caso_de_uso(): void
    {
        $response = $this->postJson('/api/v1/matriculas/reservar', [
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
        ], $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.estado', 'reservada');
    }

    public function test_confirmar_matricula_mediante_caso_de_uso(): void
    {
        $reserva = app(ReservarMatricula::class)->ejecutar([
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
        ], new ContextoUsuario($this->admin->id))->data()['matricula'];

        $casoUso = app(ConfirmarMatricula::class);
        $resultado = $casoUso->ejecutar($reserva->id, new ContextoUsuario($this->admin->id));

        $this->assertTrue($resultado->ok());
        $this->assertSame('en_revision', $resultado->data()['matricula']->estado);

        $obligaciones = ObligacionPagoEstudiante::where('matricula_id', $reserva->id)->get();
        $this->assertCount(2, $obligaciones);
    }

    public function test_confirmar_mediante_caso_de_uso_rechaza_estado_no_reservada(): void
    {
        $reserva = app(ReservarMatricula::class)->ejecutar([
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
        ], new ContextoUsuario($this->admin->id))->data()['matricula'];

        app(ConfirmarMatricula::class)->ejecutar($reserva->id, new ContextoUsuario($this->admin->id));

        $resultado = app(ConfirmarMatricula::class)->ejecutar($reserva->id, new ContextoUsuario($this->admin->id));

        $this->assertFalse($resultado->ok());
        $this->assertSame(422, $resultado->codigo());
        $this->assertSame('Solo se pueden confirmar matrículas reservadas', $resultado->mensaje());
    }

    public function test_cancelar_matricula_mediante_caso_de_uso(): void
    {
        $reserva = app(ReservarMatricula::class)->ejecutar([
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
        ], new ContextoUsuario($this->admin->id))->data()['matricula'];

        $casoUso = app(CancelarMatricula::class);
        $resultado = $casoUso->ejecutar($reserva->id, 'El estudiante ya no puede asistir', new ContextoUsuario($this->admin->id));

        $this->assertTrue($resultado->ok());
        $this->assertSame('rechazado', $resultado->data()['matricula']->estado);

        $this->oferta->refresh();
        $this->assertEquals(0, $this->oferta->cupos_reservados);

        $this->assertDatabaseHas('obligaciones_pago_estudiante', [
            'matricula_id' => $reserva->id,
            'estado' => 'rechazado',
        ]);
    }
}
