<?php

namespace Tests\Feature;

use App\Models\Aula;
use App\Models\DepartamentoAcademico;
use App\Models\Docente;
use App\Models\Horario;
use App\Models\Modalidad;
use App\Models\Modulo;
use App\Models\NivelAcademico;
use App\Models\OfertaAcademica;
use App\Models\OpcionModulo;
use App\Models\PeriodoAcademico;
use App\Models\Permiso;
use App\Models\PlanEstudio;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\VersionPlanEstudio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AlcanceApiTest extends TestCase
{
    use RefreshDatabase;

    private Rol $rol;

    private Sucursal $sucursalSPS;

    private Sucursal $sucursalTGU;

    private OfertaAcademica $ofertaSPS;

    private OfertaAcademica $ofertaTGU;

    private User $admin;

    private PeriodoAcademico $periodo;

    private NivelAcademico $nivel;

    private Modalidad $modalidad;

    private Horario $horario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearPermisosBase();

        $this->rol = Rol::create(['codigo' => 'TEST_ALCANCE', 'nombre' => 'Test Alcance', 'estado' => 'activo']);
        $this->rol->permisos()->attach(
            Permiso::where('codigo', 'like', 'asistencias.%')->pluck('id')->toArray(),
            ['estado' => 'activo'],
        );

        $this->admin = User::create([
            'name' => 'Admin Alcance',
            'email' => 'alcance@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $this->admin->roles()->attach($this->rol->id, ['estado' => 'activo']);

        $this->sucursalSPS = Sucursal::factory()->create(['codigo' => 'SPS']);
        $this->sucursalTGU = Sucursal::factory()->create(['codigo' => 'TGU']);

        $this->crearEstructuraAcademica();

        $this->ofertaSPS = $this->crearOferta($this->sucursalSPS, 'OF-SPS');
        $this->ofertaTGU = $this->crearOferta($this->sucursalTGU, 'OF-TGU');
    }

    private function crearPermisosBase(): void
    {
        $moduloAsistencias = Modulo::create(['codigo' => 'asistencias', 'nombre' => 'Asistencias', 'estado' => 'activo', 'orden' => 9]);
        $opcion = OpcionModulo::create(['modulo_id' => $moduloAsistencias->id, 'codigo' => 'asistencias.lista', 'nombre' => 'Pasar lista', 'estado' => 'activo']);
        foreach (['consultar', 'crear'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcion->id,
                'codigo' => 'asistencias.'.$accion,
                'nombre' => ucfirst($accion),
                'accion' => $accion,
                'estado' => 'activo',
            ]);
        }

        $moduloOfertas = Modulo::create(['codigo' => 'ofertas', 'nombre' => 'Ofertas', 'estado' => 'activo', 'orden' => 5]);
        $opcionOfertas = OpcionModulo::create(['modulo_id' => $moduloOfertas->id, 'codigo' => 'ofertas.general', 'nombre' => 'General', 'estado' => 'activo']);
        foreach (['consultar', 'crear', 'modificar', 'eliminar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcionOfertas->id,
                'codigo' => 'ofertas.'.$accion,
                'nombre' => ucfirst($accion),
                'accion' => $accion,
                'estado' => 'activo',
            ]);
        }
    }

    private function crearEstructuraAcademica(): void
    {
        $this->periodo = PeriodoAcademico::create([
            'codigo' => '2026-I',
            'nombre' => 'Semestre 1',
            'fecha_inicio' => '2026-01-15',
            'fecha_fin' => '2026-06-30',
            'estado' => 'activo',
        ]);

        $depto = DepartamentoAcademico::factory()->create(['codigo' => 'ING']);
        $plan = PlanEstudio::create(['departamento_academico_id' => $depto->id, 'codigo' => 'ING-GEN', 'nombre' => 'Inglés General']);
        $version = VersionPlanEstudio::create(['plan_estudio_id' => $plan->id, 'numero_version' => 1, 'vigente_desde' => '2026-01-01']);
        $regimen = Modalidad::create(['codigo' => 'INT', 'nombre' => 'Intensivo', 'tipo' => 'regimen_academico']);
        $this->nivel = NivelAcademico::create([
            'version_plan_estudio_id' => $version->id,
            'regimen_academico_id' => $regimen->id,
            'codigo' => 'ING-1',
            'nombre' => 'Inglés 1',
            'orden' => 1,
            'nota_minima_aprobar' => 80,
            'faltas_maximas_permitidas' => 7,
        ]);
        $this->modalidad = Modalidad::create(['codigo' => 'PRES', 'nombre' => 'Presencial', 'tipo' => 'atencion']);
        $this->horario = Horario::create(['codigo' => 'M1', 'nombre' => 'Matutino', 'hora_inicio' => '07:00', 'hora_fin' => '09:00']);
    }

    private function crearOferta(Sucursal $sucursal, string $codigo): OfertaAcademica
    {
        $docente = Docente::factory()->create(['codigo' => 'DOC-'.substr($codigo, -3)]);
        $aula = Aula::create(['sucursal_id' => $sucursal->id, 'codigo' => 'AUL-'.substr($codigo, -3), 'nombre' => 'Aula '.$codigo, 'capacidad' => 25]);

        return OfertaAcademica::create([
            'sucursal_id' => $sucursal->id,
            'periodo_academico_id' => $this->periodo->id,
            'nivel_academico_id' => $this->nivel->id,
            'modalidad_id' => $this->modalidad->id,
            'horario_id' => $this->horario->id,
            'docente_id' => $docente->id,
            'aula_id' => $aula->id,
            'codigo' => $codigo,
            'cupo_maximo' => 25,
            'cupos_reservados' => 0,
            'cupos_matriculados' => 0,
            'estado' => 'abierto',
            'creado_por' => $this->admin->id,
        ]);
    }

    private function headers(): array
    {
        return ['Authorization' => "Bearer {$this->admin->createToken('test')->plainTextToken}"];
    }

    public function test_usuario_global_ve_ofertas_de_todas_las_sucursales(): void
    {
        DB::table('alcances_usuario')->insert([
            'usuario_id' => $this->admin->id,
            'tipo' => 'global',
            'estado' => 'activo',
        ]);

        $response = $this->getJson('/api/v1/asistencias/ofertas-disponibles', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(2, 'data');
    }

    public function test_usuario_con_sucursal_asignada_solo_ve_su_sucursal(): void
    {
        $usuario = $this->crearUsuarioConRol('Solo SPS', 'solo-sps@test.com');
        $usuario->sucursales()->attach($this->sucursalSPS->id, ['estado' => 'activo']);

        $token = $usuario->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/v1/asistencias/ofertas-disponibles', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->ofertaSPS->id);
    }

    public function test_usuario_sin_alcance_no_ve_ofertas(): void
    {
        $usuario = $this->crearUsuarioConRol('Sin Alcance', 'sin-alcance@test.com');

        $token = $usuario->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/v1/asistencias/ofertas-disponibles', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_docente_solo_ve_sus_ofertas(): void
    {
        $docente = $this->ofertaSPS->docente;

        $usuario = $this->crearUsuarioConRol('Docente', 'docente@test.com');
        $usuario->update(['docente_id' => $docente->id]);

        $token = $usuario->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/v1/asistencias/ofertas-disponibles', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->ofertaSPS->id);
    }

    public function test_monitor_cupos_requiere_permiso(): void
    {
        $usuario = User::create([
            'name' => 'Sin Permiso Ofertas',
            'email' => 'sin-ofertas@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $token = $usuario->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/v1/ofertas/monitor', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertForbidden()
            ->assertJsonPath('resultado', 'R');
    }

    public function test_asistencias_requiere_permiso(): void
    {
        $usuario = User::create([
            'name' => 'Sin Permiso Asistencias',
            'email' => 'sin-asistencias@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $token = $usuario->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/v1/asistencias/ofertas-disponibles', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertForbidden()
            ->assertJsonPath('resultado', 'R');
    }

    private function crearUsuarioConRol(string $name, string $email): User
    {
        $usuario = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $usuario->roles()->attach($this->rol->id, ['estado' => 'activo']);

        return $usuario;
    }
}
