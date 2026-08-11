<?php

namespace Tests\Feature;

use App\Models\AsistenciaEstudiante;
use App\Models\Aula;
use App\Models\DepartamentoAcademico;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Modalidad;
use App\Models\Modulo;
use App\Models\NivelAcademico;
use App\Models\OfertaAcademica;
use App\Models\OpcionModulo;
use App\Models\PeriodoAcademico;
use App\Models\Permiso;
use App\Models\PlanEstudio;
use App\Models\Rol;
use App\Models\SincronizacionDocenteMovil;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\VersionPlanEstudio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocenteMovilTest extends TestCase
{
    use RefreshDatabase;

    private User $docenteUser;

    private string $token;

    private OfertaAcademica $ofertaPropia;

    private OfertaAcademica $ofertaAjena;

    private Matricula $matriculaPropia;

    private Estudiante $estudiantePropio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearPermisosBase();
        $this->crearEscenario();
    }

    private function crearPermisosBase(): void
    {
        foreach (['asistencias', 'calificaciones'] as $codigoModulo) {
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

            foreach (['consultar', 'crear', 'modificar'] as $accion) {
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

    private function crearEscenario(): void
    {
        $rol = Rol::create(['codigo' => 'DOC_MOBILE', 'nombre' => 'Docente Movil', 'estado' => 'activo']);
        $rol->permisos()->attach(Permiso::pluck('id')->all(), ['estado' => 'activo']);

        $sucursal = Sucursal::factory()->create(['codigo' => 'SPS']);
        $otraSucursal = Sucursal::factory()->create(['codigo' => 'TGU']);
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

        $docente = Docente::factory()->create(['codigo' => 'DOC001']);
        $otroDocente = Docente::factory()->create(['codigo' => 'DOC999']);

        $this->docenteUser = User::create([
            'name' => 'Docente Movil',
            'email' => 'docente-movil@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
            'docente_id' => $docente->id,
        ]);
        $this->docenteUser->roles()->attach($rol->id, ['estado' => 'activo']);
        $this->token = $this->docenteUser->createToken('test')->plainTextToken;

        $aula = Aula::create(['sucursal_id' => $sucursal->id, 'codigo' => 'A1', 'nombre' => 'Aula 1', 'capacidad' => 25]);
        $aulaAjena = Aula::create(['sucursal_id' => $otraSucursal->id, 'codigo' => 'A2', 'nombre' => 'Aula 2', 'capacidad' => 25]);

        $this->ofertaPropia = OfertaAcademica::create([
            'codigo' => 'OF-DOC-1',
            'sucursal_id' => $sucursal->id,
            'periodo_academico_id' => $periodo->id,
            'nivel_academico_id' => $nivel->id,
            'modalidad_id' => $modalidad->id,
            'horario_id' => $horario->id,
            'docente_id' => $docente->id,
            'aula_id' => $aula->id,
            'cupo_maximo' => 25,
            'cupos_reservados' => 0,
            'cupos_matriculados' => 1,
            'estado' => 'abierto',
        ]);
        $this->ofertaAjena = OfertaAcademica::create([
            'codigo' => 'OF-DOC-2',
            'sucursal_id' => $otraSucursal->id,
            'periodo_academico_id' => $periodo->id,
            'nivel_academico_id' => $nivel->id,
            'modalidad_id' => $modalidad->id,
            'horario_id' => $horario->id,
            'docente_id' => $otroDocente->id,
            'aula_id' => $aulaAjena->id,
            'cupo_maximo' => 25,
            'cupos_reservados' => 0,
            'cupos_matriculados' => 1,
            'estado' => 'abierto',
        ]);

        $this->estudiantePropio = Estudiante::factory()->create([
            'codigo' => 'EST-DOC-1',
            'sucursal_id' => $sucursal->id,
        ]);
        $estudianteAjeno = Estudiante::factory()->create([
            'codigo' => 'EST-DOC-2',
            'sucursal_id' => $otraSucursal->id,
        ]);

        $this->matriculaPropia = Matricula::create([
            'codigo' => 'MAT-DOC-1',
            'estudiante_id' => $this->estudiantePropio->id,
            'oferta_academica_id' => $this->ofertaPropia->id,
            'sucursal_id' => $sucursal->id,
            'estado' => 'matriculado',
            'fecha_reserva' => now(),
            'fecha_confirmacion' => now(),
        ]);
        Matricula::create([
            'codigo' => 'MAT-DOC-2',
            'estudiante_id' => $estudianteAjeno->id,
            'oferta_academica_id' => $this->ofertaAjena->id,
            'sucursal_id' => $otraSucursal->id,
            'estado' => 'matriculado',
            'fecha_reserva' => now(),
            'fecha_confirmacion' => now(),
        ]);
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    public function test_sincronizar_descarga_solo_ofertas_del_docente(): void
    {
        $response = $this->getJson('/api/v1/docente-movil/sincronizar', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(1, 'data.ofertas')
            ->assertJsonPath('data.ofertas.0.codigo', 'OF-DOC-1')
            ->assertJsonPath('data.ofertas.0.alumnos.0.codigo', 'EST-DOC-1');
    }

    public function test_oferta_detalle_rechaza_oferta_ajena(): void
    {
        $response = $this->getJson('/api/v1/docente-movil/ofertas/'.$this->ofertaAjena->id, $this->headers());

        $response->assertStatus(404);
    }

    public function test_sincronizacion_aplica_asistencia_y_calificacion(): void
    {
        $response = $this->postJson('/api/v1/docente-movil/sincronizar', [
            'operaciones' => [
                [
                    'uuid' => '11111111-1111-4111-8111-111111111111',
                    'tipo' => 'asistencia',
                    'oferta_academica_id' => $this->ofertaPropia->id,
                    'fecha' => '2026-08-10',
                    'datos' => [
                        'matricula_id' => $this->matriculaPropia->id,
                        'estado' => 'presente',
                        'cuenta_como_falta' => false,
                    ],
                ],
                [
                    'uuid' => '22222222-2222-4222-8222-222222222222',
                    'tipo' => 'calificacion',
                    'oferta_academica_id' => $this->ofertaPropia->id,
                    'datos' => [
                        'estudiante_id' => $this->estudiantePropio->id,
                        'nota_final' => 91,
                        'faltas' => 1,
                        'observaciones' => 'Sincronizada desde APK',
                    ],
                ],
            ],
        ], $this->headers());

        $response->assertOk()
            ->assertJsonPath('data.operaciones.0.estado', 'aplicada')
            ->assertJsonPath('data.operaciones.1.estado', 'aplicada');

        $this->assertDatabaseHas('asistencias_estudiante', [
            'matricula_id' => $this->matriculaPropia->id,
            'estado' => 'presente',
        ]);
        $this->assertDatabaseHas('calificaciones', [
            'estudiante_id' => $this->estudiantePropio->id,
            'oferta_academica_id' => $this->ofertaPropia->id,
            'estado' => 'registrado',
        ]);
        $this->assertDatabaseHas('historial_academico', [
            'estudiante_id' => $this->estudiantePropio->id,
            'oferta_academica_id' => $this->ofertaPropia->id,
        ]);
    }

    public function test_sincronizacion_reintenta_uuid_sin_duplicar_operacion(): void
    {
        $payload = [
            'operaciones' => [[
                'uuid' => '33333333-3333-4333-8333-333333333333',
                'tipo' => 'asistencia',
                'oferta_academica_id' => $this->ofertaPropia->id,
                'fecha' => '2026-08-11',
                'datos' => [
                    'matricula_id' => $this->matriculaPropia->id,
                    'estado' => 'falta',
                    'cuenta_como_falta' => true,
                ],
            ]],
        ];

        $this->postJson('/api/v1/docente-movil/sincronizar', $payload, $this->headers())
            ->assertOk()
            ->assertJsonPath('data.operaciones.0.estado', 'aplicada');

        $this->postJson('/api/v1/docente-movil/sincronizar', $payload, $this->headers())
            ->assertOk()
            ->assertJsonPath('data.operaciones.0.estado', 'aplicada');

        $this->assertSame(1, AsistenciaEstudiante::where('matricula_id', $this->matriculaPropia->id)->whereDate('fecha', '2026-08-11')->count());
        $this->assertSame(1, SincronizacionDocenteMovil::where('uuid', '33333333-3333-4333-8333-333333333333')->count());
    }
}
