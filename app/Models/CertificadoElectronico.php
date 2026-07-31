<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificadoElectronico extends Model
{
    use HasFactory;

    protected $table = 'certificados_electronicos';

    protected $fillable = [
        'codigo','token_validacion','estudiante_id','historial_academico_id','nivel_academico_id',
        'nota_final','estado','emitido_en','validado_en','ruta_pdf','hash_documento','codigo_verificacion',
    ];

    protected function casts(): array
    {
        return [
            'nota_final' => 'decimal:2',
            'emitido_en' => 'datetime',
            'validado_en' => 'datetime',
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function nivelAcademico(): BelongsTo
    {
        return $this->belongsTo(NivelAcademico::class, 'nivel_academico_id');
    }

    public function historialAcademico(): BelongsTo
    {
        return $this->belongsTo(HistorialAcademico::class, 'historial_academico_id');
    }
}
