<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Libro extends Model
{
    use HasFactory;

    protected $table = 'libros';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'titulo',
        'autor',
        'editorial',
        'isbn',
        'precio_venta',
        'estado',
        'creado_por',
        'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'precio_venta' => 'decimal:2',
        ];
    }

    public function niveles(): BelongsToMany
    {
        return $this->belongsToMany(NivelAcademico::class, 'libro_niveles', 'libro_id', 'nivel_academico_id');
    }

    public function inventarios(): HasMany
    {
        return $this->hasMany(InventarioLibro::class, 'libro_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('libros.estado', 'activo');
    }

    public function scopePorCodigo($query, $codigo)
    {
        return $query->where('libros.codigo', $codigo);
    }
}
