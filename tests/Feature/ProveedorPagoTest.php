<?php

namespace Tests\Feature;

use App\Models\{ConfiguracionProveedorPago, Modulo, OpcionModulo, Permiso, ProveedorPago, Rol, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProveedorPagoTest extends TestCase
{
    use RefreshDatabase;

    private ProveedorPago $proveedor;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $modulo = Modulo::create(['codigo' => 'configuracion', 'nombre' => 'Configuración', 'estado' => 'activo', 'orden' => 1]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'configuracion.pagos', 'nombre' => 'Proveedores de pago', 'estado' => 'activo']);
        $permisos = collect(['consultar', 'modificar'])->map(fn (string $accion) => Permiso::create([
            'opcion_modulo_id' => $opcion->id,
            'codigo' => 'configuracion.pagos.' . $accion,
            'nombre' => ucfirst($accion),
            'accion' => $accion,
            'estado' => 'activo',
        ]));
        $rol = Rol::create(['codigo' => 'TEST_CONFIG_PAGOS', 'nombre' => 'Configuración de pagos', 'estado' => 'activo']);
        $rol->permisos()->attach($permisos->pluck('id')->all(), ['estado' => 'activo']);
        $usuario = User::factory()->create(['estado' => 'activo']);
        $usuario->roles()->attach($rol->id, ['estado' => 'activo']);
        $this->token = $usuario->createToken('test')->plainTextToken;

        $this->proveedor = ProveedorPago::create([
            'codigo' => 'PAYPAL',
            'nombre' => 'PayPal',
            'activo' => true,
        ]);
        ConfiguracionProveedorPago::create([
            'proveedor_pago_id' => $this->proveedor->id,
            'clave' => 'client_secret',
            'valor' => 'secreto-anterior',
        ]);
    }

    private function headers(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_listado_enmascara_configuracion_sensible(): void
    {
        $this->getJson('/api/v1/proveedores-pago', $this->headers())
            ->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.0.configuraciones.0.clave', 'client_secret')
            ->assertJsonMissing(['valor' => 'secreto-anterior']);
    }

    public function test_guarda_configuracion_con_contrato_del_formulario(): void
    {
        $this->postJson("/api/v1/proveedores-pago/{$this->proveedor->id}/configuracion", [
            'config' => [
                'client_id' => 'client-id-nuevo',
                'client_secret' => 'secreto-nuevo',
                'modo' => 'live',
                'webhook_id' => 'webhook-nuevo',
            ],
        ], $this->headers())
            ->assertOk()
            ->assertJsonPath('resultado', 'A');

        $this->assertDatabaseHas('configuraciones_proveedor_pago', [
            'proveedor_pago_id' => $this->proveedor->id,
            'clave' => 'client_secret',
            'valor' => 'secreto-nuevo',
        ]);
    }
}
