<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialAcademico extends Model
{
    use HasFactory;

    protected $table = 'historial_academico';
    public $timestamps = false;

    protected $fillable = [
        'codigo', 'estudiante_id', 'matricula_id', 'oferta_academica_id',
        'nivel_academico_id', 'periodo_academico_id', 'estado',
        'nota_final', 'faltas', 'observaciones', 'creado_por',
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

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class, 'matricula_id');
    }

    public function ofertaAcademica(): BelongsTo
    {
        return $this->belongsTo(OfertaAcademica::class, 'oferta_academica_id');
    }

    public function nivelAcademico(): BelongsTo
    {
        return $this->belongsTo(NivelAcademico::class, 'nivel_academico_id');
    }

    public function periodoAcademico(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_academico_id');
    }

    public function scopePorEstudiante($query, int $estudianteId)
    {
        return $query->where('historial_academico.estudiante_id', $estudianteId);
    }

    public function scopeAprobados($query)
    {
        return $query->where('historial_academico.estado', 'aprobado');
    }
}
