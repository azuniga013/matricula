<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GestionMatricula extends Model
{
    use HasFactory;

    protected $table = 'gestiones_matricula';
    public $timestamps = false;

    protected $fillable = [
        'matricula_id', 'tipo_gestion_matricula_id', 'motivo', 'estado',
        'oferta_academica_origen_id', 'oferta_academica_destino_id', 'datos_antes', 'despues',
        'solicitado_por', 'decidido_por', 'fecha_solicitud', 'fecha_decision',
        'motivo_decision', 'creado_por', 'actualizado_por', 'creado_en', 'actualizado_en',
    ];

    protected function casts(): array
    {
        return [
            'datos_antes' => 'array',
            'despues' => 'array',
            'fecha_solicitud' => 'datetime',
            'fecha_decision' => 'datetime',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class, 'matricula_id');
    }

    public function tipoGestion(): BelongsTo
    {
        return $this->belongsTo(TipoGestionMatricula::class, 'tipo_gestion_matricula_id');
    }

    public function ofertaDestino(): BelongsTo
    {
        return $this->belongsTo(OfertaAcademica::class, 'oferta_academica_destino_id');
    }

    public function ofertaOrigen(): BelongsTo
    {
        return $this->belongsTo(OfertaAcademica::class, 'oferta_academica_origen_id');
    }

    public function scopePendientes($query)
    {
        return $query->where('gestiones_matricula.estado', 'pendiente');
    }
}
