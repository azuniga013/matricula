<?php

namespace App\Modules\Calificaciones\Repositorios;

use App\Models\Calificacion;
use App\Models\Matricula;
use App\Models\OfertaAcademica;

interface CalificacionRepositorio
{
    public function buscarOferta(int $id): ?OfertaAcademica;

    public function matriculaActivaEnOferta(int $estudianteId, int $ofertaId): ?Matricula;

    public function buscarConOferta(int $id): ?Calificacion;

    public function crearOActualizar(array $claves, array $atributos): Calificacion;

    public function actualizar(Calificacion $calificacion, array $atributos): void;

    public function cargarRelaciones(Calificacion $calificacion): void;
}
