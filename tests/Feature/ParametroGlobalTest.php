<?php

namespace Tests\Feature;

use App\Models\{Modulo, OpcionModulo, ParametroGlobal, Permiso, Rol, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParametroGlobalTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $modulo = Modulo::create(['codigo' => 'seguridad', 'nombre' => 'Seguridad', 'estado' => 'activo', 'orden' => 1]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'seguridad.parametros', 'nombre' => 'Parámetros', 'estado' => 'activo']);
        foreach (['consultar', 'crear', 'modificar', 'eliminar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcion->id,
                'codigo' => 'seguridad.' . $accion,
                'nombre' => ucfirst($accion),
                'accion' => $accion,
                'estado' => 'activo',
            ]);
        }

        $rol = Rol::create(['codigo' => 'ADMIN_PARAMS', 'nombre' => 'Admin Params', 'estado' => 'activo']);
        $permisos = Permiso::where('codigo', 'like', 'seguridad.%')->get();
        $rol->permisos()->attach($permisos->pluck('id')->toArray(), ['estado' => 'activo']);

        $user = User::create(['name' => 'Test', 'email' => 'test@param.com', 'password' => bcrypt('x'), 'estado' => 'activo']);
        $user->roles()->attach($rol->id, ['estado' => 'activo']);
        $this->token = $user->createToken('test')->plainTextToken;
    }

    private function headers(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_listar_parametros(): void
    {
        ParametroGlobal::create(['grupo' => '01', 'codigo' => 'TEST', 'nombre' => 'Test', 'valor' => 'Valor', 'tipo' => 'texto', 'estado' => true]);

        $response = $this->getJson('/api/v1/seguridad/parametros-globales', $this->headers());

        $response->assertOk()->assertJsonPath('resultado', 'A')->assertJsonFragment(['codigo' => 'TEST']);
    }

    public function test_crear_parametro(): void
    {
        $response = $this->postJson('/api/v1/seguridad/parametros-globales', [
            'grupo' => '01', 'codigo' => 'NUEVO_PARAM', 'nombre' => 'Nuevo', 'valor' => '123', 'tipo' => 'texto', 'estado' => true,
        ], $this->headers());

        $response->assertStatus(201)->assertJsonPath('data.codigo', 'NUEVO_PARAM');
        $this->assertDatabaseHas('parametros_globales', ['codigo' => 'NUEVO_PARAM']);
    }

    public function test_no_duplicar_codigo_por_grupo(): void
    {
        ParametroGlobal::create(['grupo' => '01', 'codigo' => 'DUP', 'nombre' => 'Test', 'valor' => 'V', 'tipo' => 'texto']);

        $response = $this->postJson('/api/v1/seguridad/parametros-globales', [
            'grupo' => '01', 'codigo' => 'DUP', 'nombre' => 'Otro', 'tipo' => 'texto',
        ], $this->headers());

        $response->assertStatus(422);
    }

    public function test_actualizar_parametro(): void
    {
        $p = ParametroGlobal::create(['grupo' => '01', 'codigo' => 'ACTUAL', 'nombre' => 'Original', 'valor' => 'V1', 'tipo' => 'texto']);

        $response = $this->putJson("/api/v1/seguridad/parametros-globales/{$p->id}", [
            'nombre' => 'Actualizado', 'valor' => 'V2',
        ], $this->headers());

        $response->assertOk()->assertJsonPath('data.nombre', 'Actualizado');
    }

    public function test_eliminar_parametro(): void
    {
        $p = ParametroGlobal::create(['grupo' => '01', 'codigo' => 'DEL', 'nombre' => 'Borrar', 'valor' => 'V', 'tipo' => 'texto']);

        $this->deleteJson("/api/v1/seguridad/parametros-globales/{$p->id}", [], $this->headers())
            ->assertOk()->assertJsonPath('resultado', 'A');

        $this->assertDatabaseMissing('parametros_globales', ['id' => $p->id]);
    }

    public function test_obtener_valor_estatico(): void
    {
        ParametroGlobal::create(['grupo' => '01', 'codigo' => 'EMPRESA_NOMBRE', 'nombre' => 'Empresa', 'valor' => 'Mi Empresa', 'tipo' => 'texto', 'estado' => true]);

        $this->assertEquals('Mi Empresa', ParametroGlobal::obtener('EMPRESA_NOMBRE'));
    }

    public function test_requires_permiso(): void
    {
        $user = User::create(['name' => 'NoPerm', 'email' => 'no@perm.com', 'password' => bcrypt('x'), 'estado' => 'activo']);
        $token = $user->createToken('test')->plainTextToken;

        $this->getJson('/api/v1/seguridad/parametros-globales', ['Authorization' => "Bearer $token"])
            ->assertForbidden();
    }
}