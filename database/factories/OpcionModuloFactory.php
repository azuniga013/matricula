<?php

namespace Database\Factories;

use App\Models\Modulo;
use Illuminate\Database\Eloquent\Factories\Factory;

class OpcionModuloFactory extends Factory
{
    protected static int $count = 0;

    public function definition(): array
    {
        static::$count++;

        return [
            'modulo_id' => Modulo::factory(),
            'codigo' => 'opcion_' . strtolower(uniqid()),
            'nombre' => fake()->word(),
            'ruta' => '/' . fake()->unique()->slug(),
            'estado' => 'activo',
        ];
    }
}
