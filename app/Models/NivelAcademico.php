<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NivelAcademico extends Model
{
    use HasFactory;

    protected $table = 'niveles_academicos';
    public $timestamps = false;

    protected $fillable = [
        'version_plan_estudio_id',
        'regimen_academico_id',
        'codigo',
        'nombre',
        'orden',
        'nota_minima_aprobar',
        'faltas_maximas_permitidas',
        'estado',
        'creado_por',
        'actualizado_por',
        'creado_en',
        'actualizado_en',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'nota_minima_aprobar' => 'integer',
            'faltas_maximas_permitidas' => 'integer',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function versionPlanEstudio(): BelongsTo
    {
        return $this->belongsTo(VersionPlanEstudio::class, 'version_plan_estudio_id');
    }

    public function regimenAcademico(): BelongsTo
    {
        return $this->belongsTo(Modalidad::class, 'regimen_academico_id');
    }

    public function modalidades(): BelongsToMany
    {
        return $this->belongsToMany(Modalidad::class, 'nivel_modalidades', 'nivel_academico_id', 'modalidad_id');
    }

    public function prerrequisitos(): BelongsToMany
    {
        return $this->belongsToMany(NivelAcademico::class, 'prerrequisitos_nivel', 'nivel_academico_id', 'prerrequisito_id');
    }

    public function esPrerequisitoDe(): BelongsToMany
    {
        return $this->belongsToMany(NivelAcademico::class, 'prerrequisitos_nivel', 'prerrequisito_id', 'nivel_academico_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('niveles_academicos.estado', 'activo');
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('niveles_academicos.orden');
    }
}
