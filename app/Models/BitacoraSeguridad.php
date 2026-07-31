<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BitacoraSeguridad extends Model
{
    use HasFactory;

    protected $table = 'bitacora_seguridad';
    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'accion',
        'modulo',
        'registro_id',
        'valores_antes',
        'valores_despues',
        'ip',
        'agente',
        'resultado',
        'motivo',
    ];

    protected function casts(): array
    {
        return [
            'valores_antes' => 'array',
            'valores_despues' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function scopeRecientes($query, int $dias = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($dias));
    }
}
