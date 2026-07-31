<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetallePlanCobro extends Model
{
    use HasFactory;

    protected $table = 'detalle_plan_cobro';
    public $timestamps = false;

    protected $fillable = [
        'plan_cobro_id', 'concepto_pago_id', 'numero_cuota', 'nombre_cargo',
        'monto', 'dias_vencimiento', 'estado', 'creado_por', 'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'dias_vencimiento' => 'integer',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function planCobro(): BelongsTo
    {
        return $this->belongsTo(PlanCobro::class, 'plan_cobro_id');
    }

    public function conceptoPago(): BelongsTo
    {
        return $this->belongsTo(ConceptoPago::class, 'concepto_pago_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('detalle_plan_cobro.estado', 'activo');
    }
}
