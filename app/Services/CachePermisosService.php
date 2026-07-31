<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CachePermisosService
{
    protected string $prefijo;
    protected int $ttl;

    public function __construct()
    {
        $this->prefijo = config('seguridad.cache.prefijo', 'permisos_usuario_');
        $this->ttl = config('seguridad.cache.ttl_minutos', 60) * 60;
    }

    public function obtenerPermisos(User $user): \Illuminate\Support\Collection
    {
        $cacheKey = $this->prefijo . $user->id;

        return Cache::remember($cacheKey, $this->ttl, function () use ($user) {
            return $user->permisosEfectivos();
        });
    }

    public function invalidarPermisos(int $usuarioId): void
    {
        Cache::forget($this->prefijo . $usuarioId);
    }

    public function invalidarPermisosMasivos(array $usuarioIds): void
    {
        foreach ($usuarioIds as $id) {
            $this->invalidarPermisos($id);
        }
    }

    public function invalidarTodos(): void
    {
        $usuarioIds = User::pluck('id')->toArray();
        $this->invalidarPermisosMasivos($usuarioIds);
    }
}
