<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Estudiante extends Model
{
    use HasFactory;

    protected $table = 'estudiantes';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'apellido',
        'identidad',
        'fecha_nacimiento',
        'sexo',
        'correo',
        'telefono',
        'direccion',
        'sucursal_id',
        'nombre_padre',
        'telefono_padre',
        'correo_padre',
        'estado',
        'es_primer_ingreso',
        'creado_por',
        'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'es_primer_ingreso' => 'boolean',
            'creado_en' => 'datetime',
            'actualizado_en' => 'datetime',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function acceso(): HasOne
    {
        return $this->hasOne(AccesoEstudiante::class, 'estudiante_id');
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(SolicitudActualizacionDatos::class, 'estudiante_id');
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class, 'estudiante_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'estudiante_id');
    }

    public function contactosResponsable(): HasMany
    {
        return $this->hasMany(ContactoResponsableEstudiante::class, 'estudiante_id');
    }

    public function recibos(): HasMany
    {
        return $this->hasMany(ReciboCaja::class, 'estudiante_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function scopeActivos($query)
    {
        return $query->where('estudiantes.estado', 'activo');
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('estudiantes.apellido')->orderBy('estudiantes.nombre');
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }

    public function getCorreoEnmascaradoAttribute(): ?string
    {
        if (! $this->correo) {
            return null;
        }
        $parts = explode('@', $this->correo);
        $local = $parts[0];
        $dominio = $parts[1] ?? '';
        $len = strlen($local);
        if ($len <= 2) {
            return str_repeat('*', $len).'@'.$dominio;
        }

        return substr($local, 0, 2).str_repeat('*', $len - 2).'@'.$dominio;
    }

    public function getTelefonoEnmascaradoAttribute(): ?string
    {
        if (! $this->telefono) {
            return null;
        }
        $len = strlen($this->telefono);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return str_repeat('*', $len - 4).substr($this->telefono, -4);
    }
}
