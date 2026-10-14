<?php

namespace Database\Factories;

use App\Models\Periodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Periodo>
 */
class PeriodoFactory extends Factory
{
    protected $model = Periodo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ano' => (int) date('Y') + $this->faker->unique()->numberBetween(0, 100000),
            'semestre' => $this->faker->randomElement([1, 2]),
            'data_inicio_inscricao' => now()->subWeek(),
            'data_fim_inscricao' => now()->addMonth(),
        ];
    }
}
