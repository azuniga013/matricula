<?php

namespace Tests\Feature;

use App\Models\{
    Aula, AccesoEstudiante, ConceptoPago, DepartamentoAcademico,
    Docente, Estudiante, Horario, MetodoPago, Modalidad, NivelAcademico,
    OfertaAcademica, PeriodoAcademico, PlanCobro, PlanEstudio,
    Sucursal, VersionPlanEstudio, Pago
};
use App\Models\DetallePlanCobro;
use App\Models\Matricula;
use App\Services\DetectorPagoDuplicado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class PagoDuplicadoTest extends TestCase
{
    use RefreshDatabase;

    private Sucursal $sucursal;
    private Estudiante $estudianteA;
    private Estudiante $estudianteB;
    private string $tokenA;
    private string $tokenB;
    private OfertaAcademica $ofertaA;
    private OfertaAcademica $ofertaB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sucursal = Sucursal::factory()->create(['codigo' => 'SPS']);

        [$this->estudianteA, $this->tokenA] = $this->crearEstudianteConToken('a@test.com');
        [$this->estudianteB, $this->tokenB] = $this->crearEstudianteConToken('b@test.com');

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

        $this->ofertaA = OfertaAcademica::create([
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

        $this->ofertaB = OfertaAcademica::create([
            'codigo' => 'OF-002',
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
        MetodoPago::create(['codigo' => 'TRA', 'nombre' => 'Transferencia', 'estado' => 'activo']);
        MetodoPago::create(['codigo' => 'LNK', 'nombre' => 'Link de pago', 'estado' => 'activo']);
    }

    private function crearEstudianteConToken(string $email): array
    {
        $estudiante = Estudiante::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'activo',
        ]);

        $acceso = AccesoEstudiante::create([
            'estudiante_id' => $estudiante->id,
            'email' => $email,
            'password' => 'password',
            'estado' => 'activo',
        ]);

        $rawToken = Str::random(60);
        $acceso->update(['token' => hash('sha256', $rawToken)]);

        return [$estudiante, $rawToken];
    }

    private function headersA(): array
    {
        return ['Authorization' => "Bearer {$this->tokenA}"];
    }

    private function headersB(): array
    {
        return ['Authorization' => "Bearer {$this->tokenB}"];
    }

    private function reservarPara(Estudiante $estudiante, OfertaAcademica $oferta, array $headers): Matricula
    {
        $this->postJson('/api/v1/estudiantes/reservar-matricula', [
            'oferta_academica_id' => $oferta->id,
        ], $headers);

        return Matricula::where('estudiante_id', $estudiante->id)->firstOrFail();
    }

    public function test_deposito_duplicado_marca_alerta_y_envia_correo(): void
    {
        Mail::fake();

        $matA = $this->reservarPara($this->estudianteA, $this->ofertaA, $this->headersA());
        $matB = $this->reservarPara($this->estudianteB, $this->ofertaB, $this->headersB());

        $metodoDep = MetodoPago::where('codigo', 'DEP')->firstOrFail();
        $fechaPago = '2026-08-01';

        $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matA->id,
            'metodo_pago_id' => $metodoDep->id,
            'referencia' => 'DEP-12345',
            'fecha_pago' => $fechaPago,
            'obligacion_ids' => $matA->obligaciones()->pluck('id')->all(),
        ], $this->headersA());

        $response = $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matB->id,
            'metodo_pago_id' => $metodoDep->id,
            'referencia' => 'DEP-12345',
            'fecha_pago' => $fechaPago,
            'obligacion_ids' => $matB->obligaciones()->pluck('id')->all(),
        ], $this->headersB());

        $response->assertCreated()
            ->assertJsonPath('data.alerta_duplicado', true);

        $this->assertDatabaseHas('pagos', [
            'estudiante_id' => $this->estudianteB->id,
            'alerta_duplicado' => true,
        ]);

        Mail::assertSent(\App\Mail\AlertaPagoDuplicado::class, function ($mail) {
            return $mail->hasTo('antalma61@hotmail.com')
                && $mail->hasTo('kcontreras1995@hotmail.com');
        });
    }

    public function test_deposito_referencia_distinta_no_marca_alerta(): void
    {
        Mail::fake();

        $matA = $this->reservarPara($this->estudianteA, $this->ofertaA, $this->headersA());
        $matB = $this->reservarPara($this->estudianteB, $this->ofertaB, $this->headersB());

        $metodoDep = MetodoPago::where('codigo', 'DEP')->firstOrFail();

        $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matA->id,
            'metodo_pago_id' => $metodoDep->id,
            'referencia' => 'DEP-11111',
            'fecha_pago' => '2026-08-01',
            'obligacion_ids' => $matA->obligaciones()->pluck('id')->all(),
        ], $this->headersA());

        $response = $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matB->id,
            'metodo_pago_id' => $metodoDep->id,
            'referencia' => 'DEP-99999',
            'fecha_pago' => '2026-08-01',
            'obligacion_ids' => $matB->obligaciones()->pluck('id')->all(),
        ], $this->headersB());

        $response->assertCreated()
            ->assertJsonPath('data.alerta_duplicado', false);

        $this->assertDatabaseHas('pagos', [
            'estudiante_id' => $this->estudianteB->id,
            'alerta_duplicado' => false,
        ]);

        Mail::assertNotSent(\App\Mail\AlertaPagoDuplicado::class);
    }

    public function test_deposito_misma_referencia_distinta_fecha_no_marca_alerta(): void
    {
        Mail::fake();

        $matA = $this->reservarPara($this->estudianteA, $this->ofertaA, $this->headersA());
        $matB = $this->reservarPara($this->estudianteB, $this->ofertaB, $this->headersB());

        $metodoDep = MetodoPago::where('codigo', 'DEP')->firstOrFail();

        $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matA->id,
            'metodo_pago_id' => $metodoDep->id,
            'referencia' => 'DEP-SHARED',
            'fecha_pago' => '2026-08-01',
            'obligacion_ids' => $matA->obligaciones()->pluck('id')->all(),
        ], $this->headersA());

        $response = $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matB->id,
            'metodo_pago_id' => $metodoDep->id,
            'referencia' => 'DEP-SHARED',
            'fecha_pago' => '2026-08-05',
            'obligacion_ids' => $matB->obligaciones()->pluck('id')->all(),
        ], $this->headersB());

        $response->assertCreated()
            ->assertJsonPath('data.alerta_duplicado', false);

        Mail::assertNotSent(\App\Mail\AlertaPagoDuplicado::class);
    }

    public function test_deposito_sin_referencia_retorna_422(): void
    {
        $matA = $this->reservarPara($this->estudianteA, $this->ofertaA, $this->headersA());

        $metodoDep = MetodoPago::where('codigo', 'DEP')->firstOrFail();

        $response = $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matA->id,
            'metodo_pago_id' => $metodoDep->id,
            'referencia' => '',
            'fecha_pago' => '2026-08-01',
            'obligacion_ids' => $matA->obligaciones()->pluck('id')->all(),
        ], $this->headersA());

        $response->assertStatus(422)
            ->assertJsonPath('resultado', 'R');
    }

    public function test_deposito_sin_fecha_pago_retorna_422(): void
    {
        $matA = $this->reservarPara($this->estudianteA, $this->ofertaA, $this->headersA());

        $metodoDep = MetodoPago::where('codigo', 'DEP')->firstOrFail();

        $response = $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matA->id,
            'metodo_pago_id' => $metodoDep->id,
            'referencia' => 'DEP-NO-FECHA',
            'obligacion_ids' => $matA->obligaciones()->pluck('id')->all(),
        ], $this->headersA());

        $response->assertStatus(422)
            ->assertJsonPath('resultado', 'R');
    }

    public function test_transferencia_duplicada_tambien_marca_alerta(): void
    {
        Mail::fake();

        $matA = $this->reservarPara($this->estudianteA, $this->ofertaA, $this->headersA());
        $matB = $this->reservarPara($this->estudianteB, $this->ofertaB, $this->headersB());

        $metodoTra = MetodoPago::where('codigo', 'TRA')->firstOrFail();

        $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matA->id,
            'metodo_pago_id' => $metodoTra->id,
            'referencia' => 'TRA-777',
            'fecha_pago' => '2026-08-03',
            'obligacion_ids' => $matA->obligaciones()->pluck('id')->all(),
        ], $this->headersA());

        $response = $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matB->id,
            'metodo_pago_id' => $metodoTra->id,
            'referencia' => 'TRA-777',
            'fecha_pago' => '2026-08-03',
            'obligacion_ids' => $matB->obligaciones()->pluck('id')->all(),
        ], $this->headersB());

        $response->assertCreated()
            ->assertJsonPath('data.alerta_duplicado', true);

        Mail::assertSent(\App\Mail\AlertaPagoDuplicado::class);
    }

    public function test_detector_directo_marca_y_no_falla_sin_referencia(): void
    {
        $metodoDep = MetodoPago::where('codigo', 'DEP')->firstOrFail();
        $concepto = ConceptoPago::where('codigo', 'MAT')->firstOrFail();

        $pago = Pago::create([
            'codigo' => 'PAG-TEST-1',
            'estudiante_id' => $this->estudianteA->id,
            'concepto_pago_id' => $concepto->id,
            'metodo_pago_id' => $metodoDep->id,
            'sucursal_id' => $this->sucursal->id,
            'monto' => 100,
            'estado' => 'pendiente',
            'referencia_externa' => null,
            'fecha_proceso' => null,
            'creado_en' => now(),
        ]);

        $resultado = app(DetectorPagoDuplicado::class)->aplicar($pago, null, null, false);

        $this->assertFalse($resultado['duplicado']);
        $this->assertFalse((bool) $pago->fresh()->alerta_duplicado);
    }
}