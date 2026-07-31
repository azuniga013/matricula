<?php

namespace Database\Seeders;

use App\Models\DepartamentoAcademico;
use App\Models\PlanEstudio;
use App\Models\VersionPlanEstudio;
use App\Models\NivelAcademico;
use App\Models\Modalidad;
use Illuminate\Database\Seeder;

class CatalogoAcademicoSeeder extends Seeder
{
    public function run(): void
    {
        $departamento = DepartamentoAcademico::updateOrCreate(
            ['codigo' => 'ING'],
            ['nombre' => 'Inglés', 'descripcion' => 'Área de formación en inglés']
        );

        $plan = PlanEstudio::updateOrCreate(
            ['codigo' => 'ING-GEN'],
            [
                'departamento_academico_id' => $departamento->id,
                'nombre' => 'Inglés General',
                'descripcion' => 'Plan general de inglés',
            ]
        );

        $version = VersionPlanEstudio::updateOrCreate(
            ['plan_estudio_id' => $plan->id, 'numero_version' => 1],
            [
                'vigente_desde' => '2026-01-01',
                'estado' => 'activo',
            ]
        );

        $modalidadIntensivo = Modalidad::where('codigo', 'INT')->first();
        $modalidadSemi = Modalidad::where('codigo', 'SEMI')->first();

        $regimenIntensivo = $modalidadIntensivo?->id;

        $nivel1 = NivelAcademico::updateOrCreate(
            ['codigo' => 'ING-1'],
            [
                'version_plan_estudio_id' => $version->id,
                'regimen_academico_id' => $regimenIntensivo,
                'nombre' => 'Inglés 1 - Phonics',
                'orden' => 1,
                'nota_minima_aprobar' => 80,
                'faltas_maximas_permitidas' => 7,
            ]
        );

        $nivel2 = NivelAcademico::updateOrCreate(
            ['codigo' => 'ING-2'],
            [
                'version_plan_estudio_id' => $version->id,
                'regimen_academico_id' => $regimenIntensivo,
                'nombre' => 'Inglés 2 - Beginner',
                'orden' => 2,
                'nota_minima_aprobar' => 80,
                'faltas_maximas_permitidas' => 7,
            ]
        );

        $nivel3 = NivelAcademico::updateOrCreate(
            ['codigo' => 'ING-3'],
            [
                'version_plan_estudio_id' => $version->id,
                'regimen_academico_id' => $regimenIntensivo,
                'nombre' => 'Inglés 3 - Elementary',
                'orden' => 3,
                'nota_minima_aprobar' => 80,
                'faltas_maximas_permitidas' => 7,
            ]
        );

        if ($modalidadIntensivo) {
            $nivel1->modalidades()->syncWithoutDetaching([$modalidadIntensivo->id]);
            $nivel2->modalidades()->syncWithoutDetaching([$modalidadIntensivo->id]);
            $nivel3->modalidades()->syncWithoutDetaching([$modalidadIntensivo->id]);
        }

        if ($modalidadSemi) {
            $nivel1->modalidades()->syncWithoutDetaching([$modalidadSemi->id]);
            $nivel2->modalidades()->syncWithoutDetaching([$modalidadSemi->id]);
            $nivel3->modalidades()->syncWithoutDetaching([$modalidadSemi->id]);
        }

        $nivel2->prerrequisitos()->syncWithoutDetaching([$nivel1->id]);
        $nivel3->prerrequisitos()->syncWithoutDetaching([$nivel2->id]);
    }
}
