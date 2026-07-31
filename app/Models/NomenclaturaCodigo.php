<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NomenclaturaCodigo extends Model
{
    use HasFactory;

    protected $table = 'nomenclaturas_codigos';
    public $timestamps = false;

    protected $fillable = [
        'entidad',
        'formato',
        'longitud_secuencia',
        'secuencia_actual',
        'estado',
        'creado_por',
        'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'longitud_secuencia' => 'integer',
            'secuencia_actual' => 'integer',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function scopeActivos($query)
    {
        return $query->where('nomenclaturas_codigos.estado', 'activo');
    }

    public function generarSiguiente(?string $anio = null): string
    {
        $this->increment('secuencia_actual');
        $secuencia = str_pad($this->secuencia_actual, $this->longitud_secuencia, '0', STR_PAD_LEFT);
        $codigo = str_replace('{SECUENCIA:' . $this->longitud_secuencia . '}', $secuencia, $this->formato);

        if ($anio !== null) {
            $codigo = str_replace('{ANIO}', $anio, $codigo);
        }

        return $codigo;
    }
}
