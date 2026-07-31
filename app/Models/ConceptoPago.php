<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConceptoPago extends Model
{
    use HasFactory;

    protected $table = 'conceptos_pago';
    public $timestamps = false;

    protected $fillable = [
        'codigo', 'nombre', 'tipo_monto', 'monto_fijo', 'monto_minimo', 'monto_maximo',
        'requiere_autorizacion_monto', 'descripcion', 'estado', 'portal_disponible',
        'creado_por', 'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'monto_fijo' => 'decimal:2',
            'monto_minimo' => 'decimal:2',
            'monto_maximo' => 'decimal:2',
            'requiere_autorizacion_monto' => 'boolean',
            'portal_disponible' => 'boolean',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function scopeActivos($query)
    {
        return $query->where('conceptos_pago.estado', 'activo');
    }

    public function scopeDisponiblesPortal($query)
    {
        return $query->where('conceptos_pago.estado', 'activo')
            ->where('conceptos_pago.portal_disponible', true);
    }
}
