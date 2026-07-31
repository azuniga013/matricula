<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aula extends Model
{
    use HasFactory;

    protected $table = 'aulas';
    public $timestamps = false;

    protected $fillable = [
        'sucursal_id',
        'codigo',
        'nombre',
        'capacidad',
        'descripcion',
        'estado',
        'creado_por',
        'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'capacidad' => 'integer',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('aulas.estado', 'activo');
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('aulas.nombre');
    }
}
