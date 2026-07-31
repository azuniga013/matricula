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
        'codigo', 'estudiante_id', 'nivel_academico_id', 'nota_obtenida',
        'aprobado', 'observaciones', 'autorizado_por', 'estado', 'creado_por',
    ];

    protected function casts(): array
    {
        return [
            'nota_obtenida' => 'decimal:2',
            'aprobado' => 'boolean',
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

    public function scopePorEstudiante($query, int $estudianteId)
    {
        return $query->where('evaluaciones_nivelacion.estudiante_id', $estudianteId);
    }
}
