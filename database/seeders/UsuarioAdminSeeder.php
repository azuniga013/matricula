<?php

namespace Database\Seeders;

use App\Models\AlcanceRol;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioAdminSeeder extends Seeder
{
    public function run(): void
    {
        $rolSuperadmin = Rol::where('codigo', 'SUPERADMIN')->first();

        if (!$rolSuperadmin) {
            return;
        }

        $admin = User::updateOrCreate(
            ['email' => 'admin@cursossvp.hn'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'estado' => 'activo',
            ]
        );

        $admin->roles()->syncWithoutDetaching([$rolSuperadmin->id]);

        AlcanceRol::updateOrCreate(
            ['rol_id' => $rolSuperadmin->id, 'tipo' => 'global'],
            ['estado' => 'activo']
        );
    }
}
