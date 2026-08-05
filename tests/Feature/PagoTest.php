<?php

namespace Tests\Feature;

use App\Models\{Aula, CuentaBancaria, DepartamentoAcademico, Docente, EnlacePago, Estudiante, Horario, Matricula, Modalidad, Modulo, NivelAcademico, ObligacionPagoEstudiante, OpcionModulo, OfertaAcademica, Permiso, PeriodoAcademico, PlanCobro, PlanEstudio, Rol, Sucursal, User, VersionPlanEstudio};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PagoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $token;
    private Sucursal $sucursal;
    private Estudiante $estudiante;
    private string $studentToken;
    private Matricula $matricula;
    private int $conceptoMatId;
    private int $conceptoCuoId;
    private int $metodoEfeId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearPermisosBase();

        $rol = Rol::create(['codigo' => 'TEST_ADMIN', 'nombre' => 'Test Admin', 'estado' => 'activo']);
        $permisos = Permiso::where('codigo', 'like', 'pagos.%')->get();
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

        $this->conceptoMatId = DB::table('conceptos_pago')->insertGetId([
            'codigo' => 'MAT', 'nombre' => 'Matrícula', 'tipo_monto' => 'por_oferta',
            'requiere_autorizacion_monto' => false, 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);
        $this->conceptoCuoId = DB::table('conceptos_pago')->insertGetId([
            'codigo' => 'CUO', 'nombre' => 'Cuota', 'tipo_monto' => 'por_oferta',
            'requiere_autorizacion_monto' => false, 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);
        $this->metodoEfeId = DB::table('metodos_pago')->insertGetId([
            'codigo' => 'EFE', 'nombre' => 'Efectivo', 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);

        $this->estudiante = Estudiante::factory()->create(['sucursal_id' => $this->sucursal->id]);
        $this->studentToken = 'student-test-token';
        DB::table('accesos_estudiante')->insert([
            'estudiante_id' => $this->estudiante->id,
            'email' => 'student@test.com',
            'password' => 'password',
            'token' => hash('sha256', $this->studentToken),
            'estado' => 'activo',
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);

        $planCobro = PlanCobro::create(['codigo' => 'PLN-TEST', 'nombre' => 'Plan Test', 'estado' => 'activo']);
        DB::table('detalle_plan_cobro')->insert([
            ['plan_cobro_id' => $planCobro->id, 'concepto_pago_id' => $this->conceptoMatId, 'numero_cuota' => 0, 'nombre_cargo' => 'Matrícula', 'monto' => 1200.00, 'dias_vencimiento' => 0, 'estado' => 'activo', 'creado_en' => now(), 'actualizado_en' => now()],
            ['plan_cobro_id' => $planCobro->id, 'concepto_pago_id' => $this->conceptoCuoId, 'numero_cuota' => 1, 'nombre_cargo' => 'Cuota 1', 'monto' => 1100.00, 'dias_vencimiento' => 30, 'estado' => 'activo', 'creado_en' => now(), 'actualizado_en' => now()],
        ]);

        $depto = DepartamentoAcademico::factory()->create(['codigo' => 'ING']);
        $plan = PlanEstudio::create(['departamento_academico_id' => $depto->id, 'codigo' => 'ING-GEN', 'nombre' => 'Inglés General']);
        $version = VersionPlanEstudio::create(['plan_estudio_id' => $plan->id, 'numero_version' => 1, 'vigente_desde' => '2026-01-01']);
        $regimen = Modalidad::create(['codigo' => 'INT', 'nombre' => 'Intensivo', 'tipo' => 'regimen_academico']);
        $nivel = NivelAcademico::create(['version_plan_estudio_id' => $version->id, 'regimen_academico_id' => $regimen->id, 'codigo' => 'ING-1', 'nombre' => 'Inglés 1', 'orden' => 1, 'nota_minima_aprobar' => 80, 'faltas_maximas_permitidas' => 7]);
        $modalidad = Modalidad::create(['codigo' => 'PRES', 'nombre' => 'Presencial', 'tipo' => 'atencion']);
        $horario = Horario::create(['codigo' => 'M1', 'nombre' => 'Matutino', 'hora_inicio' => '07:00', 'hora_fin' => '09:00']);
        $horario->update(['lunes' => true]);
        $docente = Docente::factory()->create(['codigo' => 'DOC001']);
        $aula = Aula::create(['sucursal_id' => $this->sucursal->id, 'codigo' => 'AUL-01', 'nombre' => 'Aula 1', 'capacidad' => 25]);
        $periodo = PeriodoAcademico::create(['codigo' => '2026-I', 'nombre' => 'Semestre 1', 'fecha_inicio' => '2026-01-15', 'fecha_fin' => '2026-06-30', 'estado' => 'activo']);

        $oferta = OfertaAcademica::create([
            'sucursal_id' => $this->sucursal->id, 'periodo_academico_id' => $periodo->id,
            'nivel_academico_id' => $nivel->id, 'modalidad_id' => $modalidad->id,
            'horario_id' => $horario->id, 'docente_id' => $docente->id,
            'aula_id' => $aula->id, 'plan_cobro_id' => $planCobro->id,
            'codigo' => 'SPS-2026I-ING1-INT-MAT', 'cupo_maximo' => 25, 'estado' => 'abierto',
        ]);

        $this->matricula = Matricula::create([
            'codigo' => 'MAT-001', 'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $oferta->id, 'sucursal_id' => $this->sucursal->id,
            'estado' => 'matriculado', 'fecha_reserva' => now(), 'fecha_confirmacion' => now(),
        ]);

        ObligacionPagoEstudiante::insert([
            ['matricula_id' => $this->matricula->id, 'concepto_pago_id' => $this->conceptoMatId, 'numero_cuota' => 0, 'nombre_cargo' => 'Matrícula', 'monto' => 1200.00, 'monto_pagado' => 0, 'fecha_vencimiento' => now(), 'estado' => 'pendiente', 'creado_en' => now(), 'actualizado_en' => now()],
            ['matricula_id' => $this->matricula->id, 'concepto_pago_id' => $this->conceptoCuoId, 'numero_cuota' => 1, 'nombre_cargo' => 'Cuota 1', 'monto' => 1100.00, 'monto_pagado' => 0, 'fecha_vencimiento' => now()->addDays(30), 'estado' => 'pendiente', 'creado_en' => now(), 'actualizado_en' => now()],
        ]);
    }

    private function crearPermisosBase(): void
    {
        $modulo = Modulo::create(['codigo' => 'pagos', 'nombre' => 'Pagos', 'estado' => 'activo', 'orden' => 7]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'pagos.general', 'nombre' => 'General', 'estado' => 'activo']);

        foreach (['consultar', 'crear', 'modificar', 'eliminar', 'aprobar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcion->id,
                'codigo' => 'pagos.' . $accion,
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

    public function test_registrar_pago(): void
    {
        $response = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
        ], $this->headers());

        $response->assertCreated()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.estado', 'aprobado');

        $this->assertDatabaseHas('pagos', [
            'estudiante_id' => $this->estudiante->id,
            'estado' => 'aprobado',
        ]);

        $this->assertDatabaseHas('recibos_caja', [
            'pago_id' => $response->json('data.id'),
            'estado' => 'emitido',
        ]);
    }

    public function test_deposito_requiere_cuenta_bancaria_activa_y_la_conserva_en_el_pago(): void
    {
        $metodoDepositoId = DB::table('metodos_pago')->insertGetId([
            'codigo' => 'DEP', 'nombre' => 'Depósito', 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);
        $cuenta = CuentaBancaria::create([
            'codigo' => 'BAC-DEP-TEST', 'nombre' => 'Cuenta de depósito', 'banco' => 'BAC',
            'numero_cuenta' => '123456789', 'tipo_cuenta' => 'ahorro', 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);
        $datosBase = [
            'estudiante_id' => $this->estudiante->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $metodoDepositoId,
            'monto' => 1200.00,
        ];

        $this->postJson('/api/v1/pagos/registrar', $datosBase, $this->headers())
            ->assertStatus(422)
            ->assertJsonPath('codigo_error', '422_CUENTA_BANCARIA_REQUERIDA');

        $response = $this->postJson('/api/v1/pagos/registrar', [
            ...$datosBase,
            'cuenta_bancaria_id' => $cuenta->id,
        ], $this->headers());

        $response->assertCreated()
            ->assertJsonPath('data.cuenta_bancaria_id', $cuenta->id);
        $this->assertDatabaseHas('pagos', ['id' => $response->json('data.id'), 'cuenta_bancaria_id' => $cuenta->id]);
    }

    public function test_consulta_obligaciones_pendientes_por_concepto_para_pago_administrativo(): void
    {
        $response = $this->getJson('/api/v1/pagos/obligaciones-estudiante?estudiante_id=' . $this->estudiante->id . '&concepto_pago_id=' . $this->conceptoCuoId, $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.habilita_seleccion_obligaciones', true)
            ->assertJsonCount(1, 'data.obligaciones')
            ->assertJsonPath('data.obligaciones.0.concepto', 'CUO')
            ->assertJsonPath('data.obligaciones.0.saldo', 1100);
    }

    public function test_aprobar_pago_aplica_a_obligaciones(): void
    {
        $pago = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
        ], $this->headers())->json('data');

        $this->assertEquals('aprobado', $pago['estado']);

        $obligacion = ObligacionPagoEstudiante::where('matricula_id', $this->matricula->id)
            ->where('concepto_pago_id', $this->conceptoMatId)->first();
        $this->assertEquals(1200.00, $obligacion->monto_pagado);
        $this->assertEquals('pagado', $obligacion->estado);
    }

    public function test_rechazar_pago_solo_pendientes(): void
    {
        $pago = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
        ], $this->headers())->json('data');

        $response = $this->postJson("/api/v1/pagos/{$pago['id']}/rechazar", [
            'motivo_rechazo' => 'Comprobante ilegible',
        ], $this->headers());

        $response->assertStatus(422);
    }

    public function test_listar_pagos(): void
    {
        $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
        ], $this->headers());

        $response = $this->getJson('/api/v1/pagos', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(1, 'data.data');
    }

    public function test_ver_detalle_pago(): void
    {
        $pago = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
        ], $this->headers())->json('data');

        $response = $this->getJson("/api/v1/pagos/{$pago['id']}", $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonStructure([
                'data' => ['id', 'codigo', 'estudiante', 'concepto_pago', 'metodo_pago', 'sucursal'],
            ]);
    }

    public function test_listar_recibos(): void
    {
        $pago = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
        ], $this->headers())->json('data');

        $this->postJson("/api/v1/pagos/{$pago['id']}/aprobar", [], $this->headers());

        $response = $this->getJson('/api/v1/recibos-caja', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(1, 'data.data');
    }

    public function test_anular_recibo(): void
    {
        $pago = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
        ], $this->headers())->json('data');

        $this->postJson("/api/v1/pagos/{$pago['id']}/aprobar", [], $this->headers());

        $recibo = \App\Models\ReciboCaja::where('pago_id', $pago['id'])->first();

        $response = $this->postJson("/api/v1/recibos-caja/{$recibo->id}/anular", [
            'motivo_anulacion' => 'Error en monto',
        ], $this->headers());

        $response->assertOk()
            ->assertJsonPath('data.estado', 'anulado');
    }

    public function test_crear_enlace_pago(): void
    {
        $cuenta = CuentaBancaria::create([
            'codigo' => 'BAC-TEST', 'nombre' => 'BAC Test', 'banco' => 'BAC Honduras',
            'numero_cuenta' => '123456789', 'tipo_cuenta' => 'ahorro', 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);

        $response = $this->postJson('/api/v1/enlaces-pago', [
            'codigo' => 'LNK-TEST-001',
            'nombre' => 'Link Test',
            'monto' => 1200.00,
            'concepto_pago_id' => $this->conceptoMatId,
            'cuenta_bancaria_id' => $cuenta->id,
            'usos_maximos' => 50,
            'fecha_vencimiento' => '2026-12-31',
        ], $this->headers());

        $response->assertCreated()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.codigo', 'LNK-TEST-001')
            ->assertJsonPath('data.usos_actuales', 0);
    }

    public function test_listar_enlaces_pago(): void
    {
        $cuenta = CuentaBancaria::create([
            'codigo' => 'BAC-TEST', 'nombre' => 'BAC Test', 'banco' => 'BAC Honduras',
            'numero_cuenta' => '123456789', 'tipo_cuenta' => 'ahorro', 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);

        EnlacePago::create([
            'codigo' => 'LNK-LIST', 'nombre' => 'Link List', 'monto' => 500.00,
            'concepto_pago_id' => $this->conceptoMatId, 'cuenta_bancaria_id' => $cuenta->id,
            'usos_maximos' => 10, 'usos_actuales' => 0, 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);

        $response = $this->getJson('/api/v1/enlaces-pago', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(1, 'data');
    }

    public function test_eliminar_enlace_pago(): void
    {
        $cuenta = CuentaBancaria::create([
            'codigo' => 'BAC-TEST', 'nombre' => 'BAC Test', 'banco' => 'BAC Honduras',
            'numero_cuenta' => '123456789', 'tipo_cuenta' => 'ahorro', 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);

        $enlace = EnlacePago::create([
            'codigo' => 'LNK-DEL', 'nombre' => 'Link Delete', 'monto' => 500.00,
            'concepto_pago_id' => $this->conceptoMatId, 'cuenta_bancaria_id' => $cuenta->id,
            'usos_maximos' => 10, 'usos_actuales' => 0, 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);

        $response = $this->deleteJson("/api/v1/enlaces-pago/{$enlace->id}", [], $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');

        $this->assertDatabaseHas('enlaces_pago', ['id' => $enlace->id, 'estado' => 'inactivo']);
    }

    public function test_enlace_pago_disponible(): void
    {
        $cuenta = CuentaBancaria::create([
            'codigo' => 'BAC-TEST', 'nombre' => 'BAC Test', 'banco' => 'BAC Honduras',
            'numero_cuenta' => '123456789', 'tipo_cuenta' => 'ahorro', 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);

        $enlace = EnlacePago::create([
            'codigo' => 'LNK-DISP', 'nombre' => 'Link Disponible', 'monto' => 1200.00,
            'concepto_pago_id' => $this->conceptoMatId, 'cuenta_bancaria_id' => $cuenta->id,
            'usos_maximos' => 10, 'usos_actuales' => 0, 'estado' => 'activo',
            'fecha_vencimiento' => '2026-12-31', 'creado_en' => now(), 'actualizado_en' => now(),
        ]);

        $response = $this->postJson("/api/v1/enlaces-pago/{$enlace->id}/usar", [], $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');

        $this->assertDatabaseHas('enlaces_pago', ['id' => $enlace->id, 'usos_actuales' => 1]);
    }

    public function test_requiere_permiso_para_registrar(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertForbidden();
    }

    public function test_registrar_pago_solicita_link_y_pegar_link(): void
    {
        $response = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
            'solicitar_link' => true,
        ], $this->headers());

        $response->assertCreated()
            ->assertJsonPath('data.estado', 'solicita_link');

        $pagoId = $response->json('data.id');

        $actualizar = $this->postJson("/api/v1/pagos/{$pagoId}/link-pago", [
            'link_pago_url' => 'https://pago.ejemplo/test-link',
        ], $this->headers());

        $actualizar->assertOk()
            ->assertJsonPath('data.link_pago_url', 'https://pago.ejemplo/test-link')
            ->assertJsonPath('data.link_pago_estado', 'enviado');

        $this->assertDatabaseHas('pagos', [
            'id' => $pagoId,
            'estado' => 'solicita_link',
            'link_pago_url' => 'https://pago.ejemplo/test-link',
        ]);
    }

    public function test_listar_solicitudes_de_link_incluye_pagos_solicita_link(): void
    {
        $response = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
            'solicitar_link' => true,
        ], $this->headers());

        $pagoId = $response->json('data.id');

        $listado = $this->getJson('/api/v1/pagos?estado=solicita_link&per_page=50', $this->headers());

        $listado->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonFragment(['id' => $pagoId]);
    }

    public function test_confirmar_link_pago_mueve_a_revision(): void
    {
        $response = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
            'solicitar_link' => true,
        ], $this->headers());

        $pagoId = $response->json('data.id');

        $this->assertDatabaseHas('aplicaciones_pago', [
            'pago_id' => $pagoId,
            'estado' => 'activo',
        ]);

        $this->postJson("/api/v1/pagos/{$pagoId}/link-pago", [
            'link_pago_url' => 'https://pago.ejemplo/test-link',
        ], $this->headers());

        $confirmar = $this->postJson('/api/v1/estudiantes/confirmar-link-pago', [
            'pago_id' => $pagoId,
        ], ['Authorization' => "Bearer {$this->studentToken}"]);

        $confirmar->assertOk()
            ->assertJsonPath('resultado', 'A');

        $this->assertDatabaseHas('pagos', [
            'id' => $pagoId,
            'estado' => 'en_revision',
            'link_pago_estado' => 'ejecutado',
        ]);
    }

    public function test_registrar_pago_link_sin_obligaciones_explicitas_toma_todas_las_pendientes(): void
    {
        $response = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
            'solicitar_link' => true,
        ], $this->headers());

        $response->assertCreated();
        $pagoId = $response->json('data.id');

        $this->assertDatabaseHas('aplicaciones_pago', [
            'pago_id' => $pagoId,
            'estado' => 'activo',
        ]);
    }

    public function test_rechazar_pago_en_solicita_link(): void
    {
        $response = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
            'solicitar_link' => true,
        ], $this->headers());

        $pagoId = $response->json('data.id');

        $rechazo = $this->postJson("/api/v1/pagos/{$pagoId}/rechazar", [
            'motivo_rechazo' => 'Link inválido',
        ], $this->headers());

        $rechazo->assertOk()
            ->assertJsonPath('resultado', 'A');

        $this->assertDatabaseHas('pagos', [
            'id' => $pagoId,
            'estado' => 'rechazado',
            'link_pago_estado' => 'rechazado',
        ]);
    }

    public function test_eliminar_pago_por_completo(): void
    {
        $pago = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
        ], $this->headers())->json('data');

        $response = $this->deleteJson("/api/v1/pagos/{$pago['id']}/eliminar-total", [], $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');

        $this->assertDatabaseMissing('pagos', ['id' => $pago['id']]);
        $this->assertDatabaseMissing('recibos_caja', ['pago_id' => $pago['id']]);
    }
}
