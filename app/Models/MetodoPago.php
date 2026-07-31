<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetodoPago extends Model
{
    use HasFactory;

    protected $table = 'metodos_pago';
    public $timestamps = false;

    protected $fillable = [
        'codigo', 'nombre', 'descripcion', 'estado', 'portal_disponible',
        'permite_link_pago',
        'proveedor_pago_id',
        'creado_por', 'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'portal_disponible' => 'boolean',
            'permite_link_pago' => 'boolean',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function scopeActivos($query)
    {
        return $query->where('metodos_pago.estado', 'activo');
    }

    public function scopeDisponiblesPortal($query)
    {
        return $query->where('metodos_pago.estado', 'activo')
            ->where('metodos_pago.portal_disponible', true);
    }

    public function proveedorPago(): BelongsTo
    {
        return $this->belongsTo(ProveedorPago::class, 'proveedor_pago_id');
    }

    public function getRequiereProveedorAttribute(): bool
    {
        return $this->proveedor_pago_id !== null;
    }
}
