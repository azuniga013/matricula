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
use Illuminate\Support\Str;

class OfertaAcademicaSeeder extends Seeder
{
    public function run(): void
    {
        $periodo = PeriodoAcademico::query()
            ->where('estado', 'activo')
            ->orderByDesc('fecha_inicio')
            ->first();

        if (! $periodo) {
            $periodo = PeriodoAcademico::firstOrCreate(
                ['codigo' => '2026-I'],
                [
                    'nombre' => 'Periodo Académico 2026-I',
                    'fecha_inicio' => '2026-01-15',
                    'fecha_fin' => '2026-06-30',
                    'estado' => 'activo',
                ]
            );
        }

        $modalidadAtencion = Modalidad::query()
            ->where('tipo', 'atencion')
            ->where('codigo', 'PRES')
            ->first() ?? Modalidad::query()->where('tipo', 'atencion')->orderBy('id')->first();

        $horarios = Horario::query()->activos()->ordenados()->get()->values();
        $docentes = Docente::query()->where('estado', 'activo')->orderBy('id')->get()->values();
        $sucursales = Sucursal::query()->where('estado', 'activo')->orderBy('id')->get();

        if (! $modalidadAtencion || $horarios->isEmpty() || $docentes->isEmpty() || $sucursales->isEmpty()) {
            return;
        }

        $niveles = NivelAcademico::query()
            ->with(['versionPlanEstudio.planEstudio'])
            ->where('estado', 'activo')
            ->orderBy('version_plan_estudio_id')
            ->orderBy('orden')
            ->get();

        foreach ($sucursales as $sucursal) {
            $aulas = Aula::query()
                ->where('sucursal_id', $sucursal->id)
                ->where('estado', 'activo')
                ->orderBy('id')
                ->get()
                ->values();

            if ($aulas->isEmpty()) {
                continue;
            }

            foreach ($niveles as $index => $nivel) {
                $horario = $horarios[$index % $horarios->count()];
                $docente = $docentes[$index % $docentes->count()];
                $aula = $aulas[$index % $aulas->count()];
                $planCodigo = $nivel->versionPlanEstudio?->planEstudio?->codigo ?? 'PLAN';
                $periodoCodigo = Str::upper(str_replace(['-', ' '], '', $periodo->codigo));
                $codigo = Str::upper($sucursal->codigo . '-' . $periodoCodigo . '-' . $planCodigo . '-N' . str_pad((string) $nivel->orden, 2, '0', STR_PAD_LEFT));

                OfertaAcademica::updateOrCreate(
                    ['codigo' => $codigo],
                    [
                        'sucursal_id' => $sucursal->id,
                        'periodo_academico_id' => $periodo->id,
                        'nivel_academico_id' => $nivel->id,
                        'modalidad_id' => $modalidadAtencion->id,
                        'horario_id' => $horario->id,
                        'docente_id' => $docente->id,
                        'aula_id' => $aula->id,
                        'cupo_maximo' => 25,
                        'cupos_reservados' => 0,
                        'cupos_matriculados' => 0,
                        'estado' => 'abierto',
                        'acepta_cambios_horario' => true,
                        'observaciones' => trim(($nivel->versionPlanEstudio?->planEstudio?->nombre ?? 'Plan') . ' · ' . $nivel->nombre),
                    ]
                );
            }
        }
    }
}
