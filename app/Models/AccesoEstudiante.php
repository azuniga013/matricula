<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccesoEstudiante extends Model
{
    use HasFactory;

    protected $table = 'accesos_estudiante';
    public $timestamps = false;

    protected $fillable = [
        'estudiante_id',
        'email',
        'password',
        'estado',
        'token',
        'ultimo_acceso',
        'creado_en',
        'actualizado_en',
    ];

    protected $hidden = [
        'password',
        'token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'ultimo_acceso' => 'datetime',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('accesos_estudiante.estado', 'activo');
    }
}
