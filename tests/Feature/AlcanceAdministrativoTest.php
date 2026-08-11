<?php

namespace Tests\Feature;

use App\Models\Aula;
use App\Models\ConceptoPago;
use App\Models\DepartamentoAcademico;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\MetodoPago;
use App\Models\Modalidad;
use App\Models\Modulo;
use App\Models\NivelAcademico;
use App\Models\OfertaAcademica;
use App\Models\OpcionModulo;
use App\Models\Pago;
use App\Models\PeriodoAcademico;
use App\Models\Permiso;
use App\Models\PlanEstudio;
use App\Models\ReciboCaja;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\VersionPlanEstudio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlcanceAdministrativoTest extends TestCase
{
    use RefreshDatabase;

    private Sucursal $sucursalSps;

    private Sucursal $sucursalTgu;

    private User $usuarioSucursal;

    private User $usuarioPropietario;

    private Estudiante $estudianteSps;

    private Estudiante $estudianteTgu;

    private Estudiante $estudiantePropio;

    private Matricula $matriculaSps;

    private Matricula $matriculaTgu;

    private Matricula $matriculaPropia;

    private Pago $pagoSps;

    private Pago $pagoTgu;

    private Pago $pagoPropio;

    private ReciboCaja $reciboSps;

    private ReciboCaja $reciboTgu;

    private ReciboCaja $reciboPropio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearPermisosBase();
        $this->crearUsuarios();
        $this->crearDatos();
    }

    private function crearPermisosBase(): void
    {
        $modulos = [
            'estudiantes' => ['consultar'],
            'matriculas' => ['consultar'],
            'pagos' => ['consultar'],
            'reportes' => ['consultar'],
        ];

        foreach ($modulos as $codigoModulo => $acciones) {
            $modulo = Modulo::create([
                'codigo' => $codigoModulo,
                'nombre' => ucfirst($codigoModulo),
                'estado' => 'activo',
                'orden' => 1,
            ]);

            $opcion = OpcionModulo::create([
                'modulo_id' => $modulo->id,
                'codigo' => $codigoModulo.'.general',
                'nombre' => 'General',
                'estado' => 'activo',
            ]);

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

    private function crearUsuarios(): void
    {
        $rol = Rol::create([
            'codigo' => 'ALCANCE_ADMIN',
            'nombre' => 'Alcance Admin',
            'estado' => 'activo',
        ]);
        $rol->permisos()->attach(Permiso::pluck('id')->toArray(), ['estado' => 'activo']);

        $this->usuarioSucursal = User::create([
            'name' => 'Usuario Sucursal',
            'email' => 'sucursal@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $this->usuarioSucursal->roles()->attach($rol->id, ['estado' => 'activo']);

        $this->usuarioPropietario = User::create([
            'name' => 'Usuario Propietario',
            'email' => 'propio@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $this->usuarioPropietario->roles()->attach($rol->id, ['estado' => 'activo']);

        $this->sucursalSps = Sucursal::factory()->create(['codigo' => 'SPS']);
        $this->sucursalTgu = Sucursal::factory()->create(['codigo' => 'TGU']);

        $this->usuarioSucursal->sucursales()->attach($this->sucursalSps->id, ['estado' => 'activo']);
    }

    private function crearDatos(): void
    {
        $periodo = PeriodoAcademico::create([
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
            'nombre' => 'Ingles General',
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
            'nombre' => 'Ingles 1',
            'orden' => 1,
            'nota_minima_aprobar' => 80,
            'faltas_maximas_permitidas' => 7,
        ]);
        $modalidad = Modalidad::create([
            'codigo' => 'PRES',
            'nombre' => 'Presencial',
            'tipo' => 'atencion',
        ]);
        $horario = Horario::create([
            'codigo' => 'M1',
            'nombre' => 'Matutino',
            'hora_inicio' => '07:00',
            'hora_fin' => '09:00',
        ]);
        $docente = Docente::factory()->create(['codigo' => 'DOC001']);
        $aulaSps = Aula::create([
            'sucursal_id' => $this->sucursalSps->id,
            'codigo' => 'AUL-SPS',
            'nombre' => 'Aula SPS',
            'capacidad' => 25,
        ]);
        $aulaTgu = Aula::create([
            'sucursal_id' => $this->sucursalTgu->id,
            'codigo' => 'AUL-TGU',
            'nombre' => 'Aula TGU',
            'capacidad' => 25,
        ]);

        $ofertaSps = OfertaAcademica::create([
            'codigo' => 'OF-SPS',
            'sucursal_id' => $this->sucursalSps->id,
            'periodo_academico_id' => $periodo->id,
            'nivel_academico_id' => $nivel->id,
            'modalidad_id' => $modalidad->id,
            'horario_id' => $horario->id,
            'docente_id' => $docente->id,
            'aula_id' => $aulaSps->id,
            'cupo_maximo' => 25,
            'cupos_reservados' => 0,
            'cupos_matriculados' => 1,
            'estado' => 'abierto',
            'creado_por' => $this->usuarioSucursal->id,
        ]);
        $ofertaTgu = OfertaAcademica::create([
            'codigo' => 'OF-TGU',
            'sucursal_id' => $this->sucursalTgu->id,
            'periodo_academico_id' => $periodo->id,
            'nivel_academico_id' => $nivel->id,
            'modalidad_id' => $modalidad->id,
            'horario_id' => $horario->id,
            'docente_id' => $docente->id,
            'aula_id' => $aulaTgu->id,
            'cupo_maximo' => 25,
            'cupos_reservados' => 0,
            'cupos_matriculados' => 1,
            'estado' => 'abierto',
            'creado_por' => $this->usuarioPropietario->id,
        ]);

        $this->estudianteSps = Estudiante::factory()->create([
            'codigo' => 'EST-SPS',
            'sucursal_id' => $this->sucursalSps->id,
            'creado_por' => $this->usuarioSucursal->id,
        ]);
        $this->estudianteTgu = Estudiante::factory()->create([
            'codigo' => 'EST-TGU',
            'sucursal_id' => $this->sucursalTgu->id,
            'creado_por' => $this->usuarioSucursal->id,
        ]);
        $this->estudiantePropio = Estudiante::factory()->create([
            'codigo' => 'EST-PROPIO',
            'sucursal_id' => $this->sucursalTgu->id,
            'creado_por' => $this->usuarioPropietario->id,
        ]);

        $this->matriculaSps = Matricula::create([
            'codigo' => 'MAT-SPS',
            'estudiante_id' => $this->estudianteSps->id,
            'oferta_academica_id' => $ofertaSps->id,
            'sucursal_id' => $this->sucursalSps->id,
            'estado' => 'matriculado',
            'fecha_reserva' => now(),
            'fecha_confirmacion' => now(),
            'creado_por' => $this->usuarioSucursal->id,
        ]);
        $this->matriculaTgu = Matricula::create([
            'codigo' => 'MAT-TGU',
            'estudiante_id' => $this->estudianteTgu->id,
            'oferta_academica_id' => $ofertaTgu->id,
            'sucursal_id' => $this->sucursalTgu->id,
            'estado' => 'matriculado',
            'fecha_reserva' => now(),
            'fecha_confirmacion' => now(),
            'creado_por' => $this->usuarioSucursal->id,
        ]);
        $this->matriculaPropia = Matricula::create([
            'codigo' => 'MAT-PROPIA',
            'estudiante_id' => $this->estudiantePropio->id,
            'oferta_academica_id' => $ofertaTgu->id,
            'sucursal_id' => $this->sucursalTgu->id,
            'estado' => 'matriculado',
            'fecha_reserva' => now(),
            'fecha_confirmacion' => now(),
            'creado_por' => $this->usuarioPropietario->id,
        ]);

        $concepto = ConceptoPago::create([
            'codigo' => 'MAT',
            'nombre' => 'Matricula',
            'tipo_monto' => 'por_oferta',
            'requiere_autorizacion_monto' => false,
            'estado' => 'activo',
        ]);
        $metodo = MetodoPago::create([
            'codigo' => 'EFE',
            'nombre' => 'Efectivo',
            'estado' => 'activo',
        ]);

        $this->pagoSps = Pago::create([
            'codigo' => 'PAG-SPS',
            'estudiante_id' => $this->estudianteSps->id,
            'matricula_id' => $this->matriculaSps->id,
            'concepto_pago_id' => $concepto->id,
            'metodo_pago_id' => $metodo->id,
            'sucursal_id' => $this->sucursalSps->id,
            'monto' => 100,
            'estado' => 'aprobado',
            'fecha_aprobacion' => now(),
            'creado_por' => $this->usuarioSucursal->id,
        ]);
        $this->pagoTgu = Pago::create([
            'codigo' => 'PAG-TGU',
            'estudiante_id' => $this->estudianteTgu->id,
            'matricula_id' => $this->matriculaTgu->id,
            'concepto_pago_id' => $concepto->id,
            'metodo_pago_id' => $metodo->id,
            'sucursal_id' => $this->sucursalTgu->id,
            'monto' => 125,
            'estado' => 'aprobado',
            'fecha_aprobacion' => now(),
            'creado_por' => $this->usuarioSucursal->id,
        ]);
        $this->pagoPropio = Pago::create([
            'codigo' => 'PAG-PROPIO',
            'estudiante_id' => $this->estudiantePropio->id,
            'matricula_id' => $this->matriculaPropia->id,
            'concepto_pago_id' => $concepto->id,
            'metodo_pago_id' => $metodo->id,
            'sucursal_id' => $this->sucursalTgu->id,
            'monto' => 150,
            'estado' => 'aprobado',
            'fecha_aprobacion' => now(),
            'creado_por' => $this->usuarioPropietario->id,
        ]);

        $this->reciboSps = ReciboCaja::create([
            'codigo' => 'RC-SPS',
            'numero_recibo' => 1,
            'pago_id' => $this->pagoSps->id,
            'estudiante_id' => $this->estudianteSps->id,
            'sucursal_id' => $this->sucursalSps->id,
            'concepto_pago_id' => $concepto->id,
            'metodo_pago_id' => $metodo->id,
            'monto_total' => 100,
            'estado' => 'emitido',
            'anio' => (int) date('Y'),
            'fecha_proceso' => now(),
            'fecha_recibo' => now(),
            'creado_por' => $this->usuarioSucursal->id,
        ]);
        $this->reciboTgu = ReciboCaja::create([
            'codigo' => 'RC-TGU',
            'numero_recibo' => 2,
            'pago_id' => $this->pagoTgu->id,
            'estudiante_id' => $this->estudianteTgu->id,
            'sucursal_id' => $this->sucursalTgu->id,
            'concepto_pago_id' => $concepto->id,
            'metodo_pago_id' => $metodo->id,
            'monto_total' => 125,
            'estado' => 'emitido',
            'anio' => (int) date('Y'),
            'fecha_proceso' => now(),
            'fecha_recibo' => now(),
            'creado_por' => $this->usuarioSucursal->id,
        ]);
        $this->reciboPropio = ReciboCaja::create([
            'codigo' => 'RC-PROPIO',
            'numero_recibo' => 3,
            'pago_id' => $this->pagoPropio->id,
            'estudiante_id' => $this->estudiantePropio->id,
            'sucursal_id' => $this->sucursalTgu->id,
            'concepto_pago_id' => $concepto->id,
            'metodo_pago_id' => $metodo->id,
            'monto_total' => 150,
            'estado' => 'emitido',
            'anio' => (int) date('Y'),
            'fecha_proceso' => now(),
            'fecha_recibo' => now(),
            'creado_por' => $this->usuarioPropietario->id,
        ]);
    }

    private function headers(User $usuario): array
    {
        return ['Authorization' => 'Bearer '.$usuario->createToken('test')->plainTextToken];
    }

    public function test_usuario_con_sucursal_solo_ve_estudiantes_de_su_sucursal(): void
    {
        $response = $this->getJson('/api/v1/estudiantes', $this->headers($this->usuarioSucursal));

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.codigo', 'EST-SPS');
    }

    public function test_usuario_propietario_solo_ve_sus_estudiantes(): void
    {
        $response = $this->getJson('/api/v1/estudiantes', $this->headers($this->usuarioPropietario));

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.codigo', 'EST-PROPIO');
    }

    public function test_usuario_con_sucursal_solo_ve_matriculas_de_su_sucursal(): void
    {
        $response = $this->getJson('/api/v1/matriculas', $this->headers($this->usuarioSucursal));

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.codigo', 'MAT-SPS');
    }

    public function test_usuario_con_sucursal_solo_ve_pagos_de_su_sucursal(): void
    {
        $response = $this->getJson('/api/v1/pagos', $this->headers($this->usuarioSucursal));

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.codigo', 'PAG-SPS');
    }

    public function test_usuario_propietario_solo_ve_sus_pagos(): void
    {
        $response = $this->getJson('/api/v1/pagos', $this->headers($this->usuarioPropietario));

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.codigo', 'PAG-PROPIO');
    }

    public function test_usuario_con_sucursal_solo_ve_recibos_de_su_sucursal(): void
    {
        $response = $this->getJson('/api/v1/recibos-caja', $this->headers($this->usuarioSucursal));

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.codigo', 'RC-SPS');
    }

    public function test_usuario_propietario_no_puede_ver_detalle_estudiante_ajeno(): void
    {
        $response = $this->getJson('/api/v1/estudiantes/'.$this->estudianteSps->id, $this->headers($this->usuarioPropietario));

        $response->assertNotFound();
    }

    public function test_usuario_con_sucursal_no_puede_ver_detalle_pago_de_otra_sucursal(): void
    {
        $response = $this->getJson('/api/v1/pagos/'.$this->pagoTgu->id, $this->headers($this->usuarioSucursal));

        $response->assertNotFound();
    }

    public function test_usuario_propietario_solo_ve_pagos_pendientes_creados_por_el(): void
    {
        $this->pagoSps->update([
            'estado' => 'pendiente',
            'aprobado_por' => null,
            'fecha_aprobacion' => null,
        ]);
        $this->pagoPropio->update([
            'estado' => 'pendiente',
            'aprobado_por' => null,
            'fecha_aprobacion' => null,
        ]);

        $response = $this->getJson('/api/v1/reportes/financieros/pagos-pendientes', $this->headers($this->usuarioPropietario));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.pago_codigo', 'PAG-PROPIO');
    }
}
