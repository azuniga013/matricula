<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrupoWhatsapp extends Model
{
    use HasFactory;

    protected $table = 'grupos_whatsapp';
    public $timestamps = false;

    protected $fillable = [
        'sucursal_id',
        'codigo',
        'nombre',
        'link',
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

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function ofertasAcademicas(): HasMany
    {
        return $this->hasMany(OfertaAcademica::class, 'grupo_whatsapp_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('grupos_whatsapp.estado', 'activo');
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('grupos_whatsapp.nombre');
    }
}
