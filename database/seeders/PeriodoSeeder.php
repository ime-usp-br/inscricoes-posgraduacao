<?php

namespace Database\Seeders;

use App\Models\Periodo;
use Illuminate\Database\Seeder;

class PeriodoSeeder extends Seeder
{
    public function run(): void
    {
        Periodo::query()->updateOrCreate(
            [
                'ano' => (int) date('Y'),
                'semestre' => 1,
            ],
            [
                'data_inicio_inscricao' => now()->subWeek(),
                'data_fim_inscricao' => now()->addMonths(2),
                'status' => 'aberto',
            ],
        );
    }
}
