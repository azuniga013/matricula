<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MovimientoInventarioLibro extends Model
{
    use HasFactory;

    protected $table = 'movimientos_inventario_libros';

    public $timestamps = false;

    protected $fillable = [
        'inventario_libro_id',
        'tipo_movimiento',
        'cantidad',
        'existencia_antes',
        'existencia_despues',
        'motivo',
        'referencia_type',
        'referencia_id',
        'creado_por',
        'creado_en',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'existencia_antes' => 'integer',
            'existencia_despues' => 'integer',
            'creado_en' => 'datetime',
        ];
    }

    public function inventarioLibro(): BelongsTo
    {
        return $this->belongsTo(InventarioLibro::class, 'inventario_libro_id');
    }

    public function referencia(): MorphTo
    {
        return $this->morphTo();
    }
}
