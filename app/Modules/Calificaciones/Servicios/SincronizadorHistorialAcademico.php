<?php

namespace App\Modules\Calificaciones\Servicios;

use App\Models\Calificacion;
use App\Models\HistorialAcademico;

final class SincronizadorHistorialAcademico
{
    public function sincronizar(Calificacion $calificacion): void
    {
        $matricula = $calificacion->matricula;
        $oferta = $calificacion->ofertaAcademica()->with(['nivelAcademico', 'periodoAcademico', 'modalidad'])->first();

        if (! $matricula || ! $oferta) {
            return;
        }

        $estado = 'matriculado';
        if ($calificacion->nota_final !== null) {
            $estado = $calificacion->estaAprobada() ? 'aprobado' : 'reprobado';
        }

        $codigoHistorial = substr('HIS-'.$calificacion->codigo, 0, 50);

        $historial = HistorialAcademico::where('estudiante_id', $calificacion->estudiante_id)
            ->where('matricula_id', $calificacion->matricula_id)
            ->first();

        $atributos = [
            'oferta_academica_id' => $oferta->id,
            'nivel_academico_id' => $oferta->nivel_academico_id,
            'periodo_academico_id' => $oferta->periodo_academico_id,
            'estado' => $estado,
            'nota_final' => $calificacion->nota_final,
            'faltas' => $calificacion->faltas ?? 0,
            'observaciones' => $calificacion->observaciones,
        ];

        if ($historial) {
            $historial->update($atributos);

            return;
        }

        HistorialAcademico::create([
            'codigo' => $codigoHistorial,
            'estudiante_id' => $calificacion->estudiante_id,
            'matricula_id' => $calificacion->matricula_id,
            ...$atributos,
        ]);
    }
}
