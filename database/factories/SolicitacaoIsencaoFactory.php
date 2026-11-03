<?php

namespace Database\Factories;

use App\Enums\SolicitacaoIsencaoStatus;
use App\Models\Inscricao;
use App\Models\SolicitacaoIsencao;
use App\Models\SolicitacaoIsencaoDocumento;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SolicitacaoIsencao>
 */
class SolicitacaoIsencaoFactory extends Factory
{
    protected $model = SolicitacaoIsencao::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inscricao_id' => Inscricao::factory(),
            'status' => SolicitacaoIsencaoStatus::Pendente,
            'justificativa' => $this->faker->paragraph(),
            'disciplinas_snapshot' => [],
            'resposta_secretaria' => null,
            'motivo_reprovacao' => null,
            'decidida_por' => null,
            'decidida_em' => null,
            'prazo_pagamento_em' => null,
            'prazo_pagamento_estendido_em' => null,
            'lembrete_agendado_para' => null,
            'lembrete_enviado_em' => null,
            'cancelada_em' => null,
            'motivo_cancelamento' => null,
        ];
    }

    public function aprovada(): static
    {
        return $this->state(fn (): array => [
            'status' => SolicitacaoIsencaoStatus::Aprovada,
            'decidida_por' => User::factory(),
            'decidida_em' => now(),
            'prazo_pagamento_em' => now()->addDays(5),
            'resposta_secretaria' => 'Solicitação aprovada.',
        ]);
    }

    public function reprovada(): static
    {
        return $this->state(fn (): array => [
            'status' => SolicitacaoIsencaoStatus::Reprovada,
            'decidida_por' => User::factory(),
            'decidida_em' => now(),
            'prazo_pagamento_em' => now()->addDays(5),
            'motivo_reprovacao' => 'Documentação insuficiente.',
        ]);
    }

    public function cancelada(): static
    {
        return $this->state(fn (): array => [
            'status' => SolicitacaoIsencaoStatus::Cancelada,
            'cancelada_em' => now(),
            'motivo_cancelamento' => 'Cancelada pelo candidato.',
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $snapshot
     */
    public function comSnapshot(array $snapshot): static
    {
        return $this->state(fn (): array => [
            'disciplinas_snapshot' => $snapshot,
        ]);
    }

    public function comDocumentos(int $quantidade = 1): static
    {
        return $this->has(
            SolicitacaoIsencaoDocumento::factory()->count($quantidade),
            'documentos'
        );
    }
}
