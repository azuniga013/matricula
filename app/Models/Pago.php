<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';
    public $timestamps = false;

    protected $fillable = [
        'codigo', 'estudiante_id', 'matricula_id', 'concepto_pago_id',
        'metodo_pago_id', 'cuenta_bancaria_id', 'proveedor_pago_id', 'transaccion_id', 'procesador_respuesta',
        'sucursal_id', 'sesion_caja_id', 'monto', 'monto_recibido', 'vuelto', 'fecha_proceso', 'fecha_deposito', 'estado', 'referencia_externa',
        'alerta_duplicado', 'alerta_duplicado_mensaje', 'alerta_duplicado_en',
        'link_pago_url', 'link_pago_estado', 'link_generado_por', 'link_generado_en',
        'confirmado_por_estudiante_id', 'confirmado_por_estudiante_en',
        'observaciones', 'creado_por', 'aprobado_por', 'fecha_aprobacion',
        'rechazado_por', 'fecha_rechazo', 'motivo_rechazo', 'actualizado_por',
        'creado_en', 'actualizado_en',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'monto_recibido' => 'decimal:2',
            'vuelto' => 'decimal:2',
            'fecha_proceso' => 'datetime',
            'fecha_deposito' => 'datetime',
            'fecha_aprobacion' => 'datetime',
            'fecha_rechazo' => 'datetime',
            'link_generado_en' => 'datetime',
            'confirmado_por_estudiante_en' => 'datetime',
            'alerta_duplicado' => 'boolean',
            'alerta_duplicado_en' => 'datetime',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class, 'matricula_id');
    }

    public function conceptoPago(): BelongsTo
    {
        return $this->belongsTo(ConceptoPago::class, 'concepto_pago_id');
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function proveedorPago(): BelongsTo
    {
        return $this->belongsTo(ProveedorPago::class, 'proveedor_pago_id');
    }

    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class, 'cuenta_bancaria_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function sesionCaja(): BelongsTo
    {
        return $this->belongsTo(SesionCaja::class, 'sesion_caja_id');
    }

    public function comprobantes(): HasMany
    {
        return $this->hasMany(ComprobantePago::class, 'pago_id');
    }

    public function aplicaciones(): HasMany
    {
        return $this->hasMany(AplicacionPago::class, 'pago_id');
    }

    public function reciboCaja(): HasOne
    {
        return $this->hasOne(ReciboCaja::class, 'pago_id');
    }

    public function movimientosInventario(): MorphMany
    {
        return $this->morphMany(MovimientoInventarioLibro::class, 'referencia');
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function rechazadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rechazado_por');
    }

    public function scopePendientes($query)
    {
        return $query->where('pagos.estado', 'pendiente');
    }

    public function scopeAprobados($query)
    {
        return $query->where('pagos.estado', 'aprobado');
    }

    public function getFechaProcesoAttribute($value): ?Carbon
    {
        if ($value) {
            return $this->asDateTime($value);
        }

        if ($this->fecha_aprobacion) {
            return $this->fecha_aprobacion;
        }

        $creadoEn = $this->getAttributeFromArray('creado_en');

        return $creadoEn ? $this->asDateTime($creadoEn) : null;
    }
}
