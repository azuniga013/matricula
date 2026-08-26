<?php

namespace App\Services;

use App\Models\AsistenciaEstudiante;
use App\Models\Calificacion;

class RecalcularFaltasCalificacion
{
    public function ejecutar(int $matriculaId): void
    {
        $faltas = AsistenciaEstudiante::where('matricula_id', $matriculaId)
            ->where('cuenta_como_falta', true)
            ->count();

        Calificacion::where('matricula_id', $matriculaId)->update([
            'faltas' => $faltas,
            'actualizado_en' => now(),
        ]);
    }
}
