<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    use HasFactory;

    protected $table = 'permisos';
    public $timestamps = false;

    protected $fillable = [
        'opcion_modulo_id',
        'codigo',
        'nombre',
        'accion',
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

    public function opcionModulo()
    {
        return $this->belongsTo(OpcionModulo::class, 'opcion_modulo_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'rol_permisos', 'permiso_id', 'rol_id')
            ->withPivot('estado')
            ->wherePivot('estado', 'activo');
    }

    public function scopeActivos($query)
    {
        return $query->where('permisos.estado', 'activo');
    }

    public function scopeDeAccion($query, string $accion)
    {
        return $query->where('accion', $accion);
    }
}
