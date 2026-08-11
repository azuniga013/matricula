<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactoResponsableEstudiante extends Model
{
    use HasFactory;

    protected $table = 'contactos_responsable_estudiante';

    public $timestamps = false;

    protected $fillable = [
        'estudiante_id',
        'nombre',
        'parentesco',
        'correo',
        'telefono_whatsapp',
        'recibe_asistencia_email',
        'recibe_asistencia_whatsapp',
        'consentimiento_asistencia_en',
        'consentimiento_evidencia',
        'prioridad',
        'vigente_desde',
        'vigente_hasta',
        'estado',
        'creado_por',
        'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'recibe_asistencia_email' => 'boolean',
            'recibe_asistencia_whatsapp' => 'boolean',
            'consentimiento_asistencia_en' => 'datetime',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function notificacionesAsistencia(): HasMany
    {
        return $this->hasMany(NotificacionAsistencia::class, 'contacto_responsable_estudiante_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('contactos_responsable_estudiante.estado', 'activo');
    }

    public function scopeVigentes($query)
    {
        return $query
            ->where(function ($sub) {
                $sub->whereNull('vigente_desde')->orWhere('vigente_desde', '<=', now()->toDateString());
            })
            ->where(function ($sub) {
                $sub->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', now()->toDateString());
            });
    }
}
