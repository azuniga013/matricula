<?php

namespace Tests\Feature;

use App\Models\AccesoEstudiante;
use App\Models\Aula;
use App\Models\ConceptoPago;
use App\Models\CuentaBancaria;
use App\Models\DepartamentoAcademico;
use App\Models\DetallePlanCobro;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\HistorialAcademico;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\MetodoPago;
use App\Models\Modalidad;
use App\Models\NivelAcademico;
use App\Models\OfertaAcademica;
use App\Models\Pago;
use App\Models\PeriodoAcademico;
use App\Models\EnlacePago;
use App\Models\PlanCobro;
use App\Models\PlanEstudio;
use App\Models\ReciboCaja;
use App\Models\Sucursal;
use App\Models\VersionPlanEstudio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PortalEstudianteTest extends TestCase
{
    use RefreshDatabase;

    private Sucursal $sucursal;

    private Estudiante $estudiante;

    private string $token;

    private OfertaAcademica $oferta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sucursal = Sucursal::factory()->create(['codigo' => 'SPS']);

        $this->estudiante = Estudiante::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'activo',
        ]);

        $acceso = AccesoEstudiante::create([
            'estudiante_id' => $this->estudiante->id,
            'email' => 'portal@test.com',
            'password' => 'password',
            'estado' => 'activo',
        ]);

        $rawToken = Str::random(60);
        $acceso->update(['token' => hash('sha256', $rawToken)]);
        $this->token = $rawToken;

        $periodo = PeriodoAcademico::create([
            'codigo' => '2026-I',
            'nombre' => 'Semestre 1',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addMonths(4)->toDateString(),
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
        $regimen = Modalidad::create([
            'codigo' => 'INT',
            'nombre' => 'Intensivo',
            'tipo' => 'regimen_academico',
        ]);

        $nivel = NivelAcademico::create([
            'version_plan_estudio_id' => $version->id,
            'regimen_academico_id' => $regimen->id,
            'codigo' => 'ING-1',
            'nombre' => 'Inglés 1',
            'orden' => 1,
        ]);

        $modalidad = Modalidad::create([
            'codigo' => 'PRES',
            'nombre' => 'Presencial',
            'tipo' => 'atencion',
        ]);

        $horario = Horario::create([
            'codigo' => 'M1',
            'nombre' => 'Matutino',
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
        ]);
        $horario->update(['lunes' => true]);

        $docente = Docente::factory()->create(['codigo' => 'DOC001']);

        $aula = Aula::create([
            'sucursal_id' => $this->sucursal->id,
            'codigo' => 'AUL-01',
            'nombre' => 'Aula 1',
            'capacidad' => 25,
        ]);

        $planCobro = PlanCobro::create([
            'codigo' => 'PC1',
            'nombre' => 'Plan Base',
            'estado' => 'activo',
        ]);

        $conceptoMat = ConceptoPago::create(['codigo' => 'MAT', 'nombre' => 'Matrícula', 'tipo_monto' => 'por_oferta']);
        $conceptoCuo = ConceptoPago::create(['codigo' => 'CUO', 'nombre' => 'Cuota', 'tipo_monto' => 'por_oferta']);

        DetallePlanCobro::create([
            'plan_cobro_id' => $planCobro->id,
            'concepto_pago_id' => $conceptoMat->id,
            'numero_cuota' => 0,
            'nombre_cargo' => 'Matrícula',
            'monto' => 1200.00,
            'dias_vencimiento' => 15,
        ]);
        DetallePlanCobro::create([
            'plan_cobro_id' => $planCobro->id,
            'concepto_pago_id' => $conceptoCuo->id,
            'numero_cuota' => 1,
            'nombre_cargo' => 'Cuota 1',
            'monto' => 1100.00,
            'dias_vencimiento' => 45,
        ]);

        $this->oferta = OfertaAcademica::create([
            'codigo' => 'OF-001',
            'sucursal_id' => $this->sucursal->id,
            'periodo_academico_id' => $periodo->id,
            'nivel_academico_id' => $nivel->id,
            'modalidad_id' => $modalidad->id,
            'horario_id' => $horario->id,
            'docente_id' => $docente->id,
            'aula_id' => $aula->id,
            'plan_cobro_id' => $planCobro->id,
            'cupo_maximo' => 25,
            'cupos_reservados' => 0,
            'cupos_matriculados' => 0,
            'estado' => 'abierto',
        ]);

        MetodoPago::create(['codigo' => 'DEP', 'nombre' => 'Depósito', 'estado' => 'activo']);
        MetodoPago::create(['codigo' => 'LNK', 'nombre' => 'Link de pago', 'estado' => 'activo']);
    }

    private function studentHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_portal_sin_autenticacion(): void
    {
        $response = $this->postJson('/api/v1/estudiantes/portal');

        $response->assertStatus(401)
            ->assertJsonPath('resultado', 'R');
    }

    public function test_portal_con_autenticacion(): void
    {
        $response = $this->postJson('/api/v1/estudiantes/portal', [], $this->studentHeaders());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonStructure([
                'data' => ['estudiante', 'nivel_actual', 'matriculas', 'obligaciones', 'pagos', 'recibos', 'whatsapp'],
            ]);
    }

    public function test_portal_incluye_las_cuentas_bancarias_activas(): void
    {
        $cuenta = CuentaBancaria::create([
            'codigo' => 'CUENTA-PORTAL', 'nombre' => 'Cuenta portal', 'banco' => 'Banco de prueba',
            'numero_cuenta' => '123456789', 'tipo_cuenta' => 'ahorro', 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);

        $this->postJson('/api/v1/estudiantes/portal', [], $this->studentHeaders())
            ->assertOk()
            ->assertJsonPath('data.cuentas_bancarias.0.id', $cuenta->id)
            ->assertJsonPath('data.cuentas_bancarias.0.banco', 'Banco de prueba');
    }

    public function test_mis_ofertas(): void
    {
        $response = $this->getJson('/api/v1/estudiantes/mis-ofertas', $this->studentHeaders());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.periodo_actual.codigo', '2026-I')
            ->assertJsonPath('data.periodo_actual.fecha_inicio', now()->toDateString())
            ->assertJsonPath('data.ofertas.0.periodo_codigo', '2026-I')
            ->assertJsonPath('data.ofertas.0.periodo_fecha_inicio', now()->toDateString());

        $this->assertNotEmpty($response->json('data'));
    }

    public function test_mis_ofertas_no_devuelve_ofertas_de_periodo_cerrado(): void
    {
        PeriodoAcademico::whereKey($this->oferta->periodo_academico_id)->update([
            'fecha_fin' => now()->subDay()->toDateString(),
        ]);

        $response = $this->getJson('/api/v1/estudiantes/mis-ofertas', $this->studentHeaders());

        $response->assertOk()
            ->assertJsonPath('data.periodo_actual', null)
            ->assertJsonPath('data.ofertas', []);
    }

    public function test_no_reservar_en_periodo_cerrado(): void
    {
        PeriodoAcademico::whereKey($this->oferta->periodo_academico_id)->update([
            'fecha_fin' => now()->subDay()->toDateString(),
        ]);

        $response = $this->postJson('/api/v1/estudiantes/reservar-matricula', [
            'oferta_academica_id' => $this->oferta->id,
        ], $this->studentHeaders());

        $response->assertStatus(422)
            ->assertJsonPath('codigo_error', '422_PERIODO_NO_ABIERTO');

        $this->assertDatabaseCount('matriculas', 0);
    }

    public function test_reservar_matricula(): void
    {
        $response = $this->postJson('/api/v1/estudiantes/reservar-matricula', [
            'oferta_academica_id' => $this->oferta->id,
            'plan_estudio_id' => $this->oferta->nivelAcademico->versionPlanEstudio->plan_estudio_id,
        ], $this->studentHeaders());

        $response->assertCreated()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonStructure(['data' => ['matricula_codigo']]);

        $this->assertDatabaseHas('matriculas', [
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'estado' => 'reservada',
        ]);

        $matricula = Matricula::where('estudiante_id', $this->estudiante->id)->first();
        $this->assertDatabaseHas('obligaciones_pago_estudiante', [
            'matricula_id' => $matricula->id,
        ]);
    }

    public function test_no_reservar_si_no_hay_cupo(): void
    {
        $this->oferta->update(['cupo_maximo' => 1, 'cupos_matriculados' => 1]);

        $response = $this->postJson('/api/v1/estudiantes/reservar-matricula', [
            'oferta_academica_id' => $this->oferta->id,
        ], $this->studentHeaders());

        $response->assertStatus(422)
            ->assertJsonPath('mensaje', 'No hay cupos disponibles en esta oferta');
    }

    public function test_no_duplicar_matricula(): void
    {
        $this->postJson('/api/v1/estudiantes/reservar-matricula', [
            'oferta_academica_id' => $this->oferta->id,
        ], $this->studentHeaders());

        $response = $this->postJson('/api/v1/estudiantes/reservar-matricula', [
            'oferta_academica_id' => $this->oferta->id,
        ], $this->studentHeaders());

        $response->assertStatus(422)
            ->assertJsonPath('mensaje', 'Ya tiene ese nivel matriculado en el mismo período');
    }

    public function test_mis_matriculas(): void
    {
        $response = $this->getJson('/api/v1/estudiantes/mis-matriculas', $this->studentHeaders());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');
    }

    public function test_mis_matriculas_no_muestra_matriculas_de_otro_estudiante(): void
    {
        Matricula::create([
            'codigo' => 'MAT-SELF-001',
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'matriculado',
            'fecha_reserva' => now(),
        ]);

        $otro = Estudiante::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'activo',
        ]);
        $otroToken = 'otro-mat-'.Str::random(20);
        AccesoEstudiante::create([
            'estudiante_id' => $otro->id,
            'email' => 'otro-mat@test.com',
            'password' => 'password',
            'estado' => 'activo',
            'token' => hash('sha256', $otroToken),
        ]);
        Matricula::create([
            'codigo' => 'MAT-OTRO-001',
            'estudiante_id' => $otro->id,
            'oferta_academica_id' => $this->oferta->id,
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'matriculado',
            'fecha_reserva' => now()->addMinute(),
        ]);

        $response = $this->getJson('/api/v1/estudiantes/mis-matriculas', [
            'Authorization' => "Bearer {$otroToken}",
        ]);

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.codigo', 'MAT-OTRO-001');
    }

    public function test_mis_pagos(): void
    {
        $response = $this->getJson('/api/v1/estudiantes/mis-pagos', $this->studentHeaders());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');
    }

    public function test_mis_pagos_no_muestra_pagos_de_otro_estudiante(): void
    {
        $otro = Estudiante::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'activo',
        ]);

        AccesoEstudiante::create([
            'estudiante_id' => $otro->id,
            'email' => 'otro@test.com',
            'password' => 'password',
            'estado' => 'activo',
        ]);

        $this->postJson('/api/v1/estudiantes/reservar-matricula', [
            'oferta_academica_id' => $this->oferta->id,
        ], $this->studentHeaders());

        $matricula = Matricula::where('estudiante_id', $this->estudiante->id)->firstOrFail();
        $metodoLink = MetodoPago::where('codigo', 'LNK')->firstOrFail();

        $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matricula->id,
            'metodo_pago_id' => $metodoLink->id,
            'obligacion_ids' => $matricula->obligaciones()->pluck('id')->all(),
        ], $this->studentHeaders());

        $otroToken = 'otro-token-'.Str::random(20);
        $accesoOtro = AccesoEstudiante::where('estudiante_id', $otro->id)->firstOrFail();
        $accesoOtro->update(['token' => hash('sha256', $otroToken)]);

        $response = $this->getJson('/api/v1/estudiantes/mis-pagos', ['Authorization' => "Bearer {$otroToken}"]);

        $response->assertOk()->assertJsonPath('resultado', 'A');
        $this->assertCount(0, $response->json('data'));
    }

    public function test_no_puede_eliminar_pago_de_otro_estudiante(): void
    {
        $otro = Estudiante::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'activo',
        ]);
        $concepto = ConceptoPago::where('codigo', 'MAT')->firstOrFail();
        $metodo = MetodoPago::where('codigo', 'LNK')->firstOrFail();
        $pago = Pago::create([
            'codigo' => 'PAG-AJENO-001',
            'estudiante_id' => $otro->id,
            'concepto_pago_id' => $concepto->id,
            'metodo_pago_id' => $metodo->id,
            'sucursal_id' => $this->sucursal->id,
            'monto' => 250.00,
            'estado' => 'solicita_link',
        ]);

        $response = $this->postJson("/api/v1/estudiantes/mis-pagos/{$pago->id}", [], $this->studentHeaders());

        $response->assertStatus(404)
            ->assertJsonPath('resultado', 'R');
    }

    public function test_no_puede_registrar_pago_para_matricula_de_otro_estudiante(): void
    {
        $otro = Estudiante::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'activo',
        ]);
        $matriculaAjena = Matricula::create([
            'codigo' => 'MAT-AJENA-001',
            'estudiante_id' => $otro->id,
            'oferta_academica_id' => $this->oferta->id,
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'reservada',
            'fecha_reserva' => now(),
        ]);
        $metodoLink = MetodoPago::where('codigo', 'LNK')->firstOrFail();

        $response = $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matriculaAjena->id,
            'metodo_pago_id' => $metodoLink->id,
        ], $this->studentHeaders());

        $response->assertStatus(404)
            ->assertJsonPath('resultado', 'R');
    }

    public function test_registrar_pago_link_sin_comprobante_queda_en_solicita_link(): void
    {
        $this->postJson('/api/v1/estudiantes/reservar-matricula', [
            'oferta_academica_id' => $this->oferta->id,
        ], $this->studentHeaders());

        $matricula = Matricula::where('estudiante_id', $this->estudiante->id)->firstOrFail();
        $metodoLink = MetodoPago::where('codigo', 'LNK')->firstOrFail();

        $response = $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matricula->id,
            'metodo_pago_id' => $metodoLink->id,
            'obligacion_ids' => $matricula->obligaciones()->pluck('id')->all(),
            'solicitar_link' => true,
        ], $this->studentHeaders());

        $response->assertCreated()
            ->assertJsonPath('data.estado', 'solicita_link')
            ->assertJsonPath('data.estado_pago', 'solicita_link');

        $this->assertDatabaseHas('pagos', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $matricula->id,
            'estado' => 'solicita_link',
            'metodo_pago_id' => $metodoLink->id,
        ]);

        $misPagos = $this->getJson('/api/v1/estudiantes/mis-pagos', $this->studentHeaders());
        $misPagos->assertOk()->assertJsonFragment(['estado' => 'solicita_link']);
    }

    public function test_no_puede_solicitar_link_doble_para_misma_obligacion(): void
    {
        $this->postJson('/api/v1/estudiantes/reservar-matricula', [
            'oferta_academica_id' => $this->oferta->id,
        ], $this->studentHeaders());

        $matricula = Matricula::where('estudiante_id', $this->estudiante->id)->firstOrFail();
        $metodoLink = MetodoPago::where('codigo', 'LNK')->firstOrFail();
        $obligacionIds = $matricula->obligaciones()->pluck('id')->all();

        $primerPago = $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matricula->id,
            'metodo_pago_id' => $metodoLink->id,
            'obligacion_ids' => $obligacionIds,
        ], $this->studentHeaders());

        $primerPago->assertCreated()
            ->assertJsonPath('data.estado', 'solicita_link');

        $segundoPago = $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matricula->id,
            'metodo_pago_id' => $metodoLink->id,
            'obligacion_ids' => $obligacionIds,
        ], $this->studentHeaders());

        $segundoPago->assertStatus(422)
            ->assertJsonPath('resultado', 'R')
            ->assertJsonPath('codigo', 422)
            ->assertJsonPath('mensaje', 'Ya tiene una solicitud de pago en proceso para estas obligaciones. Espere la respuesta de contabilidad antes de solicitar otro link.');

        $pagosConSolicitud = Pago::where('estudiante_id', $this->estudiante->id)
            ->whereIn('estado', ['solicita_link', 'esperando_respuesta', 'en_revision'])
            ->count();
        $this->assertEquals(1, $pagosConSolicitud, 'Solo debe existir una solicitud de link en proceso');
    }

    public function test_registrar_pago_link_automatico_usa_enlace_valido(): void
    {
        $this->postJson('/api/v1/estudiantes/reservar-matricula', [
            'oferta_academica_id' => $this->oferta->id,
        ], $this->studentHeaders());

        $matricula = Matricula::where('estudiante_id', $this->estudiante->id)->firstOrFail();
        $metodoLink = MetodoPago::where('codigo', 'LNK')->firstOrFail();
        $metodoLink->update(['permite_link_pago' => true]);

        $enlace = EnlacePago::create([
            'codigo' => 'LNK-PORTAL-001',
            'nombre' => 'Link portal matrícula',
            'enlace_url' => 'https://pago.ejemplo/portal-001',
            'monto_objetivo' => 2300.00,
            'metodo_pago_id' => $metodoLink->id,
            'concepto_pago_id' => $matricula->obligaciones()->first()->concepto_pago_id,
            'estado' => 'activo',
            'estado_operativo' => 'disponible',
            'usos_actuales' => 0,
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);

        $response = $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matricula->id,
            'metodo_pago_id' => $metodoLink->id,
            'obligacion_ids' => $matricula->obligaciones()->pluck('id')->all(),
            'solicitar_link' => true,
        ], $this->studentHeaders());

        $response->assertCreated()
            ->assertJsonPath('data.estado', 'esperando_respuesta');

        $this->assertDatabaseHas('pagos', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $matricula->id,
            'estado' => 'esperando_respuesta',
            'link_pago_url' => $enlace->enlace_url,
            'link_pago_estado' => 'enviado',
        ]);

        $this->assertDatabaseHas('enlaces_pago', [
            'id' => $enlace->id,
            'estado_operativo' => 'reservado',
            'usos_actuales' => 1,
        ]);
    }

    public function test_registrar_pago_link_automatico_rechaza_enlace_invalido(): void
    {
        $this->postJson('/api/v1/estudiantes/reservar-matricula', [
            'oferta_academica_id' => $this->oferta->id,
        ], $this->studentHeaders());

        $matricula = Matricula::where('estudiante_id', $this->estudiante->id)->firstOrFail();
        $metodoLink = MetodoPago::where('codigo', 'LNK')->firstOrFail();
        $metodoLink->update(['permite_link_pago' => true]);

        EnlacePago::create([
            'codigo' => 'LNK-PORTAL-INV-001',
            'nombre' => 'Link portal inválido',
            'enlace_url' => 'no-es-una-url',
            'monto_objetivo' => 2300.00,
            'metodo_pago_id' => $metodoLink->id,
            'concepto_pago_id' => $matricula->obligaciones()->first()->concepto_pago_id,
            'estado' => 'activo',
            'estado_operativo' => 'disponible',
            'usos_actuales' => 0,
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);

        $response = $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matricula->id,
            'metodo_pago_id' => $metodoLink->id,
            'obligacion_ids' => $matricula->obligaciones()->pluck('id')->all(),
            'solicitar_link' => true,
        ], $this->studentHeaders());

        $response->assertCreated()
            ->assertJsonPath('data.estado', 'solicita_link');

        $this->assertDatabaseHas('pagos', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $matricula->id,
            'estado' => 'solicita_link',
            'link_pago_url' => null,
        ]);
    }

    public function test_confirmar_link_pago_automatico_marca_enlace_usado(): void
    {
        $this->postJson('/api/v1/estudiantes/reservar-matricula', [
            'oferta_academica_id' => $this->oferta->id,
        ], $this->studentHeaders());

        $matricula = Matricula::where('estudiante_id', $this->estudiante->id)->firstOrFail();
        $metodoLink = MetodoPago::where('codigo', 'LNK')->firstOrFail();
        $metodoLink->update(['permite_link_pago' => true]);

        $enlace = EnlacePago::create([
            'codigo' => 'LNK-PORTAL-USED',
            'nombre' => 'Link portal usado',
            'enlace_url' => 'https://pago.ejemplo/portal-used',
            'monto_objetivo' => 2300.00,
            'metodo_pago_id' => $metodoLink->id,
            'concepto_pago_id' => $matricula->obligaciones()->first()->concepto_pago_id,
            'estado' => 'activo',
            'estado_operativo' => 'disponible',
            'usos_actuales' => 0,
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);

        $response = $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matricula->id,
            'metodo_pago_id' => $metodoLink->id,
            'obligacion_ids' => $matricula->obligaciones()->pluck('id')->all(),
            'solicitar_link' => true,
        ], $this->studentHeaders());

        $this->postJson('/api/v1/estudiantes/confirmar-link-pago', [
            'pago_id' => $response->json('data.pago_id'),
        ], $this->studentHeaders())->assertOk();

        $this->assertDatabaseHas('enlaces_pago', [
            'id' => $enlace->id,
            'estado_operativo' => 'usado',
        ]);
    }

    public function test_reenganchar_pago_link_automatico_marca_enlace_desuso(): void
    {
        $this->postJson('/api/v1/estudiantes/reservar-matricula', [
            'oferta_academica_id' => $this->oferta->id,
        ], $this->studentHeaders());

        $matricula = Matricula::where('estudiante_id', $this->estudiante->id)->firstOrFail();
        $metodoLink = MetodoPago::where('codigo', 'LNK')->firstOrFail();
        $metodoLink->update(['permite_link_pago' => true]);

        $enlace = EnlacePago::create([
            'codigo' => 'LNK-PORTAL-DESUSO',
            'nombre' => 'Link portal desuso',
            'enlace_url' => 'https://pago.ejemplo/portal-desuso',
            'monto_objetivo' => 2300.00,
            'metodo_pago_id' => $metodoLink->id,
            'concepto_pago_id' => $matricula->obligaciones()->first()->concepto_pago_id,
            'estado' => 'activo',
            'estado_operativo' => 'disponible',
            'usos_actuales' => 0,
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);

        $response = $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matricula->id,
            'metodo_pago_id' => $metodoLink->id,
            'obligacion_ids' => $matricula->obligaciones()->pluck('id')->all(),
            'solicitar_link' => true,
        ], $this->studentHeaders());

        $this->postJson('/api/v1/estudiantes/reenganchar-flujo-pago', [
            'pago_id' => $response->json('data.pago_id'),
        ], $this->studentHeaders())->assertOk();

        $this->assertDatabaseHas('enlaces_pago', [
            'id' => $enlace->id,
            'estado_operativo' => 'desuso',
        ]);
    }

    public function test_reenganchar_flujo_pago_reencauza_matricula_y_pago(): void
    {
        $this->postJson('/api/v1/estudiantes/reservar-matricula', [
            'oferta_academica_id' => $this->oferta->id,
        ], $this->studentHeaders());

        $matricula = Matricula::where('estudiante_id', $this->estudiante->id)->firstOrFail();
        $metodoLink = MetodoPago::where('codigo', 'LNK')->firstOrFail();

        $response = $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matricula->id,
            'metodo_pago_id' => $metodoLink->id,
            'obligacion_ids' => $matricula->obligaciones()->pluck('id')->all(),
        ], $this->studentHeaders());

        $pagoId = $response->json('data.pago_id');

        Pago::where('id', $pagoId)->update([
            'estado' => 'en_revision',
            'link_pago_url' => null,
            'link_pago_estado' => null,
        ]);

        $reenganche = $this->postJson('/api/v1/estudiantes/reenganchar-flujo-pago', [
            'pago_id' => $pagoId,
        ], $this->studentHeaders());

        $reenganche->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.estado_pago', 'solicita_link')
            ->assertJsonPath('data.estado_matricula', 'reservada');
    }

    public function test_mis_recibos(): void
    {
        $response = $this->getJson('/api/v1/estudiantes/mis-recibos', $this->studentHeaders());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');
    }

    public function test_mis_recibos_no_muestra_recibos_de_otro_estudiante(): void
    {
        $otro = Estudiante::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'activo',
        ]);
        $otroToken = 'otro-recibo-'.Str::random(20);
        $accesoOtro = AccesoEstudiante::create([
            'estudiante_id' => $otro->id,
            'email' => 'otro-recibo@test.com',
            'password' => 'password',
            'estado' => 'activo',
            'token' => hash('sha256', $otroToken),
        ]);

        $concepto = ConceptoPago::where('codigo', 'MAT')->firstOrFail();
        $metodo = MetodoPago::firstOrFail();

        $pagoPropio = Pago::create([
            'codigo' => 'PAG-SELF-001',
            'estudiante_id' => $this->estudiante->id,
            'concepto_pago_id' => $concepto->id,
            'metodo_pago_id' => $metodo->id,
            'sucursal_id' => $this->sucursal->id,
            'monto' => 100.00,
            'estado' => 'aprobado',
            'fecha_aprobacion' => now(),
        ]);
        ReciboCaja::create([
            'codigo' => 'RC-SELF-001',
            'numero_recibo' => 1,
            'pago_id' => $pagoPropio->id,
            'estudiante_id' => $this->estudiante->id,
            'sucursal_id' => $this->sucursal->id,
            'concepto_pago_id' => $concepto->id,
            'metodo_pago_id' => $metodo->id,
            'monto_total' => 100.00,
            'estado' => 'emitido',
            'anio' => (int) date('Y'),
            'fecha_proceso' => now(),
            'fecha_recibo' => now(),
        ]);

        $pagoOtro = Pago::create([
            'codigo' => 'PAG-OTRO-001',
            'estudiante_id' => $otro->id,
            'concepto_pago_id' => $concepto->id,
            'metodo_pago_id' => $metodo->id,
            'sucursal_id' => $this->sucursal->id,
            'monto' => 125.00,
            'estado' => 'aprobado',
            'fecha_aprobacion' => now(),
        ]);
        ReciboCaja::create([
            'codigo' => 'RC-OTRO-001',
            'numero_recibo' => 2,
            'pago_id' => $pagoOtro->id,
            'estudiante_id' => $otro->id,
            'sucursal_id' => $this->sucursal->id,
            'concepto_pago_id' => $concepto->id,
            'metodo_pago_id' => $metodo->id,
            'monto_total' => 125.00,
            'estado' => 'emitido',
            'anio' => (int) date('Y'),
            'fecha_proceso' => now(),
            'fecha_recibo' => now()->addMinute(),
        ]);

        $response = $this->getJson('/api/v1/estudiantes/mis-recibos', [
            'Authorization' => "Bearer {$otroToken}",
        ]);

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.numero_recibo', 2);
    }

    public function test_mi_nivel_sin_matricula(): void
    {
        $response = $this->getJson('/api/v1/estudiantes/mi-nivel', $this->studentHeaders());

        $response->assertOk();
    }

    public function test_whatsapp_sin_pago(): void
    {
        $this->oferta->update([
            'whatsapp_grupo_nombre' => 'Grupo Test',
            'whatsapp_link_periodo' => 'https://chat.whatsapp.com/test',
        ]);

        $this->postJson('/api/v1/estudiantes/reservar-matricula', [
            'oferta_academica_id' => $this->oferta->id,
        ], $this->studentHeaders());

        $response = $this->getJson('/api/v1/estudiantes/whatsapp', $this->studentHeaders());

        $response->assertOk()
            ->assertJsonPath('data.whatsapp_link', null);
    }

    public function test_whatsapp_link_invalido(): void
    {
        $response = $this->postJson('/api/v1/estudiantes/portal', [], $this->studentHeaders());

        $response->assertOk();
        $this->assertNull($response->json('data.whatsapp'));
    }

    public function test_cerrar_sesion_estudiante(): void
    {
        $response = $this->postJson('/api/v1/estudiantes/cerrar-sesion', [], $this->studentHeaders());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');

        $acceso = AccesoEstudiante::where('estudiante_id', $this->estudiante->id)->first();
        $this->assertNull($acceso->token);
    }

    public function test_token_invalido(): void
    {
        $response = $this->postJson('/api/v1/estudiantes/portal', [], [
            'Authorization' => 'Bearer invalid-token-here',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('codigo_error', '401_TOKEN_INVALIDO');
    }

    public function test_mis_calificaciones(): void
    {
        $response = $this->getJson('/api/v1/estudiantes/mis-calificaciones', $this->studentHeaders());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A');
    }

    public function test_mis_calificaciones_no_muestra_historial_de_otro_estudiante(): void
    {
        $matriculaPropia = Matricula::create([
            'codigo' => 'MAT-CAL-001',
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'matriculado',
        ]);
        HistorialAcademico::create([
            'codigo' => 'HIS-SELF-001',
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $matriculaPropia->id,
            'oferta_academica_id' => $this->oferta->id,
            'nivel_academico_id' => $this->oferta->nivel_academico_id,
            'periodo_academico_id' => $this->oferta->periodo_academico_id,
            'nota_final' => 90,
            'faltas' => 0,
            'estado' => 'aprobado',
        ]);

        $otro = Estudiante::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'activo',
        ]);
        $otroToken = 'otro-cal-'.Str::random(20);
        AccesoEstudiante::create([
            'estudiante_id' => $otro->id,
            'email' => 'otro-cal@test.com',
            'password' => 'password',
            'estado' => 'activo',
            'token' => hash('sha256', $otroToken),
        ]);
        $matriculaOtro = Matricula::create([
            'codigo' => 'MAT-CAL-002',
            'estudiante_id' => $otro->id,
            'oferta_academica_id' => $this->oferta->id,
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'matriculado',
        ]);
        HistorialAcademico::create([
            'codigo' => 'HIS-OTRO-001',
            'estudiante_id' => $otro->id,
            'matricula_id' => $matriculaOtro->id,
            'oferta_academica_id' => $this->oferta->id,
            'nivel_academico_id' => $this->oferta->nivel_academico_id,
            'periodo_academico_id' => $this->oferta->periodo_academico_id,
            'nota_final' => 75,
            'faltas' => 2,
            'estado' => 'reprobado',
        ]);

        $response = $this->getJson('/api/v1/estudiantes/mis-calificaciones', [
            'Authorization' => "Bearer {$otroToken}",
        ]);

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.codigo', 'HIS-OTRO-001');
    }

    public function test_estudiante_puede_generar_certificado_desde_su_historial_aprobado(): void
    {
        $this->postJson('/api/v1/estudiantes/reservar-matricula', [
            'oferta_academica_id' => $this->oferta->id,
        ], $this->studentHeaders())->assertCreated();

        $matricula = Matricula::where('estudiante_id', $this->estudiante->id)->firstOrFail();
        $historial = HistorialAcademico::create([
            'codigo' => 'HIS-PORTAL-001',
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $matricula->id,
            'oferta_academica_id' => $this->oferta->id,
            'nivel_academico_id' => $this->oferta->nivel_academico_id,
            'periodo_academico_id' => $this->oferta->periodo_academico_id,
            'estado' => 'aprobado',
            'nota_final' => 90,
            'faltas' => 0,
        ]);

        $response = $this->postJson('/api/v1/estudiantes/certificados/electronicos', [
            'historial_academico_id' => $historial->id,
        ], $this->studentHeaders());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonStructure(['data' => ['codigo', 'pdf_url', 'vista_url']]);

        $this->assertDatabaseHas('certificados_electronicos', [
            'historial_academico_id' => $historial->id,
            'estudiante_id' => $this->estudiante->id,
            'estado' => 'emitido',
        ]);
    }
}
