<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlcanceUsuario extends Model
{
    use HasFactory;

    protected $table = 'alcances_usuario';
    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'tipo',
        'sucursal_id',
        'docente_id',
        'estudiante_id',
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

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('alcances_usuario.estado', 'activo');
    }
}
