<?php

namespace App\Modules\Nivelaciones\CasosUso;

use App\Models\EvaluacionNivelacion;
use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;
use Illuminate\Support\Facades\DB;

final class AnularResultadoNivelacion
{
    public function ejecutar(EvaluacionNivelacion $evaluacion, string $motivo, ContextoUsuario $contexto): ResultadoCasoUso
    {
        return DB::transaction(function () use ($evaluacion, $motivo, $contexto) {
            $evaluacion = EvaluacionNivelacion::lockForUpdate()->findOrFail($evaluacion->id);
            if ($evaluacion->estado === 'anulada') {
                return ResultadoCasoUso::error(422, 'El resultado de nivelación ya está anulado', '422_RESULTADO_NIVELACION_ANULADO');
            }

            $evaluacion->update([
                'estado' => 'anulada',
                'aprobado' => false,
                'motivo_anulacion' => $motivo,
                'evaluado_por' => $contexto->usuarioId(),
                'evaluado_en' => now(),
                'actualizado_en' => now(),
            ]);

            return ResultadoCasoUso::exito('Resultado de nivelación anulado', ['evaluacion' => $evaluacion->fresh()]);
        });
    }
}
