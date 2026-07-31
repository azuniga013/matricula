<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReglaAprobacion extends Model
{
    use HasFactory;

    protected $table = 'reglas_aprobacion';
    public $timestamps = false;

    protected $fillable = [
        'codigo', 'nombre', 'modalidad_id', 'nota_minima_aprobar',
        'faltas_maximas_permitidas', 'descripcion', 'estado',
        'creado_por', 'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'nota_minima_aprobar' => 'decimal:2',
            'faltas_maximas_permitidas' => 'integer',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function modalidad(): BelongsTo
    {
        return $this->belongsTo(Modalidad::class, 'modalidad_id');
    }

    public function scopeActivas($query)
    {
        return $query->where('reglas_aprobacion.estado', 'activo');
    }
}
