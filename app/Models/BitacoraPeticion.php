<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BitacoraPeticion extends Model
{
    use HasFactory;

    protected $table = 'bitacora_peticiones';
    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'metodo',
        'ruta',
        'estado_http',
        'duracion_ms',
        'ip',
        'agente',
    ];

    protected function casts(): array
    {
        return [
            'estado_http' => 'integer',
            'duracion_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function scopeRecientes($query, int $minutos = 15)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutos));
    }
}
