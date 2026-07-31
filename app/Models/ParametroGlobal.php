<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ParametroGlobal extends Model
{
    use HasFactory;

    protected $table = 'parametros_globales';
    protected $fillable = [
        'grupo', 'codigo', 'nombre', 'valor', 'tipo', 'opciones',
        'descripcion', 'estado', 'creado_por', 'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
            'opciones' => 'array',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function scopeActivos($query)
    {
        return $query->where('parametros_globales.estado', true);
    }

    public function scopePorGrupo($query, string $grupo)
    {
        return $query->where('parametros_globales.grupo', $grupo);
    }

    public static function obtener(string $codigo, string $grupo = '01'): mixed
    {
        return Cache::remember("parametro_global:{$grupo}:{$codigo}", 300, function () use ($codigo, $grupo) {
            $param = self::where('grupo', $grupo)->where('codigo', $codigo)->where('estado', true)->first();
            return $param?->valor;
        });
    }

    public static function obtenerBool(string $codigo, string $grupo = '01'): bool
    {
        $valor = self::obtener($codigo, $grupo);
        return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
    }

    public static function obtenerInt(string $codigo, string $grupo = '01', int $default = 0): int
    {
        $valor = self::obtener($codigo, $grupo);
        return $valor !== null ? (int) $valor : $default;
    }

    public static function invalidarCache(): void
    {
        $parametros = self::all(['grupo', 'codigo']);
        foreach ($parametros as $p) {
            Cache::forget("parametro_global:{$p->grupo}:{$p->codigo}");
        }
    }
}