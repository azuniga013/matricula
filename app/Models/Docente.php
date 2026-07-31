<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Docente extends Model
{
    use HasFactory;

    protected $table = 'docentes';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'apellido',
        'correo',
        'telefono',
        'identidad',
        'estado',
        'creado_por',
        'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('docentes.estado', 'activo');
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('docentes.apellido')->orderBy('docentes.nombre');
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }
}
