<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SucursalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codigo' => strtoupper(fake()->unique()->bothify('??##')),
            'nombre' => fake()->city(),
            'direccion' => fake()->address(),
            'telefono' => fake()->phoneNumber(),
            'correo' => fake()->safeEmail(),
            'estado' => 'activo',
        ];
    }
}
