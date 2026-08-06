<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicacionApkDocente extends Model
{
    use HasFactory;

    protected $table = 'publicaciones_apk_docentes';

    protected $fillable = [
        'version', 'version_code', 'nombre_archivo', 'ruta_archivo', 'tamano_bytes',
        'sha256', 'notas_version', 'publicado', 'publicado_en', 'creado_por', 'publicado_por',
    ];

    protected function casts(): array
    {
        return ['publicado' => 'boolean', 'publicado_en' => 'datetime'];
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function publicador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publicado_por');
    }
}
