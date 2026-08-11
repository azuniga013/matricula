<?php

namespace Tests\Feature;

use App\Models\ContactoResponsableEstudiante;
use App\Models\Estudiante;
use App\Models\Modulo;
use App\Models\OpcionModulo;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\UsuarioSucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContactoResponsableEstudianteTest extends TestCase
{
    use RefreshDatabase;

    private User $adminGlobal;

    private string $tokenGlobal;

    private User $adminSucursalA;

    private string $tokenSucursalA;

    private Sucursal $sucursalA;

    private Sucursal $sucursalB;

    private Estudiante $estudianteA;

    private Estudiante $estudianteB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearPermisosBase();

        $rol = Rol::create(['codigo' => 'TEST_EST', 'nombre' => 'Test Est', 'estado' => 'activo']);
        $rol->permisos()->attach(Permiso::pluck('id')->all(), ['estado' => 'activo']);

        $this->adminGlobal = User::create([
            'name' => 'Admin Global',
            'email' => 'global@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $this->adminGlobal->roles()->attach($rol->id, ['estado' => 'activo']);
        $this->tokenGlobal = $this->adminGlobal->createToken('test')->plainTextToken;
        DB::table('alcances_usuario')->insert([
            'usuario_id' => $this->adminGlobal->id,
            'tipo' => 'global',
            'estado' => 'activo',
        ]);

        $this->adminSucursalA = User::create([
            'name' => 'Admin Sucursal A',
            'email' => 'sps@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $this->adminSucursalA->roles()->attach($rol->id, ['estado' => 'activo']);
        $this->tokenSucursalA = $this->adminSucursalA->createToken('test')->plainTextToken;

        $this->sucursalA = Sucursal::factory()->create(['codigo' => 'SPS']);
        $this->sucursalB = Sucursal::factory()->create(['codigo' => 'LCE']);

        UsuarioSucursal::create([
            'usuario_id' => $this->adminSucursalA->id,
            'sucursal_id' => $this->sucursalA->id,
            'estado' => 'activo',
            'creado_por' => $this->adminSucursalA->id,
        ]);

        $this->estudianteA = Estudiante::create([
            'codigo' => 'EST-A',
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'sucursal_id' => $this->sucursalA->id,
            'estado' => 'activo',
            'creado_por' => $this->adminGlobal->id,
        ]);
        $this->estudianteB = Estudiante::create([
            'codigo' => 'EST-B',
            'nombre' => 'Beto',
            'apellido' => 'Ajeno',
            'sucursal_id' => $this->sucursalB->id,
            'estado' => 'activo',
            'creado_por' => $this->adminGlobal->id,
        ]);
    }

    private function crearPermisosBase(): void
    {
        $modulo = Modulo::create(['codigo' => 'estudiantes', 'nombre' => 'Estudiantes', 'estado' => 'activo', 'orden' => 4]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'estudiantes.general', 'nombre' => 'General', 'estado' => 'activo']);

        foreach (['consultar', 'modificar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcion->id,
                'codigo' => 'estudiantes.'.$accion,
                'nombre' => ucfirst($accion),
                'accion' => $accion,
                'estado' => 'activo',
            ]);
        }
    }

    private function headersGlobal(): array
    {
        return ['Authorization' => 'Bearer '.$this->tokenGlobal];
    }

    private function headersSucursalA(): array
    {
        return ['Authorization' => 'Bearer '.$this->tokenSucursalA];
    }

    public function test_crear_contacto_responsable_consentido(): void
    {
        $response = $this->postJson('/api/v1/estudiantes/'.$this->estudianteA->id.'/contactos-responsable', [
            'nombre' => 'Madre Responsable',
            'parentesco' => 'madre',
            'correo' => 'madre@test.com',
            'telefono_whatsapp' => '50499990000',
            'recibe_asistencia_email' => true,
            'recibe_asistencia_whatsapp' => true,
            'consentimiento_asistencia_en' => now()->toDateTimeString(),
            'prioridad' => 1,
        ], $this->headersGlobal());

        $response->assertCreated()
            ->assertJsonPath('data.telefono_whatsapp', '+50499990000');

        $this->assertDatabaseHas('contactos_responsable_estudiante', [
            'estudiante_id' => $this->estudianteA->id,
            'correo' => 'madre@test.com',
            'telefono_whatsapp' => '+50499990000',
            'recibe_asistencia_email' => true,
        ]);
    }

    public function test_no_activa_notificaciones_sin_consentimiento(): void
    {
        $this->postJson('/api/v1/estudiantes/'.$this->estudianteA->id.'/contactos-responsable', [
            'nombre' => 'Madre Responsable',
            'correo' => 'madre@test.com',
            'recibe_asistencia_email' => true,
        ], $this->headersGlobal())
            ->assertStatus(422)
            ->assertJsonPath('codigo_error', '422_CONSENTIMIENTO_REQUERIDO');
    }

    public function test_listar_contactos_respeta_alcance_por_sucursal(): void
    {
        ContactoResponsableEstudiante::create([
            'estudiante_id' => $this->estudianteA->id,
            'nombre' => 'Responsable A',
            'estado' => 'activo',
        ]);
        ContactoResponsableEstudiante::create([
            'estudiante_id' => $this->estudianteB->id,
            'nombre' => 'Responsable B',
            'estado' => 'activo',
        ]);

        $this->getJson('/api/v1/estudiantes/'.$this->estudianteA->id.'/contactos-responsable', $this->headersSucursalA())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Responsable A');

        $this->getJson('/api/v1/estudiantes/'.$this->estudianteB->id.'/contactos-responsable', $this->headersSucursalA())
            ->assertStatus(404);
    }

    public function test_actualizar_y_desactivar_contacto_preserva_historial(): void
    {
        $contacto = ContactoResponsableEstudiante::create([
            'estudiante_id' => $this->estudianteA->id,
            'nombre' => 'Responsable A',
            'correo' => 'old@test.com',
            'estado' => 'activo',
        ]);

        $this->postJson('/api/v1/estudiantes/'.$this->estudianteA->id.'/contactos-responsable/'.$contacto->id, [
            'nombre' => 'Responsable Actualizado',
            'correo' => 'new@test.com',
        ], $this->headersGlobal())
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Responsable Actualizado');

        $this->postJson('/api/v1/estudiantes/'.$this->estudianteA->id.'/contactos-responsable/'.$contacto->id.'/desactivar', [], $this->headersGlobal())
            ->assertOk();

        $this->assertDatabaseHas('contactos_responsable_estudiante', [
            'id' => $contacto->id,
            'estado' => 'inactivo',
            'correo' => 'new@test.com',
        ]);
    }
}
