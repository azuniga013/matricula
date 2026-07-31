<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Modalidad extends Model
{
    use HasFactory;

    protected $table = 'modalidades';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo',
        'descripcion',
        'estado',
        'creado_por',
        'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function niveles(): BelongsToMany
    {
        return $this->belongsToMany(NivelAcademico::class, 'nivel_modalidades', 'modalidad_id', 'nivel_academico_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('modalidades.estado', 'activo');
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('modalidades.nombre');
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('modalidades.tipo', $tipo);
    }
}
