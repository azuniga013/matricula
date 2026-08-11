<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SincronizacionDocenteMovil extends Model
{
    use HasFactory;

    protected $table = 'sincronizaciones_docente_movil';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'usuario_id',
        'docente_id',
        'tipo',
        'oferta_academica_id',
        'estado',
        'respuesta_json',
    ];

    protected function casts(): array
    {
        return [
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }
}
