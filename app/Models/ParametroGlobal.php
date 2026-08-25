<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ParametroGlobal extends Model
{
    use HasFactory;

    protected $table = 'parametros_globales';
    public $timestamps = true;

    protected $fillable = [
        'grupo', 'codigo', 'nombre', 'valor', 'tipo', 'opciones',
        'descripcion', 'estado', 'creado_por', 'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
            'opciones' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
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
        $ttl = max(0, (int) config('parametros_globales.cache_ttl_segundos', 300));

        if ($ttl === 0) {
            $param = self::where('grupo', $grupo)->where('codigo', $codigo)->where('estado', true)->first();

            return $param?->valor;
        }

        return Cache::remember("parametro_global:{$grupo}:{$codigo}", $ttl, function () use ($codigo, $grupo) {
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
