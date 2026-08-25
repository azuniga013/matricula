<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventarioLibro extends Model
{
    use HasFactory;

    protected $table = 'inventario_libros';

    public $timestamps = false;

    protected $fillable = [
        'libro_id',
        'sucursal_id',
        'existencia_actual',
        'existencia_minima',
        'creado_por',
        'actualizado_por',
        'creado_en',
        'actualizado_en',
    ];

    protected function casts(): array
    {
        return [
            'existencia_actual' => 'integer',
            'existencia_minima' => 'integer',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function libro(): BelongsTo
    {
        return $this->belongsTo(Libro::class, 'libro_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventarioLibro::class, 'inventario_libro_id');
    }

    public function scopePorSucursal($query, $sucursalId)
    {
        return $query->where('inventario_libros.sucursal_id', $sucursalId);
    }

    public function scopePorLibro($query, $libroId)
    {
        return $query->where('inventario_libros.libro_id', $libroId);
    }

    public function scopeStockBajo($query)
    {
        return $query->whereColumn('inventario_libros.existencia_actual', '<=', 'inventario_libros.existencia_minima');
    }
}
