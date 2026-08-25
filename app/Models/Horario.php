<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Horario extends Model
{
    use HasFactory;

    protected $table = 'horarios';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'hora_inicio',
        'hora_fin',
        'es_24_horas',
        'lunes',
        'martes',
        'miercoles',
        'jueves',
        'viernes',
        'sabado',
        'domingo',
        'descripcion',
        'estado',
        'creado_por',
        'actualizado_por',
        'creado_en',
        'actualizado_en',
    ];

    protected function casts(): array
    {
        return [
            'es_24_horas' => 'boolean',
            'lunes' => 'boolean',
            'martes' => 'boolean',
            'miercoles' => 'boolean',
            'jueves' => 'boolean',
            'viernes' => 'boolean',
            'sabado' => 'boolean',
            'domingo' => 'boolean',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    protected function horaInicio(): Attribute
    {
        return Attribute::get(fn($value) => $value ? substr($value, 0, 5) : null);
    }

    protected function horaFin(): Attribute
    {
        return Attribute::get(fn($value) => $value ? substr($value, 0, 5) : null);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    public function scopeActivos($query)
    {
        return $query->where('horarios.estado', 'activo');
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('horarios.hora_inicio');
    }
}
