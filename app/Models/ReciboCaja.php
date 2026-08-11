<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ReciboCaja extends Model
{
    use HasFactory;

    protected $table = 'recibos_caja';
    public $timestamps = false;

    protected $fillable = [
        'codigo', 'numero_recibo', 'pago_id', 'estudiante_id', 'sucursal_id',
        'concepto_pago_id', 'metodo_pago_id', 'monto_total', 'estado', 'anio',
        'fecha_proceso',
        'fecha_recibo',
        'periodo', 'creado_por', 'anulado_por', 'fecha_anulacion',
        'motivo_anulacion', 'veces_reimpreso', 'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'numero_recibo' => 'integer',
            'monto_total' => 'decimal:2',
            'fecha_proceso' => 'datetime',
            'fecha_recibo' => 'datetime',
            'veces_reimpreso' => 'integer',
            'fecha_anulacion' => 'datetime',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'pago_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function conceptoPago(): BelongsTo
    {
        return $this->belongsTo(ConceptoPago::class, 'concepto_pago_id');
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function scopeEmitidos($query)
    {
        return $query->where('recibos_caja.estado', 'emitido');
    }

    public function scopePorSucursal($query, int $sucursalId)
    {
        return $query->where('recibos_caja.sucursal_id', $sucursalId);
    }

    public function getFechaProcesoAttribute($value): ?Carbon
    {
        return $this->resolverFecha($value, 'fecha_recibo');
    }

    public function getFechaReciboAttribute($value): ?Carbon
    {
        return $this->resolverFecha($value, 'fecha_proceso');
    }

    private function resolverFecha(mixed $value, string $atributoAlterno): ?Carbon
    {
        if ($value) {
            return $this->asDateTime($value);
        }

        $alterno = $this->getAttributeFromArray($atributoAlterno);
        if ($alterno) {
            return $this->asDateTime($alterno);
        }

        if ($this->pago?->fecha_proceso) {
            return $this->pago->fecha_proceso;
        }

        $creadoEn = $this->getAttributeFromArray('creado_en');

        return $creadoEn ? $this->asDateTime($creadoEn) : null;
    }
}
