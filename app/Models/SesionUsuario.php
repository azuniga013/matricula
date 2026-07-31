<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SesionUsuario extends Model
{
    use HasFactory;

    protected $table = 'sesiones_usuario';
    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'token_hash',
        'ip',
        'agente',
        'vencimiento',
        'revocado_en',
        'ultimo_acceso',
    ];

    protected function casts(): array
    {
        return [
            'vencimiento' => 'datetime',
            'revocado_en' => 'datetime',
            'ultimo_acceso' => 'datetime',
            'creado_en' => 'datetime',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function scopeActivas($query)
    {
        return $query->whereNull('revocado_en')
            ->where(function ($q) {
                $q->whereNull('vencimiento')
                    ->orWhere('vencimiento', '>', now());
            });
    }

    public function estaActiva(): bool
    {
        return is_null($this->revocado_en)
            && (is_null($this->vencimiento) || $this->vencimiento->isFuture());
    }
}
