<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rol extends Model
{
    use HasFactory;

    protected $table = 'roles';
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

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'usuario_roles', 'rol_id', 'usuario_id')
            ->withPivot('estado')
            ->wherePivot('estado', 'activo');
    }

    public function permisos()
    {
        return $this->belongsToMany(Permiso::class, 'rol_permisos', 'rol_id', 'permiso_id')
            ->withPivot('estado')
            ->wherePivot('estado', 'activo');
    }

    public function permisosEfectivos()
    {
        return $this->permisos()->where('permisos.estado', 'activo');
    }

    public function alcances()
    {
        return $this->hasMany(AlcanceRol::class, 'rol_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('roles.estado', 'activo');
    }
}
