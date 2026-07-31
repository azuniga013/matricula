<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfiguracionProveedorPago extends Model
{
    protected $table = 'configuraciones_proveedor_pago';

    protected $fillable = [
        'proveedor_pago_id',
        'clave',
        'valor',
    ];

    public function proveedorPago(): BelongsTo
    {
        return $this->belongsTo(ProveedorPago::class, 'proveedor_pago_id');
    }
}
