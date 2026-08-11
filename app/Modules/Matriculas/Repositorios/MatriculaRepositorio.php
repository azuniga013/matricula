<?php

namespace App\Modules\Matriculas\Repositorios;

use App\Models\Matricula;
use App\Models\OfertaAcademica;

interface MatriculaRepositorio
{
    public function buscarConBloqueo(int $id): ?Matricula;

    public function ofertaConDetallesParaBloqueo(int $id): ?OfertaAcademica;

    public function ofertaParaBloqueo(int $id): ?OfertaAcademica;

    public function existeMatriculaActivaEnOferta(int $estudianteId, int $ofertaId): bool;

    public function tienePlanActivoDiferente(int $estudianteId, int $planNuevoId): bool;

    public function crearMatricula(array $atributos): Matricula;

    public function reservarCupo(OfertaAcademica $oferta): void;

    public function confirmarMatricula(Matricula $matricula, int $usuarioId): void;

    public function marcarOfertaLlenaSiCorresponde(OfertaAcademica $oferta): void;

    public function cancelarMatricula(Matricula $matricula, string $motivo, int $usuarioId): void;

    public function liberarCupos(OfertaAcademica $oferta, string $estadoAnterior): void;

    public function rechazarObligaciones(Matricula $matricula): void;
}
