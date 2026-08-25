<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpcionModulo extends Model
{
    use HasFactory;

    protected $table = 'opciones_modulo';
    public $timestamps = false;

    protected $fillable = [
        'modulo_id',
        'codigo',
        'nombre',
        'ruta',
        'orden',
        'estado',
        'creado_por',
        'actualizado_por',
        'creado_en',
        'actualizado_en',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function modulo()
    {
        return $this->belongsTo(Modulo::class, 'modulo_id');
    }

    public function permisos()
    {
        return $this->hasMany(Permiso::class, 'opcion_modulo_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('opciones_modulo.estado', 'activo');
    }
}
