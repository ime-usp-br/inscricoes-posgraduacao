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

    private static int $periodoFactoryCounter = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $suffix = self::$periodoFactoryCounter++;

        return [
            'ano' => (int) date('Y') + intdiv($suffix, 2),
            'semestre' => ($suffix % 2) + 1,
            'data_inicio_inscricao' => now()->subWeek(),
            'data_fim_inscricao' => now()->addMonth(),
        ];
    }
}
