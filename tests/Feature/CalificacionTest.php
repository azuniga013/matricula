<?php

namespace Tests\Feature;

use App\Models\{AlcanceUsuario, Aula, Calificacion, DepartamentoAcademico, Docente, Estudiante, Horario, Modalidad, Modulo, NivelAcademico, OpcionModulo, OfertaAcademica, Permiso, PeriodoAcademico, PlanCobro, PlanEstudio, Rol, Sucursal, User, VersionPlanEstudio};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalificacionTest extends TestCase
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
        $permisos = Permiso::where('codigo', 'like', 'calificaciones.%')
            ->orWhereIn('codigo', ['asistencias.consultar', 'asistencias.crear'])
            ->get();
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
            'codigo' => 'OF-2026-ING1-INT',
            'sucursal_id' => $this->sucursal->id,
            'periodo_academico_id' => $this->periodo->id,
            'nivel_academico_id' => $this->nivel->id,
            'modalidad_id' => $this->modalidad->id,
            'horario_id' => $this->horario->id,
            'docente_id' => $this->docente->id,
            'aula_id' => $this->aula->id,
            'plan_cobro_id' => $this->planCobro->id,
            'cupo_maximo' => 25,
            'cupos_reservados' => 0,
            'cupos_matriculados' => 0,
            'estado' => 'abierto',
        ]);

        $this->estudiante = Estudiante::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'codigo' => 'EST-001',
        ]);

        \DB::table('matriculas')->insertGetId([
            'codigo' => 'MAT-2026-001',
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'matriculado',
            'creado_en' => now(),
        ]);
    }

    private function crearPermisosBase(): void
    {
        $modulo = Modulo::create(['codigo' => 'calificaciones', 'nombre' => 'Calificaciones', 'estado' => 'activo', 'orden' => 8]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'calificaciones.general', 'nombre' => 'General', 'estado' => 'activo']);

        foreach (['consultar', 'crear', 'modificar', 'eliminar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcion->id,
                'codigo' => 'calificaciones.' . $accion,
                'nombre' => ucfirst($accion),
                'accion' => $accion,
                'estado' => 'activo',
            ]);
        }

        $moduloAsistencias = Modulo::create(['codigo' => 'asistencias', 'nombre' => 'Asistencias', 'estado' => 'activo', 'orden' => 9]);
        $opcionAsistencias = OpcionModulo::create(['modulo_id' => $moduloAsistencias->id, 'codigo' => 'asistencias.lista', 'nombre' => 'Pasar lista', 'estado' => 'activo']);
        foreach (['consultar', 'crear'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcionAsistencias->id,
                'codigo' => 'asistencias.' . $accion,
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

    public function test_registrar_calificaciones(): void
    {
        $response = $this->postJson('/api/v1/calificaciones/registrar', [
            'oferta_academica_id' => $this->oferta->id,
            'calificaciones' => [
                [
                    'estudiante_id' => $this->estudiante->id,
                    'nota_final' => 85.5,
                    'faltas' => 2,
                ],
            ],
        ], $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.0.nota_final', '85.50')
            ->assertJsonPath('data.0.faltas', 2);
    }

    public function test_docente_con_permiso_calificaciones_puede_generar_certificado_desde_la_calificacion(): void
    {
        $this->postJson('/api/v1/calificaciones/registrar', [
            'oferta_academica_id' => $this->oferta->id,
            'calificaciones' => [[
                'estudiante_id' => $this->estudiante->id,
                'nota_final' => 85,
                'faltas' => 0,
            ]],
        ], $this->headers())->assertOk();

        $calificacion = Calificacion::where('estudiante_id', $this->estudiante->id)->firstOrFail();

        $this->postJson('/api/v1/estudiantes/certificados/electronicos/admin', [
            'calificacion_id' => $calificacion->id,
        ], $this->headers())
            ->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.nota_final', '85.00');

        $this->assertDatabaseHas('historial_academico', [
            'matricula_id' => $calificacion->matricula_id,
            'estado' => 'aprobado',
        ]);
        $this->assertDatabaseCount('certificados_electronicos', 1);
    }

    public function test_ofertas_disponibles_asistencias_carga_el_regimen_desde_el_nivel(): void
    {
        AlcanceUsuario::create([
            'usuario_id' => $this->admin->id,
            'tipo' => 'global',
            'estado' => 'activo',
        ]);

        $response = $this->getJson('/api/v1/asistencias/ofertas-disponibles?periodo_academico_id=' . $this->periodo->id, $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.0.id', $this->oferta->id)
            ->assertJsonPath('data.0.nivel_academico.regimen_academico.nombre', 'Intensivo');
    }

    public function test_estudiantes_por_oferta_incluye_identificador_para_calificaciones_moviles(): void
    {
        AlcanceUsuario::create([
            'usuario_id' => $this->admin->id,
            'tipo' => 'global',
            'estado' => 'activo',
        ]);

        $this->getJson('/api/v1/asistencias/estudiantes-por-oferta?oferta_academica_id=' . $this->oferta->id, $this->headers())
            ->assertOk()
            ->assertJsonPath('data.0.estudiante_id', $this->estudiante->id);
    }

    public function test_registrar_asistencia_de_la_oferta(): void
    {
        $matriculaId = \DB::table('matriculas')
            ->where('estudiante_id', $this->estudiante->id)
            ->where('oferta_academica_id', $this->oferta->id)
            ->value('id');

        $this->postJson('/api/v1/asistencias/registrar', [
            'oferta_academica_id' => $this->oferta->id,
            'fecha' => '2026-08-05',
            'asistencias' => [[
                'matricula_id' => $matriculaId,
                'estado' => 'tardanza',
            ]],
        ], $this->headers())
            ->assertOk()
            ->assertJsonPath('data.registradas', 1);

        $this->assertTrue(
            \App\Models\AsistenciaEstudiante::where('matricula_id', $matriculaId)
                ->where('estado', 'tardanza')
                ->whereDate('fecha', '2026-08-05')
                ->exists()
        );
    }

    public function test_registrar_calificaciones_estudiante_no_matriculado(): void
    {
        $otroEstudiante = Estudiante::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'codigo' => 'EST-999',
        ]);

        $response = $this->postJson('/api/v1/calificaciones/registrar', [
            'oferta_academica_id' => $this->oferta->id,
            'calificaciones' => [
                [
                    'estudiante_id' => $otroEstudiante->id,
                    'nota_final' => 90,
                    'faltas' => 0,
                ],
            ],
        ], $this->headers());

        $response->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_listar_calificaciones_por_oferta(): void
    {
        $matricula = \DB::table('matriculas')->first();

        Calificacion::create([
            'codigo' => 'CAL-001',
            'matricula_id' => $matricula->id,
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'nota_final' => 75.0,
            'faltas' => 3,
            'docente_id' => $this->docente->id,
            'estado' => 'registrado',
        ]);

        $response = $this->getJson('/api/v1/calificaciones?oferta_academica_id=' . $this->oferta->id, $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(1, 'data.data');
    }

    public function test_obtener_calificacion_por_id(): void
    {
        $matricula = \DB::table('matriculas')->first();

        $cal = Calificacion::create([
            'codigo' => 'CAL-002',
            'matricula_id' => $matricula->id,
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'nota_final' => 90.0,
            'faltas' => 1,
            'docente_id' => $this->docente->id,
            'estado' => 'registrado',
        ]);

        $response = $this->getJson('/api/v1/calificaciones/' . $cal->id, $this->headers());

        $response->assertOk()
            ->assertJsonPath('data.nota_final', '90.00');
    }

    public function test_actualizar_calificacion(): void
    {
        $matricula = \DB::table('matriculas')->first();

        $cal = Calificacion::create([
            'codigo' => 'CAL-003',
            'matricula_id' => $matricula->id,
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'nota_final' => 70.0,
            'faltas' => 5,
            'docente_id' => $this->docente->id,
            'estado' => 'registrado',
        ]);

        $response = $this->putJson('/api/v1/calificaciones/' . $cal->id, [
            'nota_final' => 82.0,
            'faltas' => 2,
        ], $this->headers());

        $response->assertOk()
            ->assertJsonPath('data.nota_final', '82.00')
            ->assertJsonPath('data.estado', 'corregido');
    }

    public function test_esta_aprobada_intensivo(): void
    {
        $matricula = \DB::table('matriculas')->first();

        $cal = Calificacion::create([
            'codigo' => 'CAL-004',
            'matricula_id' => $matricula->id,
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'nota_final' => 85.0,
            'faltas' => 5,
            'docente_id' => $this->docente->id,
            'estado' => 'registrado',
        ]);

        $this->assertTrue($cal->estaAprobada());
    }

    public function test_esta_reprobada_por_nota(): void
    {
        $matricula = \DB::table('matriculas')->first();

        $cal = Calificacion::create([
            'codigo' => 'CAL-005',
            'matricula_id' => $matricula->id,
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'nota_final' => 70.0,
            'faltas' => 2,
            'docente_id' => $this->docente->id,
            'estado' => 'registrado',
        ]);

        $this->assertFalse($cal->estaAprobada());
    }

    public function test_esta_reprobado_por_faltas(): void
    {
        $matricula = \DB::table('matriculas')->first();

        $cal = Calificacion::create([
            'codigo' => 'CAL-006',
            'matricula_id' => $matricula->id,
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'nota_final' => 90.0,
            'faltas' => 8,
            'docente_id' => $this->docente->id,
            'estado' => 'registrado',
        ]);

        $this->assertFalse($cal->estaAprobada());
    }

    public function test_calificaciones_requiere_permiso(): void
    {
        $user = User::create([
            'name' => 'Sin Permiso',
            'email' => 'sin@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $rol = Rol::create(['codigo' => 'BASIC', 'nombre' => 'Básico', 'estado' => 'activo']);
        $user->roles()->attach($rol->id, ['estado' => 'activo']);
        $token2 = $user->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/v1/calificaciones', [
            'Authorization' => "Bearer {$token2}",
        ]);

        $response->assertStatus(403);
    }
}
