<?php

namespace Tests\Feature;

use App\Models\AccesoEstudiante;
use App\Models\Aula;
use App\Models\ComprobantePago;
use App\Models\ConceptoPago;
use App\Models\ConfiguracionFlujoMatricula;
use App\Models\CuentaBancaria;
use App\Models\DepartamentoAcademico;
use App\Models\DetallePlanCobro;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\GrupoWhatsapp;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\MetodoPago;
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
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\VersionPlanEstudio;
use App\Services\ResolutorFlujoMatricula;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class FlujoMatriculaConfiguracionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $adminToken;

    private Estudiante $estudiante;

    private string $studentToken;

    private Sucursal $sucursal;

    private OfertaAcademica $oferta;

    private int $conceptoMatId;

    private int $conceptoCuoId;

    private int $metodoEfeId;

    private int $metodoDepId;

    private int $metodoLinkId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearPermisosBase();

        $rol = Rol::create(['codigo' => 'TEST_CFG', 'nombre' => 'Test CFG', 'estado' => 'activo']);
        $permisos = Permiso::whereIn('codigo', [
            'seguridad.consultar',
            'seguridad.crear',
            'seguridad.modificar',
            'seguridad.eliminar',
            'pagos.crear',
            'pagos.aprobar',
        ])->pluck('id')->all();
        $rol->permisos()->attach($permisos, ['estado' => 'activo']);

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $this->admin->roles()->attach($rol->id, ['estado' => 'activo']);
        $this->adminToken = $this->admin->createToken('test')->plainTextToken;

        $this->sucursal = Sucursal::factory()->create(['codigo' => 'SPS']);
        $this->estudiante = Estudiante::factory()->create([
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'activo',
        ]);

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
        $this->metodoDepId = MetodoPago::create([
            'codigo' => 'DEP',
            'nombre' => 'Deposito',
            'estado' => 'activo',
            'permite_link_pago' => false,
        ])->id;
        $this->metodoLinkId = MetodoPago::create([
            'codigo' => 'LNK',
            'nombre' => 'Link',
            'estado' => 'activo',
            'permite_link_pago' => true,
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
        $aula = Aula::create(['sucursal_id' => $this->sucursal->id, 'codigo' => 'AUL-01', 'nombre' => 'Aula 1', 'capacidad' => 25]);
        $grupo = GrupoWhatsapp::create([
            'sucursal_id' => $this->sucursal->id,
            'codigo' => 'GWA-01',
            'nombre' => 'Grupo Test',
            'link' => 'https://chat.whatsapp.com/test',
            'estado' => 'activo',
        ]);
        $periodo = PeriodoAcademico::create([
            'codigo' => '2026-I',
            'nombre' => 'Semestre 1',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addMonths(4)->toDateString(),
            'estado' => 'activo',
        ]);

        $this->oferta = OfertaAcademica::create([
            'sucursal_id' => $this->sucursal->id,
            'periodo_academico_id' => $periodo->id,
            'nivel_academico_id' => $nivel->id,
            'modalidad_id' => $modalidad->id,
            'horario_id' => $horario->id,
            'docente_id' => $docente->id,
            'aula_id' => $aula->id,
            'grupo_whatsapp_id' => $grupo->id,
            'plan_cobro_id' => $planCobro->id,
            'codigo' => 'SPS-2026I-ING1-INT-MAT',
            'cupo_maximo' => 25,
            'estado' => 'abierto',
        ]);
    }

    private function crearPermisosBase(): void
    {
        $modSeguridad = Modulo::create(['codigo' => 'seguridad', 'nombre' => 'Seguridad', 'estado' => 'activo', 'orden' => 1]);
        $opSeguridad = OpcionModulo::create(['modulo_id' => $modSeguridad->id, 'codigo' => 'seguridad.general', 'nombre' => 'General', 'estado' => 'activo']);
        foreach (['consultar', 'crear', 'modificar', 'eliminar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opSeguridad->id,
                'codigo' => 'seguridad.'.$accion,
                'nombre' => ucfirst($accion),
                'accion' => $accion,
                'estado' => 'activo',
            ]);
        }

        $modPagos = Modulo::create(['codigo' => 'pagos', 'nombre' => 'Pagos', 'estado' => 'activo', 'orden' => 7]);
        $opPagos = OpcionModulo::create(['modulo_id' => $modPagos->id, 'codigo' => 'pagos.general', 'nombre' => 'General', 'estado' => 'activo']);
        foreach (['consultar', 'crear', 'modificar', 'eliminar', 'aprobar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opPagos->id,
                'codigo' => 'pagos.'.$accion,
                'nombre' => ucfirst($accion),
                'accion' => $accion,
                'estado' => 'activo',
            ]);
        }
    }

    private function adminHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->adminToken}"];
    }

    private function studentHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->studentToken}"];
    }

    private function crearConfiguracion(array $atributos): ConfiguracionFlujoMatricula
    {
        $conceptoIds = $atributos['concepto_pago_ids'] ?? [$atributos['concepto_pago_id'] ?? $this->conceptoMatId];
        $metodoIds = $atributos['metodo_pago_ids'] ?? [$atributos['metodo_pago_id'] ?? $this->metodoEfeId];

        $cfg = ConfiguracionFlujoMatricula::create(array_merge([
            'codigo' => 'CFG-'.Str::upper(Str::random(8)),
            'origen' => 'portal_estudiante',
            'concepto_pago_id' => $conceptoIds[0],
            'metodo_pago_id' => $metodoIds[0],
            'estado' => 'activo',
            'habilita_reserva_cupo' => true,
            'habilita_carga_comprobante' => true,
            'requiere_comprobante' => true,
            'habilita_revision_contable' => true,
            'habilita_aprobacion_pago' => true,
            'habilita_generacion_recibo' => true,
            'habilita_confirmacion_matricula' => true,
            'habilita_seleccion_obligaciones' => true,
            'habilita_whatsapp' => true,
            'habilita_reenganche' => true,
            'habilita_solicitud_link' => true,
        ], collect($atributos)->except(['concepto_pago_ids', 'metodo_pago_ids'])->all()));

        $cfg->conceptosPago()->sync(array_fill_keys($conceptoIds, ['creado_por' => $this->admin->id, 'creado_en' => now()]));
        $cfg->metodosPago()->sync(array_fill_keys($metodoIds, ['creado_por' => $this->admin->id, 'creado_en' => now()]));

        return $cfg;
    }

    private function reservarMatricula(): Matricula
    {
        $this->postJson('/api/v1/estudiantes/reservar-matricula', [
            'oferta_academica_id' => $this->oferta->id,
            'plan_estudio_id' => $this->oferta->nivelAcademico->versionPlanEstudio->plan_estudio_id,
        ], $this->studentHeaders())->assertCreated();

        return Matricula::where('estudiante_id', $this->estudiante->id)->latest('id')->firstOrFail();
    }

    public function test_resolutor_aplica_precedencia_y_fallback_tecnico_para_portal_administrativo(): void
    {
        $this->crearConfiguracion([
            'codigo' => 'CFG-TEC',
            'origen' => 'tecnico',
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'habilita_generacion_recibo' => false,
        ]);

        $cfgVieja = $this->crearConfiguracion([
            'codigo' => 'CFG-ADM-1',
            'origen' => 'portal_administrativo',
            'concepto_pago_id' => $this->conceptoCuoId,
            'metodo_pago_id' => $this->metodoEfeId,
            'concepto_pago_ids' => [$this->conceptoCuoId, $this->conceptoMatId],
            'habilita_generacion_recibo' => false,
        ]);
        $cfgNueva = $this->crearConfiguracion([
            'codigo' => 'CFG-ADM-2',
            'origen' => 'portal_administrativo',
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'habilita_generacion_recibo' => true,
        ]);

        $resolutor = app(ResolutorFlujoMatricula::class);
        $resultado = $resolutor->resolver('portal_administrativo', $this->conceptoMatId, $this->metodoEfeId);
        $this->assertTrue($resultado['habilita_generacion_recibo']);
        $this->assertSame($cfgNueva->id, $resultado['id']);

        $cfgVieja->update(['estado' => 'inactivo']);
        $cfgNueva->update(['estado' => 'inactivo']);

        $fallback = $resolutor->resolver('portal_administrativo', $this->conceptoMatId, $this->metodoEfeId);
        $this->assertFalse($fallback['habilita_generacion_recibo']);
        $this->assertSame('tecnico', $fallback['origen']);
    }

    public function test_crear_y_desactivar_configuracion_conserva_asociaciones_y_el_resolutor_la_ignora(): void
    {
        $response = $this->postJson('/api/v1/seguridad/configuraciones-flujo-matricula', [
            'codigo' => 'CFG-API-01',
            'origen' => 'portal_estudiante',
            'metodo_pago_id' => $this->metodoLinkId,
            'metodo_pago_ids' => [$this->metodoLinkId, $this->metodoDepId],
            'concepto_pago_ids' => [$this->conceptoMatId, $this->conceptoCuoId],
            'habilita_reserva_cupo' => true,
            'habilita_solicitud_link' => true,
            'habilita_reenganche' => false,
        ], $this->adminHeaders());

        $response->assertCreated();
        $configId = $response->json('data.id');

        $this->assertDatabaseHas('configuracion_flujo_matricula_conceptos', [
            'configuracion_flujo_matricula_id' => $configId,
            'concepto_pago_id' => $this->conceptoCuoId,
        ]);
        $this->assertDatabaseHas('configuracion_flujo_matricula_metodos', [
            'configuracion_flujo_matricula_id' => $configId,
            'metodo_pago_id' => $this->metodoDepId,
        ]);

        $this->deleteJson("/api/v1/seguridad/configuraciones-flujo-matricula/{$configId}", [], $this->adminHeaders())
            ->assertOk();

        $this->assertDatabaseHas('configuraciones_flujo_matricula', ['id' => $configId, 'estado' => 'inactivo']);
        $this->assertDatabaseHas('configuracion_flujo_matricula_conceptos', [
            'configuracion_flujo_matricula_id' => $configId,
            'concepto_pago_id' => $this->conceptoMatId,
        ]);

        $resuelto = app(ResolutorFlujoMatricula::class)->resolver('portal_estudiante', $this->conceptoMatId, $this->metodoLinkId);
        $this->assertTrue($resuelto['habilita_reenganche']);
        $this->assertArrayNotHasKey('id', $resuelto);
    }

    public function test_reserva_online_rechaza_cuando_flujo_desactiva_reserva_cupo(): void
    {
        $this->crearConfiguracion([
            'origen' => 'portal_estudiante',
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'habilita_reserva_cupo' => false,
        ]);

        $this->postJson('/api/v1/estudiantes/reservar-matricula', [
            'oferta_academica_id' => $this->oferta->id,
            'plan_estudio_id' => $this->oferta->nivelAcademico->versionPlanEstudio->plan_estudio_id,
        ], $this->studentHeaders())
            ->assertStatus(422)
            ->assertJsonPath('codigo_error', '422_FLUJO_MATRICULA_DESHABILITADO');
    }

    public function test_portal_rechaza_solicitar_link_cuando_flujo_lo_desactiva(): void
    {
        $this->crearConfiguracion([
            'origen' => 'portal_estudiante',
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoLinkId,
            'habilita_solicitud_link' => false,
        ]);

        $matricula = $this->reservarMatricula();

        $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matricula->id,
            'metodo_pago_id' => $this->metodoLinkId,
            'obligacion_ids' => [$matricula->obligaciones()->where('concepto_pago_id', $this->conceptoMatId)->value('id')],
            'solicitar_link' => true,
        ], $this->studentHeaders())
            ->assertStatus(422)
            ->assertJsonPath('codigo_error', '422_SOLICITUD_LINK_DESHABILITADA');
    }

    public function test_subir_comprobante_rechaza_cuando_carga_esta_deshabilitada(): void
    {
        $matricula = $this->reservarMatricula();
        $pago = Pago::create([
            'codigo' => 'PAG-TEST-0001',
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoDepId,
            'sucursal_id' => $this->sucursal->id,
            'monto' => 1200,
            'estado' => 'pendiente',
            'creado_en' => now(),
        ]);

        $this->crearConfiguracion([
            'origen' => 'portal_estudiante',
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoDepId,
            'habilita_carga_comprobante' => false,
        ]);

        $this->postJson('/api/v1/estudiantes/subir-comprobante', [
            'pago_id' => $pago->id,
            'metodo_pago_id' => $this->metodoDepId,
            'cuenta_bancaria_id' => CuentaBancaria::create([
                'codigo' => 'CTA-001',
                'nombre' => 'Cuenta test',
                'banco' => 'BAC',
                'numero_cuenta' => '1234',
                'tipo_cuenta' => 'ahorro',
                'estado' => 'activo',
            ])->id,
            'referencia' => 'DEP-001',
            'fecha_pago' => now()->toDateString(),
            'comprobante' => UploadedFile::fake()->image('comp.jpg'),
        ], $this->studentHeaders())
            ->assertStatus(422)
            ->assertJsonPath('mensaje', 'La carga de comprobante está deshabilitada para este proceso');

        $this->assertDatabaseCount('comprobantes_pago', 0);
    }

    public function test_subir_comprobante_exige_archivo_cuando_la_configuracion_lo_requiere(): void
    {
        $matricula = $this->reservarMatricula();
        $pago = Pago::create([
            'codigo' => 'PAG-TEST-0002',
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoDepId,
            'sucursal_id' => $this->sucursal->id,
            'monto' => 1200,
            'estado' => 'pendiente',
            'creado_en' => now(),
        ]);

        $this->crearConfiguracion([
            'origen' => 'portal_estudiante',
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoDepId,
            'habilita_carga_comprobante' => true,
            'requiere_comprobante' => true,
        ]);

        $this->postJson('/api/v1/estudiantes/subir-comprobante', [
            'pago_id' => $pago->id,
            'metodo_pago_id' => $this->metodoDepId,
            'cuenta_bancaria_id' => CuentaBancaria::create([
                'codigo' => 'CTA-002',
                'nombre' => 'Cuenta test 2',
                'banco' => 'BAC',
                'numero_cuenta' => '5678',
                'tipo_cuenta' => 'ahorro',
                'estado' => 'activo',
            ])->id,
            'referencia' => 'DEP-002',
            'fecha_pago' => now()->toDateString(),
        ], $this->studentHeaders())
            ->assertStatus(422)
            ->assertJsonPath('mensaje', 'Este proceso requiere comprobante');

        $this->assertDatabaseCount('comprobantes_pago', 0);
    }

    public function test_pago_administrativo_directo_rechaza_cuando_aprobacion_inmediata_esta_deshabilitada(): void
    {
        $matricula = Matricula::create([
            'codigo' => 'MAT-ADM-001',
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'reservada',
            'fecha_reserva' => now(),
        ]);
        ObligacionPagoEstudiante::create([
            'matricula_id' => $matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'numero_cuota' => 0,
            'nombre_cargo' => 'Matricula',
            'monto' => 1200,
            'monto_pagado' => 0,
            'fecha_vencimiento' => now(),
            'estado' => 'pendiente',
        ]);

        $this->crearConfiguracion([
            'origen' => 'portal_administrativo',
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'habilita_aprobacion_pago' => false,
        ]);

        $this->postJson('/api/v1/pagos/registrar', [
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto' => 1200,
        ], $this->adminHeaders())
            ->assertStatus(422)
            ->assertJsonPath('codigo_error', '422_APROBACION_DESHABILITADA');
    }

    public function test_reenganche_rechaza_cuando_flujo_lo_desactiva(): void
    {
        $matricula = $this->reservarMatricula();

        $pago = $this->postJson('/api/v1/estudiantes/registrar-pago', [
            'matricula_id' => $matricula->id,
            'metodo_pago_id' => $this->metodoLinkId,
            'obligacion_ids' => $matricula->obligaciones()->pluck('id')->all(),
        ], $this->studentHeaders())->json('data');

        Pago::where('id', $pago['pago_id'])->update(['estado' => 'en_revision']);

        $this->crearConfiguracion([
            'origen' => 'portal_estudiante',
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoLinkId,
            'habilita_reenganche' => false,
        ]);

        $this->postJson('/api/v1/estudiantes/reenganchar-flujo-pago', [
            'pago_id' => $pago['pago_id'],
        ], $this->studentHeaders())
            ->assertStatus(422)
            ->assertJsonPath('mensaje', 'El reenganche de flujo está deshabilitado para este proceso.');
    }

    public function test_whatsapp_no_entrega_link_cuando_flujo_lo_desactiva(): void
    {
        $matricula = Matricula::create([
            'codigo' => 'MAT-WSP-001',
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'matriculado',
            'fecha_reserva' => now(),
            'fecha_confirmacion' => now(),
        ]);
        Pago::create([
            'codigo' => 'PAG-WSP-001',
            'estudiante_id' => $this->estudiante->id,
            'matricula_id' => $matricula->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'sucursal_id' => $this->sucursal->id,
            'monto' => 1200,
            'estado' => 'aprobado',
            'fecha_aprobacion' => now(),
            'creado_en' => now(),
        ]);

        $this->crearConfiguracion([
            'origen' => 'portal_estudiante',
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'habilita_whatsapp' => false,
        ]);

        $this->getJson('/api/v1/estudiantes/whatsapp', $this->studentHeaders())
            ->assertOk()
            ->assertJsonPath('data.whatsapp_link', null);
    }
}
