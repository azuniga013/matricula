<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoGestionMatricula extends Model
{
    use HasFactory;

    protected $table = 'tipos_gestion_matricula';
    public $timestamps = false;

    protected $fillable = [
        'codigo', 'nombre', 'descripcion', 'estado', 'creado_por', 'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function scopeActivos($query)
    {
        return $query->where('tipos_gestion_matricula.estado', 'activo');
    }
}
