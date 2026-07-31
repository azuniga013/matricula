<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanCobro extends Model
{
    use HasFactory;

    protected $table = 'planes_cobro';
    public $timestamps = false;

    protected $fillable = [
        'codigo', 'nombre', 'descripcion', 'estado', 'creado_por', 'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePlanCobro::class, 'plan_cobro_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('planes_cobro.estado', 'activo');
    }
}
