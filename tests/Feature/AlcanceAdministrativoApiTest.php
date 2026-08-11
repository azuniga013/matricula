<?php

namespace Tests\Feature;

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
use App\Models\Pago;
use App\Models\PeriodoAcademico;
use App\Models\Permiso;
use App\Models\PlanCobro;
use App\Models\PlanEstudio;
use App\Models\ReciboCaja;
use App\Models\Rol;
use App\Models\SesionCaja;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\UsuarioSucursal;
use App\Models\VersionPlanEstudio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AlcanceAdministrativoApiTest extends TestCase
{
    use RefreshDatabase;

    private User $adminSucursalA;

    private string $tokenSucursalA;

    private User $adminPropietario;

    private string $tokenPropietario;

    private Sucursal $sucursalA;

    private Sucursal $sucursalB;

    private Estudiante $estudianteA;

    private Estudiante $estudianteB;

    private Matricula $matriculaA;

    private Matricula $matriculaB;

    private Pago $pagoA;

    private Pago $pagoB;

    private ReciboCaja $reciboA;

    private ReciboCaja $reciboB;

    private SesionCaja $sesionCajaA;

    private SesionCaja $sesionCajaB;

    private int $inventarioAId;

    private int $inventarioBId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearPermisosBase();

        $rol = Rol::create(['codigo' => 'TEST_SCOPE', 'nombre' => 'Scope', 'estado' => 'activo']);
        $rol->permisos()->attach(Permiso::pluck('id')->all(), ['estado' => 'activo']);

        $this->adminSucursalA = User::create([
            'name' => 'Admin Sucursal A',
            'email' => 'scope-a@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $this->adminSucursalA->roles()->attach($rol->id, ['estado' => 'activo']);
        $this->tokenSucursalA = $this->adminSucursalA->createToken('test')->plainTextToken;

        $this->adminPropietario = User::create([
            'name' => 'Admin Propietario',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $this->adminPropietario->roles()->attach($rol->id, ['estado' => 'activo']);
        $this->tokenPropietario = $this->adminPropietario->createToken('test')->plainTextToken;

        $this->sucursalA = Sucursal::factory()->create(['codigo' => 'SPS']);
        $this->sucursalB = Sucursal::factory()->create(['codigo' => 'LCE']);

        UsuarioSucursal::create([
            'usuario_id' => $this->adminSucursalA->id,
            'sucursal_id' => $this->sucursalA->id,
            'estado' => 'activo',
            'creado_por' => $this->adminSucursalA->id,
        ]);

        $conceptoMat = ConceptoPago::create([
            'codigo' => 'MAT',
            'nombre' => 'Matricula',
            'tipo_monto' => 'por_oferta',
            'requiere_autorizacion_monto' => false,
            'estado' => 'activo',
        ]);
        $metodoEfe = MetodoPago::create([
            'codigo' => 'EFE',
            'nombre' => 'Efectivo',
            'estado' => 'activo',
        ]);

        $planCobro = PlanCobro::create(['codigo' => 'PLAN-SCOPE', 'nombre' => 'Plan', 'estado' => 'activo']);
        DetallePlanCobro::create([
            'plan_cobro_id' => $planCobro->id,
            'concepto_pago_id' => $conceptoMat->id,
            'numero_cuota' => 0,
            'nombre_cargo' => 'Matricula',
            'monto' => 1200,
            'dias_vencimiento' => 0,
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
        $periodo = PeriodoAcademico::create([
            'codigo' => '2026-I',
            'nombre' => 'Semestre 1',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addMonths(4)->toDateString(),
            'estado' => 'activo',
        ]);

        $ofertaA = $this->crearOferta($this->sucursalA, $nivel->id, $modalidad->id, $horario->id, $docente->id, $periodo->id, $planCobro->id, 'OFE-A');
        $ofertaB = $this->crearOferta($this->sucursalB, $nivel->id, $modalidad->id, $horario->id, $docente->id, $periodo->id, $planCobro->id, 'OFE-B');

        $this->estudianteA = Estudiante::create([
            'codigo' => 'EST-A',
            'nombre' => 'Ana',
            'apellido' => 'Sucursal',
            'sucursal_id' => $this->sucursalA->id,
            'estado' => 'activo',
            'creado_por' => $this->adminSucursalA->id,
        ]);
        $this->estudianteB = Estudiante::create([
            'codigo' => 'EST-B',
            'nombre' => 'Beto',
            'apellido' => 'Ajeno',
            'sucursal_id' => $this->sucursalB->id,
            'estado' => 'activo',
            'creado_por' => $this->adminPropietario->id,
        ]);

        $this->matriculaA = Matricula::create([
            'codigo' => 'MAT-A',
            'estudiante_id' => $this->estudianteA->id,
            'oferta_academica_id' => $ofertaA->id,
            'sucursal_id' => $this->sucursalA->id,
            'estado' => 'matriculado',
            'fecha_reserva' => now(),
            'fecha_confirmacion' => now(),
            'creado_por' => $this->adminSucursalA->id,
        ]);
        $this->matriculaB = Matricula::create([
            'codigo' => 'MAT-B',
            'estudiante_id' => $this->estudianteB->id,
            'oferta_academica_id' => $ofertaB->id,
            'sucursal_id' => $this->sucursalB->id,
            'estado' => 'matriculado',
            'fecha_reserva' => now(),
            'fecha_confirmacion' => now(),
            'creado_por' => $this->adminPropietario->id,
        ]);

        $this->pagoA = Pago::create([
            'codigo' => 'PAG-A',
            'estudiante_id' => $this->estudianteA->id,
            'matricula_id' => $this->matriculaA->id,
            'concepto_pago_id' => $conceptoMat->id,
            'metodo_pago_id' => $metodoEfe->id,
            'sucursal_id' => $this->sucursalA->id,
            'monto' => 1200,
            'estado' => 'aprobado',
            'aprobado_por' => $this->adminSucursalA->id,
            'fecha_aprobacion' => now(),
            'creado_por' => $this->adminSucursalA->id,
        ]);
        $this->pagoB = Pago::create([
            'codigo' => 'PAG-B',
            'estudiante_id' => $this->estudianteB->id,
            'matricula_id' => $this->matriculaB->id,
            'concepto_pago_id' => $conceptoMat->id,
            'metodo_pago_id' => $metodoEfe->id,
            'sucursal_id' => $this->sucursalB->id,
            'monto' => 1300,
            'estado' => 'aprobado',
            'aprobado_por' => $this->adminPropietario->id,
            'fecha_aprobacion' => now(),
            'creado_por' => $this->adminPropietario->id,
        ]);

        $this->reciboA = ReciboCaja::create([
            'codigo' => 'REC-A',
            'numero_recibo' => 1,
            'pago_id' => $this->pagoA->id,
            'estudiante_id' => $this->estudianteA->id,
            'sucursal_id' => $this->sucursalA->id,
            'concepto_pago_id' => $conceptoMat->id,
            'metodo_pago_id' => $metodoEfe->id,
            'monto_total' => 1200,
            'estado' => 'emitido',
            'anio' => date('Y'),
            'creado_por' => $this->adminSucursalA->id,
            'fecha_recibo' => now(),
        ]);
        $this->reciboB = ReciboCaja::create([
            'codigo' => 'REC-B',
            'numero_recibo' => 2,
            'pago_id' => $this->pagoB->id,
            'estudiante_id' => $this->estudianteB->id,
            'sucursal_id' => $this->sucursalB->id,
            'concepto_pago_id' => $conceptoMat->id,
            'metodo_pago_id' => $metodoEfe->id,
            'monto_total' => 1300,
            'estado' => 'emitido',
            'anio' => date('Y'),
            'creado_por' => $this->adminPropietario->id,
            'fecha_recibo' => now(),
        ]);

        $this->sesionCajaA = SesionCaja::create([
            'codigo' => 'SC-A',
            'sucursal_id' => $this->sucursalA->id,
            'usuario_cajero_id' => $this->adminSucursalA->id,
            'monto_inicial' => 0,
            'estado' => 'cerrada',
            'fecha_apertura' => now()->subHour(),
            'fecha_cierre' => now(),
            'monto_final' => 1200,
            'creado_por' => $this->adminSucursalA->id,
        ]);
        $this->sesionCajaB = SesionCaja::create([
            'codigo' => 'SC-B',
            'sucursal_id' => $this->sucursalB->id,
            'usuario_cajero_id' => $this->adminPropietario->id,
            'monto_inicial' => 0,
            'estado' => 'cerrada',
            'fecha_apertura' => now()->subHour(),
            'fecha_cierre' => now(),
            'monto_final' => 1300,
            'creado_por' => $this->adminPropietario->id,
        ]);

        $this->inventarioAId = DB::table('inventario_libros')->insertGetId([
            'libro_id' => DB::table('libros')->insertGetId([
                'codigo' => 'LIB-A',
                'titulo' => 'Libro A',
                'precio_venta' => 100,
                'creado_en' => now(),
            ]),
            'sucursal_id' => $this->sucursalA->id,
            'existencia_actual' => 5,
            'creado_por' => $this->adminSucursalA->id,
            'creado_en' => now(),
        ]);
        $this->inventarioBId = DB::table('inventario_libros')->insertGetId([
            'libro_id' => DB::table('libros')->insertGetId([
                'codigo' => 'LIB-B',
                'titulo' => 'Libro B',
                'precio_venta' => 120,
                'creado_en' => now(),
            ]),
            'sucursal_id' => $this->sucursalB->id,
            'existencia_actual' => 8,
            'creado_por' => $this->adminPropietario->id,
            'creado_en' => now(),
        ]);
    }

    private function crearPermisosBase(): void
    {
        $modulos = [
            'seguridad' => ['consultar', 'crear', 'modificar', 'configurar'],
            'estudiantes' => ['consultar', 'crear', 'modificar'],
            'matriculas' => ['consultar', 'crear', 'modificar', 'aprobar'],
            'pagos' => ['consultar', 'crear', 'modificar', 'eliminar', 'aprobar'],
            'caja' => ['consultar', 'crear', 'modificar', 'aprobar'],
            'inventario' => ['consultar', 'crear', 'modificar', 'aprobar'],
            'reportes' => ['consultar'],
        ];

        $orden = 1;
        foreach ($modulos as $codigoModulo => $acciones) {
            $modulo = Modulo::create(['codigo' => $codigoModulo, 'nombre' => ucfirst($codigoModulo), 'estado' => 'activo', 'orden' => $orden++]);
            $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => $codigoModulo.'.general', 'nombre' => 'General', 'estado' => 'activo']);
            foreach ($acciones as $accion) {
                Permiso::create([
                    'opcion_modulo_id' => $opcion->id,
                    'codigo' => $codigoModulo.'.'.$accion,
                    'nombre' => ucfirst($accion),
                    'accion' => $accion,
                    'estado' => 'activo',
                ]);
            }
        }
    }

    private function crearOferta(Sucursal $sucursal, int $nivelId, int $modalidadId, int $horarioId, int $docenteId, int $periodoId, int $planCobroId, string $codigo): OfertaAcademica
    {
        $aula = Aula::create([
            'sucursal_id' => $sucursal->id,
            'codigo' => 'AUL-'.$codigo,
            'nombre' => 'Aula '.$codigo,
            'capacidad' => 25,
        ]);

        return OfertaAcademica::create([
            'sucursal_id' => $sucursal->id,
            'periodo_academico_id' => $periodoId,
            'nivel_academico_id' => $nivelId,
            'modalidad_id' => $modalidadId,
            'horario_id' => $horarioId,
            'docente_id' => $docenteId,
            'aula_id' => $aula->id,
            'plan_cobro_id' => $planCobroId,
            'codigo' => $codigo,
            'cupo_maximo' => 25,
            'estado' => 'abierto',
        ]);
    }

    private function headersSucursalA(): array
    {
        return ['Authorization' => 'Bearer '.$this->tokenSucursalA];
    }

    private function headersPropietario(): array
    {
        return ['Authorization' => 'Bearer '.$this->tokenPropietario];
    }

    public function test_admin_con_sucursal_asignada_solo_ve_estudiantes_de_su_sucursal(): void
    {
        $this->getJson('/api/v1/estudiantes', $this->headersSucursalA())
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.codigo', 'EST-A');

        $this->getJson('/api/v1/estudiantes/'.$this->estudianteB->id, $this->headersSucursalA())
            ->assertStatus(404);
    }

    public function test_admin_con_sucursal_asignada_solo_ve_matriculas_de_su_sucursal(): void
    {
        $this->getJson('/api/v1/matriculas', $this->headersSucursalA())
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.codigo', 'MAT-A');

        $this->getJson('/api/v1/matriculas/'.$this->matriculaB->id, $this->headersSucursalA())
            ->assertStatus(404);
    }

    public function test_admin_con_sucursal_asignada_solo_ve_pagos_y_recibos_de_su_sucursal(): void
    {
        $this->getJson('/api/v1/pagos', $this->headersSucursalA())
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.codigo', 'PAG-A');

        $this->getJson('/api/v1/pagos/'.$this->pagoB->id, $this->headersSucursalA())
            ->assertStatus(404);

        $this->getJson('/api/v1/recibos-caja', $this->headersSucursalA())
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.codigo', 'REC-A');

        $this->getJson('/api/v1/recibos-caja/'.$this->reciboB->id, $this->headersSucursalA())
            ->assertStatus(404);
    }

    public function test_admin_sin_sucursales_solo_ve_registros_propios_por_creado_por(): void
    {
        $this->getJson('/api/v1/estudiantes', $this->headersPropietario())
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.codigo', 'EST-B');

        $this->getJson('/api/v1/pagos', $this->headersPropietario())
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.codigo', 'PAG-B');
    }

    public function test_obligaciones_estudiante_respeta_alcance_administrativo_por_sucursal(): void
    {
        ObligacionPagoEstudiante::create([
            'matricula_id' => $this->matriculaA->id,
            'concepto_pago_id' => ConceptoPago::where('codigo', 'MAT')->value('id'),
            'numero_cuota' => 1,
            'nombre_cargo' => 'Matricula extra',
            'monto' => 100,
            'monto_pagado' => 0,
            'fecha_vencimiento' => now(),
            'estado' => 'pendiente',
        ]);

        $this->getJson('/api/v1/pagos/obligaciones-estudiante?estudiante_id='.$this->estudianteA->id.'&concepto_pago_id='.ConceptoPago::where('codigo', 'MAT')->value('id'), $this->headersSucursalA())
            ->assertOk();

        $this->getJson('/api/v1/pagos/obligaciones-estudiante?estudiante_id='.$this->estudianteB->id.'&concepto_pago_id='.ConceptoPago::where('codigo', 'MAT')->value('id'), $this->headersSucursalA())
            ->assertStatus(404);
    }

    public function test_reporte_financiero_pendientes_respeta_alcance_por_sucursal(): void
    {
        DB::table('pagos')->where('id', $this->pagoA->id)->update(['estado' => 'pendiente', 'aprobado_por' => null, 'fecha_aprobacion' => null]);
        DB::table('pagos')->where('id', $this->pagoB->id)->update(['estado' => 'pendiente', 'aprobado_por' => null, 'fecha_aprobacion' => null]);

        $this->getJson('/api/v1/reportes/financieros/pagos-pendientes', $this->headersSucursalA())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.pago_codigo', 'PAG-A');
    }

    public function test_reporte_academico_grupo_respeta_alcance_por_sucursal(): void
    {
        $this->getJson('/api/v1/reportes/academicos/grupo?oferta_academica_id='.$this->matriculaA->oferta_academica_id, $this->headersSucursalA())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.estudiante_codigo', 'EST-A');

        $this->getJson('/api/v1/reportes/academicos/grupo?oferta_academica_id='.$this->matriculaB->oferta_academica_id, $this->headersSucursalA())
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_reporte_recibos_por_orden_respeta_alcance_por_sucursal(): void
    {
        $this->getJson('/api/v1/reportes/recibos/por-orden?fecha_desde='.now()->toDateString().'&fecha_hasta='.now()->toDateString(), $this->headersSucursalA())
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.codigo', 'REC-A');
    }

    public function test_admin_con_sucursal_asignada_solo_ve_sesiones_de_caja_de_su_sucursal(): void
    {
        $this->getJson('/api/v1/caja/sesiones', $this->headersSucursalA())
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.codigo', 'SC-A');

        $this->getJson('/api/v1/caja/'.$this->sesionCajaB->id, $this->headersSucursalA())
            ->assertStatus(404);
    }

    public function test_admin_con_sucursal_asignada_solo_ve_inventario_de_su_sucursal(): void
    {
        $this->getJson('/api/v1/inventario/stock', $this->headersSucursalA())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->inventarioAId);

        $this->getJson('/api/v1/inventario/stock/'.$this->inventarioBId, $this->headersSucursalA())
            ->assertStatus(404);

        $this->getJson('/api/v1/inventario/kardex?inventario_libro_id='.$this->inventarioBId, $this->headersSucursalA())
            ->assertStatus(404);
    }
}
