<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VersionPlanEstudio extends Model
{
    use HasFactory;

    protected $table = 'versiones_plan_estudio';
    public $timestamps = false;

    protected $fillable = [
        'plan_estudio_id',
        'codigo',
        'numero_version',
        'vigente_desde',
        'vigente_hasta',
        'estado',
        'creado_por',
        'actualizado_por',
        'creado_en',
        'actualizado_en',
    ];

    protected $appends = ['nombre'];

    protected function casts(): array
    {
        return [
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function getNombreAttribute(): string
    {
        $plan = $this->planEstudio;
        return ($plan ? $plan->nombre . ' · ' : '') . 'Versión ' . $this->numero_version;
    }

    public function planEstudio(): BelongsTo
    {
        return $this->belongsTo(PlanEstudio::class, 'plan_estudio_id');
    }

    public function niveles(): HasMany
    {
        return $this->hasMany(NivelAcademico::class, 'version_plan_estudio_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('versiones_plan_estudio.estado', 'activo');
    }

    public function scopeVigentes($query)
    {
        return $query->activos()
            ->where('vigente_desde', '<=', now())
            ->where(function ($q) {
                $q->whereNull('vigente_hasta')
                    ->orWhere('vigente_hasta', '>=', now());
            });
    }
}
