<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartamentoAcademico extends Model
{
    use HasFactory;

    protected $table = 'departamentos_academicos';
    public $timestamps = false;

    protected $fillable = [
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

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    public function planesEstudio(): HasMany
    {
        return $this->hasMany(PlanEstudio::class, 'departamento_academico_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('departamentos_academicos.estado', 'activo');
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('departamentos_academicos.nombre');
    }
}
