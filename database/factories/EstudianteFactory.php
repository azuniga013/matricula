<?php

namespace Database\Factories;

use App\Models\Estudiante;
use Illuminate\Database\Eloquent\Factories\Factory;

class EstudianteFactory extends Factory
{
    protected $model = Estudiante::class;

    public function definition(): array
    {
        return [
            'codigo' => strtoupper($this->faker->unique()->bothify('EST-####-???')),
            'nombre' => $this->faker->firstName(),
            'apellido' => $this->faker->lastName(),
            'identidad' => $this->faker->unique()->bothify('####-####-#####'),
            'correo' => $this->faker->unique()->safeEmail(),
            'telefono' => $this->faker->phoneNumber(),
            'estado' => 'activo',
            'es_primer_ingreso' => false,
        ];
    }
}
