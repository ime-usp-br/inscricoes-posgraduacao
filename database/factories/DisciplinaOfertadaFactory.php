<?php

namespace Database\Factories;

use App\Models\DisciplinaOfertada;
use App\Models\Periodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DisciplinaOfertada>
 */
class DisciplinaOfertadaFactory extends Factory
{
    protected $model = DisciplinaOfertada::class;

    public function definition(): array
    {
        return [
            'periodo_id' => Periodo::factory(),
            'departamento' => $this->faker->randomElement(['MAT', 'MAC', 'MAP', 'MAE']),
            'codigo' => str_pad((string) $this->faker->numberBetween(0, 9999), 4, '0', STR_PAD_LEFT),
            'nome' => $this->faker->sentence(3),
            'professor_nome' => $this->faker->name(),
            'professor_email' => $this->faker->safeEmail(),
        ];
    }
}

