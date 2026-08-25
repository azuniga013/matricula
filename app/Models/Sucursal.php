<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sucursal extends Model
{
    use HasFactory;

    protected $table = 'sucursales';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'direccion',
        'telefono',
        'correo',
        'estado',
        'creado_por',
        'actualizado_por',
        'creado_en',
        'actualizado_en',
    ];

    protected function casts(): array
    {
        return [
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'usuario_sucursales', 'sucursal_id', 'usuario_id')
            ->withPivot('estado')
            ->wherePivot('estado', 'activo');
    }

    public function ofertasAcademicas()
    {
        return $this->hasMany(OfertaAcademica::class, 'sucursal_id');
    }

    public function modalidadesAtencion(): BelongsToMany
    {
        return $this->belongsToMany(Modalidad::class, 'sucursal_modalidad_atencion', 'sucursal_id', 'modalidad_id')
            ->withPivot('estado')
            ->wherePivot('estado', 'activo');
    }

    public function scopeActivos($query)
    {
        return $query->where('sucursales.estado', 'activo');
    }
}
