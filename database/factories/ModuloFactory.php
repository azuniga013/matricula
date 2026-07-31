<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ModuloFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codigo' => strtolower(fake()->unique()->bothify('??##')),
            'nombre' => fake()->unique()->word(),
            'orden' => fake()->numberBetween(1, 100),
            'estado' => 'activo',
        ];
    }
}
