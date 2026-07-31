<?php

namespace App\Http\Controllers\Concerns;

use App\Models\{Horario, Matricula, OfertaAcademica};

trait ValidaConflictoHorario
{
    private function diasActivos(Horario $horario): array
    {
        $dias = [];
        foreach (['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'] as $dia) {
            if ($horario->$dia) {
                $dias[] = $dia;
            }
        }
        return $dias;
    }

    private function validarConflictoHorario(int $estudianteId, int $ofertaAcademicaId, ?int $excluirMatriculaId = null): ?string
    {
        $nuevaOferta = OfertaAcademica::with('horario')->findOrFail($ofertaAcademicaId);
        $nuevoHorario = $nuevaOferta->horario;
        if (!$nuevoHorario) return null;

        $periodoId = $nuevaOferta->periodo_academico_id;

        $nuevosDias = $this->diasActivos($nuevoHorario);
        if (empty($nuevosDias)) return null;

        $query = Matricula::where('estudiante_id', $estudianteId)
            ->whereIn('estado', ['reservada', 'en_revision', 'matriculado'])
            ->whereHas('ofertaAcademica', fn ($q) => $q->where('periodo_academico_id', $periodoId))
            ->where('oferta_academica_id', '!=', $ofertaAcademicaId)
            ->with('ofertaAcademica.horario');

        if ($excluirMatriculaId) {
            $query->where('id', '!=', $excluirMatriculaId);
        }

        $matriculasActivas = $query->get();

        foreach ($matriculasActivas as $matricula) {
            $horarioExistente = $matricula->ofertaAcademica->horario;
            if (!$horarioExistente) continue;

            $diasExistentes = $this->diasActivos($horarioExistente);
            $diasComunes = array_intersect($nuevosDias, $diasExistentes);
            if (empty($diasComunes)) continue;

            if ($this->horariosSeSuperponen($nuevoHorario, $horarioExistente)) {
                $nombreNivel = $matricula->ofertaAcademica?->nivelAcademico?->nombre ?? 'otra oferta';
                $nombreHorario = $horarioExistente->nombre ?? $horarioExistente->codigo ?? 'desconocido';
                return "El horario choca con su matrícula activa en {$nombreNivel} ({$nombreHorario})";
            }
        }

        return null;
    }

    private function horariosSeSuperponen(Horario $a, Horario $b): bool
    {
        if ($a->es_24_horas || $b->es_24_horas) return true;

        $aInicio = strtotime($a->hora_inicio);
        $aFin = strtotime($a->hora_fin);
        $bInicio = strtotime($b->hora_inicio);
        $bFin = strtotime($b->hora_fin);

        return $aInicio < $bFin && $aFin > $bInicio;
    }
}
