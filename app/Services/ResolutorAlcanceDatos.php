<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ResolutorAlcanceDatos
{
    public function __construct(
        protected CachePermisosService $cachePermisos,
    ) {}

    public function aplicarAlcance(Builder $query, User $user, string $entidad = ''): Builder
    {
        if ($user->tieneAlcanceGlobal()) {
            return $query;
        }

        $idsSucursales = $user->idsSucursalesAsignadas();

        if (!empty($idsSucursales) && $entidad === 'sucursales') {
            return $query->whereIn($entidad . '.id', $idsSucursales);
        }

        if (!empty($idsSucursales) && $this->entidadTieneSucursal($entidad)) {
            return $query->whereIn($entidad . '.sucursal_id', $idsSucursales);
        }

        if ($user->docente_id && $entidad === 'ofertas_academicas') {
            return $query->where($entidad . '.docente_id', $user->docente_id);
        }

        if ($this->entidadTieneCreadoPor($entidad)) {
            return $query->where($entidad . '.creado_por', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function puedeAccederRegistro(Model $registro, User $user): bool
    {
        if ($user->tieneAlcanceGlobal()) {
            return true;
        }

        $clase = class_basename($registro);

        $idsSucursales = $user->idsSucursalesAsignadas();
        if (!empty($idsSucursales) && method_exists($registro, 'sucursal_id')) {
            return in_array($registro->sucursal_id, $idsSucursales);
        }

        if ($user->docente_id && method_exists($registro, 'docente_id')) {
            return $registro->docente_id === $user->docente_id;
        }

        if (method_exists($registro, 'creado_por')) {
            return $registro->creado_por === $user->id;
        }

        return false;
    }

    protected function entidadTieneSucursal(string $entidad): bool
    {
        return in_array($entidad, [
            'sucursales', 'estudiantes', 'ofertas_academicas', 'matriculas', 'pagos',
            'recibos_caja', 'sesiones_caja', 'aulas', 'grupos_whatsapp',
            'inventario_libros',
        ]);
    }

    protected function entidadTieneCreadoPor(string $entidad): bool
    {
        return !empty($entidad);
    }
}
