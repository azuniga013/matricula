<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluacionNivelacion extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones_nivelacion';
    public $timestamps = false;

    protected $fillable = [
        'codigo', 'estudiante_id', 'matricula_examen_id', 'oferta_examen_id',
        'nivel_academico_id', 'nivel_recomendado_id', 'nota_obtenida',
        'aprobado', 'observaciones', 'motivo_anulacion', 'autorizado_por',
        'evaluado_por', 'evaluado_en', 'estado', 'creado_por',
    ];

    protected function casts(): array
    {
        return [
            'nota_obtenida' => 'decimal:2',
            'aprobado' => 'boolean',
            'evaluado_en' => 'datetime',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function nivelAcademico(): BelongsTo
    {
        return $this->belongsTo(NivelAcademico::class, 'nivel_academico_id');
    }

    public function matriculaExamen(): BelongsTo
    {
        return $this->belongsTo(Matricula::class, 'matricula_examen_id');
    }

    public function ofertaExamen(): BelongsTo
    {
        return $this->belongsTo(OfertaAcademica::class, 'oferta_examen_id');
    }

    public function nivelRecomendado(): BelongsTo
    {
        return $this->belongsTo(NivelAcademico::class, 'nivel_recomendado_id');
    }

    public function evaluador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluado_por');
    }

    public function scopePorEstudiante($query, int $estudianteId)
    {
        return $query->where('evaluaciones_nivelacion.estudiante_id', $estudianteId);
    }
}
