<?php

namespace Tests\Feature;

use App\Enums\AprovacaoSecretariaDisciplina;
use App\Enums\PagamentoDisciplinaStatus;
use App\Enums\ResumoPagamentoInscricao;
use App\Enums\SolicitacaoIsencaoStatus;
use App\Models\DisciplinaOfertada;
use App\Models\Inscricao;
use App\Models\Periodo;
use App\Models\User;
use App\Services\InscricaoPagamentoService;
use App\Services\SolicitacaoIsencaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SolicitacaoIsencaoDominioTest extends TestCase
{
    use RefreshDatabase;

    private SolicitacaoIsencaoService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['isencao.habilitado' => true]);

        Role::firstOrCreate(['name' => 'Secretario', 'guard_name' => 'web']);

        $this->service = app(SolicitacaoIsencaoService::class);
    }

    #[Test]
    public function pode_solicitar_isencao_somente_quando_elegivel(): void
    {
        $inscricao = $this->criarInscricao(['MAC', 'MAE']);

        $this->assertTrue($inscricao->podeSolicitarIsencao());

        config(['isencao.habilitado' => false]);
        $this->assertFalse($inscricao->podeSolicitarIsencao());

        config(['isencao.habilitado' => true]);
        $inscricao->etapa_concluida = 2;
        $this->assertFalse($inscricao->podeSolicitarIsencao());
        $inscricao->etapa_concluida = 3;

        $solicitacao = $this->service->criar($inscricao, ['obrigatoria'], $this->justificativa());
        $this->assertFalse($inscricao->fresh()->podeSolicitarIsencao());

        $this->service->cancelar($solicitacao);
        $this->assertTrue($inscricao->fresh()->podeSolicitarIsencao());

        $this->service->criar($inscricao->fresh(), ['obrigatoria'], $this->justificativa());
        $this->assertFalse($inscricao->fresh()->podeSolicitarIsencao());

        $inscricao->refresh();
        $inscricao->pix_pago_em = now();
        $this->assertFalse($inscricao->podeSolicitarIsencao());
    }

    #[Test]
    public function criar_solicitacao_parcial_gera_snapshot_e_marca_slots(): void
    {
        $inscricao = $this->criarInscricao(['MAC', 'MAE', 'MAC']);
        $inscricao->update(['pix_idfpix' => 'PIX-ANTIGO', 'pix_status' => 'Ativo']);

        $solicitacao = $this->service->criar(
            $inscricao,
            ['obrigatoria', 'opcional_2'],
            $this->justificativa()
        );

        $inscricao->refresh();

        $this->assertSame(SolicitacaoIsencaoStatus::Pendente, $solicitacao->status);
        $this->assertSame($this->justificativa(), $solicitacao->justificativa);
        $this->assertCount(2, $solicitacao->disciplinas_snapshot);
        $this->assertSame(['obrigatoria', 'opcional_2'], array_column($solicitacao->disciplinas_snapshot, 'slot'));
        $this->assertEquals(107.0, $solicitacao->disciplinas_snapshot[0]['valor']);
        $this->assertSame('MAC', $solicitacao->disciplinas_snapshot[0]['departamento']);

        $this->assertSame(
            PagamentoDisciplinaStatus::AguardandoIsencao->value,
            $inscricao->pagamentos_disciplinas['obrigatoria']
        );
        $this->assertSame(
            PagamentoDisciplinaStatus::Pendente->value,
            $inscricao->pagamentos_disciplinas['opcional_1']
        );
        $this->assertSame(
            PagamentoDisciplinaStatus::AguardandoIsencao->value,
            $inscricao->pagamentos_disciplinas['opcional_2']
        );

        $this->assertSame(ResumoPagamentoInscricao::AguardandoIsencao, $inscricao->resumoPagamento());
        $this->assertTrue($inscricao->temPagamentoEmAberto());
        $this->assertNull($inscricao->pix_idfpix);
    }

    #[Test]
    public function criar_rejeita_justificativa_curta_e_slots_invalidos(): void
    {
        $inscricao = $this->criarInscricao(['MAC']);

        try {
            $this->service->criar($inscricao, ['obrigatoria'], 'curta');
            $this->fail('Justificativa curta deveria falhar.');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }

        try {
            $this->service->criar($inscricao, ['opcional_1'], $this->justificativa());
            $this->fail('Slot inexistente deveria falhar.');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }
    }

    #[Test]
    public function pagamento_fica_bloqueado_enquanto_solicitacao_estiver_pendente(): void
    {
        $inscricao = $this->criarInscricao(['MAC', 'MAE']);
        $this->service->criar($inscricao, ['obrigatoria'], $this->justificativa());

        Http::fake();

        try {
            app(InscricaoPagamentoService::class)->garantirPixAtivo($inscricao->fresh());
            $this->fail('A geração de Pix deveria estar bloqueada.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('isenção', $exception->getMessage());
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function decisao_aprovada_marca_disciplinas_isentas_e_agenda_prazo(): void
    {
        $inscricao = $this->criarInscricao(['MAC', 'MAE']);
        $secretario = $this->secretario();

        $solicitacao = $this->service->criar($inscricao, ['obrigatoria', 'opcional_1'], $this->justificativa());
        $solicitacao = $this->service->decidir($solicitacao, $secretario, true, 'Documentação aceita.');

        $inscricao->refresh();

        $this->assertSame(SolicitacaoIsencaoStatus::Aprovada, $solicitacao->status);
        $this->assertTrue($solicitacao->decididaPor->is($secretario));
        $this->assertNotNull($solicitacao->decidida_em);
        $this->assertSame('Documentação aceita.', $solicitacao->resposta_secretaria);
        $this->assertNull($solicitacao->motivo_reprovacao);

        $this->assertSame(
            PagamentoDisciplinaStatus::Isento->value,
            $inscricao->pagamentos_disciplinas['obrigatoria']
        );
        $this->assertSame(
            PagamentoDisciplinaStatus::Isento->value,
            $inscricao->pagamentos_disciplinas['opcional_1']
        );
        $this->assertSame(ResumoPagamentoInscricao::Isento, $inscricao->resumoPagamento());
        $this->assertFalse($inscricao->temPagamentoEmAberto());

        $prazoDecisao = $solicitacao->decidida_em->copy()
            ->addDays((int) config('isencao.prazo_pagamento_dias'))
            ->endOfDay();
        $this->assertSame(
            $prazoDecisao->toDateTimeString(),
            $solicitacao->prazo_pagamento_em?->toDateTimeString()
        );

        $prazoEsperado = $inscricao->periodo->data_fim_inscricao->copy();
        if ($prazoDecisao->greaterThan($prazoEsperado)) {
            $prazoEsperado = $prazoDecisao;
        }

        $this->assertSame(
            $prazoEsperado->endOfDay()->toDateTimeString(),
            $inscricao->prazoPagamento()?->toDateTimeString()
        );
        $this->assertSame(
            $prazoEsperado->copy()->subDays((int) config('isencao.lembrete_dias_antes'))->endOfDay()->toDateTimeString(),
            $solicitacao->lembrete_agendado_para?->toDateTimeString()
        );
    }

    #[Test]
    public function detalhes_da_inscricao_exibem_disciplina_isenta_apos_decisao_aprovada(): void
    {
        $inscricao = $this->criarInscricao(['MAC']);
        $usuario = $this->secretario();
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $usuario->assignRole('Admin');

        $solicitacao = $this->service->criar($inscricao, ['obrigatoria'], $this->justificativa());
        $this->service->decidir($solicitacao, $usuario, true, 'Documentação aceita.');

        $inscricao->update([
            'aprovacao_obrigatoria_secretaria' => AprovacaoSecretariaDisciplina::Aprovado,
        ]);

        foreach (['inscricoes.show', 'professor.inscricoes.show'] as $route) {
            $this->actingAs($usuario)
                ->get(route($route, $inscricao))
                ->assertOk()
                ->assertSee('Pagamento:')
                ->assertSee('Isento')
                ->assertDontSee('Não pago');
        }
    }

    #[Test]
    public function decisao_reprovada_exige_motivo_e_restaura_pendencias(): void
    {
        $inscricao = $this->criarInscricao(['MAC', 'MAE']);
        $secretario = $this->secretario();
        $solicitacao = $this->service->criar($inscricao, ['obrigatoria', 'opcional_1'], $this->justificativa());

        try {
            $this->service->decidir($solicitacao, $secretario, false);
            $this->fail('Reprovação sem motivo deveria falhar.');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }

        $solicitacao = $this->service->decidir($solicitacao->fresh(), $secretario, false, null, 'Renda incompatível.');

        $inscricao->refresh();

        $this->assertSame(SolicitacaoIsencaoStatus::Reprovada, $solicitacao->status);
        $this->assertSame('Renda incompatível.', $solicitacao->motivo_reprovacao);
        $this->assertSame(PagamentoDisciplinaStatus::Pendente->value, $inscricao->pagamentos_disciplinas['obrigatoria']);
        $this->assertSame(PagamentoDisciplinaStatus::Pendente->value, $inscricao->pagamentos_disciplinas['opcional_1']);
        $this->assertSame(ResumoPagamentoInscricao::NaoPago, $inscricao->resumoPagamento());
        $this->assertTrue($inscricao->temPagamentoEmAberto());

        $this->assertTrue($this->service->podeRevisar($solicitacao));

        $inscricao->update(['pix_idfpix' => 'PIX-1', 'pix_status' => 'Ativo']);
        $this->assertFalse($this->service->podeRevisar($solicitacao->fresh()));

        $this->expectException(RuntimeException::class);
        $this->service->decidir($solicitacao->fresh(), $secretario, true, 'Revisão.');
    }

    #[Test]
    public function revisao_bloqueada_apos_aprovacao_da_inscricao(): void
    {
        $inscricao = $this->criarInscricao(['MAC']);
        $secretario = $this->secretario();
        $solicitacao = $this->service->criar($inscricao, ['obrigatoria'], $this->justificativa());
        $solicitacao = $this->service->decidir($solicitacao, $secretario, false, null, 'Sem documentos.');

        $this->assertTrue($this->service->podeRevisar($solicitacao));

        $inscricao->update([
            'aprovacao_obrigatoria_secretaria' => AprovacaoSecretariaDisciplina::Aprovado,
            'status' => \App\Enums\InscricaoStatus::AprovadoSecretaria,
        ]);

        $this->assertFalse($this->service->podeRevisar($solicitacao->fresh()));
    }

    #[Test]
    public function cancelar_restaura_pendencias_e_permite_reabrir_a_mesma_solicitacao(): void
    {
        $inscricao = $this->criarInscricao(['MAC', 'MAE']);
        $solicitacao = $this->service->criar($inscricao, ['obrigatoria'], $this->justificativa());
        $idOriginal = $solicitacao->id;

        $this->service->cancelar($solicitacao, 'Desisti da isenção.');

        $inscricao->refresh();
        $solicitacao->refresh();

        $this->assertSame(SolicitacaoIsencaoStatus::Cancelada, $solicitacao->status);
        $this->assertSame('Desisti da isenção.', $solicitacao->motivo_cancelamento);
        $this->assertNotNull($solicitacao->cancelada_em);
        $this->assertSame(PagamentoDisciplinaStatus::Pendente->value, $inscricao->pagamentos_disciplinas['obrigatoria']);
        $this->assertTrue($inscricao->podeSolicitarIsencao());

        $reaberta = $this->service->criar($inscricao, ['opcional_1'], $this->justificativa());

        $inscricao->refresh();

        $this->assertSame($idOriginal, $reaberta->id);
        $this->assertSame(SolicitacaoIsencaoStatus::Pendente, $reaberta->status);
        $this->assertNull($reaberta->cancelada_em);
        $this->assertNull($reaberta->motivo_cancelamento);
        $this->assertSame(['opcional_1'], array_column($reaberta->disciplinas_snapshot, 'slot'));
        $this->assertSame(PagamentoDisciplinaStatus::Pendente->value, $inscricao->pagamentos_disciplinas['obrigatoria']);
        $this->assertSame(
            PagamentoDisciplinaStatus::AguardandoIsencao->value,
            $inscricao->pagamentos_disciplinas['opcional_1']
        );
    }

    #[Test]
    public function edicao_pendente_troca_as_disciplinas_pleiteadas(): void
    {
        $inscricao = $this->criarInscricao(['MAC', 'MAE']);
        $solicitacao = $this->service->criar($inscricao, ['obrigatoria'], $this->justificativa());

        $solicitacao = $this->service->atualizar(
            $solicitacao,
            ['opcional_1'],
            'Justificativa atualizada com novos documentos anexados.'
        );

        $inscricao->refresh();

        $this->assertSame(['opcional_1'], array_column($solicitacao->disciplinas_snapshot, 'slot'));
        $this->assertSame(PagamentoDisciplinaStatus::Pendente->value, $inscricao->pagamentos_disciplinas['obrigatoria']);
        $this->assertSame(
            PagamentoDisciplinaStatus::AguardandoIsencao->value,
            $inscricao->pagamentos_disciplinas['opcional_1']
        );

        $this->service->decidir($solicitacao, $this->secretario(), true, 'Aprovada.');

        $this->expectException(RuntimeException::class);
        $this->service->atualizar($solicitacao->fresh(), ['obrigatoria'], $this->justificativa());
    }

    #[Test]
    public function extensao_do_prazo_reagenda_lembrete(): void
    {
        $inscricao = $this->criarInscricao(['MAC']);
        $solicitacao = $this->service->criar($inscricao, ['obrigatoria'], $this->justificativa());
        $solicitacao = $this->service->decidir($solicitacao, $this->secretario(), false, null, 'Falta documento.');

        $prazoAtual = $inscricao->fresh()->prazoPagamento();
        $novoPrazo = $prazoAtual->copy()->addDays(3);

        $solicitacao = $this->service->estenderPrazo($solicitacao, $novoPrazo);

        $this->assertSame(
            $novoPrazo->endOfDay()->toDateTimeString(),
            $solicitacao->prazo_pagamento_estendido_em?->toDateTimeString()
        );
        $this->assertSame(
            $novoPrazo->endOfDay()->toDateTimeString(),
            $inscricao->fresh()->prazoPagamento()?->toDateTimeString()
        );
        $this->assertSame(
            $novoPrazo->copy()->subDays((int) config('isencao.lembrete_dias_antes'))->endOfDay()->toDateTimeString(),
            $solicitacao->lembrete_agendado_para?->toDateTimeString()
        );
    }

    #[Test]
    public function pagamento_confirmado_cancela_solicitacao_pendente(): void
    {
        $inscricao = $this->criarInscricao(['MAC', 'MAE']);
        $solicitacao = $this->service->criar($inscricao, ['obrigatoria', 'opcional_1'], $this->justificativa());

        $inscricao->refresh();
        $inscricao->update(['pix_idfpix' => 'PIX-ANTIGO', 'pix_status' => 'Ativo']);

        app(InscricaoPagamentoService::class)->confirmarPagamento($inscricao->fresh(), [
            'dataPag' => now()->format('d/m/Y H:i:s'),
        ]);

        $inscricao->refresh();
        $solicitacao->refresh();

        $this->assertSame(SolicitacaoIsencaoStatus::Cancelada, $solicitacao->status);
        $this->assertSame('Pagamento confirmado via Pix.', $solicitacao->motivo_cancelamento);
        $this->assertSame(PagamentoDisciplinaStatus::Pago->value, $inscricao->pagamentos_disciplinas['obrigatoria']);
        $this->assertSame(PagamentoDisciplinaStatus::Pago->value, $inscricao->pagamentos_disciplinas['opcional_1']);
        $this->assertSame(ResumoPagamentoInscricao::Pago, $inscricao->resumoPagamento());
    }

    #[Test]
    public function aprovacao_bloqueada_com_pagamento_em_aberto(): void
    {
        $inscricao = $this->criarInscricao(['MAC']);

        try {
            $inscricao->aprovarPelaSecretaria();
            $this->fail('Aprovação deveria ser bloqueada.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('pagamento ou isenção pendente', $exception->getMessage());
        }

        $secretario = $this->secretario();

        $this->actingAs($secretario)
            ->post(route('inscricoes.aprovar-secretaria', $inscricao), ['disciplina' => 'obrigatoria'])
            ->assertRedirect(route('inscricoes.show', $inscricao))
            ->assertSessionHasErrors('aprovacao');

        $inscricao->refresh();
        $this->assertNull($inscricao->aprovacao_obrigatoria_secretaria);
    }

    #[Test]
    public function aprovacao_permitida_apos_todas_as_disciplinas_serem_pagas_ou_isentas(): void
    {
        $inscricao = $this->criarInscricao(['MAC', 'MAE']);
        $solicitacao = $this->service->criar($inscricao, ['obrigatoria'], $this->justificativa());
        $this->service->decidir($solicitacao, $this->secretario(), true, 'Aprovada.');

        $inscricao->refresh();
        $inscricao->pagamentos_disciplinas = [
            'obrigatoria' => PagamentoDisciplinaStatus::Isento->value,
            'opcional_1' => PagamentoDisciplinaStatus::Pago->value,
        ];
        $inscricao->save();

        $inscricao->aprovarPelaSecretaria();

        $this->assertSame(AprovacaoSecretariaDisciplina::Aprovado, $inscricao->aprovacao_obrigatoria_secretaria);
        $this->assertSame(AprovacaoSecretariaDisciplina::Aprovado, $inscricao->aprovacao_opcional_1_secretaria);
    }

    #[Test]
    public function resumo_de_pagamento_respeita_prioridade(): void
    {
        $casos = [
            [['obrigatoria' => 'aguardando_isencao', 'opcional_1' => 'pendente'], ResumoPagamentoInscricao::AguardandoIsencao],
            [['obrigatoria' => 'pendente', 'opcional_1' => 'pago'], ResumoPagamentoInscricao::NaoPago],
            [['obrigatoria' => 'isento', 'opcional_1' => 'isento'], ResumoPagamentoInscricao::Isento],
            [['obrigatoria' => 'pago', 'opcional_1' => 'pago'], ResumoPagamentoInscricao::Pago],
            [['obrigatoria' => 'pago', 'opcional_1' => 'isento'], ResumoPagamentoInscricao::PagoOuIsento],
            [null, ResumoPagamentoInscricao::NaoAplicavel],
        ];

        foreach ($casos as [$status, $esperado]) {
            $inscricao = new Inscricao(['pagamentos_disciplinas' => $status]);
            $this->assertSame($esperado, $inscricao->resumoPagamento());
        }
    }

    #[Test]
    public function comando_de_backfill_sincroniza_pagamentos_preservando_status_decisionais(): void
    {
        $pendente = $this->criarInscricao(['MAC', 'MAE'], null);
        $pendente->update(['pagamentos_disciplinas' => null]);

        $decidida = $this->criarInscricao(['MAC'], [
            'obrigatoria' => PagamentoDisciplinaStatus::Isento->value,
        ]);
        $decidida->update(['pagamentos_disciplinas' => [
            'obrigatoria' => PagamentoDisciplinaStatus::Isento->value,
        ]]);

        $semPagamento = $this->criarInscricao(['MAC']);
        $semPagamento->update(['aluno_usp' => true, 'pagamentos_disciplinas' => null]);

        $this->artisan('pagamentos:backfill')->assertExitCode(0);

        $pendente->refresh();
        $decidida->refresh();
        $semPagamento->refresh();

        $this->assertSame(PagamentoDisciplinaStatus::Pendente->value, $pendente->pagamentos_disciplinas['obrigatoria']);
        $this->assertSame(PagamentoDisciplinaStatus::Pendente->value, $pendente->pagamentos_disciplinas['opcional_1']);
        $this->assertSame(PagamentoDisciplinaStatus::Isento->value, $decidida->pagamentos_disciplinas['obrigatoria']);
        $this->assertNull($semPagamento->pagamentos_disciplinas);
    }

    #[Test]
    public function comando_de_backfill_simula_sem_alterar_dados(): void
    {
        $inscricao = $this->criarInscricao(['MAC']);
        $inscricao->update(['pagamentos_disciplinas' => null]);

        $this->artisan('pagamentos:backfill', ['--dry-run' => true])->assertExitCode(0);

        $this->assertNull($inscricao->fresh()->pagamentos_disciplinas);

        $this->artisan('pagamentos:backfill')->assertExitCode(0);

        $this->assertSame(
            PagamentoDisciplinaStatus::Pendente->value,
            $inscricao->fresh()->pagamentos_disciplinas['obrigatoria']
        );
    }

    private function criarInscricao(array $departamentos, ?array $pagamentos = null): Inscricao
    {
        $periodo = Periodo::factory()->create();

        $disciplinas = [];
        foreach ($departamentos as $departamento) {
            $disciplinas[] = DisciplinaOfertada::factory()->create([
                'periodo_id' => $periodo->id,
                'departamento' => $departamento,
            ]);
        }

        $pagamentos ??= $this->pagamentosPendentes(count($disciplinas));

        return Inscricao::factory()->create([
            'periodo_id' => $periodo->id,
            'aluno_usp' => false,
            'etapa_concluida' => 3,
            'concluido_em' => now(),
            'dados_etapa_2' => [
                'tipo' => 'nao_usp',
                'formado_graduacao_usp' => 'nao',
                'cpf' => '52998224725',
            ],
            'disciplina_obrigatoria_id' => $disciplinas[0]->id,
            'justificativa_disciplina_obrigatoria' => 'Justificativa da obrigatória.',
            'disciplina_opcional_1_id' => $disciplinas[1]->id ?? null,
            'justificativa_disciplina_opcional_1' => isset($disciplinas[1]) ? 'Justificativa da opcional 1.' : null,
            'disciplina_opcional_2_id' => $disciplinas[2]->id ?? null,
            'justificativa_disciplina_opcional_2' => isset($disciplinas[2]) ? 'Justificativa da opcional 2.' : null,
            'pagamentos_disciplinas' => $pagamentos,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function pagamentosPendentes(int $quantidade): array
    {
        $slots = array_slice(Inscricao::SLOTS_DISCIPLINA, 0, $quantidade);

        return collect($slots)
            ->mapWithKeys(fn (string $slot): array => [$slot => PagamentoDisciplinaStatus::Pendente->value])
            ->all();
    }

    private function justificativa(): string
    {
        return 'Sou estudante sem renda suficiente para arcar com a taxa de inscrição neste semestre.';
    }

    private function secretario(): User
    {
        $usuario = User::factory()->create();
        $usuario->assignRole('Secretario');

        return $usuario;
    }
}
