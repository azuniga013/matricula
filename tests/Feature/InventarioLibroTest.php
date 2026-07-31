<?php

namespace Tests\Feature;

use App\Models\{InventarioLibro, Libro, Modulo, OpcionModulo, Permiso, Rol, Sucursal, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventarioLibroTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $token;
    private Sucursal $sucursal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearPermisosBase();

        $rol = Rol::create(['codigo' => 'TEST_INV', 'nombre' => 'Test Inv', 'estado' => 'activo']);
        $permisos = Permiso::where('codigo', 'like', 'inventario.%')->get();
        $rol->permisos()->attach($permisos->pluck('id')->toArray(), ['estado' => 'activo']);

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $this->admin->roles()->attach($rol->id, ['estado' => 'activo']);
        $this->token = $this->admin->createToken('test')->plainTextToken;

        $this->sucursal = Sucursal::factory()->create(['codigo' => 'SPS']);
    }

    private function crearPermisosBase(): void
    {
        $modulo = Modulo::create(['codigo' => 'inventario', 'nombre' => 'Inventario', 'estado' => 'activo', 'orden' => 9]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'inventario.general', 'nombre' => 'General', 'estado' => 'activo']);

        foreach (['consultar', 'crear', 'modificar', 'aprobar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcion->id,
                'codigo' => 'inventario.' . $accion,
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

    public function test_crear_libro(): void
    {
        $response = $this->postJson('/api/v1/inventario/libros', [
            'codigo' => 'LIB-001',
            'titulo' => 'English Book A1',
            'autor' => 'John Smith',
            'editorial' => 'Cambridge',
            'precio_venta' => 350.00,
        ], $this->headers());

        $response->assertCreated()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.codigo', 'LIB-001')
            ->assertJsonPath('data.precio_venta', '350.00');

        $this->assertDatabaseHas('libros', ['codigo' => 'LIB-001', 'titulo' => 'English Book A1']);
    }

    public function test_listar_libros(): void
    {
        Libro::create(['codigo' => 'LIB-001', 'titulo' => 'Book A', 'precio_venta' => 100, 'creado_en' => now()]);
        Libro::create(['codigo' => 'LIB-002', 'titulo' => 'Book B', 'precio_venta' => 200, 'creado_en' => now()]);

        $response = $this->getJson('/api/v1/inventario/libros', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(2, 'data');
    }

    public function test_mostrar_libro(): void
    {
        $libro = Libro::create(['codigo' => 'LIB-001', 'titulo' => 'Book A', 'precio_venta' => 100, 'creado_en' => now()]);

        $response = $this->getJson("/api/v1/inventario/libros/{$libro->id}", $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.codigo', 'LIB-001');
    }

    public function test_actualizar_libro(): void
    {
        $libro = Libro::create(['codigo' => 'LIB-001', 'titulo' => 'Old Title', 'precio_venta' => 100, 'creado_en' => now()]);

        $response = $this->putJson("/api/v1/inventario/libros/{$libro->id}", [
            'codigo' => 'LIB-001',
            'titulo' => 'New Title',
            'precio_venta' => 150.00,
        ], $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.titulo', 'New Title');

        $this->assertDatabaseHas('libros', ['id' => $libro->id, 'titulo' => 'New Title']);
    }

    public function test_crear_inventario(): void
    {
        $libro = Libro::create(['codigo' => 'LIB-001', 'titulo' => 'Book A', 'precio_venta' => 100, 'creado_en' => now()]);

        $response = $this->postJson('/api/v1/inventario/stock', [
            'libro_id' => $libro->id,
            'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 10,
            'existencia_minima' => 2,
        ], $this->headers());

        $response->assertCreated()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.existencia_actual', 10);

        $this->assertDatabaseHas('inventario_libros', [
            'libro_id' => $libro->id,
            'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 10,
        ]);
    }

    public function test_no_duplicar_inventario(): void
    {
        $libro = Libro::create(['codigo' => 'LIB-001', 'titulo' => 'Book', 'precio_venta' => 100, 'creado_en' => now()]);
        InventarioLibro::create([
            'libro_id' => $libro->id, 'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 5, 'creado_en' => now(),
        ]);

        $response = $this->postJson('/api/v1/inventario/stock', [
            'libro_id' => $libro->id,
            'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 10,
        ], $this->headers());

        $response->assertUnprocessable()
            ->assertJsonPath('resultado', 'R');
    }

    public function test_listar_stock(): void
    {
        $libro = Libro::create(['codigo' => 'LIB-001', 'titulo' => 'Book', 'precio_venta' => 100, 'creado_en' => now()]);
        InventarioLibro::create([
            'libro_id' => $libro->id, 'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 10, 'creado_en' => now(),
        ]);

        $response = $this->getJson('/api/v1/inventario/stock', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(1, 'data');
    }

    public function test_ajustar_stock_entrada(): void
    {
        $libro = Libro::create(['codigo' => 'LIB-001', 'titulo' => 'Book', 'precio_venta' => 100, 'creado_en' => now()]);
        $inv = InventarioLibro::create([
            'libro_id' => $libro->id, 'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 5, 'creado_en' => now(),
        ]);

        $response = $this->postJson("/api/v1/inventario/stock/{$inv->id}/ajustar", [
            'inventario_libro_id' => $inv->id,
            'cantidad' => 3,
            'motivo' => 'Reabastecimiento',
        ], $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.existencia_actual', 8);

        $this->assertDatabaseHas('movimientos_inventario_libros', [
            'inventario_libro_id' => $inv->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 3,
            'existencia_antes' => 5,
            'existencia_despues' => 8,
        ]);
    }

    public function test_ajustar_stock_salida(): void
    {
        $libro = Libro::create(['codigo' => 'LIB-001', 'titulo' => 'Book', 'precio_venta' => 100, 'creado_en' => now()]);
        $inv = InventarioLibro::create([
            'libro_id' => $libro->id, 'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 10, 'creado_en' => now(),
        ]);

        $response = $this->postJson("/api/v1/inventario/stock/{$inv->id}/ajustar", [
            'inventario_libro_id' => $inv->id,
            'cantidad' => -3,
            'motivo' => 'Ajuste por daño',
        ], $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.existencia_actual', 7);
    }

    public function test_ajustar_no_negativo(): void
    {
        $libro = Libro::create(['codigo' => 'LIB-001', 'titulo' => 'Book', 'precio_venta' => 100, 'creado_en' => now()]);
        $inv = InventarioLibro::create([
            'libro_id' => $libro->id, 'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 2, 'creado_en' => now(),
        ]);

        $response = $this->postJson("/api/v1/inventario/stock/{$inv->id}/ajustar", [
            'inventario_libro_id' => $inv->id,
            'cantidad' => -5,
            'motivo' => 'Intentar negativo',
        ], $this->headers());

        $response->assertUnprocessable()
            ->assertJsonPath('resultado', 'R');
    }

    public function test_vender_libro(): void
    {
        $libro = Libro::create(['codigo' => 'LIB-001', 'titulo' => 'Book', 'precio_venta' => 350.00, 'creado_en' => now()]);
        $inv = InventarioLibro::create([
            'libro_id' => $libro->id, 'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 10, 'creado_en' => now(),
        ]);

        $response = $this->postJson("/api/v1/inventario/stock/{$inv->id}/vender", [
            'inventario_libro_id' => $inv->id,
            'cantidad' => 2,
            'motivo' => 'Venta a estudiante',
        ], $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonPath('data.inventario.existencia_actual', 8)
            ->assertJsonPath('data.total_venta', 700);

        $this->assertDatabaseHas('movimientos_inventario_libros', [
            'inventario_libro_id' => $inv->id,
            'tipo_movimiento' => 'salida',
            'cantidad' => 2,
        ]);
    }

    public function test_vender_sin_stock_suficiente(): void
    {
        $libro = Libro::create(['codigo' => 'LIB-001', 'titulo' => 'Book', 'precio_venta' => 100, 'creado_en' => now()]);
        $inv = InventarioLibro::create([
            'libro_id' => $libro->id, 'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 1, 'creado_en' => now(),
        ]);

        $response = $this->postJson("/api/v1/inventario/stock/{$inv->id}/vender", [
            'inventario_libro_id' => $inv->id,
            'cantidad' => 5,
        ], $this->headers());

        $response->assertUnprocessable()
            ->assertJsonPath('resultado', 'R');
    }

    public function test_requiere_permiso_inventario(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/v1/inventario/libros', [
            'codigo' => 'LIB-001', 'titulo' => 'Book', 'precio_venta' => 100,
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertForbidden();
    }

    public function test_kardex_movimientos(): void
    {
        $libro = Libro::create(['codigo' => 'LIB-001', 'titulo' => 'Book', 'precio_venta' => 100, 'creado_en' => now()]);
        $invData = $this->postJson('/api/v1/inventario/stock', [
            'libro_id' => $libro->id, 'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 5,
        ], $this->headers())->json('data');

        $this->postJson("/api/v1/inventario/stock/{$invData['id']}/ajustar", [
            'inventario_libro_id' => $invData['id'], 'cantidad' => 3, 'motivo' => '+3',
        ], $this->headers());

        $response = $this->getJson('/api/v1/inventario/kardex?inventario_libro_id=' . $invData['id'], $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(2, 'data.movimientos');
    }
}
