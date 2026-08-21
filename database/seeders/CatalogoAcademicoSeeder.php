<?php

namespace Database\Seeders;

use App\Models\DepartamentoAcademico;
use App\Models\Modalidad;
use App\Models\NivelAcademico;
use App\Models\PlanEstudio;
use App\Models\VersionPlanEstudio;
use Illuminate\Database\Seeder;

class CatalogoAcademicoSeeder extends Seeder
{
    public function run(): void
    {
        $departamento = DepartamentoAcademico::updateOrCreate(
            ['codigo' => 'ING'],
            ['nombre' => 'Inglés', 'descripcion' => 'Área de formación en inglés']
        );

        $regimenes = Modalidad::query()
            ->whereIn('codigo', ['INT', 'SEMI', 'INF-INT', 'INF-SEMI'])
            ->get()
            ->keyBy('codigo');

        $planes = [
            [
                'codigo' => 'PLAN-INT',
                'nombre' => 'Intensivo',
                'regimen_codigo' => 'INT',
                'niveles' => [
                    'Phonics',
                    'A1',
                    'A1+',
                    'B1',
                    'B1+',
                    'English Communication Skills',
                ],
            ],
            [
                'codigo' => 'PLAN-SEMI',
                'nombre' => 'Semi Intensivo',
                'regimen_codigo' => 'SEMI',
                'niveles' => [
                    'Phonics A',
                    'Phonics B',
                    'A1.1',
                    'A1.2',
                    'A2.1',
                    'A2.2',
                    'B1.1',
                    'B1.2',
                    'B1.+.1',
                    'B1.+.2',
                    'English Communication Skills A',
                    'English Communication Skills B',
                ],
            ],
            [
                'codigo' => 'PLAN-INF-INT',
                'nombre' => 'Infantil Intensivo',
                'regimen_codigo' => 'INF-INT',
                'niveles' => [
                    'Nivel 1',
                    'Nivel 2',
                    'Nivel 3',
                    'Nivel 4',
                    'Nivel 5',
                    'Nivel 6',
                ],
            ],
            [
                'codigo' => 'PLAN-INF-SEMI',
                'nombre' => 'Infantil Semi Intensivo',
                'regimen_codigo' => 'INF-SEMI',
                'niveles' => [
                    'Nivel 1 A',
                    'Nivel 1 B',
                    'Nivel 2 A',
                    'Nivel 2 B',
                    'Nivel 3 A',
                    'Nivel 3 B',
                    'Nivel 4 A',
                    'Nivel 4 B',
                    'Nivel 5 A',
                    'Nivel 5 B',
                    'Nivel 6 A',
                    'Nivel 6 B',
                ],
            ],
        ];

        foreach ($planes as $planData) {
            $plan = PlanEstudio::updateOrCreate(
                ['codigo' => $planData['codigo']],
                [
                    'departamento_academico_id' => $departamento->id,
                    'nombre' => $planData['nombre'],
                    'descripcion' => 'Plan de estudio ' . $planData['nombre'],
                    'estado' => 'activo',
                ]
            );

            $version = VersionPlanEstudio::updateOrCreate(
                ['plan_estudio_id' => $plan->id, 'numero_version' => 1],
                [
                    'vigente_desde' => '2026-01-01',
                    'vigente_hasta' => null,
                    'estado' => 'activo',
                ]
            );

            $regimen = $regimenes->get($planData['regimen_codigo']);
            $anterior = null;

            foreach ($planData['niveles'] as $indice => $nombreNivel) {
                $codigoNivel = $planData['codigo'] . '-N' . str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT);

                $nivel = NivelAcademico::updateOrCreate(
                    ['codigo' => $codigoNivel],
                    [
                        'version_plan_estudio_id' => $version->id,
                        'regimen_academico_id' => $regimen?->id,
                        'nombre' => $nombreNivel,
                        'orden' => $indice + 1,
                        'nota_minima_aprobar' => 80,
                        'faltas_maximas_permitidas' => 7,
                        'estado' => 'activo',
                    ]
                );

                if ($regimen) {
                    $nivel->modalidades()->sync([$regimen->id]);
                }

                $nivel->prerrequisitos()->sync($anterior ? [$anterior->id] : []);
                $anterior = $nivel;
            }
        }
    }
}
