<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodoAcademico extends Model
{
    use HasFactory;

    protected $table = 'periodos_academicos';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'fecha_inicio_matricula',
        'fecha_cierre_matricula',
        'estado',
        'creado_por',
        'actualizado_por',
        'creado_en',
        'actualizado_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'fecha_inicio_matricula' => 'date',
            'fecha_cierre_matricula' => 'date',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    public function scopeActivos($query)
    {
        return $query->where('periodos_academicos.estado', 'activo');
    }

    public function scopeAbierto($query)
    {
        return $query->where('periodos_academicos.estado', 'activo')
            ->whereDate('fecha_inicio', '<=', today())
            ->whereDate('fecha_fin', '>=', today());
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('periodos_academicos.fecha_inicio', 'desc');
    }

    public function estaAbierto(): bool
    {
        return $this->estado === 'activo'
            && $this->fecha_inicio->lte(today())
            && $this->fecha_fin->gte(today());
    }

    public function estaAbiertoParaMatricula(): bool
    {
        $inicio = $this->fecha_inicio_matricula ?? $this->fecha_inicio;
        $cierre = $this->fecha_cierre_matricula ?? $this->fecha_fin;

        return $this->estado === 'activo'
            && $inicio?->lte(today())
            && $cierre?->gte(today());
    }
}
