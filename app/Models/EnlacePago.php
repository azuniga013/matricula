<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnlacePago extends Model
{
    use HasFactory;

    protected $table = 'enlaces_pago';
    public $timestamps = false;

    protected $fillable = [
        'codigo', 'nombre', 'enlace_url', 'monto', 'monto_objetivo', 'concepto_pago_id', 'cuenta_bancaria_id',
        'fecha_vencimiento', 'usos_maximos', 'usos_actuales', 'estado', 'estado_operativo',
        'asignado_a_pago_id', 'asignado_a_estudiante_id', 'fecha_asignacion', 'fecha_uso', 'observaciones',
        'creado_por', 'actualizado_por', 'creado_en', 'actualizado_en',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'monto_objetivo' => 'decimal:2',
            'fecha_vencimiento' => 'date',
            'usos_maximos' => 'integer',
            'usos_actuales' => 'integer',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
            'fecha_asignacion' => 'datetime',
            'fecha_uso' => 'datetime',
        ];
    }

    public function conceptoPago(): BelongsTo
    {
        return $this->belongsTo(ConceptoPago::class, 'concepto_pago_id');
    }

    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class, 'cuenta_bancaria_id');
    }

    public function estaDisponible(): bool
    {
        if ($this->estado !== 'activo') return false;
        if ($this->fecha_vencimiento && $this->fecha_vencimiento->isPast()) return false;
        if ($this->usos_maximos && $this->usos_actuales >= $this->usos_maximos) return false;
        return true;
    }

    public function scopeActivos($query)
    {
        return $query->where('enlaces_pago.estado', 'activo');
    }
}
