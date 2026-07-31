<?php

namespace App\Services;

use App\Models\Modulo;
use App\Models\OpcionModulo;
use App\Models\Permiso;
use Illuminate\Support\Facades\DB;

class RegistroPermisosService
{
    public function registrarModulosDesdeConfig(): void
    {
        $modulos = config('rbac.modulos', []);

        DB::transaction(function () use ($modulos) {
            foreach ($modulos as $codigoModulo => $configModulo) {
                $modulo = Modulo::updateOrCreate(
                    ['codigo' => $codigoModulo],
                    [
                        'nombre' => $configModulo['nombre'],
                        'orden' => $configModulo['orden'] ?? 0,
                        'estado' => 'activo',
                    ]
                );

                $accionesModulo = $configModulo['acciones'] ?? ['consultar'];

                // Opción sintética nivel módulo: aloja los permisos agregados
                // <modulo>.<accion> que exige el middleware de la API (AGENTS.md §18).
                $opcionGeneral = OpcionModulo::updateOrCreate(
                    ['codigo' => $codigoModulo],
                    [
                        'modulo_id' => $modulo->id,
                        'nombre' => 'General',
                        'ruta' => null,
                        'orden' => 0,
                        'estado' => 'activo',
                    ]
                );

                foreach ($accionesModulo as $accion) {
                    Permiso::updateOrCreate(
                        ['codigo' => $codigoModulo . '.' . $accion],
                        [
                            'opcion_modulo_id' => $opcionGeneral->id,
                            'nombre' => $configModulo['nombre'] . ' ' . ucfirst($accion),
                            'accion' => $accion,
                            'estado' => 'activo',
                        ]
                    );
                }

                foreach ($configModulo['opciones'] as $codigoOpcion => $configOpcion) {
                    $opcion = OpcionModulo::updateOrCreate(
                        ['codigo' => $codigoOpcion],
                        [
                            'modulo_id' => $modulo->id,
                            'nombre' => $configOpcion['nombre'],
                            'ruta' => $configOpcion['ruta'] ?? null,
                            'orden' => 0,
                            'estado' => 'activo',
                        ]
                    );

                    foreach ($accionesModulo as $accion) {
                        $codigoPermiso = $codigoOpcion . '.' . $accion;
                        $nombrePermiso = $configOpcion['nombre'] . ' ' . ucfirst($accion);

                        Permiso::updateOrCreate(
                            ['codigo' => $codigoPermiso],
                            [
                                'opcion_modulo_id' => $opcion->id,
                                'nombre' => $nombrePermiso,
                                'accion' => $accion,
                                'estado' => 'activo',
                            ]
                        );
                    }
                }
            }
        });
    }

    public function obtenerPermisosPorModulo(string $codigoModulo): \Illuminate\Support\Collection
    {
        return Permiso::whereHas('opcionModulo.modulo', function ($q) use ($codigoModulo) {
            $q->where('codigo', $codigoModulo);
        })->activos()->get();
    }

    public function obtenerTodosLosPermisos(): \Illuminate\Support\Collection
    {
        return Permiso::activos()->with('opcionModulo.modulo')->get();
    }
}
