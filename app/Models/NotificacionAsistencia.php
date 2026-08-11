<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificacionAsistencia extends Model
{
    use HasFactory;

    protected $table = 'notificaciones_asistencia';

    public $timestamps = false;

    protected $fillable = [
        'asistencia_estudiante_id',
        'contacto_responsable_estudiante_id',
        'estudiante_id',
        'canal',
        'tipo',
        'clave_idempotente',
        'estado',
        'proveedor',
        'identificador_externo',
        'intentos',
        'error_seguro',
        'enviado_en',
        'omitido_en',
        'fallido_en',
    ];

    protected function casts(): array
    {
        return [
            'enviado_en' => 'datetime',
            'omitido_en' => 'datetime',
            'fallido_en' => 'datetime',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function asistencia(): BelongsTo
    {
        return $this->belongsTo(AsistenciaEstudiante::class, 'asistencia_estudiante_id');
    }

    public function contacto(): BelongsTo
    {
        return $this->belongsTo(ContactoResponsableEstudiante::class, 'contacto_responsable_estudiante_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }
}
