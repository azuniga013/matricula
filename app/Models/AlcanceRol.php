<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlcanceRol extends Model
{
    use HasFactory;

    protected $table = 'alcances_rol';
    public $timestamps = false;

    protected $fillable = [
        'rol_id',
        'tipo',
        'sucursal_id',
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

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('alcances_rol.estado', 'activo');
    }

    public function scopeDeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }
}
