<?php

namespace Tests\Feature;

use App\Models\Modulo;
use App\Models\OpcionModulo;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DenegacionRbacTest extends TestCase
{
    use RefreshDatabase;

    private Rol $rol;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearPermisosBase();
    }

    private function crearPermisosBase(): void
    {
        $moduloSeguridad = Modulo::create(['codigo' => 'seguridad', 'nombre' => 'Seguridad', 'estado' => 'activo', 'orden' => 1]);
        $opcionSeguridad = OpcionModulo::create(['modulo_id' => $moduloSeguridad->id, 'codigo' => 'seguridad.general', 'nombre' => 'General', 'estado' => 'activo']);
        foreach (['consultar', 'crear', 'modificar', 'eliminar', 'configurar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcionSeguridad->id,
                'codigo' => 'seguridad.'.$accion,
                'nombre' => ucfirst($accion),
                'accion' => $accion,
                'estado' => 'activo',
            ]);
        }

        $moduloMatriculas = Modulo::create(['codigo' => 'matriculas', 'nombre' => 'Matrículas', 'estado' => 'activo', 'orden' => 6]);
        $opcionMatriculas = OpcionModulo::create(['modulo_id' => $moduloMatriculas->id, 'codigo' => 'matriculas.general', 'nombre' => 'General', 'estado' => 'activo']);
        foreach (['consultar', 'crear', 'modificar', 'eliminar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcionMatriculas->id,
                'codigo' => 'matriculas.'.$accion,
                'nombre' => ucfirst($accion),
                'accion' => $accion,
                'estado' => 'activo',
            ]);
        }
    }

    private function crearUsuarioSinPermisos(string $email): array
    {
        $rol = Rol::create(['codigo' => 'SIN_PERMISOS', 'nombre' => 'Sin Permisos', 'estado' => 'activo']);
        $usuario = User::create([
            'name' => 'Sin Permisos',
            'email' => $email,
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $usuario->roles()->attach($rol->id, ['estado' => 'activo']);

        return [$rol, $usuario];
    }

    private function headersToken(User $usuario): array
    {
        return ['Authorization' => 'Bearer '.$usuario->createToken('test')->plainTextToken];
    }

    public function test_seguridad_usuarios_requiere_permiso(): void
    {
        [, $usuario] = $this->crearUsuarioSinPermisos('sin-usuarios@test.com');

        $response = $this->getJson('/api/v1/seguridad/usuarios', $this->headersToken($usuario));

        $response->assertForbidden()
            ->assertJsonPath('resultado', 'R')
            ->assertJsonPath('mensaje', 'No tiene permiso para realizar esta acción');
    }

    public function test_seguridad_roles_requiere_permiso(): void
    {
        [, $usuario] = $this->crearUsuarioSinPermisos('sin-roles@test.com');

        $response = $this->getJson('/api/v1/seguridad/roles', $this->headersToken($usuario));

        $response->assertForbidden()
            ->assertJsonPath('resultado', 'R');
    }

    public function test_seguridad_roles_asignar_permisos_requiere_configurar(): void
    {
        [, $usuario] = $this->crearUsuarioSinPermisos('sin-configurar@test.com');
        $rol = Rol::create(['codigo' => 'ROL_DESTINO', 'nombre' => 'Destino', 'estado' => 'activo']);

        $response = $this->postJson("/api/v1/seguridad/roles/{$rol->id}/permisos", [
            'permisos' => ['seguridad.consultar'],
        ], $this->headersToken($usuario));

        $response->assertForbidden()
            ->assertJsonPath('resultado', 'R');
    }

    public function test_gestiones_matricula_listar_requiere_permiso(): void
    {
        [, $usuario] = $this->crearUsuarioSinPermisos('sin-gestiones@test.com');

        $response = $this->getJson('/api/v1/gestiones-matricula', $this->headersToken($usuario));

        $response->assertForbidden()
            ->assertJsonPath('resultado', 'R');
    }

    public function test_gestiones_matricula_solicitar_requiere_permiso(): void
    {
        [, $usuario] = $this->crearUsuarioSinPermisos('sin-solicitar@test.com');

        $response = $this->postJson('/api/v1/gestiones-matricula/solicitar', [
            'matricula_id' => 1,
            'tipo_gestion' => 'cambio_plan',
        ], $this->headersToken($usuario));

        $response->assertForbidden()
            ->assertJsonPath('resultado', 'R');
    }

    public function test_gestiones_matricula_aprobar_requiere_modificar(): void
    {
        [, $usuario] = $this->crearUsuarioSinPermisos('sin-aprobar@test.com');

        $response = $this->postJson('/api/v1/gestiones-matricula/1/aprobar', [], $this->headersToken($usuario));

        $response->assertForbidden()
            ->assertJsonPath('resultado', 'R');
    }

    public function test_estudiantes_listar_requiere_permiso(): void
    {
        [, $usuario] = $this->crearUsuarioSinPermisos('sin-estudiantes@test.com');

        $response = $this->getJson('/api/v1/estudiantes', $this->headersToken($usuario));

        $response->assertForbidden()
            ->assertJsonPath('resultado', 'R');
    }

    public function test_matriculas_listar_requiere_permiso(): void
    {
        [, $usuario] = $this->crearUsuarioSinPermisos('sin-matriculas@test.com');

        $response = $this->getJson('/api/v1/matriculas', $this->headersToken($usuario));

        $response->assertForbidden()
            ->assertJsonPath('resultado', 'R');
    }

    public function test_pagos_listar_requiere_permiso(): void
    {
        [, $usuario] = $this->crearUsuarioSinPermisos('sin-pagos@test.com');

        $response = $this->getJson('/api/v1/pagos', $this->headersToken($usuario));

        $response->assertForbidden()
            ->assertJsonPath('resultado', 'R');
    }

    public function test_recibos_caja_listar_requiere_permiso(): void
    {
        [, $usuario] = $this->crearUsuarioSinPermisos('sin-recibos@test.com');

        $response = $this->getJson('/api/v1/recibos-caja', $this->headersToken($usuario));

        $response->assertForbidden()
            ->assertJsonPath('resultado', 'R');
    }

    public function test_caja_listar_requiere_permiso(): void
    {
        [, $usuario] = $this->crearUsuarioSinPermisos('sin-caja@test.com');

        $response = $this->getJson('/api/v1/caja/sesiones', $this->headersToken($usuario));

        $response->assertForbidden()
            ->assertJsonPath('resultado', 'R');
    }

    public function test_cierre_caja_listar_requiere_permiso(): void
    {
        [, $usuario] = $this->crearUsuarioSinPermisos('sin-cierre@test.com');

        $response = $this->getJson('/api/v1/cierre-caja?fecha_desde=2026-01-01&fecha_hasta=2026-12-31', $this->headersToken($usuario));

        $response->assertForbidden()
            ->assertJsonPath('resultado', 'R');
    }

    public function test_calificaciones_listar_requiere_permiso(): void
    {
        [, $usuario] = $this->crearUsuarioSinPermisos('sin-calificaciones@test.com');

        $response = $this->getJson('/api/v1/calificaciones', $this->headersToken($usuario));

        $response->assertForbidden()
            ->assertJsonPath('resultado', 'R');
    }

    public function test_inventario_stock_listar_requiere_permiso(): void
    {
        [, $usuario] = $this->crearUsuarioSinPermisos('sin-inventario@test.com');

        $response = $this->getJson('/api/v1/inventario/stock', $this->headersToken($usuario));

        $response->assertForbidden()
            ->assertJsonPath('resultado', 'R');
    }

    public function test_reportes_listar_requiere_permiso(): void
    {
        [, $usuario] = $this->crearUsuarioSinPermisos('sin-reportes@test.com');

        $response = $this->getJson('/api/v1/reportes/academicos/por-periodo', $this->headersToken($usuario));

        $response->assertForbidden()
            ->assertJsonPath('resultado', 'R');
    }
}
