<?php

namespace Database\Seeders;

use App\Models\Aula;
use App\Models\Docente;
use App\Models\Horario;
use App\Models\Modalidad;
use App\Models\NivelAcademico;
use App\Models\OfertaAcademica;
use App\Models\PeriodoAcademico;
use App\Models\Sucursal;
use Illuminate\Database\Seeder;

class OfertaAcademicaSeeder extends Seeder
{
    public function run(): void
    {
        $sucursalSPS = Sucursal::where('codigo', 'SPS')->first();
        $sucursalTGU = Sucursal::where('codigo', 'TGU')->first();

        if (!$sucursalSPS || !$sucursalTGU) {
            return;
        }

        $periodo = PeriodoAcademico::firstOrCreate(
            ['codigo' => '2026-I'],
            [
                'nombre' => 'Primer Semestre 2026',
                'fecha_inicio' => '2026-01-15',
                'fecha_fin' => '2026-06-30',
                'estado' => 'activo',
            ]
        );

        $nivel1 = NivelAcademico::where('codigo', 'ING-1')->first();
        $nivel2 = NivelAcademico::where('codigo', 'ING-2')->first();
        $nivel3 = NivelAcademico::where('codigo', 'ING-3')->first();

        if (!$nivel1) {
            return;
        }

        $modalidadIntensivo = Modalidad::where('codigo', 'INT')->first();
        $modalidadSemi = Modalidad::where('codigo', 'SEMI')->first();
        $modalidadPresencial = Modalidad::where('codigo', 'PRES')->first();

        $horarioMatutino = Horario::firstOrCreate(
            ['codigo' => 'MAT-7AM'],
            [
                'nombre' => 'Matutino 7:00-9:00',
                'hora_inicio' => '07:00',
                'hora_fin' => '09:00',
                'lunes' => true,
                'miercoles' => true,
                'viernes' => true,
            ]
        );

        $horarioVespertino = Horario::firstOrCreate(
            ['codigo' => 'VES-4PM'],
            [
                'nombre' => 'Vespertino 4:00-6:00',
                'hora_inicio' => '16:00',
                'hora_fin' => '18:00',
                'martes' => true,
                'jueves' => true,
            ]
        );

        $docente1 = Docente::firstOrCreate(
            ['codigo' => 'DOC001'],
            [
                'nombre' => 'María',
                'apellido' => 'López',
                'correo' => 'maria@svps.hn',
                'estado' => 'activo',
            ]
        );

        $docente2 = Docente::firstOrCreate(
            ['codigo' => 'DOC002'],
            [
                'nombre' => 'Carlos',
                'apellido' => 'Ramírez',
                'correo' => 'carlos@svps.hn',
                'estado' => 'activo',
            ]
        );

        $aula1 = Aula::firstOrCreate(
            ['codigo' => 'AUL-SPS-01'],
            [
                'sucursal_id' => $sucursalSPS->id,
                'nombre' => 'Aula 1 SPS',
                'capacidad' => 25,
                'estado' => 'activo',
            ]
        );

        $aula2 = Aula::firstOrCreate(
            ['codigo' => 'AUL-TGU-01'],
            [
                'sucursal_id' => $sucursalTGU->id,
                'nombre' => 'Aula 1 TGU',
                'capacidad' => 25,
                'estado' => 'activo',
            ]
        );

        $ofertas = [
            [
                'sucursal_id' => $sucursalSPS->id,
                'nivel_academico_id' => $nivel1->id,
                'modalidad_id' => $modalidadPresencial?->id,
                'horario_id' => $horarioMatutino->id,
                'docente_id' => $docente1->id,
                'aula_id' => $aula1->id,
                'codigo' => 'SPS-2026I-ING1-INT-MAT',
                'cupo_maximo' => 25,
                'estado' => 'abierto',
                'observaciones' => 'Inglés 1 Intensivo Matutino SPS',
            ],
            [
                'sucursal_id' => $sucursalSPS->id,
                'nivel_academico_id' => $nivel2->id,
                'modalidad_id' => $modalidadPresencial?->id,
                'horario_id' => $horarioMatutino->id,
                'docente_id' => $docente1->id,
                'aula_id' => $aula1->id,
                'codigo' => 'SPS-2026I-ING2-INT-MAT',
                'cupo_maximo' => 25,
                'estado' => 'abierto',
                'observaciones' => 'Inglés 2 Intensivo Matutino SPS',
            ],
            [
                'sucursal_id' => $sucursalSPS->id,
                'nivel_academico_id' => $nivel1->id,
                'modalidad_id' => $modalidadPresencial?->id,
                'horario_id' => $horarioVespertino->id,
                'docente_id' => $docente2->id,
                'aula_id' => $aula1->id,
                'codigo' => 'SPS-2026I-ING1-SEMI-VES',
                'cupo_maximo' => 25,
                'estado' => 'abierto',
                'observaciones' => 'Inglés 1 Semi Intensivo Vespertino SPS',
            ],
            [
                'sucursal_id' => $sucursalTGU->id,
                'nivel_academico_id' => $nivel1->id,
                'modalidad_id' => $modalidadPresencial?->id,
                'horario_id' => $horarioMatutino->id,
                'docente_id' => $docente2->id,
                'aula_id' => $aula2->id,
                'codigo' => 'TGU-2026I-ING1-INT-MAT',
                'cupo_maximo' => 25,
                'estado' => 'abierto',
                'observaciones' => 'Inglés 1 Intensivo Matutino TGU',
            ],
            [
                'sucursal_id' => $sucursalTGU->id,
                'nivel_academico_id' => $nivel3->id,
                'modalidad_id' => $modalidadPresencial?->id,
                'horario_id' => $horarioMatutino->id,
                'docente_id' => $docente1->id,
                'aula_id' => $aula2->id,
                'codigo' => 'TGU-2026I-ING3-INT-MAT',
                'cupo_maximo' => 25,
                'estado' => 'borrador',
                'observaciones' => 'Inglés 3 Intensivo Matutino TGU - Pendiente',
            ],
        ];

        foreach ($ofertas as $oferta) {
            OfertaAcademica::firstOrCreate(
                ['codigo' => $oferta['codigo']],
                array_merge($oferta, [
                    'periodo_academico_id' => $periodo->id,
                ])
            );
        }
    }
}
