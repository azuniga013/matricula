<?php

namespace App\Modules\Calificaciones\Repositorios;

use App\Models\Calificacion;
use App\Models\Matricula;
use App\Models\OfertaAcademica;

final class EloquentCalificacionRepositorio implements CalificacionRepositorio
{
    public function buscarOferta(int $id): ?OfertaAcademica
    {
        return OfertaAcademica::find($id);
    }

    public function matriculaActivaEnOferta(int $estudianteId, int $ofertaId): ?Matricula
    {
        return Matricula::where('estudiante_id', $estudianteId)
            ->where('oferta_academica_id', $ofertaId)
            ->where('estado', 'matriculado')
            ->first();
    }

    public function buscarConOferta(int $id): ?Calificacion
    {
        return Calificacion::with('ofertaAcademica')->find($id);
    }

    public function crearOActualizar(array $claves, array $atributos): Calificacion
    {
        $calificacion = Calificacion::firstOrNew($claves);

        if ($calificacion->exists) {
            unset($atributos['codigo'], $atributos['creado_por'], $atributos['creado_en']);
        }

        $calificacion->fill($atributos);
        $calificacion->save();

        return $calificacion;
    }

    public function actualizar(Calificacion $calificacion, array $atributos): void
    {
        $calificacion->update($atributos);
    }

    public function cargarRelaciones(Calificacion $calificacion): void
    {
        $calificacion->load(
            'matricula',
            'ofertaAcademica.nivelAcademico',
            'ofertaAcademica.periodoAcademico',
            'ofertaAcademica.modalidad'
        );
    }
}
