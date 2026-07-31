<?php

namespace Database\Factories;

use App\Models\OfertaAcademica;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfertaAcademicaFactory extends Factory
{
    protected $model = OfertaAcademica::class;

    public function definition(): array
    {
        return [
            'codigo' => strtoupper($this->faker->unique()->bothify('??-#####-???-???')),
            'cupo_maximo' => 25,
            'cupos_reservados' => 0,
            'cupos_matriculados' => 0,
            'estado' => 'borrador',
            'acepta_cambios_horario' => false,
        ];
    }
}
