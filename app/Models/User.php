<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'telefono',
        'estado',
        'bloqueado_hasta',
        'debe_cambiar_contrasena',
        'docente_id',
        'sucursal_id',
        'creado_por',
        'actualizado_por',
        'creado_en',
        'actualizado_en',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'bloqueado_hasta' => 'datetime',
            'debe_cambiar_contrasena' => 'boolean',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'usuario_roles', 'usuario_id', 'rol_id')
            ->withPivot('estado')
            ->wherePivot('estado', 'activo');
    }

    public function sucursales()
    {
        return $this->belongsToMany(Sucursal::class, 'usuario_sucursales', 'usuario_id', 'sucursal_id')
            ->withPivot('estado')
            ->wherePivot('estado', 'activo');
    }

    public function alcances()
    {
        return $this->hasMany(AlcanceUsuario::class, 'usuario_id');
    }

    public function sesiones()
    {
        return $this->hasMany(SesionUsuario::class, 'usuario_id');
    }

    public function intentosAcceso()
    {
        return $this->hasMany(IntentoAcceso::class, 'usuario_id');
    }

    public function bitacoraSeguridad()
    {
        return $this->hasMany(BitacoraSeguridad::class, 'usuario_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    public function tienePermiso(string $codigo): bool
    {
        return $this->permisosEfectivos()->contains('codigo', $codigo);
    }

    public function permisosEfectivos()
    {
        $rolIds = $this->roles()->activos()->pluck('roles.id');

        if ($rolIds->isEmpty()) {
            return collect();
        }

        return Permiso::activos()->whereHas('roles', function ($q) use ($rolIds) {
            $q->whereIn('roles.id', $rolIds)
                ->where('rol_permisos.estado', 'activo');
        })->get();
    }

    public function tieneAlcanceGlobal(): bool
    {
        return AlcanceUsuario::where('usuario_id', $this->id)
                ->where('estado', 'activo')
                ->where('tipo', 'global')
                ->exists()
            || $this->roles()->where('roles.estado', 'activo')
                ->whereHas('alcances', function ($q) {
                    $q->where('estado', 'activo')->where('tipo', 'global');
                })->exists();
    }

    public function idsSucursalesAsignadas(): array
    {
        return $this->sucursales()->activos()->pluck('sucursales.id')->toArray();
    }

    public function estaBloqueado(): bool
    {
        return $this->bloqueado_hasta && $this->bloqueado_hasta->isFuture();
    }

    public function scopeActivos($query)
    {
        return $query->where('users.estado', 'activo');
    }
}
