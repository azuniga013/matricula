<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitacoraCorreo extends Model
{
    protected $table = 'bitacora_correos';
    public $timestamps = false;

    protected $fillable = [
        'destinatario',
        'asunto',
        'tipo',
        'codigo_estudiante',
        'estado',
        'error',
        'cuerpo_html',
    ];

    protected function casts(): array
    {
        return [
            'creado_en' => 'datetime',
        ];
    }
}
