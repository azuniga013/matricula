<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RolFactory extends Factory
{
    protected static int $count = 0;

    public function definition(): array
    {
        static::$count++;

        return [
            'codigo' => 'ROL_' . strtoupper(uniqid()),
            'nombre' => fake()->unique()->word(),
            'descripcion' => fake()->sentence(),
            'estado' => 'activo',
        ];
    }
}
