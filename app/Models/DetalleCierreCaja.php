<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleCierreCaja extends Model
{
    use HasFactory;

    protected $table = 'detalle_cierre_caja';
    public $timestamps = false;

    protected $fillable = [
        'sesion_caja_id', 'concepto_pago_id', 'metodo_pago_id',
        'cantidad_transacciones', 'monto_total', 'estado', 'creado_por',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_transacciones' => 'integer',
            'monto_total' => 'decimal:2',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function sesionCaja(): BelongsTo
    {
        return $this->belongsTo(SesionCaja::class, 'sesion_caja_id');
    }

    public function conceptoPago(): BelongsTo
    {
        return $this->belongsTo(ConceptoPago::class, 'concepto_pago_id');
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }
}
