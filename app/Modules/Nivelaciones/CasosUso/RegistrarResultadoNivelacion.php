<?php

namespace App\Modules\Nivelaciones\CasosUso;

use App\Models\EvaluacionNivelacion;
use App\Models\Matricula;
use App\Models\NivelAcademico;
use App\Modules\Comun\ContextoUsuario;
use App\Modules\Comun\ResultadoCasoUso;
use App\Modules\Nivelaciones\Servicios\ResolutorNivelRecomendado;
use App\Services\ServicioNomenclatura;
use Illuminate\Support\Facades\DB;

final class RegistrarResultadoNivelacion
{
    public function __construct(private ResolutorNivelRecomendado $resolutor) {}

    public function ejecutar(Matricula $matricula, array $datos, ContextoUsuario $contexto): ResultadoCasoUso
    {
        return DB::transaction(function () use ($matricula, $datos, $contexto) {
            $matricula = Matricula::with('ofertaAcademica.nivelAcademico')->lockForUpdate()->findOrFail($matricula->id);
            $oferta = $matricula->ofertaAcademica;
            if (! $oferta?->esNivelacion()) {
                return ResultadoCasoUso::error(422, 'La matrícula no corresponde a una oferta de nivelación', '422_OFERTA_NO_NIVELACION');
            }
            if ($matricula->estado !== 'matriculado') {
                return ResultadoCasoUso::error(422, 'El examen debe tener el pago aprobado antes de registrar el resultado', '422_EXAMEN_NO_PAGADO');
            }
            if (EvaluacionNivelacion::where('matricula_examen_id', $matricula->id)->exists()) {
                return ResultadoCasoUso::error(422, 'Esta matrícula ya tiene un resultado de nivelación', '422_RESULTADO_NIVELACION_EXISTENTE');
            }

            $nivelAcreditado = null;
            $nivelRecomendado = null;
            if ($datos['aprobado']) {
                $nivelAcreditado = NivelAcademico::findOrFail($datos['nivel_acreditado_id']);
                $nivelRecomendado = $this->resolutor->resolver($oferta, $nivelAcreditado);
                if (! $nivelRecomendado) {
                    return ResultadoCasoUso::error(422, 'El nivel acreditado no pertenece al plan del examen o no tiene un nivel posterior recomendado', '422_NIVEL_ACREDITADO_INVALIDO');
                }
            }

            $codigo = app(ServicioNomenclatura::class)->generarCodigo('evaluaciones_nivelacion', 'NIV-{ANIO}-{SECUENCIA:6}', 6, date('Y'))['codigo'];
            $evaluacion = EvaluacionNivelacion::create([
                'codigo' => $codigo,
                'estudiante_id' => $matricula->estudiante_id,
                'matricula_examen_id' => $matricula->id,
                'oferta_examen_id' => $oferta->id,
                'nivel_academico_id' => $nivelAcreditado?->id,
                'nivel_recomendado_id' => $nivelRecomendado?->id,
                'nota_obtenida' => $datos['nota_obtenida'],
                'aprobado' => $datos['aprobado'],
                'observaciones' => $datos['observaciones'] ?? null,
                'autorizado_por' => (string) $contexto->usuarioId(),
                'evaluado_por' => $contexto->usuarioId(),
                'evaluado_en' => now(),
                'estado' => $datos['aprobado'] ? 'aprobada' : 'rechazada',
                'creado_por' => $contexto->usuarioId(),
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]);
            $evaluacion->load('nivelAcademico', 'nivelRecomendado', 'estudiante', 'matriculaExamen', 'ofertaExamen');

            return ResultadoCasoUso::exito('Resultado de nivelación registrado', ['evaluacion' => $evaluacion], 201);
        });
    }
}
