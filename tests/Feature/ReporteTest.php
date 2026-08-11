<?php

namespace Tests\Feature;

use App\Models\{Aula, Calificacion, ConceptoPago, DepartamentoAcademico, Docente, Estudiante, Horario, Matricula, MetodoPago, Modalidad, Modulo, NivelAcademico, OpcionModulo, OfertaAcademica, Permiso, PeriodoAcademico, PlanCobro, PlanEstudio, ReciboCaja, Rol, Sucursal, User, VersionPlanEstudio};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReporteTest extends TestCase
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
    private ConceptoPago $conceptoMat;
    private MetodoPago $metodoPago;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearPermisosBase();

        $rol = Rol::create(['codigo' => 'TEST_ADMIN', 'nombre' => 'Test Admin', 'estado' => 'activo']);
        $permisos = Permiso::where('codigo', 'like', 'reportes.%')->get();
        $rol->permisos()->attach($permisos->pluck('id')->toArray(), ['estado' => 'activo']);

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $this->admin->roles()->attach($rol->id, ['estado' => 'activo']);
        $this->token = $this->admin->createToken('test')->plainTextToken;
        DB::table('alcances_usuario')->insert([
            'usuario_id' => $this->admin->id,
            'tipo' => 'global',
            'estado' => 'activo',
        ]);

        $this->sucursal = Sucursal::factory()->create(['codigo' => 'SPS']);
        $this->periodo = PeriodoAcademico::create([
            'codigo' => '2026-I', 'nombre' => 'Semestre 1',
            'fecha_inicio' => '2026-01-15', 'fecha_fin' => '2026-06-30', 'estado' => 'activo',
        ]);

        $depto = DepartamentoAcademico::factory()->create(['codigo' => 'ING']);
        $plan = PlanEstudio::create(['departamento_academico_id' => $depto->id, 'codigo' => 'ING-GEN', 'nombre' => 'Inglés General']);
        $version = VersionPlanEstudio::create(['plan_estudio_id' => $plan->id, 'numero_version' => 1, 'vigente_desde' => '2026-01-01']);

        $this->regimen = Modalidad::create(['codigo' => 'INT', 'nombre' => 'Intensivo', 'tipo' => 'regimen_academico']);

        $this->nivel = NivelAcademico::create([
            'version_plan_estudio_id' => $version->id, 'regimen_academico_id' => $this->regimen->id,
            'codigo' => 'ING-1', 'nombre' => 'Inglés 1',
            'orden' => 1, 'nota_minima_aprobar' => 80, 'faltas_maximas_permitidas' => 7,
        ]);

        $this->modalidad = Modalidad::create(['codigo' => 'PRES', 'nombre' => 'Presencial', 'tipo' => 'atencion']);

        $this->horario = Horario::create(['codigo' => 'M1', 'nombre' => 'Matutino', 'hora_inicio' => '07:00', 'hora_fin' => '09:00']);
        $this->horario->update(['lunes' => true, 'miercoles' => true]);

        $this->docente = Docente::factory()->create(['codigo' => 'DOC001']);
        $this->aula = Aula::create(['sucursal_id' => $this->sucursal->id, 'codigo' => 'AUL-01', 'nombre' => 'Aula 1', 'capacidad' => 25]);

        $this->conceptoMat = ConceptoPago::create([
            'codigo' => 'MAT', 'nombre' => 'Matrícula', 'tipo_monto' => 'por_oferta',
            'requiere_autorizacion_monto' => false, 'estado' => 'activo',
        ]);
        $conceptoCuo = ConceptoPago::create([
            'codigo' => 'CUO', 'nombre' => 'Cuota', 'tipo_monto' => 'por_oferta',
            'requiere_autorizacion_monto' => false, 'estado' => 'activo',
        ]);

        $this->metodoPago = MetodoPago::create([
            'codigo' => 'DEP', 'nombre' => 'Depósito', 'tipo' => 'bancario', 'estado' => 'activo',
        ]);

        $this->planCobro = PlanCobro::create(['codigo' => 'PLN-TEST', 'nombre' => 'Plan Test', 'estado' => 'activo']);
        DB::table('detalle_plan_cobro')->insert([
            ['plan_cobro_id' => $this->planCobro->id, 'concepto_pago_id' => $this->conceptoMat->id, 'numero_cuota' => 0, 'nombre_cargo' => 'Matrícula', 'monto' => 1200.00, 'dias_vencimiento' => 0, 'estado' => 'activo', 'creado_en' => now(), 'actualizado_en' => now()],
            ['plan_cobro_id' => $this->planCobro->id, 'concepto_pago_id' => $conceptoCuo->id, 'numero_cuota' => 1, 'nombre_cargo' => 'Cuota 1', 'monto' => 1100.00, 'dias_vencimiento' => 30, 'estado' => 'activo', 'creado_en' => now(), 'actualizado_en' => now()],
        ]);

        $this->oferta = OfertaAcademica::create([
            'codigo' => 'OF-2026-ING1', 'sucursal_id' => $this->sucursal->id,
            'periodo_academico_id' => $this->periodo->id, 'nivel_academico_id' => $this->nivel->id,
            'modalidad_id' => $this->modalidad->id, 'horario_id' => $this->horario->id,
            'docente_id' => $this->docente->id, 'aula_id' => $this->aula->id,
            'plan_cobro_id' => $this->planCobro->id, 'cupo_maximo' => 25,
            'cupos_reservados' => 0, 'cupos_matriculados' => 0, 'estado' => 'abierto',
        ]);

        $this->estudiante = Estudiante::factory()->create([
            'sucursal_id' => $this->sucursal->id, 'codigo' => 'EST-001',
        ]);

        DB::table('matriculas')->insertGetId([
            'codigo' => 'MAT-2026-001', 'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id, 'sucursal_id' => $this->sucursal->id,
            'estado' => 'matriculado', 'creado_en' => now(),
        ]);
    }

    private function crearPermisosBase(): void
    {
        $modulo = Modulo::create(['codigo' => 'reportes', 'nombre' => 'Reportes', 'estado' => 'activo', 'orden' => 10]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'reportes.academicos', 'nombre' => 'Académicos', 'estado' => 'activo']);

        foreach (['consultar', 'exportar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcion->id,
                'codigo' => 'reportes.' . $accion,
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

    public function test_matriculados_por_periodo(): void
    {
        $response = $this->getJson('/api/v1/reportes/academicos/por-periodo?periodo_academico_id=' . $this->periodo->id, $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(1, 'data');
    }

    public function test_matriculados_por_sucursal(): void
    {
        $response = $this->getJson('/api/v1/reportes/academicos/por-sucursal', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');
    }

    public function test_matriculados_por_nivel(): void
    {
        $response = $this->getJson('/api/v1/reportes/academicos/por-nivel?periodo_academico_id=' . $this->periodo->id, $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');
    }

    public function test_matriculados_por_docente(): void
    {
        $response = $this->getJson('/api/v1/reportes/academicos/por-docente?periodo_academico_id=' . $this->periodo->id, $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');
    }

    public function test_grupo_alumnos(): void
    {
        $response = $this->getJson('/api/v1/reportes/academicos/grupo?oferta_academica_id=' . $this->oferta->id, $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(1, 'data');
    }

    public function test_calificaciones_por_grupo(): void
    {
        $response = $this->getJson('/api/v1/reportes/academicos/calificaciones-por-grupo?oferta_academica_id=' . $this->oferta->id, $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');
    }

    public function test_nivel_actual_estudiante(): void
    {
        $response = $this->getJson('/api/v1/reportes/academicos/nivel-actual?estudiante_id=' . $this->estudiante->id, $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.nivel_codigo', 'ING-1');
    }

    public function test_ingresos_por_concepto(): void
    {
        $response = $this->getJson('/api/v1/reportes/financieros/por-concepto?fecha_desde=2026-01-01&fecha_hasta=2026-12-31', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');
    }

    public function test_ingresos_por_metodo(): void
    {
        $response = $this->getJson('/api/v1/reportes/financieros/por-metodo?fecha_desde=2026-01-01&fecha_hasta=2026-12-31', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');
    }

    public function test_ingresos_por_sucursal(): void
    {
        $response = $this->getJson('/api/v1/reportes/financieros/por-sucursal?fecha_desde=2026-01-01&fecha_hasta=2026-12-31', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');
    }

    public function test_pagos_pendientes(): void
    {
        $response = $this->getJson('/api/v1/reportes/financieros/pagos-pendientes', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');
    }

    public function test_pagos_rechazados(): void
    {
        $response = $this->getJson('/api/v1/reportes/financieros/pagos-rechazados', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');
    }

    public function test_recibos_por_orden(): void
    {
        $response = $this->getJson('/api/v1/reportes/recibos/por-orden?fecha_desde=2026-01-01&fecha_hasta=2026-12-31', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');
    }

    public function test_recibos_por_metodo(): void
    {
        $response = $this->getJson('/api/v1/reportes/recibos/por-metodo?fecha_desde=2026-01-01&fecha_hasta=2026-12-31', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');
    }

    public function test_recibos_por_concepto(): void
    {
        $response = $this->getJson('/api/v1/reportes/recibos/por-concepto?fecha_desde=2026-01-01&fecha_hasta=2026-12-31', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');
    }

    public function test_recibos_anulados(): void
    {
        $response = $this->getJson('/api/v1/reportes/recibos/anulados', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');
    }

    public function test_caja_por_cajero(): void
    {
        $response = $this->getJson('/api/v1/reportes/caja/por-cajero?fecha_desde=2026-01-01&fecha_hasta=2026-12-31', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');
    }

    public function test_caja_resumen_diario(): void
    {
        $response = $this->getJson('/api/v1/reportes/caja/resumen-diario?fecha_desde=2026-01-01&fecha_hasta=2026-12-31', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');
    }

    public function test_reportes_requiere_permiso(): void
    {
        $user = User::create([
            'name' => 'Sin Permiso', 'email' => 'sin@test.com',
            'password' => bcrypt('password'), 'estado' => 'activo',
        ]);
        $rol = Rol::create(['codigo' => 'BASIC', 'nombre' => 'Básico', 'estado' => 'activo']);
        $user->roles()->attach($rol->id, ['estado' => 'activo']);
        $token2 = $user->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/v1/reportes/academicos/por-periodo', [
            'Authorization' => "Bearer {$token2}",
        ]);

        $response->assertStatus(403);
    }
}
