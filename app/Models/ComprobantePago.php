<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ComprobantePago extends Model
{
    use HasFactory;

    protected $table = 'comprobantes_pago';
    public $timestamps = false;

    protected $fillable = [
        'pago_id', 'nombre_archivo', 'ruta_archivo', 'tipo_archivo',
        'tamano_bytes', 'estado', 'observaciones', 'creado_por',
    ];

    protected function casts(): array
    {
        return [
            'tamano_bytes' => 'integer',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'pago_id');
    }

    protected function rutaDescarga(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->ruta_archivo ? Storage::url($this->ruta_archivo) : null,
        );
    }

    protected $appends = ['ruta_descarga'];
}
