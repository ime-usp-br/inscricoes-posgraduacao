<?php

namespace Tests\Feature;

use App\Enums\SolicitacaoIsencaoStatus;
use App\Models\Inscricao;
use App\Models\SolicitacaoIsencao;
use App\Models\SolicitacaoIsencaoDocumento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OwenIt\Auditing\Contracts\Auditable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SolicitacaoIsencaoEstruturaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function schema_and_relationships_are_available(): void
    {
        $inscricao = Inscricao::factory()->concluida()->comTresDisciplinas()->create();

        $solicitacao = SolicitacaoIsencao::factory()->create([
            'inscricao_id' => $inscricao->id,
            'disciplinas_snapshot' => [
                [
                    'slot' => 'obrigatoria',
                    'disciplina_id' => $inscricao->disciplina_obrigatoria_id,
                    'codigo' => $inscricao->disciplinaObrigatoria->codigo_completo,
                    'nome' => $inscricao->disciplinaObrigatoria->nome,
                    'departamento' => $inscricao->disciplinaObrigatoria->departamento,
                    'valor' => 107.0,
                ],
            ],
        ]);

        $documento = SolicitacaoIsencaoDocumento::factory()->create([
            'solicitacao_isencao_id' => $solicitacao->id,
        ]);

        $this->assertTrue($solicitacao->inscricao->is($inscricao));
        $this->assertTrue($inscricao->solicitacaoIsencao->is($solicitacao));
        $this->assertTrue($solicitacao->documentos->contains($documento));
        $this->assertSame(SolicitacaoIsencaoStatus::Pendente, $solicitacao->status);
        $this->assertIsArray($solicitacao->disciplinas_snapshot);
        $this->assertEquals(107.0, $solicitacao->disciplinas_snapshot[0]['valor']);
    }

    #[Test]
    public function factories_create_decided_and_cancelled_states(): void
    {
        $aprovada = SolicitacaoIsencao::factory()->aprovada()->create();
        $reprovada = SolicitacaoIsencao::factory()->reprovada()->create();
        $cancelada = SolicitacaoIsencao::factory()->cancelada()->create();

        $this->assertSame(SolicitacaoIsencaoStatus::Aprovada, $aprovada->status);
        $this->assertNotNull($aprovada->decidida_por);
        $this->assertNotNull($aprovada->decidida_em);
        $this->assertNotNull($aprovada->prazo_pagamento_em);

        $this->assertSame(SolicitacaoIsencaoStatus::Reprovada, $reprovada->status);
        $this->assertNotNull($reprovada->motivo_reprovacao);
        $this->assertNotNull($reprovada->prazo_pagamento_em);

        $this->assertSame(SolicitacaoIsencaoStatus::Cancelada, $cancelada->status);
        $this->assertNotNull($cancelada->cancelada_em);
        $this->assertNotNull($cancelada->motivo_cancelamento);
    }

    #[Test]
    public function factory_creates_documents(): void
    {
        $solicitacao = SolicitacaoIsencao::factory()->comDocumentos(3)->create();

        $this->assertCount(3, $solicitacao->documentos);
        $this->assertSame('application/pdf', $solicitacao->documentos->first()->mime);
    }

    #[Test]
    public function deleting_request_cascades_documents(): void
    {
        $solicitacao = SolicitacaoIsencao::factory()->comDocumentos(2)->create();
        $documentoIds = $solicitacao->documentos->pluck('id')->all();

        $solicitacao->delete();

        foreach ($documentoIds as $documentoId) {
            $this->assertDatabaseMissing('solicitacao_isencao_documentos', ['id' => $documentoId]);
        }
    }

    #[Test]
    public function deleting_inscription_cascades_request_and_documents(): void
    {
        $inscricao = Inscricao::factory()->concluida()->comTresDisciplinas()->create();
        $solicitacao = SolicitacaoIsencao::factory()->comDocumentos(2)->create([
            'inscricao_id' => $inscricao->id,
        ]);
        $documentoIds = $solicitacao->documentos->pluck('id')->all();

        $inscricao->delete();

        $this->assertDatabaseMissing('solicitacoes_isencao', ['id' => $solicitacao->id]);

        foreach ($documentoIds as $documentoId) {
            $this->assertDatabaseMissing('solicitacao_isencao_documentos', ['id' => $documentoId]);
        }
    }

    #[Test]
    public function payment_disciplines_are_deterministic_and_require_payment(): void
    {
        $inscricao = Inscricao::factory()->comTresDisciplinas()->create();

        $this->assertSame([
            'MAC',
            'MAE',
            'MAC',
        ], [
            $inscricao->disciplinaObrigatoria->departamento,
            $inscricao->disciplinaOpcional1->departamento,
            $inscricao->disciplinaOpcional2->departamento,
        ]);

        foreach ($inscricao->disciplinasPorSlot() as $disciplina) {
            $this->assertTrue(Inscricao::departamentoRequerPagamento($disciplina));
        }
    }

    #[Test]
    public function models_are_auditable(): void
    {
        $this->assertInstanceOf(Auditable::class, new SolicitacaoIsencao);
        $this->assertInstanceOf(Auditable::class, new SolicitacaoIsencaoDocumento);
    }

    #[Test]
    public function configuration_has_expected_defaults(): void
    {
        $this->assertSame(5, config('isencao.documentos.max_arquivos'));
        $this->assertSame(1024, config('isencao.documentos.max_kb'));
        $this->assertSame(5, config('isencao.prazo_pagamento_dias'));
        $this->assertSame(2, config('isencao.lembrete_dias_antes'));
        $this->assertSame(30, config('isencao.link_validade_dias'));
        $this->assertSame(2, config('isencao.sla_dias_uteis'));
    }
}
