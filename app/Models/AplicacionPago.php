<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AplicacionPago extends Model
{
    use HasFactory;

    protected $table = 'aplicaciones_pago';
    public $timestamps = false;

    protected $fillable = [
        'pago_id', 'obligacion_pago_estudiante_id', 'estudiante_id',
        'monto_aplicado', 'estado', 'creado_por',
    ];

    protected function casts(): array
    {
        return [
            'monto_aplicado' => 'decimal:2',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'pago_id');
    }

    public function obligacion(): BelongsTo
    {
        return $this->belongsTo(ObligacionPagoEstudiante::class, 'obligacion_pago_estudiante_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }
}
