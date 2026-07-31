<?php

namespace Database\Factories;

use App\Models\Modalidad;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModalidadFactory extends Factory
{
    protected $model = Modalidad::class;

    public function definition(): array
    {
        $nombre = $this->faker->unique()->word();

        return [
            'codigo' => strtoupper(substr($nombre, 0, 3)) . $this->faker->unique()->numberBetween(1, 999),
            'nombre' => $nombre,
            'tipo' => 'regimen_academico',
            'descripcion' => $this->faker->sentence(),
            'estado' => 'activo',
        ];
    }

    public function regimenAcademico(): static
    {
        return $this->state(fn () => ['tipo' => 'regimen_academico']);
    }

    public function atencion(): static
    {
        return $this->state(fn () => ['tipo' => 'atencion']);
    }
}
