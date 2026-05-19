<?php

namespace Database\Factories;

use App\Enums\InscricaoStatus;
use App\Models\DisciplinaOfertada;
use App\Models\Inscricao;
use App\Models\Periodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inscricao>
 */
class InscricaoFactory extends Factory
{
    protected $model = Inscricao::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'periodo_id' => Periodo::factory(),
            'nome_completo' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'aluno_usp' => false,
            'numero_usp' => null,
            'dados_etapa_2' => null,
            'etapa_concluida' => 0,
            'concluido_em' => null,
            'disciplina_obrigatoria_id' => null,
            'disciplina_opcional_1_id' => null,
            'disciplina_opcional_2_id' => null,
            'status' => InscricaoStatus::Inscrito,
            'aprovacao_obrigatoria_secretaria' => null,
            'aprovacao_opcional_1_secretaria' => null,
            'aprovacao_opcional_2_secretaria' => null,
            'aprovacao_obrigatoria_professor' => null,
            'aprovacao_opcional_1_professor' => null,
            'aprovacao_opcional_2_professor' => null,
        ];
    }

    public function concluida(): static
    {
        return $this->state(fn (array $attributes): array => [
            'etapa_concluida' => 3,
            'concluido_em' => now(),
            'status' => InscricaoStatus::Inscrito,
        ]);
    }

    public function comTresDisciplinas(?Periodo $periodo = null): static
    {
        return $this->state(function (array $attributes) use ($periodo): array {
            if ($periodo === null && isset($attributes['periodo_id']) && is_int($attributes['periodo_id'])) {
                $periodo = Periodo::query()->find($attributes['periodo_id']);
            }

            $periodo ??= Periodo::factory()->create();

            $disciplinas = DisciplinaOfertada::factory()->count(3)->create([
                'periodo_id' => $periodo->id,
            ]);

            return [
                'periodo_id' => $periodo->id,
                'disciplina_obrigatoria_id' => $disciplinas[0]->id,
                'disciplina_opcional_1_id' => $disciplinas[1]->id,
                'disciplina_opcional_2_id' => $disciplinas[2]->id,
            ];
        });
    }
}
