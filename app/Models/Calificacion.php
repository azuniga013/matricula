<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Calificacion extends Model
{
    use HasFactory;

    protected $table = 'calificaciones';
    public $timestamps = false;

    protected $fillable = [
        'codigo', 'matricula_id', 'estudiante_id', 'oferta_academica_id',
        'nota_final', 'faltas', 'estado', 'observaciones', 'docente_id',
        'creado_por', 'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'nota_final' => 'decimal:2',
            'faltas' => 'integer',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class, 'matricula_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function ofertaAcademica(): BelongsTo
    {
        return $this->belongsTo(OfertaAcademica::class, 'oferta_academica_id');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    public function estaAprobada(): bool
    {
        if ($this->nota_final === null) return false;

        $oferta = $this->ofertaAcademica;
        $nivel = $oferta?->nivelAcademico;
        $modalidad = $oferta?->modalidad;

        $notaMinima = $nivel->nota_minima_aprobar ?? 80;

        $faltasMaximas = 7;
        if ($modalidad && $modalidad->codigo === 'SEMI') {
            $faltasMaximas = 3;
        }

        return $this->nota_final >= $notaMinima && $this->faltas <= $faltasMaximas;
    }

    public function scopePorOferta($query, int $ofertaId)
    {
        return $query->where('calificaciones.oferta_academica_id', $ofertaId);
    }

    public function scopePorEstudiante($query, int $estudianteId)
    {
        return $query->where('calificaciones.estudiante_id', $estudianteId);
    }
}
