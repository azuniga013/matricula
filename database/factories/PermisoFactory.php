<?php

namespace Database\Factories;

use App\Models\OpcionModulo;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermisoFactory extends Factory
{
    protected static int $count = 0;

    public function definition(): array
    {
        static::$count++;

        $acciones = ['consultar', 'crear', 'modificar', 'eliminar', 'aprobar', 'configurar'];

        return [
            'opcion_modulo_id' => OpcionModulo::factory(),
            'codigo' => 'permiso_' . strtolower(uniqid()),
            'nombre' => fake()->word(),
            'accion' => $acciones[array_rand($acciones)],
            'estado' => 'activo',
        ];
    }
}
