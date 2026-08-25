<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProveedorPago extends Model
{
    protected $table = 'proveedores_pago';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activo',
        'creado_por',
        'actualizado_por',
        'creado_en',
        'actualizado_en',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function configuraciones(): HasMany
    {
        return $this->hasMany(ConfiguracionProveedorPago::class, 'proveedor_pago_id');
    }

    public function metodosPago(): HasMany
    {
        return $this->hasMany(MetodoPago::class, 'proveedor_pago_id');
    }

    public function config(string $clave, mixed $default = null): mixed
    {
        $conf = $this->configuraciones->firstWhere('clave', $clave);
        return $conf ? $conf->valor : $default;
    }
}
