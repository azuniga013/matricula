<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntentoAcceso extends Model
{
    use HasFactory;

    protected $table = 'intentos_acceso';
    public $timestamps = false;

    protected $fillable = [
        'correo',
        'usuario_id',
        'ip',
        'agente',
        'resultado',
        'motivo',
    ];

    protected function casts(): array
    {
        return [
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

    public function scopeFallidos($query)
    {
        return $query->where('resultado', 'fallido');
    }
}
