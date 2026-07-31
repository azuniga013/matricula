<?php

namespace Tests\Feature;

use App\Models\AlcanceRol;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Tests\TestCase;

class AlcanceTest extends TestCase
{
    public function test_usuario_con_alcance_global_ve_todo(): void
    {
        $rol = Rol::create(['codigo' => 'GLOBAL', 'nombre' => 'Global', 'estado' => 'activo']);
        AlcanceRol::create(['rol_id' => $rol->id, 'tipo' => 'global', 'estado' => 'activo']);

        $usuario = User::create([
            'name' => 'Global User',
            'email' => 'global@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $usuario->roles()->attach($rol->id, ['estado' => 'activo']);

        $this->assertTrue($usuario->tieneAlcanceGlobal());
    }

    public function test_usuario_solo_sucursales_asignadas(): void
    {
        $sucursalSPS = Sucursal::create(['codigo' => 'SPS', 'nombre' => 'SPS', 'estado' => 'activo']);
        $sucursalTGU = Sucursal::create(['codigo' => 'TGU', 'nombre' => 'TGU', 'estado' => 'activo']);

        $usuario = User::create([
            'name' => 'SPS User',
            'email' => 'sps@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $usuario->sucursales()->attach($sucursalSPS->id, ['estado' => 'activo']);

        $ids = $usuario->idsSucursalesAsignadas();

        $this->assertContains($sucursalSPS->id, $ids);
        $this->assertNotContains($sucursalTGU->id, $ids);
        $this->assertFalse($usuario->tieneAlcanceGlobal());
    }

    public function test_usuario_bloqueado(): void
    {
        $usuario = User::create([
            'name' => 'Bloqueado',
            'email' => 'bloqueado@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
            'bloqueado_hasta' => now()->addHour(),
        ]);

        $this->assertTrue($usuario->estaBloqueado());
    }

    public function test_usuario_no_bloqueado(): void
    {
        $usuario = User::create([
            'name' => 'No Bloqueado',
            'email' => 'nobloqueado@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
            'bloqueado_hasta' => null,
        ]);

        $this->assertFalse($usuario->estaBloqueado());
    }
}
