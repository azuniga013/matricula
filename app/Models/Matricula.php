<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matricula extends Model
{
    use HasFactory;

    protected $table = 'matriculas';
    public $timestamps = false;

    protected $fillable = [
        'codigo', 'estudiante_id', 'oferta_academica_id', 'sucursal_id',
        'estado', 'fecha_reserva', 'fecha_confirmacion', 'observaciones',
        'creado_por', 'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_reserva' => 'datetime',
            'fecha_confirmacion' => 'datetime',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function ofertaAcademica(): BelongsTo
    {
        return $this->belongsTo(OfertaAcademica::class, 'oferta_academica_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function obligaciones(): HasMany
    {
        return $this->hasMany(ObligacionPagoEstudiante::class, 'matricula_id');
    }

    public function gestiones(): HasMany
    {
        return $this->hasMany(GestionMatricula::class, 'matricula_id');
    }

    public function scopeActivas($query)
    {
        return $query->where('matriculas.estado', 'matriculado');
    }

    public function scopePorSucursal($query, int $sucursalId)
    {
        return $query->where('matriculas.sucursal_id', $sucursalId);
    }

    public function scopePorPeriodo($query, int $periodoId)
    {
        return $query->whereHas('ofertaAcademica', function ($q) use ($periodoId) {
            $q->where('periodo_academico_id', $periodoId);
        });
    }
}
