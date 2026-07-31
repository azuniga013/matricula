<?php

namespace Database\Seeders;

use App\Models\AccesoEstudiante;
use App\Models\Estudiante;
use App\Models\Sucursal;
use Illuminate\Database\Seeder;

class EstudianteSeeder extends Seeder
{
    public function run(): void
    {
        $sucursalSPS = Sucursal::where('codigo', 'SPS')->first();
        $sucursalTGU = Sucursal::where('codigo', 'TGU')->first();

        if (!$sucursalSPS || !$sucursalTGU) {
            return;
        }

        $estudiantes = [
            [
                'codigo' => 'EST-2026-001',
                'nombre' => 'Ana',
                'apellido' => 'Martínez',
                'identidad' => '0801-1995-12345',
                'correo' => 'ana.martinez@gmail.com',
                'telefono' => '9988-7766',
                'sucursal_id' => $sucursalSPS->id,
                'es_primer_ingreso' => false,
                'acceso_email' => 'ana.martinez@gmail.com',
            ],
            [
                'codigo' => 'EST-2026-002',
                'nombre' => 'Carlos',
                'apellido' => 'Rivera',
                'identidad' => '0801-1998-67890',
                'correo' => 'carlos.rivera@gmail.com',
                'telefono' => '9911-2233',
                'sucursal_id' => $sucursalSPS->id,
                'es_primer_ingreso' => true,
                'acceso_email' => null,
            ],
            [
                'codigo' => 'EST-2026-003',
                'nombre' => 'María',
                'apellido' => 'López',
                'identidad' => '0801-2000-11111',
                'correo' => 'maria.lopez@gmail.com',
                'telefono' => '9944-5566',
                'sucursal_id' => $sucursalTGU->id,
                'es_primer_ingreso' => false,
                'acceso_email' => 'maria.lopez@gmail.com',
            ],
            [
                'codigo' => 'EST-2026-004',
                'nombre' => 'José',
                'apellido' => 'García',
                'identidad' => '0801-1997-22222',
                'correo' => 'jose.garcia@gmail.com',
                'telefono' => '9977-8899',
                'sucursal_id' => $sucursalTGU->id,
                'es_primer_ingreso' => true,
                'acceso_email' => null,
            ],
        ];

        foreach ($estudiantes as $data) {
            $accesoEmail = $data['acceso_email'];
            unset($data['acceso_email']);

            $estudiante = Estudiante::firstOrCreate(
                ['codigo' => $data['codigo']],
                array_merge($data, ['estado' => 'activo'])
            );

            if ($accesoEmail) {
                AccesoEstudiante::firstOrCreate(
                    ['estudiante_id' => $estudiante->id],
                    [
                        'email' => $accesoEmail,
                        'password' => 'password',
                        'estado' => 'activo',
                    ]
                );
            }
        }
    }
}
