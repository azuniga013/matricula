<?php

namespace Database\Factories;

use App\Models\DepartamentoAcademico;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartamentoAcademicoFactory extends Factory
{
    protected $model = DepartamentoAcademico::class;

    public function definition(): array
    {
        $nombre = $this->faker->unique()->word();

        return [
            'codigo' => strtoupper(substr($nombre, 0, 3)) . $this->faker->unique()->numberBetween(1, 999),
            'nombre' => $nombre,
            'descripcion' => $this->faker->sentence(),
            'estado' => 'activo',
        ];
    }
}
