<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SesionCaja extends Model
{
    use HasFactory;

    protected $table = 'sesiones_caja';
    public $timestamps = false;

    protected $fillable = [
        'codigo', 'sucursal_id', 'usuario_cajero_id', 'monto_inicial', 'monto_final',
        'estado', 'fecha_apertura', 'fecha_cierre', 'observaciones',
        'creado_por', 'cerrado_por', 'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'monto_inicial' => 'decimal:2',
            'monto_final' => 'decimal:2',
            'fecha_apertura' => 'datetime',
            'fecha_cierre' => 'datetime',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function cajero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_cajero_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCierreCaja::class, 'sesion_caja_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'sesion_caja_id');
    }

    public function scopeAbiertas($query)
    {
        return $query->where('sesiones_caja.estado', 'abierta');
    }

    public function scopePorSucursal($query, int $sucursalId)
    {
        return $query->where('sesiones_caja.sucursal_id', $sucursalId);
    }
}
