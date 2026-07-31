<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibroNivel extends Model
{
    use HasFactory;

    protected $table = 'libro_niveles';

    public $timestamps = false;

    protected $fillable = [
        'libro_id',
        'nivel_academico_id',
        'creado_por',
        'actualizado_por',
    ];

    public function libro(): BelongsTo
    {
        return $this->belongsTo(Libro::class, 'libro_id');
    }

    public function nivelAcademico(): BelongsTo
    {
        return $this->belongsTo(NivelAcademico::class, 'nivel_academico_id');
    }
}
