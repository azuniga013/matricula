<?php

namespace Tests\Feature;

use App\Jobs\ProcesarNotificacionAsistencia;
use App\Mail\NotificacionAsistenciaResponsable;
use App\Models\Aula;
use App\Models\ContactoResponsableEstudiante;
use App\Models\DepartamentoAcademico;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Modalidad;
use App\Models\Modulo;
use App\Models\NivelAcademico;
use App\Models\NotificacionAsistencia;
use App\Models\OfertaAcademica;
use App\Models\ParametroGlobal;
use App\Models\OpcionModulo;
use App\Models\PeriodoAcademico;
use App\Models\Permiso;
use App\Models\PlanEstudio;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\VersionPlanEstudio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificacionAsistenciaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $token;

    private OfertaAcademica $oferta;

    private Matricula $matricula;

    private Estudiante $estudiante;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearPermisosBase();

        $rol = Rol::create(['codigo' => 'TEST_ASIS', 'nombre' => 'Test Asis', 'estado' => 'activo']);
        $rol->permisos()->attach(Permiso::pluck('id')->all(), ['estado' => 'activo']);

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $this->admin->roles()->attach($rol->id, ['estado' => 'activo']);
        $this->token = $this->admin->createToken('test')->plainTextToken;

        $sucursal = Sucursal::factory()->create(['codigo' => 'SPS']);
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
        $aula = Aula::create(['sucursal_id' => $sucursal->id, 'codigo' => 'AUL-01', 'nombre' => 'Aula 1', 'capacidad' => 25]);
        $periodo = PeriodoAcademico::create([
            'codigo' => '2026-I',
            'nombre' => 'Semestre 1',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addMonths(4)->toDateString(),
            'estado' => 'activo',
        ]);

        $this->oferta = OfertaAcademica::create([
            'sucursal_id' => $sucursal->id,
            'periodo_academico_id' => $periodo->id,
            'nivel_academico_id' => $nivel->id,
            'modalidad_id' => $modalidad->id,
            'horario_id' => $horario->id,
            'docente_id' => $docente->id,
            'aula_id' => $aula->id,
            'codigo' => 'OFE-ASIS-001',
            'cupo_maximo' => 25,
            'estado' => 'abierto',
        ]);

        $this->estudiante = Estudiante::create([
            'codigo' => 'EST-ASIS-001',
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'sucursal_id' => $sucursal->id,
            'estado' => 'activo',
        ]);

        $this->matricula = Matricula::create([
            'codigo' => 'MAT-ASIS-001',
            'estudiante_id' => $this->estudiante->id,
            'oferta_academica_id' => $this->oferta->id,
            'sucursal_id' => $sucursal->id,
            'estado' => 'matriculado',
            'fecha_reserva' => now(),
            'fecha_confirmacion' => now(),
        ]);
    }

    private function crearPermisosBase(): void
    {
        $modulo = Modulo::create(['codigo' => 'asistencias', 'nombre' => 'Asistencias', 'estado' => 'activo', 'orden' => 9]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'asistencias.lista', 'nombre' => 'Pasar lista', 'estado' => 'activo']);

        foreach (['consultar', 'crear'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcion->id,
                'codigo' => 'asistencias.'.$accion,
                'nombre' => ucfirst($accion),
                'accion' => $accion,
                'estado' => 'activo',
            ]);
        }
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    public function test_falta_crea_notificaciones_pendientes_por_contacto_y_canal_consentido(): void
    {
        ContactoResponsableEstudiante::create([
            'estudiante_id' => $this->estudiante->id,
            'nombre' => 'Madre Responsable',
            'parentesco' => 'madre',
            'correo' => 'madre@test.com',
            'telefono_whatsapp' => '+50499990000',
            'recibe_asistencia_email' => true,
            'recibe_asistencia_whatsapp' => true,
            'consentimiento_asistencia_en' => now(),
            'prioridad' => 1,
            'estado' => 'activo',
        ]);

        $this->postJson('/api/v1/asistencias/registrar', [
            'oferta_academica_id' => $this->oferta->id,
            'fecha' => '2026-03-10',
            'asistencias' => [[
                'matricula_id' => $this->matricula->id,
                'estado' => 'falta',
            ]],
        ], $this->headers())->assertOk();

        $this->assertDatabaseCount('notificaciones_asistencia', 2);
        $this->assertDatabaseHas('notificaciones_asistencia', [
            'canal' => 'email',
            'tipo' => 'falta',
            'estado' => 'pendiente',
            'estudiante_id' => $this->estudiante->id,
        ]);
        $this->assertDatabaseHas('notificaciones_asistencia', [
            'canal' => 'whatsapp',
            'tipo' => 'falta',
            'estado' => 'pendiente',
            'estudiante_id' => $this->estudiante->id,
        ]);
    }

    public function test_presente_no_crea_notificaciones(): void
    {
        ContactoResponsableEstudiante::create([
            'estudiante_id' => $this->estudiante->id,
            'nombre' => 'Padre Responsable',
            'correo' => 'padre@test.com',
            'recibe_asistencia_email' => true,
            'consentimiento_asistencia_en' => now(),
            'prioridad' => 1,
            'estado' => 'activo',
        ]);

        $this->postJson('/api/v1/asistencias/registrar', [
            'oferta_academica_id' => $this->oferta->id,
            'fecha' => '2026-03-10',
            'asistencias' => [[
                'matricula_id' => $this->matricula->id,
                'estado' => 'presente',
            ]],
        ], $this->headers())->assertOk();

        $this->assertDatabaseCount('notificaciones_asistencia', 0);
    }

    public function test_reintento_de_la_misma_falta_no_duplica_notificaciones(): void
    {
        ContactoResponsableEstudiante::create([
            'estudiante_id' => $this->estudiante->id,
            'nombre' => 'Madre Responsable',
            'correo' => 'madre@test.com',
            'recibe_asistencia_email' => true,
            'consentimiento_asistencia_en' => now(),
            'prioridad' => 1,
            'estado' => 'activo',
        ]);

        $payload = [
            'oferta_academica_id' => $this->oferta->id,
            'fecha' => '2026-03-10',
            'asistencias' => [[
                'matricula_id' => $this->matricula->id,
                'estado' => 'falta',
            ]],
        ];

        $this->postJson('/api/v1/asistencias/registrar', $payload, $this->headers())->assertOk();
        $this->postJson('/api/v1/asistencias/registrar', $payload, $this->headers())->assertOk();

        $this->assertDatabaseCount('notificaciones_asistencia', 1);
        $this->assertSame(1, NotificacionAsistencia::count());
    }

    public function test_procesamiento_marca_omitida_cuando_envios_reales_siguen_deshabilitados(): void
    {
        ContactoResponsableEstudiante::create([
            'estudiante_id' => $this->estudiante->id,
            'nombre' => 'Madre Responsable',
            'correo' => 'madre@test.com',
            'recibe_asistencia_email' => true,
            'consentimiento_asistencia_en' => now(),
            'prioridad' => 1,
            'estado' => 'activo',
        ]);

        $this->postJson('/api/v1/asistencias/registrar', [
            'oferta_academica_id' => $this->oferta->id,
            'fecha' => '2026-03-11',
            'asistencias' => [[
                'matricula_id' => $this->matricula->id,
                'estado' => 'falta',
            ]],
        ], $this->headers())->assertOk();

        $notificacion = NotificacionAsistencia::firstOrFail();

        dispatch_sync(new ProcesarNotificacionAsistencia($notificacion->id));

        $this->assertDatabaseHas('notificaciones_asistencia', [
            'id' => $notificacion->id,
            'estado' => 'omitida',
            'intentos' => 1,
        ]);
    }

    public function test_si_se_habilitan_envios_sin_proveedor_configurado_la_notificacion_falla(): void
    {
        config(['notificaciones_asistencia.activar_envios' => true]);
        ParametroGlobal::create([
            'grupo' => 'notificaciones_asistencia',
            'codigo' => 'WHATSAPP_HABILITADO',
            'nombre' => 'WhatsApp habilitado',
            'valor' => 'true',
            'tipo' => 'booleano',
            'estado' => true,
        ]);

        ContactoResponsableEstudiante::create([
            'estudiante_id' => $this->estudiante->id,
            'nombre' => 'Madre Responsable',
            'telefono_whatsapp' => '+50499990000',
            'recibe_asistencia_whatsapp' => true,
            'consentimiento_asistencia_en' => now(),
            'prioridad' => 1,
            'estado' => 'activo',
        ]);

        $this->postJson('/api/v1/asistencias/registrar', [
            'oferta_academica_id' => $this->oferta->id,
            'fecha' => '2026-03-12',
            'asistencias' => [[
                'matricula_id' => $this->matricula->id,
                'estado' => 'tardanza',
            ]],
        ], $this->headers())->assertOk();

        $notificacion = NotificacionAsistencia::firstOrFail();

        dispatch_sync(new ProcesarNotificacionAsistencia($notificacion->id));

        $this->assertDatabaseHas('notificaciones_asistencia', [
            'id' => $notificacion->id,
            'estado' => 'fallida',
            'intentos' => 1,
            'tipo' => 'tardanza',
        ]);
    }

    public function test_whatsapp_configurable_en_modo_stub_marca_omitida_con_proveedor_stub(): void
    {
        config(['notificaciones_asistencia.activar_envios' => true]);
        ParametroGlobal::create([
            'grupo' => 'notificaciones_asistencia',
            'codigo' => 'WHATSAPP_HABILITADO',
            'nombre' => 'WhatsApp habilitado',
            'valor' => 'true',
            'tipo' => 'booleano',
            'estado' => true,
        ]);
        ParametroGlobal::create([
            'grupo' => 'notificaciones_asistencia',
            'codigo' => 'WHATSAPP_DRIVER',
            'nombre' => 'Driver WhatsApp',
            'valor' => 'stub',
            'tipo' => 'texto',
            'estado' => true,
        ]);

        ContactoResponsableEstudiante::create([
            'estudiante_id' => $this->estudiante->id,
            'nombre' => 'Madre Responsable',
            'telefono_whatsapp' => '+50499990000',
            'recibe_asistencia_whatsapp' => true,
            'consentimiento_asistencia_en' => now(),
            'prioridad' => 1,
            'estado' => 'activo',
        ]);

        $this->postJson('/api/v1/asistencias/registrar', [
            'oferta_academica_id' => $this->oferta->id,
            'fecha' => '2026-03-14',
            'asistencias' => [[
                'matricula_id' => $this->matricula->id,
                'estado' => 'falta',
            ]],
        ], $this->headers())->assertOk();

        $notificacion = NotificacionAsistencia::firstOrFail();

        dispatch_sync(new ProcesarNotificacionAsistencia($notificacion->id));

        $this->assertDatabaseHas('notificaciones_asistencia', [
            'id' => $notificacion->id,
            'estado' => 'omitida',
            'proveedor' => 'whatsapp_stub',
        ]);
    }

    public function test_si_se_habilitan_envios_por_email_la_notificacion_se_envia_y_se_registra_en_bitacora(): void
    {
        Mail::fake();
        config(['notificaciones_asistencia.activar_envios' => true]);

        ContactoResponsableEstudiante::create([
            'estudiante_id' => $this->estudiante->id,
            'nombre' => 'Madre Responsable',
            'correo' => 'madre@test.com',
            'recibe_asistencia_email' => true,
            'consentimiento_asistencia_en' => now(),
            'prioridad' => 1,
            'estado' => 'activo',
        ]);

        $this->postJson('/api/v1/asistencias/registrar', [
            'oferta_academica_id' => $this->oferta->id,
            'fecha' => '2026-03-13',
            'asistencias' => [[
                'matricula_id' => $this->matricula->id,
                'estado' => 'falta',
            ]],
        ], $this->headers())->assertOk();

        $notificacion = NotificacionAsistencia::firstOrFail();

        dispatch_sync(new ProcesarNotificacionAsistencia($notificacion->id));

        Mail::assertSent(NotificacionAsistenciaResponsable::class, function ($mail) {
            return $mail->hasTo('madre@test.com');
        });

        $this->assertDatabaseHas('notificaciones_asistencia', [
            'id' => $notificacion->id,
            'estado' => 'enviada',
            'proveedor' => 'mail',
        ]);
        $this->assertDatabaseHas('bitacora_correos', [
            'destinatario' => 'madre@test.com',
            'tipo' => 'asistencia_familia',
            'estado' => 'enviado',
        ]);
    }
}
