<?php

namespace Tests\Feature;

use App\Models\{CuentaBancaria, DepartamentoAcademico, Docente, Estudiante, Horario, Matricula, Modalidad, Modulo, NivelAcademico, ObligacionPagoEstudiante, OpcionModulo, OfertaAcademica, Permiso, PeriodoAcademico, PlanCobro, PlanEstudio, ReciboCaja, Rol, SesionCaja, Sucursal, User, VersionPlanEstudio};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CajaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $token;
    private Sucursal $sucursal;
    private int $conceptoMatId;
    private int $metodoEfeId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearPermisosBase();

        $rol = Rol::create(['codigo' => 'TEST_ADMIN', 'nombre' => 'Test Admin', 'estado' => 'activo']);
        $permisos = Permiso::where('codigo', 'like', 'caja.%')->get();
        $rol->permisos()->attach($permisos->pluck('id')->toArray(), ['estado' => 'activo']);
        $permisosPagos = Permiso::where('codigo', 'like', 'pagos.%')->get();
        $rol->permisos()->attach($permisosPagos->pluck('id')->toArray(), ['estado' => 'activo']);

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $this->admin->roles()->attach($rol->id, ['estado' => 'activo']);
        $this->token = $this->admin->createToken('test')->plainTextToken;

        $this->sucursal = Sucursal::factory()->create(['codigo' => 'SPS']);

        $this->conceptoMatId = \DB::table('conceptos_pago')->insertGetId([
            'codigo' => 'MAT', 'nombre' => 'Matrícula', 'tipo_monto' => 'por_oferta',
            'requiere_autorizacion_monto' => false, 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);
        $this->metodoEfeId = \DB::table('metodos_pago')->insertGetId([
            'codigo' => 'EFE', 'nombre' => 'Efectivo', 'estado' => 'activo',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);
    }

    private function crearPermisosBase(): void
    {
        $modulo = Modulo::create(['codigo' => 'caja', 'nombre' => 'Caja', 'estado' => 'activo', 'orden' => 8]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'caja.general', 'nombre' => 'General', 'estado' => 'activo']);

        foreach (['consultar', 'crear', 'modificar', 'aprobar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcion->id,
                'codigo' => 'caja.' . $accion,
                'nombre' => ucfirst($accion),
                'accion' => $accion,
                'estado' => 'activo',
            ]);
        }

        $modPagos = Modulo::create(['codigo' => 'pagos', 'nombre' => 'Pagos', 'estado' => 'activo', 'orden' => 7]);
        $opPagos = OpcionModulo::create(['modulo_id' => $modPagos->id, 'codigo' => 'pagos.general', 'nombre' => 'General', 'estado' => 'activo']);
        foreach (['consultar', 'crear', 'modificar', 'aprobar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opPagos->id,
                'codigo' => 'pagos.' . $accion,
                'nombre' => ucfirst($accion),
                'accion' => $accion,
                'estado' => 'activo',
            ]);
        }
    }

    private function headers(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_abrir_sesion_caja(): void
    {
        $response = $this->postJson('/api/v1/caja/abrir', [
            'sucursal_id' => $this->sucursal->id,
            'monto_inicial' => 500.00,
        ], $this->headers());

        $response->assertCreated()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.estado', 'abierta')
            ->assertJsonPath('data.monto_inicial', '500.00');

        $this->assertDatabaseHas('sesiones_caja', [
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'abierta',
        ]);
    }

    public function test_no_abrir_dos_sesiones_misma_sucursal(): void
    {
        $this->postJson('/api/v1/caja/abrir', [
            'sucursal_id' => $this->sucursal->id,
            'monto_inicial' => 500.00,
        ], $this->headers())->assertCreated();

        $response = $this->postJson('/api/v1/caja/abrir', [
            'sucursal_id' => $this->sucursal->id,
            'monto_inicial' => 300.00,
        ], $this->headers());

        $response->assertUnprocessable()
            ->assertJsonPath('resultado', 'R');
    }

    public function test_cerrar_sesion_caja_genera_detalle(): void
    {
        $sesion = $this->postJson('/api/v1/caja/abrir', [
            'sucursal_id' => $this->sucursal->id,
            'monto_inicial' => 500.00,
        ], $this->headers())->json('data');

        $estudiante = Estudiante::factory()->create(['sucursal_id' => $this->sucursal->id]);

        \App\Models\Pago::create([
            'codigo' => 'PAG-001', 'estudiante_id' => $estudiante->id,
            'concepto_pago_id' => $this->conceptoMatId, 'metodo_pago_id' => $this->metodoEfeId,
            'sucursal_id' => $this->sucursal->id, 'sesion_caja_id' => $sesion['id'],
            'monto' => 1200.00, 'estado' => 'aprobado', 'aprobado_por' => $this->admin->id,
            'fecha_aprobacion' => now(),
        ]);

        $response = $this->postJson("/api/v1/caja/{$sesion['id']}/cerrar", [
            'monto_final' => 1700.00,
        ], $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.estado', 'cerrada')
            ->assertJsonPath('data.monto_final', '1700.00');

        $this->assertDatabaseHas('detalle_cierre_caja', [
            'sesion_caja_id' => $sesion['id'],
            'monto_total' => 1200.00,
        ]);
    }

    public function test_listar_sesiones(): void
    {
        $this->postJson('/api/v1/caja/abrir', [
            'sucursal_id' => $this->sucursal->id,
            'monto_inicial' => 500.00,
        ], $this->headers())->assertCreated();

        $response = $this->getJson('/api/v1/caja/sesiones', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(1, 'data.data');
    }

    public function test_ver_detalle_sesion(): void
    {
        $sesion = $this->postJson('/api/v1/caja/abrir', [
            'sucursal_id' => $this->sucursal->id,
            'monto_inicial' => 500.00,
        ], $this->headers())->json('data');

        $response = $this->getJson("/api/v1/caja/{$sesion['id']}", $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonStructure([
                'data' => ['id', 'codigo', 'sucursal', 'cajero', 'detalles'],
            ]);
    }

    public function test_reporte_cierre_por_fecha(): void
    {
        $sesion = $this->postJson('/api/v1/caja/abrir', [
            'sucursal_id' => $this->sucursal->id,
            'monto_inicial' => 500.00,
        ], $this->headers())->json('data');

        $this->postJson("/api/v1/caja/{$sesion['id']}/cerrar", [
            'monto_final' => 500.00,
        ], $this->headers())->assertOk();

        $response = $this->getJson('/api/v1/cierre-caja?fecha_desde=' . now()->toDateString() . '&fecha_hasta=' . now()->toDateString(), $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(1, 'data.data');
    }

    public function test_resumen_diario(): void
    {
        $response = $this->getJson('/api/v1/cierre-caja/resumen?fecha=' . now()->toDateString(), $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonStructure([
                'data' => ['fecha', 'total_ingresos', 'cantidad_pagos', 'por_concepto', 'por_metodo'],
            ]);
    }

    public function test_requiere_permiso_para_abrir(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/v1/caja/abrir', [
            'sucursal_id' => $this->sucursal->id,
            'monto_inicial' => 500.00,
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertForbidden();
    }
}
