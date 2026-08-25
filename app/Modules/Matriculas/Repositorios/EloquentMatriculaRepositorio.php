<?php

namespace App\Modules\Matriculas\Repositorios;

use App\Models\Matricula;
use App\Models\OfertaAcademica;

final class EloquentMatriculaRepositorio implements MatriculaRepositorio
{
    public function buscarConBloqueo(int $id): ?Matricula
    {
        return Matricula::lockForUpdate()->find($id);
    }

    public function ofertaConDetallesParaBloqueo(int $id): ?OfertaAcademica
    {
        return OfertaAcademica::with('nivelAcademico.versionPlanEstudio', 'planCobro.detalles.conceptoPago')
            ->lockForUpdate()
            ->find($id);
    }

    public function ofertaParaBloqueo(int $id): ?OfertaAcademica
    {
        return OfertaAcademica::lockForUpdate()->find($id);
    }

    public function existeMatriculaActivaEnOferta(int $estudianteId, int $ofertaId): bool
    {
        return Matricula::where('estudiante_id', $estudianteId)
            ->where('oferta_academica_id', $ofertaId)
            ->whereIn('matriculas.estado', ['reservada', 'en_revision', 'matriculado'])
            ->exists();
    }

    public function tienePlanActivoDiferente(int $estudianteId, int $planNuevoId): bool
    {
        return Matricula::where('estudiante_id', $estudianteId)
            ->where('estado', 'matriculado')
            ->whereHas('ofertaAcademica.nivelAcademico.versionPlanEstudio', function ($q) use ($planNuevoId) {
                $q->where('plan_estudio_id', '!=', $planNuevoId);
            })
            ->exists();
    }

    public function crearMatricula(array $atributos): Matricula
    {
        return Matricula::create($atributos);
    }

    public function reservarCupo(OfertaAcademica $oferta): void
    {
        $oferta->increment('cupos_reservados');
    }

    public function confirmarMatricula(Matricula $matricula, int $usuarioId): void
    {
        $matricula->update([
            'estado' => 'en_revision',
            'fecha_confirmacion' => now(),
            'actualizado_por' => $usuarioId,
            'actualizado_en' => now(),
        ]);
    }

    public function marcarOfertaLlenaSiCorresponde(OfertaAcademica $oferta): void
    {
        if ($oferta->cuposDisponibles() <= 0) {
            $oferta->update(['estado' => 'lleno']);
        }
    }

    public function cancelarMatricula(Matricula $matricula, string $motivo, int $usuarioId): void
    {
        $matricula->update([
            'estado' => 'rechazado',
            'observaciones' => $motivo,
            'actualizado_por' => $usuarioId,
            'actualizado_en' => now(),
        ]);
    }

    public function liberarCupos(OfertaAcademica $oferta, string $estadoAnterior): void
    {
        if (in_array($estadoAnterior, ['reservada', 'en_revision'])) {
            $oferta->decrement('cupos_reservados');
        } elseif ($estadoAnterior === 'matriculado') {
            $oferta->decrement('cupos_matriculados');
            if ($oferta->estado === 'lleno') {
                $oferta->update(['estado' => 'abierto']);
            }
        }
    }

    public function rechazarObligaciones(Matricula $matricula): void
    {
        $matricula->obligaciones()->update(['estado' => 'rechazado']);
    }
}
