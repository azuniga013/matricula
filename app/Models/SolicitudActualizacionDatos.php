<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudActualizacionDatos extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_actualizacion_datos';
    public $timestamps = false;

    protected $fillable = [
        'estudiante_id',
        'campo',
        'valor_anterior',
        'valor_nuevo',
        'estado',
        'motivo',
        'revisado_por',
        'fecha_revision',
        'motivo_rechazo',
        'creado_en',
        'actualizado_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha_revision' => 'datetime',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function revisadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function scopePendientes($query)
    {
        return $query->where('solicitudes_actualizacion_datos.estado', 'pendiente');
    }
}
