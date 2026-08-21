<?php

namespace Tests\Feature;

use App\Models\InventarioLibro;
use App\Models\Libro;
use App\Models\Modulo;
use App\Models\OpcionModulo;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use App\Modules\Comun\ContextoUsuario;
use App\Modules\Inventario\CasosUso\AjustarExistencia;
use App\Modules\Inventario\CasosUso\RegistrarInventario;
use App\Modules\Inventario\CasosUso\VenderLibro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        DB::table('alcances_usuario')->insert([
            'usuario_id' => $this->admin->id,
            'tipo' => 'global',
            'estado' => 'activo',
        ]);

        $this->sucursal = Sucursal::factory()->create(['codigo' => 'SPS']);
    }

    private function crearPermisosBase(): void
    {
        $modulo = Modulo::create(['codigo' => 'inventario', 'nombre' => 'Inventario', 'estado' => 'activo', 'orden' => 9]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'inventario.general', 'nombre' => 'General', 'estado' => 'activo']);

        foreach (['consultar', 'crear', 'modificar', 'aprobar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcion->id,
                'codigo' => 'inventario.'.$accion,
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

        $response = $this->getJson('/api/v1/inventario/kardex?inventario_libro_id='.$invData['id'], $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(2, 'data.movimientos');
    }

    public function test_kardex_movimientos_filtra_desde_fecha(): void
    {
        $libro = Libro::create(['codigo' => 'LIB-010', 'titulo' => 'Book', 'precio_venta' => 100, 'creado_en' => now()]);
        $invData = $this->postJson('/api/v1/inventario/stock', [
            'libro_id' => $libro->id, 'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 5,
        ], $this->headers())->json('data');

        $this->postJson("/api/v1/inventario/stock/{$invData['id']}/ajustar", [
            'inventario_libro_id' => $invData['id'], 'cantidad' => 3, 'motivo' => '+3',
        ], $this->headers());

        $movimientoIds = DB::table('movimientos_inventario_libros')
            ->where('inventario_libro_id', $invData['id'])
            ->orderBy('id')
            ->pluck('id')
            ->values();

        DB::table('movimientos_inventario_libros')
            ->where('id', $movimientoIds[0])
            ->update(['creado_en' => '2026-08-01 08:00:00']);

        DB::table('movimientos_inventario_libros')
            ->where('id', $movimientoIds[1])
            ->update(['creado_en' => '2026-08-20 10:00:00']);

        $response = $this->getJson('/api/v1/inventario/kardex?inventario_libro_id='.$invData['id'].'&fecha_desde=2026-08-15', $this->headers());

        $response->assertOk()
            ->assertJsonPath('resultado', 'A')
            ->assertJsonCount(1, 'data.movimientos')
            ->assertJsonPath('data.movimientos.0.motivo', '+3');
    }

    public function test_registrar_inventario_mediante_caso_de_uso(): void
    {
        $libro = Libro::create(['codigo' => 'LIB-001', 'titulo' => 'Book', 'precio_venta' => 100, 'creado_en' => now()]);

        $resultado = app(RegistrarInventario::class)->ejecutar([
            'libro_id' => $libro->id,
            'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 10,
            'existencia_minima' => 2,
        ], new ContextoUsuario($this->admin->id));

        $this->assertTrue($resultado->ok());
        $this->assertSame('Inventario registrado exitosamente', $resultado->mensaje());
        $this->assertSame(10, $resultado->data()['inventario']->existencia_actual);
        $this->assertDatabaseHas('inventario_libros', [
            'libro_id' => $libro->id,
            'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 10,
            'existencia_minima' => 2,
            'creado_por' => $this->admin->id,
        ]);
        $this->assertDatabaseHas('movimientos_inventario_libros', [
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'existencia_antes' => 0,
            'existencia_despues' => 10,
        ]);
    }

    public function test_registrar_inventario_mediante_caso_de_uso_rechaza_duplicado(): void
    {
        $libro = Libro::create(['codigo' => 'LIB-001', 'titulo' => 'Book', 'precio_venta' => 100, 'creado_en' => now()]);
        InventarioLibro::create([
            'libro_id' => $libro->id, 'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 5, 'creado_en' => now(),
        ]);

        $resultado = app(RegistrarInventario::class)->ejecutar([
            'libro_id' => $libro->id,
            'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 10,
        ], new ContextoUsuario($this->admin->id));

        $this->assertFalse($resultado->ok());
        $this->assertSame(422, $resultado->codigo());
        $this->assertSame('El libro ya tiene inventario registrado en esta sucursal', $resultado->mensaje());
    }

    public function test_registrar_inventario_sin_existencia_no_crea_movimiento(): void
    {
        $libro = Libro::create(['codigo' => 'LIB-001', 'titulo' => 'Book', 'precio_venta' => 100, 'creado_en' => now()]);

        $resultado = app(RegistrarInventario::class)->ejecutar([
            'libro_id' => $libro->id,
            'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 0,
        ], new ContextoUsuario($this->admin->id));

        $this->assertTrue($resultado->ok());
        $this->assertSame(0, $resultado->data()['inventario']->existencia_actual);
        $this->assertDatabaseCount('movimientos_inventario_libros', 0);
    }

    public function test_ajustar_existencia_entrada_mediante_caso_de_uso(): void
    {
        $libro = Libro::create(['codigo' => 'LIB-001', 'titulo' => 'Book', 'precio_venta' => 100, 'creado_en' => now()]);
        $inv = InventarioLibro::create([
            'libro_id' => $libro->id, 'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 5, 'creado_en' => now(),
        ]);

        $resultado = app(AjustarExistencia::class)->ejecutar([
            'inventario_libro_id' => $inv->id,
            'cantidad' => 3,
            'motivo' => 'Reabastecimiento',
        ], new ContextoUsuario($this->admin->id));

        $this->assertTrue($resultado->ok());
        $this->assertSame('Entrada registrada', $resultado->mensaje());
        $this->assertSame(8, $resultado->data()['inventario']->existencia_actual);
        $this->assertDatabaseHas('movimientos_inventario_libros', [
            'inventario_libro_id' => $inv->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 3,
            'existencia_antes' => 5,
            'existencia_despues' => 8,
            'creado_por' => $this->admin->id,
        ]);
    }

    public function test_ajustar_existencia_salida_mediante_caso_de_uso(): void
    {
        $libro = Libro::create(['codigo' => 'LIB-001', 'titulo' => 'Book', 'precio_venta' => 100, 'creado_en' => now()]);
        $inv = InventarioLibro::create([
            'libro_id' => $libro->id, 'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 10, 'creado_en' => now(),
        ]);

        $resultado = app(AjustarExistencia::class)->ejecutar([
            'inventario_libro_id' => $inv->id,
            'cantidad' => -3,
            'motivo' => 'Ajuste por daño',
        ], new ContextoUsuario($this->admin->id));

        $this->assertTrue($resultado->ok());
        $this->assertSame('Salida registrada', $resultado->mensaje());
        $this->assertSame(7, $resultado->data()['inventario']->existencia_actual);
    }

    public function test_ajustar_existencia_mediante_caso_de_uso_rechaza_negativo(): void
    {
        $libro = Libro::create(['codigo' => 'LIB-001', 'titulo' => 'Book', 'precio_venta' => 100, 'creado_en' => now()]);
        $inv = InventarioLibro::create([
            'libro_id' => $libro->id, 'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 2, 'creado_en' => now(),
        ]);

        $resultado = app(AjustarExistencia::class)->ejecutar([
            'inventario_libro_id' => $inv->id,
            'cantidad' => -5,
            'motivo' => 'Intentar negativo',
        ], new ContextoUsuario($this->admin->id));

        $this->assertFalse($resultado->ok());
        $this->assertSame(422, $resultado->codigo());
        $this->assertSame('La existencia no puede ser negativa', $resultado->mensaje());
        $this->assertDatabaseHas('inventario_libros', [
            'id' => $inv->id,
            'existencia_actual' => 2,
        ]);
    }

    public function test_ajustar_existencia_mediante_caso_de_uso_rechaza_inventario_inexistente(): void
    {
        $resultado = app(AjustarExistencia::class)->ejecutar([
            'inventario_libro_id' => 999999,
            'cantidad' => 3,
            'motivo' => 'Test',
        ], new ContextoUsuario($this->admin->id));

        $this->assertFalse($resultado->ok());
        $this->assertSame(404, $resultado->codigo());
        $this->assertSame('404_INVENTARIO_NO_ENCONTRADO', $resultado->codigoError());
    }

    public function test_vender_libro_mediante_caso_de_uso(): void
    {
        $libro = Libro::create(['codigo' => 'LIB-001', 'titulo' => 'Book', 'precio_venta' => 350.00, 'creado_en' => now()]);
        $inv = InventarioLibro::create([
            'libro_id' => $libro->id, 'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 10, 'creado_en' => now(),
        ]);

        $resultado = app(VenderLibro::class)->ejecutar([
            'inventario_libro_id' => $inv->id,
            'cantidad' => 2,
            'motivo' => 'Venta a estudiante',
        ], new ContextoUsuario($this->admin->id));

        $this->assertTrue($resultado->ok());
        $this->assertSame('Venta registrada', $resultado->mensaje());
        $this->assertSame(8, $resultado->data()['venta']['inventario']->existencia_actual);
        $this->assertSame(700.0, $resultado->data()['venta']['total_venta']);
        $this->assertDatabaseHas('movimientos_inventario_libros', [
            'inventario_libro_id' => $inv->id,
            'tipo_movimiento' => 'salida',
            'cantidad' => 2,
            'existencia_antes' => 10,
            'existencia_despues' => 8,
            'motivo' => 'Venta a estudiante',
            'referencia_type' => null,
            'referencia_id' => null,
            'creado_por' => $this->admin->id,
        ]);
    }

    public function test_vender_libro_mediante_caso_de_uso_rechaza_stock_insuficiente(): void
    {
        $libro = Libro::create(['codigo' => 'LIB-001', 'titulo' => 'Book', 'precio_venta' => 100, 'creado_en' => now()]);
        $inv = InventarioLibro::create([
            'libro_id' => $libro->id, 'sucursal_id' => $this->sucursal->id,
            'existencia_actual' => 1, 'creado_en' => now(),
        ]);

        $resultado = app(VenderLibro::class)->ejecutar([
            'inventario_libro_id' => $inv->id,
            'cantidad' => 5,
        ], new ContextoUsuario($this->admin->id));

        $this->assertFalse($resultado->ok());
        $this->assertSame(422, $resultado->codigo());
        $this->assertSame('No hay suficiente existencia. Disponible: 1', $resultado->mensaje());
        $this->assertDatabaseHas('inventario_libros', [
            'id' => $inv->id,
            'existencia_actual' => 1,
        ]);
    }

    public function test_vender_libro_mediante_caso_de_uso_rechaza_inventario_inexistente(): void
    {
        $resultado = app(VenderLibro::class)->ejecutar([
            'inventario_libro_id' => 999999,
            'cantidad' => 1,
        ], new ContextoUsuario($this->admin->id));

        $this->assertFalse($resultado->ok());
        $this->assertSame(404, $resultado->codigo());
        $this->assertSame('404_INVENTARIO_NO_ENCONTRADO', $resultado->codigoError());
    }
}
