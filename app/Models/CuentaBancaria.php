<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuentaBancaria extends Model
{
    use HasFactory;

    protected $table = 'cuentas_bancarias';
    public $timestamps = false;

    protected $fillable = [
        'codigo', 'nombre', 'banco', 'numero_cuenta', 'tipo_cuenta',
        'estado', 'creado_por', 'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function scopeActivas($query)
    {
        return $query->where('cuentas_bancarias.estado', 'activo');
    }
}
