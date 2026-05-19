<?php

namespace Database\Seeders;

use App\Models\DisciplinaOfertada;
use App\Models\Inscricao;
use App\Models\Periodo;
use Illuminate\Database\Seeder;

class InscricaoSeeder extends Seeder
{
    public function run(): void
    {
        $periodo = Periodo::query()
            ->where('status', 'aberto')
            ->orderByDesc('ano')
            ->orderByDesc('semestre')
            ->first();

        if ($periodo === null) {
            $this->call(PeriodoSeeder::class);
            $periodo = Periodo::query()->firstOrFail();
        }

        $disciplinas = DisciplinaOfertada::query()
            ->where('periodo_id', $periodo->id)
            ->orderBy('departamento')
            ->get();

        if ($disciplinas->count() < 3) {
            $this->call(DisciplinaOfertadaSeeder::class);
            $disciplinas = DisciplinaOfertada::query()
                ->where('periodo_id', $periodo->id)
                ->orderBy('departamento')
                ->get();
        }

        $ids = $disciplinas->pluck('id')->all();

        $combinacoes = [
            [$ids[0], $ids[1], $ids[2]],
            [$ids[0], $ids[1], $ids[3]],
            [$ids[0], $ids[2], $ids[3]],
            [$ids[1], $ids[0], $ids[2]],
            [$ids[1], $ids[2], $ids[3]],
            [$ids[2], $ids[0], $ids[1]],
            [$ids[2], $ids[3], $ids[0]],
            [$ids[3], $ids[0], $ids[1]],
            [$ids[3], $ids[1], $ids[2]],
            [$ids[0], $ids[2], null],
        ];

        foreach ($combinacoes as $combinacao) {
            Inscricao::factory()
                ->concluida()
                ->create([
                    'periodo_id' => $periodo->id,
                    'disciplina_obrigatoria_id' => $combinacao[0],
                    'disciplina_opcional_1_id' => $combinacao[1],
                    'disciplina_opcional_2_id' => $combinacao[2],
                ]);
        }
    }
}
