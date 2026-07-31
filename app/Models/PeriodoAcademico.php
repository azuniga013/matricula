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
        'estado',
        'creado_por',
        'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
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
            ->where('fecha_inicio', '<=', now())
            ->where('fecha_fin', '>=', now());
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('periodos_academicos.fecha_inicio', 'desc');
    }

    public function estaAbierto(): bool
    {
        return $this->estado === 'activo'
            && $this->fecha_inicio->lte(now())
            && $this->fecha_fin->gte(now());
    }
}
