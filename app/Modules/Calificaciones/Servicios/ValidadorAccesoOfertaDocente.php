<?php

namespace App\Modules\Calificaciones\Servicios;

use App\Models\OfertaAcademica;

final class ValidadorAccesoOfertaDocente
{
    public function puedeGestionar(?int $docenteId, ?OfertaAcademica $oferta): bool
    {
        return ! $docenteId || ($oferta && (int) $oferta->docente_id === $docenteId);
    }
}
