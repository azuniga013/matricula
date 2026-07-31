<?php

namespace Database\Factories;

use App\Models\PlanCobro;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanCobroFactory extends Factory
{
    protected $model = PlanCobro::class;

    public function definition(): array
    {
        return [
            'codigo' => strtoupper('PLN-' . fake()->unique()->bothify('####')),
            'nombre' => fake()->words(3, true),
            'descripcion' => fake()->sentence(),
            'estado' => 'activo',
            'creado_por' => 1,
        ];
    }
}
