<?php

namespace Database\Seeders;

use App\Models\DisciplinaOfertada;
use App\Models\Periodo;
use Illuminate\Database\Seeder;

class DisciplinaOfertadaSeeder extends Seeder
{
    public function run(): void
    {
        $periodo = Periodo::query()
            ->abertos()
            ->orderByDesc('ano')
            ->orderByDesc('semestre')
            ->first();

        if ($periodo === null) {
            $this->call(PeriodoSeeder::class);
            $periodo = Periodo::query()->firstOrFail();
        }

        $disciplinas = [
            [
                'departamento' => 'MAT',
                'codigo' => '0101',
                'nome' => 'Álgebra Linear',
                'professor_nome' => 'Prof. João Silva',
                'professor_email' => 'joao.silva@ime.usp.br',
            ],
            [
                'departamento' => 'MAC',
                'codigo' => '0201',
                'nome' => 'Análise Real I',
                'professor_nome' => 'Prof. Maria Santos',
                'professor_email' => 'maria.santos@ime.usp.br',
            ],
            [
                'departamento' => 'MAP',
                'codigo' => '0301',
                'nome' => 'Métodos Numéricos',
                'professor_nome' => 'Prof. Carlos Oliveira',
                'professor_email' => 'carlos.oliveira@ime.usp.br',
            ],
            [
                'departamento' => 'MAE',
                'codigo' => '0401',
                'nome' => 'Estatística Aplicada',
                'professor_nome' => 'Prof. Ana Costa',
                'professor_email' => 'ana.costa@ime.usp.br',
            ],
            [
                'departamento' => 'MPM',
                'codigo' => '0501',
                'nome' => 'Modelagem de Processos Matemáticos',
                'professor_nome' => null,
                'professor_email' => null,
            ],
        ];

        foreach ($disciplinas as $disciplina) {
            DisciplinaOfertada::query()->updateOrCreate(
                [
                    'periodo_id' => $periodo->id,
                    'departamento' => $disciplina['departamento'],
                    'codigo' => $disciplina['codigo'],
                ],
                [
                    'nome' => $disciplina['nome'],
                    'professor_nome' => $disciplina['professor_nome'],
                    'professor_email' => $disciplina['professor_email'],
                ],
            );
        }
    }
}
