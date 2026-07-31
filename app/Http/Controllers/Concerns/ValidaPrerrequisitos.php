<?php

namespace App\Http\Controllers\Concerns;

use App\Models\{EvaluacionNivelacion, HistorialAcademico, Matricula, NivelAcademico, OfertaAcademica};

trait ValidaPrerrequisitos
{
    private function validarPrerrequisitos(int $estudianteId, int $ofertaAcademicaId): ?string
    {
        $oferta = OfertaAcademica::with('nivelAcademico.prerrequisitos')->findOrFail($ofertaAcademicaId);
        $nivel = $oferta->nivelAcademico;

        if (!$nivel || $nivel->prerrequisitos->isEmpty()) {
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

        $idsCumplidosPorCalificacion = \App\Models\Calificacion::with('matricula.ofertaAcademica:nivel_academico_id,id')
            ->where('estudiante_id', $estudianteId)
            ->get()
            ->filter(fn ($calificacion) => $calificacion->estaAprobada())
            ->pluck('matricula.ofertaAcademica.nivel_academico_id')
            ->filter()
            ->toArray();

        $idsCumplidos = array_unique(array_merge($idsCumplidos, $idsCumplidosPorCalificacion));

        $matriculasActivas = Matricula::where('estudiante_id', $estudianteId)
            ->whereIn('estado', ['reservada', 'en_revision', 'matriculado'])
            ->whereHas('ofertaAcademica', fn($q) => $q->whereIn('nivel_academico_id', $prerrequisitosIds))
            ->get()
            ->pluck('ofertaAcademica.nivel_academico_id')
            ->toArray();

        $idsCumplidos = array_unique(array_merge($idsCumplidos, $matriculasActivas));

        $faltantes = $nivel->prerrequisitos->filter(function ($prerrequisito) use ($idsCumplidos, $nivelacionesAprobadas, $nivel) {
            if (in_array($prerrequisito->id, $idsCumplidos)) {
                return false;
            }

            return !$nivelacionesAprobadas->contains(function ($nivelacion) use ($prerrequisito, $nivel) {
                $nivelNivelacion = $nivelacion->nivelAcademico;
                if (!$nivelNivelacion) {
                    return false;
                }

                return $nivelNivelacion->version_plan_estudio_id === $nivel->version_plan_estudio_id
                    && $nivelNivelacion->orden >= $prerrequisito->orden;
            });
        });

        if ($faltantes->isEmpty()) {
            return null;
        }

        $nombres = $faltantes->pluck('nombre')->implode(', ');
        return "Debe aprobar primero los siguientes niveles: {$nombres}";
    }
}
