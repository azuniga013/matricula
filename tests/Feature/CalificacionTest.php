<?php

namespace Tests\Feature;

use App\Models\AlcanceUsuario;
use App\Models\AsistenciaEstudiante;
use App\Models\Aula;
use App\Models\Calificacion;
use App\Models\DepartamentoAcademico;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\HistorialAcademico;
use App\Models\Horario;
use App\Models\Modalidad;
use App\Models\Modulo;
use App\Models\NivelAcademico;
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
use App\Modules\Calificaciones\CasosUso\ActualizarCalificacion;
use App\Modules\Calificaciones\CasosUso\RegistrarCalificaciones;
use App\Modules\Comun\ContextoUsuario;
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
                'codigo' => 'calificaciones.'.$accion,
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
                'codigo' => 'asistencias.'.$accion,
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

        $response = $this->getJson('/api/v1/asistencias/ofertas-disponibles?periodo_academico_id='.$this->periodo->id, $this->headers());

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

        $this->getJson('/api/v1/asistencias/estudiantes-por-oferta?oferta_academica_id='.$this->oferta->id, $this->headers())
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
            AsistenciaEstudiante::where('matricula_id', $matriculaId)
                ->where('estado', 'tardanza')
                ->whereDate('fecha', '2026-08-05')
                ->exists()
        );
    }

    public function test_registrar_asistencia_con_lista_vacia_no_falla(): void
    {
        $this->postJson('/api/v1/asistencias/registrar', [
            'oferta_academica_id' => $this->oferta->id,
            'fecha' => '2026-08-05',
            'asistencias' => [],
        ], $this->headers())
            ->assertOk()
            ->assertJsonPath('data.registradas', 0);
    }

    public function test_por_oferta_devuelve_asistencias_sincronizadas_de_la_fecha(): void
    {
        $matriculaId = \DB::table('matriculas')
            ->where('estudiante_id', $this->estudiante->id)
            ->where('oferta_academica_id', $this->oferta->id)
            ->value('id');

        AsistenciaEstudiante::create([
            'matricula_id' => $matriculaId,
            'oferta_academica_id' => $this->oferta->id,
            'fecha' => '2026-03-10',
            'estado' => 'falta',
            'cuenta_como_falta' => true,
            'observacion' => null,
            'registrado_por' => $this->admin->id,
        ]);

        $response = $this->getJson('/api/v1/asistencias/por-oferta?oferta_academica_id='.$this->oferta->id.'&fecha=2026-03-10', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.estado', 'falta')
            ->assertJsonPath('data.0.matricula_id', $matriculaId);
    }

    public function test_faltas_por_oferta_agrupa_faltas_del_periodo(): void
    {
        $matriculaId = \DB::table('matriculas')
            ->where('estudiante_id', $this->estudiante->id)
            ->where('oferta_academica_id', $this->oferta->id)
            ->value('id');

        // Dentro del rango del período (2026-01-15 .. 2026-06-30) y cuenta_como_falta
        AsistenciaEstudiante::create([
            'matricula_id' => $matriculaId,
            'oferta_academica_id' => $this->oferta->id,
            'fecha' => '2026-03-10',
            'estado' => 'falta',
            'cuenta_como_falta' => true,
            'observacion' => null,
            'registrado_por' => $this->admin->id,
        ]);
        // Fuera del rango del período -> no cuenta
        AsistenciaEstudiante::create([
            'matricula_id' => $matriculaId,
            'oferta_academica_id' => $this->oferta->id,
            'fecha' => '2026-07-10',
            'estado' => 'falta',
            'cuenta_como_falta' => true,
            'observacion' => null,
            'registrado_por' => $this->admin->id,
        ]);
        // Dentro del rango pero no cuenta como falta -> no suma
        AsistenciaEstudiante::create([
            'matricula_id' => $matriculaId,
            'oferta_academica_id' => $this->oferta->id,
            'fecha' => '2026-04-02',
            'estado' => 'justificada',
            'cuenta_como_falta' => false,
            'observacion' => null,
            'registrado_por' => $this->admin->id,
        ]);

        $response = $this->getJson('/api/v1/asistencias/faltas-por-oferta?oferta_academica_id='.$this->oferta->id, $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.estudiante_id', $this->estudiante->id)
            ->assertJsonPath('data.0.matricula_id', $matriculaId)
            ->assertJsonPath('data.0.faltas', 1)
            ->assertJsonPath('fecha_inicio', '2026-01-15')
            ->assertJsonPath('fecha_fin', '2026-06-30');
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

        $response = $this->getJson('/api/v1/calificaciones?oferta_academica_id='.$this->oferta->id, $this->headers());

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

        $response = $this->getJson('/api/v1/calificaciones/'.$cal->id, $this->headers());

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

        $response = $this->putJson('/api/v1/calificaciones/'.$cal->id, [
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

    public function test_docente_solo_ve_calificaciones_de_sus_ofertas(): void
    {
        $matricula = \DB::table('matriculas')->first();

        Calificacion::create([
            'codigo' => 'CAL-DOC-001',
            'matricula_id' => $matricula->id,
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'nota_final' => 88.0,
            'faltas' => 1,
            'docente_id' => $this->docente->id,
            'estado' => 'registrado',
        ]);

        $otroDocente = Docente::factory()->create(['codigo' => 'DOC999']);
        $otraOferta = OfertaAcademica::create([
            'codigo' => 'OF-OTRA-DOC',
            'sucursal_id' => $this->sucursal->id,
            'periodo_academico_id' => $this->periodo->id,
            'nivel_academico_id' => $this->nivel->id,
            'modalidad_id' => $this->modalidad->id,
            'horario_id' => $this->horario->id,
            'docente_id' => $otroDocente->id,
            'aula_id' => $this->aula->id,
            'plan_cobro_id' => $this->planCobro->id,
            'cupo_maximo' => 25,
            'cupos_reservados' => 0,
            'cupos_matriculados' => 0,
            'estado' => 'abierto',
        ]);
        $otroEstudiante = Estudiante::factory()->create(['sucursal_id' => $this->sucursal->id]);
        $otraMatriculaId = \DB::table('matriculas')->insertGetId([
            'codigo' => 'MAT-DOC-002',
            'estudiante_id' => $otroEstudiante->id,
            'oferta_academica_id' => $otraOferta->id,
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'matriculado',
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);
        Calificacion::create([
            'codigo' => 'CAL-DOC-002',
            'matricula_id' => $otraMatriculaId,
            'estudiante_id' => $otroEstudiante->id,
            'oferta_academica_id' => $otraOferta->id,
            'nota_final' => 72.0,
            'faltas' => 3,
            'docente_id' => $otroDocente->id,
            'estado' => 'registrado',
        ]);

        $rolDocente = Rol::create(['codigo' => 'DOC_SCOPE', 'nombre' => 'Docente Scope', 'estado' => 'activo']);
        $rolDocente->permisos()->attach(
            Permiso::where('codigo', 'calificaciones.consultar')->pluck('id')->toArray(),
            ['estado' => 'activo'],
        );
        $usuarioDocente = User::create([
            'name' => 'Docente Scope',
            'email' => 'docente-scope@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
            'docente_id' => $this->docente->id,
        ]);
        $usuarioDocente->roles()->attach($rolDocente->id, ['estado' => 'activo']);

        $response = $this->getJson('/api/v1/calificaciones', [
            'Authorization' => 'Bearer '.$usuarioDocente->createToken('test')->plainTextToken,
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.codigo', 'CAL-DOC-001');
    }

    public function test_docente_no_puede_ver_calificacion_de_oferta_ajena(): void
    {
        $matricula = \DB::table('matriculas')->first();

        $calificacion = Calificacion::create([
            'codigo' => 'CAL-FORB-001',
            'matricula_id' => $matricula->id,
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'nota_final' => 84.0,
            'faltas' => 1,
            'docente_id' => $this->docente->id,
            'estado' => 'registrado',
        ]);

        $rolDocente = Rol::create(['codigo' => 'DOC_FORBID', 'nombre' => 'Docente Ajeno', 'estado' => 'activo']);
        $rolDocente->permisos()->attach(
            Permiso::where('codigo', 'calificaciones.consultar')->pluck('id')->toArray(),
            ['estado' => 'activo'],
        );
        $usuarioDocente = User::create([
            'name' => 'Docente Ajeno',
            'email' => 'docente-ajeno@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
            'docente_id' => 999999,
        ]);
        $usuarioDocente->roles()->attach($rolDocente->id, ['estado' => 'activo']);

        $response = $this->getJson('/api/v1/calificaciones/'.$calificacion->id, [
            'Authorization' => 'Bearer '.$usuarioDocente->createToken('test')->plainTextToken,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('codigo_error', '403_OFERTA_NO_ASIGNADA');
    }

    public function test_caso_uso_registrar_rechaza_oferta_no_asignada(): void
    {
        $contexto = new ContextoUsuario($this->admin->id);

        $resultado = app(RegistrarCalificaciones::class)->ejecutar(
            $this->oferta->id,
            [['estudiante_id' => $this->estudiante->id, 'nota_final' => 85, 'faltas' => 0]],
            99999,
            $contexto,
        );

        $this->assertFalse($resultado->ok());
        $this->assertSame(403, $resultado->codigo());
        $this->assertSame('403_OFERTA_NO_ASIGNADA', $resultado->codigoError());
    }

    public function test_caso_uso_registrar_ignora_estudiante_sin_matricula(): void
    {
        $contexto = new ContextoUsuario($this->admin->id);

        $resultado = app(RegistrarCalificaciones::class)->ejecutar(
            $this->oferta->id,
            [['estudiante_id' => $this->estudiante->id, 'nota_final' => 85, 'faltas' => 0]],
            null,
            $contexto,
        );

        $this->assertTrue($resultado->ok());
        $this->assertSame(200, $resultado->codigo());
        $this->assertCount(1, $resultado->data()['calificaciones']);

        $historial = HistorialAcademico::where('estudiante_id', $this->estudiante->id)->first();
        $this->assertNotNull($historial);
        $this->assertSame('aprobado', $historial->estado);
    }

    public function test_caso_uso_actualizar_marca_corregido_y_sincroniza_historial(): void
    {
        $contexto = new ContextoUsuario($this->admin->id);

        $matricula = \DB::table('matriculas')->first();
        $cal = Calificacion::create([
            'codigo' => 'CAL-007',
            'matricula_id' => $matricula->id,
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'nota_final' => 90.0,
            'faltas' => 2,
            'docente_id' => $this->docente->id,
            'estado' => 'registrado',
        ]);

        $resultado = app(ActualizarCalificacion::class)->ejecutar(
            $cal->id,
            ['nota_final' => 82, 'faltas' => 2],
            null,
            $contexto,
        );

        $this->assertTrue($resultado->ok());
        $calificacion = $resultado->data()['calificacion'];
        $this->assertSame('corregido', $calificacion->estado);
        $this->assertSame(82.0, (float) $calificacion->nota_final);

        $historial = HistorialAcademico::where('estudiante_id', $this->estudiante->id)->first();
        $this->assertNotNull($historial);
        $this->assertSame('aprobado', $historial->estado);
    }

    public function test_caso_uso_actualizar_rechaza_oferta_no_asignada(): void
    {
        $contexto = new ContextoUsuario($this->admin->id);

        $matricula = \DB::table('matriculas')->first();
        $cal = Calificacion::create([
            'codigo' => 'CAL-008',
            'matricula_id' => $matricula->id,
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'nota_final' => 90.0,
            'faltas' => 2,
            'docente_id' => $this->docente->id,
            'estado' => 'registrado',
        ]);

        $resultado = app(ActualizarCalificacion::class)->ejecutar(
            $cal->id,
            ['nota_final' => 50],
            99999,
            $contexto,
        );

        $this->assertFalse($resultado->ok());
        $this->assertSame(403, $resultado->codigo());
        $this->assertSame('403_OFERTA_NO_ASIGNADA', $resultado->codigoError());
    }
}
