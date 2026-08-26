<?php

namespace Tests\Feature;

use App\Models\Aula;
use App\Models\CuentaBancaria;
use App\Models\DepartamentoAcademico;
use App\Models\Docente;
use App\Models\EnlacePago;
use App\Models\Estudiante;
use App\Models\Horario;
use App\Models\InventarioLibro;
use App\Models\Libro;
use App\Models\Matricula;
use App\Models\Modalidad;
use App\Models\Modulo;
use App\Models\NivelAcademico;
use App\Models\ObligacionPagoEstudiante;
use App\Models\OfertaAcademica;
use App\Models\OpcionModulo;
use App\Models\Pago;
use App\Models\PeriodoAcademico;
use App\Models\Permiso;
use App\Models\PlanCobro;
use App\Models\PlanEstudio;
use App\Models\ReciboCaja;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\VersionPlanEstudio;
use App\Services\ResolverEnlacePagoDisponible;
use App\Modules\Comun\ContextoUsuario;
use App\Modules\Pagos\CasosUso\ActualizarLinkPago;
use App\Modules\Pagos\CasosUso\AprobarPago;
use App\Modules\Pagos\CasosUso\EliminarPagoTotal;
use App\Modules\Pagos\CasosUso\RechazarPago;
use App\Modules\Pagos\CasosUso\RegistrarPago;
use App\Modules\Pagos\CasosUso\SubirComprobantePago;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

    private int $metodoLinkId;

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
        DB::table('alcances_usuario')->insert([
            'usuario_id' => $this->admin->id,
            'tipo' => 'global',
            'estado' => 'activo',
        ]);

        $this->sucursal = Sucursal::factory()->create(['codigo' => 'SPS']);
        DB::table('sesiones_caja')->insert([
            'codigo' => 'SCA-TEST-001',
            'sucursal_id' => $this->sucursal->id,
            'usuario_cajero_id' => $this->admin->id,
            'monto_inicial' => 100.00,
            'estado' => 'abierta',
            'fecha_apertura' => now(),
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);

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
        $this->metodoLinkId = DB::table('metodos_pago')->insertGetId([
            'codigo' => 'LNK', 'nombre' => 'Link de pago', 'estado' => 'activo', 'permite_link_pago' => true,
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
                'codigo' => 'pagos.'.$accion,
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
            'monto_recibido' => 1200.00,
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

    public function test_efectivo_requiere_monto_recibido_y_guarda_vuelto(): void
    {
        $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
        ], $this->headers())
            ->assertStatus(422)
            ->assertJsonPath('codigo_error', '422_MONTO_RECIBIDO_REQUERIDO');

        $response = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
            'monto_recibido' => 1500.00,
        ], $this->headers());

        $response->assertCreated()
            ->assertJsonPath('data.monto_recibido', '1500.00')
            ->assertJsonPath('data.vuelto', '300.00');

        $this->assertDatabaseHas('pagos', [
            'id' => $response->json('data.id'),
            'monto_recibido' => 1500.00,
            'vuelto' => 300.00,
        ]);
    }

    public function test_registrar_pago_administrativo_requiere_sesion_de_caja_abierta(): void
    {
        DB::table('sesiones_caja')->delete();

        $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
            'monto_recibido' => 1200.00,
        ], $this->headers())
            ->assertStatus(422)
            ->assertJsonPath('codigo_error', '422_SESION_CAJA_REQUERIDA');
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
        $response = $this->getJson('/api/v1/pagos/obligaciones-estudiante?estudiante_id='.$this->estudiante->id.'&concepto_pago_id='.$this->conceptoCuoId, $this->headers());

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

        $recibo = ReciboCaja::where('pago_id', $pago['id'])->first();

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
            'metodo_pago_id' => $this->metodoLinkId,
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
            ->assertJsonPath('data.estado', 'esperando_respuesta')
            ->assertJsonPath('data.link_pago_estado', 'enviado');

        $this->assertDatabaseHas('pagos', [
            'id' => $pagoId,
            'estado' => 'esperando_respuesta',
            'link_pago_url' => 'https://pago.ejemplo/test-link',
        ]);

    }

    public function test_registrar_pago_asigna_link_anticipado_disponible(): void
    {
        $enlace = EnlacePago::create([
            'codigo' => 'LNK-ANT-001',
            'nombre' => 'Link anticipado matrícula',
            'enlace_url' => 'https://pago.ejemplo/ant-001',
            'monto_objetivo' => 1200.00,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoLinkId,
            'estado' => 'activo',
            'estado_operativo' => 'disponible',
            'usos_actuales' => 0,
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);

        $response = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoLinkId,
            'monto' => 1200.00,
            'solicitar_link' => true,
        ], $this->headers());

        $response->assertCreated()
            ->assertJsonPath('data.estado', 'esperando_respuesta')
            ->assertJsonPath('data.link_pago_url', $enlace->enlace_url);

        $this->assertDatabaseHas('enlaces_pago', [
            'id' => $enlace->id,
            'estado_operativo' => 'reservado',
            'usos_actuales' => 1,
        ]);
    }

    public function test_listar_solicitudes_de_link_incluye_pagos_solicita_link(): void
    {
        $response = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoLinkId,
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
            'metodo_pago_id' => $this->metodoLinkId,
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
            'metodo_pago_id' => $this->metodoLinkId,
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
            'metodo_pago_id' => $this->metodoLinkId,
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

    public function test_aprobar_pago_mediante_caso_de_uso_genera_recibo(): void
    {
        $pago = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoLinkId,
            'monto' => 1200.00,
            'solicitar_link' => true,
        ], $this->headers())->json('data');

        $this->postJson("/api/v1/pagos/{$pago['id']}/link-pago", [
            'link_pago_url' => 'https://pago.ejemplo/test-link',
        ], $this->headers());

        $this->postJson('/api/v1/estudiantes/confirmar-link-pago', [
            'pago_id' => $pago['id'],
        ], ['Authorization' => "Bearer {$this->studentToken}"])->assertOk();

        $casoUso = app(AprobarPago::class);
        $resultado = $casoUso->ejecutar($pago['id'], new ContextoUsuario($this->admin->id));

        $this->assertTrue($resultado->ok());
        $this->assertSame('Pago aprobado y recibo generado', $resultado->mensaje());
        $this->assertSame('aprobado', $resultado->data()['pago']->estado);
        $this->assertNotNull($resultado->data()['recibo']);
        $this->assertDatabaseHas('recibos_caja', ['pago_id' => $pago['id'], 'estado' => 'emitido']);

        $obligacion = ObligacionPagoEstudiante::where('matricula_id', $this->matricula->id)
            ->where('concepto_pago_id', $this->conceptoMatId)->first();
        $this->assertEquals(1200.00, $obligacion->monto_pagado);
        $this->assertEquals('pagado', $obligacion->estado);
    }

    public function test_aprobar_pago_mediante_caso_de_uso_rechaza_estado_no_aprobable(): void
    {
        $pago = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
        ], $this->headers())->json('data');

        $this->assertEquals('aprobado', $pago['estado']);

        $casoUso = app(AprobarPago::class);
        $resultado = $casoUso->ejecutar($pago['id'], new ContextoUsuario($this->admin->id));

        $this->assertFalse($resultado->ok());
        $this->assertSame(422, $resultado->codigo());
    }

    public function test_aprobar_endpoint_sigue_delegando_al_caso_de_uso(): void
    {
        $pago = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoLinkId,
            'monto' => 1200.00,
            'solicitar_link' => true,
        ], $this->headers())->json('data');

        $this->postJson("/api/v1/pagos/{$pago['id']}/link-pago", [
            'link_pago_url' => 'https://pago.ejemplo/test-link',
        ], $this->headers());

        $this->postJson('/api/v1/estudiantes/confirmar-link-pago', [
            'pago_id' => $pago['id'],
        ], ['Authorization' => "Bearer {$this->studentToken}"])->assertOk();

        $response = $this->postJson("/api/v1/pagos/{$pago['id']}/aprobar", [], $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.pago.estado', 'aprobado');

        $this->assertDatabaseHas('recibos_caja', ['pago_id' => $pago['id'], 'estado' => 'emitido']);
    }

    public function test_registrar_pago_mediante_caso_de_uso(): void
    {
        $casoUso = app(RegistrarPago::class);
        $resultado = $casoUso->ejecutar([
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
            'monto_recibido' => 1200.00,
        ], new ContextoUsuario($this->admin->id));

        $this->assertTrue($resultado->ok());
        $this->assertSame('Pago registrado y aprobado', $resultado->mensaje());
        $this->assertSame('aprobado', $resultado->data()['pago']->estado);
        $this->assertNotNull($resultado->data()['recibo']);
        $this->assertDatabaseHas('recibos_caja', ['pago_id' => $resultado->data()['pago']->id, 'estado' => 'emitido']);
    }

    public function test_rechazar_pago_mediante_caso_de_uso(): void
    {
        $pago = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoLinkId,
            'monto' => 1200.00,
            'solicitar_link' => true,
        ], $this->headers())->json('data');

        $casoUso = app(RechazarPago::class);
        $resultado = $casoUso->ejecutar($pago['id'], 'Comprobante ilegible', new ContextoUsuario($this->admin->id));

        $this->assertTrue($resultado->ok());
        $this->assertSame('Pago rechazado', $resultado->mensaje());
        $this->assertDatabaseHas('pagos', [
            'id' => $pago['id'],
            'estado' => 'rechazado',
            'link_pago_estado' => 'rechazado',
        ]);
    }

    public function test_actualizar_link_mediante_caso_de_uso(): void
    {
        $pago = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoLinkId,
            'monto' => 1200.00,
            'solicitar_link' => true,
        ], $this->headers())->json('data');

        $casoUso = app(ActualizarLinkPago::class);
        $resultado = $casoUso->ejecutar($pago['id'], 'pago.ejemplo/test-link', new ContextoUsuario($this->admin->id));

        $this->assertTrue($resultado->ok());
        $this->assertSame('Link guardado correctamente', $resultado->mensaje());
        $this->assertSame('https://pago.ejemplo/test-link', $resultado->data()['pago']->link_pago_url);
        $this->assertSame('enviado', $resultado->data()['pago']->link_pago_estado);

        $invalido = $casoUso->ejecutar($pago['id'], 'no es una url', new ContextoUsuario($this->admin->id));
        $this->assertFalse($invalido->ok());
        $this->assertSame(422, $invalido->codigo());
    }

    public function test_eliminar_pago_total_mediante_caso_de_uso(): void
    {
        $pago = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $this->matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
        ], $this->headers())->json('data');

        $resultado = app(EliminarPagoTotal::class)->ejecutar($pago['id']);

        $this->assertTrue($resultado->ok());
        $this->assertSame('Pago eliminado por completo', $resultado->mensaje());
        $this->assertTrue($resultado->data()['ok']);
        $this->assertDatabaseMissing('pagos', ['id' => $pago['id']]);
        $this->assertDatabaseMissing('recibos_caja', ['pago_id' => $pago['id']]);
    }

    public function test_subir_comprobante_mediante_caso_de_uso(): void
    {
        Storage::fake('public');

        $pago = Pago::create([
            'codigo' => 'PAG-PEND-001',
            'estudiante_id' => $this->estudiante->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'sucursal_id' => $this->sucursal->id,
            'monto' => 1200.00,
            'estado' => 'pendiente',
            'creado_por' => $this->admin->id,
            'creado_en' => now(),
        ]);

        $archivo = UploadedFile::fake()->image('comprobante.jpg');
        $resultado = app(SubirComprobantePago::class)->ejecutar($pago->id, $archivo, new ContextoUsuario($this->admin->id));

        $this->assertTrue($resultado->ok());
        $this->assertSame('Comprobante subido correctamente', $resultado->mensaje());
        $this->assertDatabaseHas('comprobantes_pago', ['pago_id' => $pago->id, 'estado' => 'adjuntado']);
    }

    public function test_subir_comprobante_mediante_caso_de_uso_rechaza_pago_no_pendiente(): void
    {
        Storage::fake('public');

        $pago = Pago::create([
            'codigo' => 'PAG-APROB-001',
            'estudiante_id' => $this->estudiante->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'sucursal_id' => $this->sucursal->id,
            'monto' => 1200.00,
            'estado' => 'aprobado',
            'aprobado_por' => $this->admin->id,
            'fecha_aprobacion' => now(),
            'creado_por' => $this->admin->id,
            'creado_en' => now(),
        ]);

        $resultado = app(SubirComprobantePago::class)->ejecutar($pago->id, UploadedFile::fake()->image('rechazo.jpg'), new ContextoUsuario($this->admin->id));

        $this->assertFalse($resultado->ok());
        $this->assertSame(422, $resultado->codigo());
    }

    public function test_registrar_pago_con_libro_sin_existencia_devuelve_422_y_no_crea_pago(): void
    {
        $conceptoVliId = DB::table('conceptos_pago')->insertGetId([
            'codigo' => 'VLI', 'nombre' => 'Venta de libro', 'tipo_monto' => 'por_inventario',
            'requiere_autorizacion_monto' => false, 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);
        $libro = Libro::create(['codigo' => 'LIB-001', 'titulo' => 'English Book A1', 'precio_venta' => 350, 'creado_en' => now()]);
        $inventario = InventarioLibro::create([
            'libro_id' => $libro->id,
            'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 1,
            'existencia_minima' => 0,
            'creado_por' => $this->admin->id,
            'creado_en' => now(),
        ]);

        $response = $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'concepto_pago_id' => $conceptoVliId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 350.00,
            'monto_recibido' => 350.00,
            'inventario_libro_id' => $inventario->id,
            'cantidad_libro' => 2,
        ], $this->headers());

        $response->assertStatus(422)
            ->assertJsonPath('resultado', 'R')
            ->assertJsonPath('codigo_error', '422_INVENTARIO_INSUFICIENTE')
            ->assertJsonPath('mensaje', 'No hay suficiente existencia. Disponible: 1');

        $this->assertDatabaseMissing('pagos', [
            'estudiante_id' => $this->estudiante->id,
            'concepto_pago_id' => $conceptoVliId,
        ]);
        $this->assertDatabaseMissing('movimientos_inventario_libros', [
            'inventario_libro_id' => $inventario->id,
        ]);
        $this->assertDatabaseHas('inventario_libros', [
            'id' => $inventario->id,
            'existencia_actual' => 1,
        ]);
    }
}
