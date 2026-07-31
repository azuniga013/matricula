<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    use HasFactory;

    protected $table = 'modulos';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'orden',
        'estado',
        'creado_por',
        'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function opciones()
    {
        return $this->hasMany(OpcionModulo::class, 'modulo_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('modulos.estado', 'activo');
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden');
    }
}
