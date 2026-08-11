<?php

namespace Tests\Feature;

use App\Models\Aula;
use App\Models\Calificacion;
use App\Models\DepartamentoAcademico;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\HistorialAcademico;
use App\Models\Horario;
use App\Models\Modalidad;
use App\Models\NivelAcademico;
use App\Models\ObligacionPagoEstudiante;
use App\Models\OfertaAcademica;
use App\Models\Pago;
use App\Models\PeriodoAcademico;
use App\Models\PlanCobro;
use App\Models\PlanEstudio;
use App\Models\ReciboCaja;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\VersionPlanEstudio;
use App\Modules\Caja\CasosUso\AbrirSesionCaja;
use App\Modules\Caja\CasosUso\AnularRecibo;
use App\Modules\Caja\CasosUso\CerrarSesionCaja;
use App\Modules\Calificaciones\CasosUso\ActualizarCalificacion;
use App\Modules\Calificaciones\CasosUso\RegistrarCalificaciones;
use App\Modules\Comun\ContextoUsuario;
use App\Modules\Matriculas\CasosUso\ConfirmarMatricula;
use App\Modules\Matriculas\CasosUso\ReservarMatricula;
use App\Modules\Pagos\CasosUso\RegistrarPago;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegracionFlujoAcademicoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ContextoUsuario $contexto;

    private Sucursal $sucursal;

    private OfertaAcademica $oferta;

    private Estudiante $estudiante;

    private int $conceptoMatId;

    private int $metodoEfeId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $this->contexto = new ContextoUsuario($this->admin->id);

        $this->sucursal = Sucursal::factory()->create(['codigo' => 'SPS']);
        $periodo = PeriodoAcademico::create([
            'codigo' => '2026-I', 'nombre' => 'Semestre 1',
            'fecha_inicio' => '2026-01-15', 'fecha_fin' => '2026-06-30', 'estado' => 'activo',
        ]);

        $depto = DepartamentoAcademico::factory()->create(['codigo' => 'ING']);
        $plan = PlanEstudio::create(['departamento_academico_id' => $depto->id, 'codigo' => 'ING-GEN', 'nombre' => 'Inglés General']);
        $version = VersionPlanEstudio::create(['plan_estudio_id' => $plan->id, 'numero_version' => 1, 'vigente_desde' => '2026-01-01']);

        $regimen = Modalidad::create(['codigo' => 'INT', 'nombre' => 'Intensivo', 'tipo' => 'regimen_academico']);
        $nivel = NivelAcademico::create([
            'version_plan_estudio_id' => $version->id, 'regimen_academico_id' => $regimen->id,
            'codigo' => 'ING-1', 'nombre' => 'Inglés 1', 'orden' => 1,
            'nota_minima_aprobar' => 80, 'faltas_maximas_permitidas' => 7,
        ]);
        $modalidad = Modalidad::create(['codigo' => 'PRES', 'nombre' => 'Presencial', 'tipo' => 'atencion']);
        $horario = Horario::create(['codigo' => 'M1', 'nombre' => 'Matutino', 'hora_inicio' => '07:00', 'hora_fin' => '09:00']);
        $horario->update(['lunes' => true, 'miercoles' => true]);
        $docente = Docente::factory()->create(['codigo' => 'DOC001']);
        $aula = Aula::create(['sucursal_id' => $this->sucursal->id, 'codigo' => 'AUL-01', 'nombre' => 'Aula 1', 'capacidad' => 25]);

        $this->conceptoMatId = \DB::table('conceptos_pago')->insertGetId([
            'codigo' => 'MAT', 'nombre' => 'Matrícula', 'tipo_monto' => 'por_oferta',
            'requiere_autorizacion_monto' => false, 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);
        $conceptoCuoId = \DB::table('conceptos_pago')->insertGetId([
            'codigo' => 'CUO', 'nombre' => 'Cuota', 'tipo_monto' => 'por_oferta',
            'requiere_autorizacion_monto' => false, 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);
        $this->metodoEfeId = \DB::table('metodos_pago')->insertGetId([
            'codigo' => 'EFE', 'nombre' => 'Efectivo', 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);

        $planCobro = PlanCobro::create(['codigo' => 'PLN-TEST-INT', 'nombre' => 'Plan Test Intensivo', 'estado' => 'activo']);
        \DB::table('detalle_plan_cobro')->insert([
            ['plan_cobro_id' => $planCobro->id, 'concepto_pago_id' => $this->conceptoMatId, 'numero_cuota' => 0, 'nombre_cargo' => 'Matrícula', 'monto' => 1200.00, 'dias_vencimiento' => 0, 'estado' => 'activo', 'creado_en' => now(), 'actualizado_en' => now()],
            ['plan_cobro_id' => $planCobro->id, 'concepto_pago_id' => $conceptoCuoId, 'numero_cuota' => 1, 'nombre_cargo' => 'Cuota 1', 'monto' => 1100.00, 'dias_vencimiento' => 30, 'estado' => 'activo', 'creado_en' => now(), 'actualizado_en' => now()],
        ]);

        $this->oferta = OfertaAcademica::create([
            'codigo' => 'OF-2026-ING1-INT', 'sucursal_id' => $this->sucursal->id,
            'periodo_academico_id' => $periodo->id, 'nivel_academico_id' => $nivel->id,
            'modalidad_id' => $modalidad->id, 'horario_id' => $horario->id,
            'docente_id' => $docente->id, 'aula_id' => $aula->id, 'plan_cobro_id' => $planCobro->id,
            'cupo_maximo' => 25, 'cupos_reservados' => 0, 'cupos_matriculados' => 0, 'estado' => 'abierto',
        ]);

        $this->estudiante = Estudiante::factory()->create(['sucursal_id' => $this->sucursal->id, 'codigo' => 'EST-001']);
    }

    public function test_flujo_completo_matricula_pago_caja_calificaciones(): void
    {
        // 1. Matrícula (P-029): reservar y confirmar genera obligaciones
        $reserva = app(ReservarMatricula::class)->ejecutar([
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
        ], $this->contexto);

        $this->assertTrue($reserva->ok());
        $matricula = $reserva->data()['matricula'];

        $confirmacion = app(ConfirmarMatricula::class)->ejecutar($matricula->id, $this->contexto);
        $this->assertTrue($confirmacion->ok());
        $this->assertSame('en_revision', $confirmacion->data()['matricula']->estado);
        $this->assertCount(2, ObligacionPagoEstudiante::where('matricula_id', $matricula->id)->get());

        // 2. Caja (P-035): abrir sesión antes de cobrar
        $sesion = app(AbrirSesionCaja::class)->ejecutar([
            'sucursal_id' => $this->sucursal->id,
            'monto_inicial' => 500.00,
        ], $this->contexto);

        $this->assertTrue($sesion->ok());
        $sesionId = $sesion->data()['sesion']->id;

        // 3. Pagos (P-028): registrar pago de matrícula genera recibo, aplica obligación y matricula al estudiante
        $pago = app(RegistrarPago::class)->ejecutar([
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
        ], $this->contexto);

        $this->assertTrue($pago->ok());
        $this->assertSame('aprobado', $pago->data()['pago']->estado);

        $matricula->refresh();
        $this->assertSame('matriculado', $matricula->estado);

        $obligacionMat = ObligacionPagoEstudiante::where('matricula_id', $matricula->id)
            ->where('concepto_pago_id', $this->conceptoMatId)->first();
        $this->assertSame('pagado', $obligacionMat->estado);
        $this->assertEquals(1200.00, $obligacionMat->monto_pagado);

        $recibo = ReciboCaja::where('pago_id', $pago->data()['pago']->id)->first();
        $this->assertNotNull($recibo);
        $this->assertSame('emitido', $recibo->estado);

        $pagoConSesion = Pago::find($pago->data()['pago']->id);
        $this->assertEquals($sesionId, $pagoConSesion->sesion_caja_id);

        // 4. Calificaciones (P-035): registrar nota sincroniza historial académico
        $calificacion = app(RegistrarCalificaciones::class)->ejecutar(
            $this->oferta->id,
            [['estudiante_id' => $this->estudiante->id, 'nota_final' => 90, 'faltas' => 1]],
            null,
            $this->contexto,
        );

        $this->assertTrue($calificacion->ok());
        $historial = HistorialAcademico::where('estudiante_id', $this->estudiante->id)->first();
        $this->assertNotNull($historial);
        $this->assertSame('aprobado', $historial->estado);
        $this->assertEquals(90, (float) $historial->nota_final);

        $calificacionRegistrada = Calificacion::where('estudiante_id', $this->estudiante->id)->first();

        $actualizacion = app(ActualizarCalificacion::class)->ejecutar(
            $calificacionRegistrada->id,
            ['nota_final' => 55],
            null,
            $this->contexto,
        );

        $this->assertTrue($actualizacion->ok());
        $this->assertSame('corregido', $actualizacion->data()['calificacion']->estado);
        $historial->refresh();
        $this->assertSame('reprobado', $historial->estado);

        // 5. Caja (P-035): anular recibo y cerrar sesión con los pagos aprobados
        $anulacion = app(AnularRecibo::class)->ejecutar($recibo->id, 'Error administrativo', $this->contexto);
        $this->assertTrue($anulacion->ok());
        $this->assertSame('anulado', $anulacion->data()['recibo']->estado);

        $cierre = app(CerrarSesionCaja::class)->ejecutar($sesionId, ['monto_final' => 1700.00], $this->contexto);
        $this->assertTrue($cierre->ok());
        $this->assertSame('cerrada', $cierre->data()['sesion']->estado);

        $detalles = \DB::table('detalle_cierre_caja')->where('sesion_caja_id', $sesionId)->get();
        $this->assertNotEmpty($detalles);
    }

    public function test_cupos_y_obligaciones_son_consistentes_tras_pago(): void
    {
        $reserva = app(ReservarMatricula::class)->ejecutar([
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
        ], $this->contexto);

        $this->assertTrue($reserva->ok());
        $matricula = $reserva->data()['matricula'];

        app(ConfirmarMatricula::class)->ejecutar($matricula->id, $this->contexto);

        app(RegistrarPago::class)->ejecutar([
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200.00,
        ], $this->contexto);

        $this->oferta->refresh();
        $this->assertEquals(1, $this->oferta->cupos_matriculados);
        $this->assertEquals(0, $this->oferta->cupos_reservados);

        $obligaciones = ObligacionPagoEstudiante::where('matricula_id', $matricula->id)->get();
        $this->assertSame('pagado', $obligaciones->first()->estado);
        $this->assertSame('pendiente', $obligaciones->last()->estado);
    }
}
