<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObligacionPagoEstudiante extends Model
{
    use HasFactory;

    protected $table = 'obligaciones_pago_estudiante';
    public $timestamps = false;

    protected $fillable = [
        'matricula_id', 'concepto_pago_id', 'numero_cuota', 'nombre_cargo',
        'monto', 'monto_pagado', 'fecha_vencimiento', 'estado',
        'creado_por', 'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'monto_pagado' => 'decimal:2',
            'fecha_vencimiento' => 'date',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class, 'matricula_id');
    }

    public function conceptoPago(): BelongsTo
    {
        return $this->belongsTo(ConceptoPago::class, 'concepto_pago_id');
    }

    public function aplicaciones(): HasMany
    {
        return $this->hasMany(AplicacionPago::class, 'obligacion_pago_estudiante_id');
    }

    public function saldoPendiente(): float
    {
        return (float) $this->monto - (float) $this->monto_pagado;
    }

    public function scopePendientes($query)
    {
        return $query->whereIn('obligaciones_pago_estudiante.estado', ['pendiente', 'parcial']);
    }

    public function scopePorMatricula($query, int $matriculaId)
    {
        return $query->where('obligaciones_pago_estudiante.matricula_id', $matriculaId);
    }
}
