<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Modulo;
use App\Models\OpcionModulo;
use App\Models\Pago;
use App\Models\Permiso;
use App\Models\ReciboCaja;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use App\Modules\Caja\CasosUso\AbrirSesionCaja;
use App\Modules\Caja\CasosUso\AnularRecibo;
use App\Modules\Caja\CasosUso\CerrarSesionCaja;
use App\Modules\Caja\CasosUso\ReimprimirRecibo;
use App\Modules\Comun\ContextoUsuario;
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
        \DB::table('alcances_usuario')->insert([
            'usuario_id' => $this->admin->id,
            'tipo' => 'global',
            'estado' => 'activo',
        ]);

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
                'codigo' => 'caja.'.$accion,
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
                'codigo' => 'pagos.'.$accion,
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

    private function crearRecibo(string $codigo, string $estado = 'emitido'): ReciboCaja
    {
        $estudiante = Estudiante::factory()->create(['sucursal_id' => $this->sucursal->id]);

        $pago = Pago::create([
            'codigo' => 'PAG-REC-'.$codigo, 'estudiante_id' => $estudiante->id,
            'concepto_pago_id' => $this->conceptoMatId, 'metodo_pago_id' => $this->metodoEfeId,
            'sucursal_id' => $this->sucursal->id,
            'monto' => 1200.00, 'estado' => 'aprobado', 'aprobado_por' => $this->admin->id,
            'fecha_aprobacion' => now(),
        ]);

        return ReciboCaja::create([
            'codigo' => $codigo, 'numero_recibo' => 1, 'pago_id' => $pago->id,
            'estudiante_id' => $estudiante->id,
            'sucursal_id' => $this->sucursal->id,
            'concepto_pago_id' => $this->conceptoMatId, 'metodo_pago_id' => $this->metodoEfeId,
            'monto_total' => 1200.00, 'estado' => $estado, 'anio' => date('Y'),
            'creado_por' => $this->admin->id,
        ]);
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

        Pago::create([
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

        $response = $this->getJson('/api/v1/cierre-caja?fecha_desde='.now()->toDateString().'&fecha_hasta='.now()->toDateString(), $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(1, 'data.data');
    }

    public function test_resumen_diario(): void
    {
        $response = $this->getJson('/api/v1/cierre-caja/resumen?fecha='.now()->toDateString(), $this->headers());

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

    public function test_abrir_sesion_caja_mediante_caso_de_uso(): void
    {
        $casoUso = app(AbrirSesionCaja::class);
        $resultado = $casoUso->ejecutar([
            'sucursal_id' => $this->sucursal->id,
            'monto_inicial' => 500.00,
        ], new ContextoUsuario($this->admin->id));

        $this->assertTrue($resultado->ok());
        $this->assertSame('Sesión de caja abierta', $resultado->mensaje());
        $this->assertSame('abierta', $resultado->data()['sesion']->estado);
        $this->assertDatabaseHas('sesiones_caja', [
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'abierta',
        ]);
    }

    public function test_abrir_endpoint_sigue_delegando_al_caso_de_uso(): void
    {
        $response = $this->postJson('/api/v1/caja/abrir', [
            'sucursal_id' => $this->sucursal->id,
            'monto_inicial' => 500.00,
        ], $this->headers());

        $response->assertCreated()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.estado', 'abierta');
    }

    public function test_abrir_mediante_caso_de_uso_rechaza_sesion_abierta(): void
    {
        $casoUso = app(AbrirSesionCaja::class);
        $casoUso->ejecutar([
            'sucursal_id' => $this->sucursal->id,
            'monto_inicial' => 500.00,
        ], new ContextoUsuario($this->admin->id));

        $resultado = $casoUso->ejecutar([
            'sucursal_id' => $this->sucursal->id,
            'monto_inicial' => 300.00,
        ], new ContextoUsuario($this->admin->id));

        $this->assertFalse($resultado->ok());
        $this->assertSame(422, $resultado->codigo());
        $this->assertSame('Ya tiene una sesión de caja abierta en esta sucursal', $resultado->mensaje());
    }

    public function test_cerrar_sesion_caja_mediante_caso_de_uso(): void
    {
        $sesion = app(AbrirSesionCaja::class)->ejecutar([
            'sucursal_id' => $this->sucursal->id,
            'monto_inicial' => 500.00,
        ], new ContextoUsuario($this->admin->id))->data()['sesion'];

        $estudiante = Estudiante::factory()->create(['sucursal_id' => $this->sucursal->id]);

        Pago::create([
            'codigo' => 'PAG-001', 'estudiante_id' => $estudiante->id,
            'concepto_pago_id' => $this->conceptoMatId, 'metodo_pago_id' => $this->metodoEfeId,
            'sucursal_id' => $this->sucursal->id, 'sesion_caja_id' => $sesion->id,
            'monto' => 1200.00, 'estado' => 'aprobado', 'aprobado_por' => $this->admin->id,
            'fecha_aprobacion' => now(),
        ]);

        $casoUso = app(CerrarSesionCaja::class);
        $resultado = $casoUso->ejecutar($sesion->id, [
            'monto_final' => 1700.00,
        ], new ContextoUsuario($this->admin->id));

        $this->assertTrue($resultado->ok());
        $this->assertSame('cerrada', $resultado->data()['sesion']->estado);
        $this->assertDatabaseHas('detalle_cierre_caja', [
            'sesion_caja_id' => $sesion->id,
            'monto_total' => 1200.00,
        ]);
    }

    public function test_anular_recibo_mediante_caso_de_uso(): void
    {
        $recibo = $this->crearRecibo('REC-0001');

        $casoUso = app(AnularRecibo::class);
        $resultado = $casoUso->ejecutar($recibo->id, 'Error en monto', new ContextoUsuario($this->admin->id));

        $this->assertTrue($resultado->ok());
        $this->assertSame('Recibo anulado', $resultado->mensaje());
        $this->assertSame('anulado', $resultado->data()['recibo']->estado);
        $this->assertDatabaseHas('recibos_caja', [
            'id' => $recibo->id,
            'estado' => 'anulado',
            'motivo_anulacion' => 'Error en monto',
        ]);
    }

    public function test_anular_mediante_caso_de_uso_rechaza_recibo_anulado(): void
    {
        $recibo = $this->crearRecibo('REC-0002');

        $casoUso = app(AnularRecibo::class);
        $casoUso->ejecutar($recibo->id, 'Primera anulación', new ContextoUsuario($this->admin->id));

        $resultado = $casoUso->ejecutar($recibo->id, 'Segunda anulación', new ContextoUsuario($this->admin->id));

        $this->assertFalse($resultado->ok());
        $this->assertSame(422, $resultado->codigo());
        $this->assertSame('El recibo ya está anulado', $resultado->mensaje());
    }

    public function test_reimprimir_recibo_mediante_caso_de_uso(): void
    {
        $recibo = $this->crearRecibo('REC-0003');

        $casoUso = app(ReimprimirRecibo::class);
        $resultado = $casoUso->ejecutar($recibo->id, new ContextoUsuario($this->admin->id));

        $this->assertTrue($resultado->ok());
        $this->assertSame('Reimpresión registrada', $resultado->mensaje());
        $this->assertSame(1, $resultado->data()['recibo']->veces_reimpreso);
    }

    public function test_reimprimir_mediante_caso_de_uso_rechaza_recibo_anulado(): void
    {
        $recibo = $this->crearRecibo('REC-0004', 'anulado');

        $resultado = app(ReimprimirRecibo::class)->ejecutar($recibo->id, new ContextoUsuario($this->admin->id));

        $this->assertFalse($resultado->ok());
        $this->assertSame(422, $resultado->codigo());
        $this->assertSame('No se puede reimprimir un recibo anulado', $resultado->mensaje());
    }
}
