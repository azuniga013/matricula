<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_login_exitoso(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJson([
                'resultado' => 'A',
                'mensaje' => 'Inicio de sesión exitoso',
            ])
            ->assertJsonStructure([
                'data' => ['token', 'usuario' => ['id', 'nombre', 'email', 'roles', 'permisos']],
            ]);
    }

    public function test_login_credenciales_invalidas(): void
    {
        User::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_usuario_inactivo(): void
    {
        User::create([
            'name' => 'Inactivo',
            'email' => 'inactivo@example.com',
            'password' => bcrypt('password'),
            'estado' => 'inactivo',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'inactivo@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'resultado' => 'R',
                'mensaje' => 'Su cuenta está inactiva',
            ]);
    }

    public function test_logout(): void
    {
        $user = User::create([
            'name' => 'Logout Test',
            'email' => 'logout@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/logout');

        $response->assertOk()
            ->assertJson([
                'resultado' => 'A',
                'mensaje' => 'Sesión cerrada exitosamente',
            ]);
    }

    public function test_me_sin_autenticar(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401)
            ->assertJson([
                'resultado' => 'R',
                'codigo' => 401,
            ]);
    }

    public function test_me_autenticado(): void
    {
        $user = User::create([
            'name' => 'Auth User',
            'email' => 'auth@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJson([
                'resultado' => 'A',
                'data' => [
                    'nombre' => 'Auth User',
                ],
            ]);
    }

    public function test_login_web_redirige_al_panel(): void
    {
        $user = User::create([
            'name' => 'Web User',
            'email' => 'web@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'web@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }
}
