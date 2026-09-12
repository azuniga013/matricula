<?php

namespace App\Modules\Nivelaciones\Servicios;

use App\Models\NivelAcademico;
use App\Models\OfertaAcademica;

final class ResolutorNivelRecomendado
{
    public function resolver(OfertaAcademica $ofertaExamen, NivelAcademico $nivelAcreditado): ?NivelAcademico
    {
        if ($nivelAcreditado->version_plan_estudio_id !== $ofertaExamen->nivelAcademico?->version_plan_estudio_id) {
            return null;
        }

        return NivelAcademico::query()
            ->where('version_plan_estudio_id', $nivelAcreditado->version_plan_estudio_id)
            ->where('orden', '>', $nivelAcreditado->orden)
            ->where('id', '!=', $ofertaExamen->nivel_academico_id)
            ->orderBy('orden')
            ->first();
    }
}
