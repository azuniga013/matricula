<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfertaAcademica extends Model
{
    use HasFactory;

    protected $table = 'ofertas_academicas';
    public $timestamps = false;

    protected $fillable = [
        'sucursal_id',
        'periodo_academico_id',
        'nivel_academico_id',
        'modalidad_id',
        'horario_id',
        'docente_id',
        'aula_id',
        'cupo_maximo',
        'cupos_reservados',
        'cupos_matriculados',
        'estado',
        'acepta_cambios_horario',
        'grupo_whatsapp_id',
        'plan_cobro_id',
        'observaciones',
        'codigo',
        'creado_por',
        'actualizado_por',
    ];

    protected $appends = ['cupos_disponibles', 'plan_estudio_id'];

    protected function casts(): array
    {
        return [
            'cupo_maximo' => 'integer',
            'cupos_reservados' => 'integer',
            'cupos_matriculados' => 'integer',
            'acepta_cambios_horario' => 'boolean',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function getCuposDisponiblesAttribute(): int
    {
        return $this->cupo_maximo - $this->cupos_matriculados - $this->cupos_reservados;
    }

    public function getPlanEstudioIdAttribute()
    {
        return $this->nivelAcademico?->versionPlanEstudio?->plan_estudio_id;
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function periodoAcademico(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_academico_id');
    }

    public function nivelAcademico(): BelongsTo
    {
        return $this->belongsTo(NivelAcademico::class, 'nivel_academico_id');
    }

    public function modalidad(): BelongsTo
    {
        return $this->belongsTo(Modalidad::class, 'modalidad_id');
    }

    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class, 'horario_id');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class, 'aula_id');
    }

    public function planCobro(): BelongsTo
    {
        return $this->belongsTo(PlanCobro::class, 'plan_cobro_id');
    }

    public function grupoWhatsapp(): BelongsTo
    {
        return $this->belongsTo(GrupoWhatsapp::class, 'grupo_whatsapp_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function cuposDisponibles(): int
    {
        return $this->cupo_maximo - $this->cupos_matriculados - $this->cupos_reservados;
    }

    public function tieneCupo(): bool
    {
        return $this->cuposDisponibles() > 0;
    }

    public function estaAbierta(): bool
    {
        return $this->estado === 'abierto' && $this->tieneCupo();
    }

    public function scopeActivos($query)
    {
        return $query->where('ofertas_academicas.estado', '!=', 'cancelado');
    }

    public function scopeAbiertos($query)
    {
        return $query->where('ofertas_academicas.estado', 'abierto');
    }

    public function scopeConCupo($query)
    {
        return $query->whereRaw('cupo_maximo - cupos_matriculados - cupos_reservados > 0');
    }

    public function scopePorSucursal($query, int $sucursalId)
    {
        return $query->where('ofertas_academicas.sucursal_id', $sucursalId);
    }

    public function scopePorPeriodo($query, int $periodoId)
    {
        return $query->where('ofertas_academicas.periodo_academico_id', $periodoId);
    }

    public function scopePorNivel($query, int $nivelId)
    {
        return $query->where('ofertas_academicas.nivel_academico_id', $nivelId);
    }

    public function scopePorDocente($query, int $docenteId)
    {
        return $query->where('ofertas_academicas.docente_id', $docenteId);
    }
}
