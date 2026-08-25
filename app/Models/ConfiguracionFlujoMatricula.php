<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ConfiguracionFlujoMatricula extends Model
{
    use HasFactory;

    protected $table = 'configuraciones_flujo_matricula';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'origen',
        'concepto_pago_id',
        'metodo_pago_id',
        'estado',
        'habilita_reserva_cupo',
        'habilita_carga_comprobante',
        'requiere_comprobante',
        'habilita_revision_contable',
        'habilita_aprobacion_pago',
        'habilita_generacion_recibo',
        'habilita_confirmacion_matricula',
        'habilita_seleccion_obligaciones',
        'habilita_whatsapp',
        'habilita_reenganche',
        'habilita_solicitud_link',
        'observaciones',
        'creado_por',
        'actualizado_por',
        'creado_en',
        'actualizado_en',
    ];

    protected function casts(): array
    {
        return [
            'habilita_reserva_cupo' => 'boolean',
            'habilita_carga_comprobante' => 'boolean',
            'requiere_comprobante' => 'boolean',
            'habilita_revision_contable' => 'boolean',
            'habilita_aprobacion_pago' => 'boolean',
            'habilita_generacion_recibo' => 'boolean',
            'habilita_confirmacion_matricula' => 'boolean',
            'habilita_seleccion_obligaciones' => 'boolean',
            'habilita_whatsapp' => 'boolean',
            'habilita_reenganche' => 'boolean',
            'habilita_solicitud_link' => 'boolean',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function conceptoPago(): BelongsTo
    {
        return $this->belongsTo(ConceptoPago::class, 'concepto_pago_id');
    }

    public function conceptosPago(): BelongsToMany
    {
        return $this->belongsToMany(ConceptoPago::class, 'configuracion_flujo_matricula_conceptos', 'configuracion_flujo_matricula_id', 'concepto_pago_id')
            ->withPivot(['creado_por', 'creado_en']);
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function metodosPago(): BelongsToMany
    {
        return $this->belongsToMany(MetodoPago::class, 'configuracion_flujo_matricula_metodos', 'configuracion_flujo_matricula_id', 'metodo_pago_id')
            ->withPivot(['creado_por', 'creado_en']);
    }
}
