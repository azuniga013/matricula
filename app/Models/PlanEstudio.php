<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanEstudio extends Model
{
    use HasFactory;

    protected $table = 'planes_estudio';
    public $timestamps = false;

    protected $fillable = [
        'departamento_academico_id',
        'codigo',
        'nombre',
        'descripcion',
        'estado',
        'creado_por',
        'actualizado_por',
        'creado_en',
        'actualizado_en',
    ];

    protected function casts(): array
    {
        return [
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function departamentoAcademico(): BelongsTo
    {
        return $this->belongsTo(DepartamentoAcademico::class, 'departamento_academico_id');
    }

    public function versiones(): HasMany
    {
        return $this->hasMany(VersionPlanEstudio::class, 'plan_estudio_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('planes_estudio.estado', 'activo');
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('planes_estudio.nombre');
    }
}
