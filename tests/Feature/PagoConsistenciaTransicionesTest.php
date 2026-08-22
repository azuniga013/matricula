<?php

namespace Tests\Feature;

use App\Models\AccesoEstudiante;
use App\Models\Aula;
use App\Models\ConceptoPago;
use App\Models\DepartamentoAcademico;
use App\Models\DetallePlanCobro;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\MetodoPago;
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
use App\Models\SesionCaja;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\VersionPlanEstudio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PagoConsistenciaTransicionesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $token;

    private Estudiante $estudiante;

    private string $studentToken;

    private Matricula $matricula;

    private int $conceptoMatId;

    private int $conceptoCuoId;

    private int $metodoEfeId;

    private int $obligacionMatId;

    private int $obligacionCuoId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearPermisosBase();

        $rol = Rol::create(['codigo' => 'TEST_PAGOS', 'nombre' => 'Test Pagos', 'estado' => 'activo']);
        $permisos = Permiso::where('codigo', 'like', 'pagos.%')->pluck('id')->all();
        $rol->permisos()->attach($permisos, ['estado' => 'activo']);

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $this->admin->roles()->attach($rol->id, ['estado' => 'activo']);
        $this->token = $this->admin->createToken('test')->plainTextToken;

        $sucursal = Sucursal::factory()->create(['codigo' => 'SPS']);
        SesionCaja::create([
            'codigo' => 'SCA-TEST-PCS',
            'sucursal_id' => $sucursal->id,
            'usuario_cajero_id' => $this->admin->id,
            'monto_inicial' => 100,
            'estado' => 'abierta',
            'fecha_apertura' => now(),
        ]);
        $this->estudiante = Estudiante::factory()->create(['sucursal_id' => $sucursal->id, 'estado' => 'activo']);

        $rawToken = Str::random(60);
        AccesoEstudiante::create([
            'estudiante_id' => $this->estudiante->id,
            'email' => 'portal@test.com',
            'password' => 'password',
            'token' => hash('sha256', $rawToken),
            'estado' => 'activo',
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);
        $this->studentToken = $rawToken;

        $this->conceptoMatId = ConceptoPago::create([
            'codigo' => 'MAT',
            'nombre' => 'Matricula',
            'tipo_monto' => 'por_oferta',
            'requiere_autorizacion_monto' => false,
            'estado' => 'activo',
        ])->id;
        $this->conceptoCuoId = ConceptoPago::create([
            'codigo' => 'CUO',
            'nombre' => 'Cuota',
            'tipo_monto' => 'por_oferta',
            'requiere_autorizacion_monto' => false,
            'estado' => 'activo',
        ])->id;
        $this->metodoEfeId = MetodoPago::create([
            'codigo' => 'EFE',
            'nombre' => 'Efectivo',
            'estado' => 'activo',
            'permite_link_pago' => false,
        ])->id;

        $planCobro = PlanCobro::create(['codigo' => 'PC-TEST', 'nombre' => 'Plan Test', 'estado' => 'activo']);
        DetallePlanCobro::create([
            'plan_cobro_id' => $planCobro->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'numero_cuota' => 0,
            'nombre_cargo' => 'Matricula',
            'monto' => 1200,
            'dias_vencimiento' => 0,
            'estado' => 'activo',
        ]);
        DetallePlanCobro::create([
            'plan_cobro_id' => $planCobro->id,
            'concepto_pago_id' => $this->conceptoCuoId,
            'numero_cuota' => 1,
            'nombre_cargo' => 'Cuota 1',
            'monto' => 1100,
            'dias_vencimiento' => 30,
            'estado' => 'activo',
        ]);

        $depto = DepartamentoAcademico::factory()->create(['codigo' => 'ING']);
        $plan = PlanEstudio::create(['departamento_academico_id' => $depto->id, 'codigo' => 'ING-GEN', 'nombre' => 'Ingles General']);
        $version = VersionPlanEstudio::create(['plan_estudio_id' => $plan->id, 'numero_version' => 1, 'vigente_desde' => '2026-01-01']);
        $regimen = Modalidad::create(['codigo' => 'INT', 'nombre' => 'Intensivo', 'tipo' => 'regimen_academico']);
        $nivel = NivelAcademico::create([
            'version_plan_estudio_id' => $version->id,
            'regimen_academico_id' => $regimen->id,
            'codigo' => 'ING-1',
            'nombre' => 'Ingles 1',
            'orden' => 1,
            'nota_minima_aprobar' => 80,
            'faltas_maximas_permitidas' => 7,
        ]);
        $modalidad = Modalidad::create(['codigo' => 'PRES', 'nombre' => 'Presencial', 'tipo' => 'atencion']);
        $horario = Horario::create(['codigo' => 'M1', 'nombre' => 'Matutino', 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);
        $horario->update(['lunes' => true]);
        $docente = Docente::factory()->create(['codigo' => 'DOC001']);
        $aula = Aula::create(['sucursal_id' => $sucursal->id, 'codigo' => 'AUL-01', 'nombre' => 'Aula 1', 'capacidad' => 25]);
        $periodo = PeriodoAcademico::create([
            'codigo' => '2026-I',
            'nombre' => 'Semestre 1',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addMonths(4)->toDateString(),
            'estado' => 'activo',
        ]);

        $oferta = OfertaAcademica::create([
            'sucursal_id' => $sucursal->id,
            'periodo_academico_id' => $periodo->id,
            'nivel_academico_id' => $nivel->id,
            'modalidad_id' => $modalidad->id,
            'horario_id' => $horario->id,
            'docente_id' => $docente->id,
            'aula_id' => $aula->id,
            'plan_cobro_id' => $planCobro->id,
            'codigo' => 'SPS-2026I-ING1-INT-MAT',
            'cupo_maximo' => 25,
            'estado' => 'abierto',
        ]);

        $this->matricula = Matricula::create([
            'codigo' => 'MAT-001',
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $oferta->id,
            'sucursal_id' => $sucursal->id,
            'estado' => 'reservada',
            'fecha_reserva' => now(),
        ]);

        $this->obligacionMatId = ObligacionPagoEstudiante::create([
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'numero_cuota' => 0,
            'nombre_cargo' => 'Matricula',
            'monto' => 1200,
            'monto_pagado' => 0,
            'fecha_vencimiento' => now(),
            'estado' => 'pendiente',
        ])->id;
        $this->obligacionCuoId = ObligacionPagoEstudiante::create([
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoCuoId,
            'numero_cuota' => 1,
            'nombre_cargo' => 'Cuota 1',
            'monto' => 1100,
            'monto_pagado' => 0,
            'fecha_vencimiento' => now()->addDays(30),
            'estado' => 'pendiente',
        ])->id;
    }

    private function crearPermisosBase(): void
    {
        $modulo = Modulo::create(['codigo' => 'pagos', 'nombre' => 'Pagos', 'estado' => 'activo', 'orden' => 7]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'pagos.general', 'nombre' => 'General', 'estado' => 'activo']);

        foreach (['consultar', 'crear', 'modificar', 'eliminar', 'aprobar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcion->id,
                'codigo' => 'pagos.'.$accion,
                'nombre' => ucfirst($accion),
                'accion' => $accion,
                'estado' => 'activo',
            ]);
        }
    }

    private function adminHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    private function studentHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->studentToken}"];
    }

    public function test_pago_administrativo_rechaza_solicitar_link_con_metodo_que_no_lo_permite(): void
    {
        $response = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200,
            'monto_recibido' => 1200,
            'solicitar_link' => true,
        ], $this->adminHeaders());

        $response->assertStatus(422)
            ->assertJsonPath('codigo_error', '422_METODO_NO_PERMITE_LINK');

        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_pago_administrativo_rechaza_pagar_cuota_sin_incluir_matricula_pendiente(): void
    {
        $response = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoCuoId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1100,
            'monto_recibido' => 1100,
            'obligaciones' => [
                ['obligacion_id' => $this->obligacionCuoId, 'monto_aplicado' => 1100],
            ],
        ], $this->adminHeaders());

        $response->assertStatus(422)
            ->assertJsonPath('codigo_error', '422_MATRICULA_OBLIGATORIA');

        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_portal_rechaza_solicitar_link_con_metodo_que_no_lo_permite(): void
    {
        $response = $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $this->matricula->id,
            'metodo_pago_id' => $this->metodoEfeId,
            'solicitar_link' => true,
            'obligacion_ids' => [$this->obligacionMatId],
        ], $this->studentHeaders());

        $response->assertStatus(422)
            ->assertJsonPath('codigo_error', '422_METODO_NO_PERMITE_LINK');

        $this->assertDatabaseCount('pagos', 0);
    }
}
