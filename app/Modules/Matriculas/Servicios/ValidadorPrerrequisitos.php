<?php

namespace App\Modules\Matriculas\Servicios;

use App\Models\Calificacion;
use App\Models\EvaluacionNivelacion;
use App\Models\HistorialAcademico;
use App\Models\Matricula;
use App\Models\OfertaAcademica;

final class ValidadorPrerrequisitos
{
    public function validar(int $estudianteId, int $ofertaAcademicaId): ?string
    {
        $oferta = OfertaAcademica::with('nivelAcademico.prerrequisitos')->findOrFail($ofertaAcademicaId);
        $nivel = $oferta->nivelAcademico;

        if (! $nivel || $nivel->prerrequisitos->isEmpty()) {
            return null;
        }

        $prerrequisitosIds = $nivel->prerrequisitos->pluck('id')->toArray();
        $nivelacionesAprobadas = EvaluacionNivelacion::with('nivelAcademico:id,version_plan_estudio_id,orden')
            ->where('estudiante_id', $estudianteId)
            ->where('aprobado', true)
            ->get();

        $idsCumplidos = HistorialAcademico::where('estudiante_id', $estudianteId)
            ->whereIn('nivel_academico_id', $prerrequisitosIds)
            ->where('estado', 'aprobado')
            ->pluck('nivel_academico_id')
            ->toArray();

        $idsCumplidosPorCalificacion = Calificacion::with('matricula.ofertaAcademica:nivel_academico_id,id')
            ->where('estudiante_id', $estudianteId)
            ->get()
            ->filter(fn ($calificacion) => $calificacion->estaAprobada())
            ->pluck('matricula.ofertaAcademica.nivel_academico_id')
            ->filter()
            ->toArray();

        $idsCumplidos = array_unique(array_merge($idsCumplidos, $idsCumplidosPorCalificacion));

        $idsCumplidosAdministrativamente = Matricula::where('estudiante_id', $estudianteId)
            ->whereHas('ofertaAcademica', fn ($q) => $q->whereIn('nivel_academico_id', $prerrequisitosIds))
            ->whereHas('obligaciones')
            ->whereDoesntHave('obligaciones', fn ($q) => $q->whereIn('estado', ['pendiente', 'parcial']))
            ->get()
            ->pluck('ofertaAcademica.nivel_academico_id')
            ->filter()
            ->unique()
            ->toArray();

        $faltantesAcademicos = [];
        $faltantesAdministrativos = [];

        foreach ($nivel->prerrequisitos as $prerrequisito) {
            $cumplePorHistorialOCalificacion = in_array($prerrequisito->id, $idsCumplidos, true);
            $cumplePorNivelacion = $nivelacionesAprobadas->contains(function ($nivelacion) use ($prerrequisito, $nivel) {
                $nivelNivelacion = $nivelacion->nivelAcademico;
                if (! $nivelNivelacion) {
                    return false;
                }

                return $nivelNivelacion->version_plan_estudio_id === $nivel->version_plan_estudio_id
                    && $nivelNivelacion->orden >= $prerrequisito->orden;
            });

            if (! $cumplePorHistorialOCalificacion && ! $cumplePorNivelacion) {
                $faltantesAcademicos[] = $prerrequisito->nombre;
                continue;
            }

            if (! $cumplePorNivelacion && ! in_array($prerrequisito->id, $idsCumplidosAdministrativamente, true)) {
                $faltantesAdministrativos[] = $prerrequisito->nombre;
            }
        }

        if (empty($faltantesAcademicos) && empty($faltantesAdministrativos)) {
            return null;
        }

        if (! empty($faltantesAcademicos)) {
            return 'Debe aprobar primero los siguientes niveles: '.implode(', ', $faltantesAcademicos);
        }

        return 'Debe finalizar administrativamente y pagar los siguientes niveles: '.implode(', ', $faltantesAdministrativos);
    }
}
