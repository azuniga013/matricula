<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsistenciaEstudiante extends Model
{
    use HasFactory;

    protected $table = 'asistencias_estudiante';

    public $timestamps = false;

    protected $fillable = [
        'matricula_id',
        'oferta_academica_id',
        'fecha',
        'estado',
        'cuenta_como_falta',
        'observacion',
        'registrado_por',
        'creado_por',
        'creado_en',
        'actualizado_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'cuenta_como_falta' => 'boolean',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class, 'matricula_id');
    }

    public function ofertaAcademica(): BelongsTo
    {
        return $this->belongsTo(OfertaAcademica::class, 'oferta_academica_id');
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
