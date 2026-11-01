<?php

namespace Tests\Feature;

use App\Enums\PagamentoDisciplinaStatus;
use App\Enums\SolicitacaoIsencaoStatus;
use App\Models\DisciplinaOfertada;
use App\Models\Inscricao;
use App\Models\Periodo;
use App\Models\SolicitacaoIsencao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SolicitacaoIsencaoFormularioTest extends TestCase
{
    use RefreshDatabase;

    private Periodo $periodo;

    protected function setUp(): void
    {
        parent::setUp();

        config(['isencao.habilitado' => true]);
        Storage::fake('local');

        $this->periodo = Periodo::factory()->create();
    }

    #[Test]
    public function formulario_exige_fluxo_habilitado_e_inscricao_na_sessao(): void
    {
        $inscricao = $this->criarInscricao();

        config(['isencao.habilitado' => false]);

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->get(route('inscricao.isencao.show'))
            ->assertNotFound();

        config(['isencao.habilitado' => true]);
        $this->flushSession();

        $this->get(route('inscricao.isencao.show'))
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('sessao');
    }

    #[Test]
    public function formulario_lista_apenas_disciplinas_elegiveis_preselecionadas(): void
    {
        $inscricao = $this->criarInscricao(['MAC', 'MAE', 'MAT']);

        $response = $this->withSession(['inscricao_id' => $inscricao->id])
            ->get(route('inscricao.isencao.show'))
            ->assertOk()
            ->assertSee('Isenção da taxa de inscrição')
            ->assertSee('MAC0101')
            ->assertSee('MAE0202')
            ->assertDontSee('MAT0303');

        $this->assertSame(2, substr_count($response->getContent(), 'name="disciplinas[]"'));
        $this->assertSame(2, substr_count($response->getContent(), 'checked'));
    }

    #[Test]
    public function envio_cria_solicitacao_com_documentos_e_bloqueia_pagamento(): void
    {
        $inscricao = $this->criarInscricao(['MAC', 'MAE']);

        $response = $this->withSession(['inscricao_id' => $inscricao->id])
            ->post(route('inscricao.isencao.store'), [
                'disciplinas' => ['obrigatoria', 'opcional_1'],
                'justificativa' => 'Não possuo renda no momento e preciso da isenção para cursar as disciplinas.',
                'documentos' => [
                    UploadedFile::fake()->create('comprovante.pdf', 100, 'application/pdf'),
                ],
            ]);

        $inscricao->refresh();
        $solicitacao = $inscricao->solicitacaoIsencao;

        $response->assertRedirect(route('inscricao.isencao.show'));

        $this->assertNotNull($solicitacao);
        $this->assertSame(SolicitacaoIsencaoStatus::Pendente, $solicitacao->status);
        $this->assertSame('obrigatoria', $solicitacao->disciplinas_snapshot[0]['slot']);
        $this->assertSame('opcional_1', $solicitacao->disciplinas_snapshot[1]['slot']);
        $this->assertCount(1, $solicitacao->documentos);
        Storage::disk('local')->assertExists($solicitacao->documentos->first()->path);

        $this->assertSame(
            PagamentoDisciplinaStatus::AguardandoIsencao->value,
            $inscricao->pagamentos_disciplinas['obrigatoria']
        );
        $this->assertSame(
            PagamentoDisciplinaStatus::AguardandoIsencao->value,
            $inscricao->pagamentos_disciplinas['opcional_1']
        );
        $this->assertNull($inscricao->pix_idfpix);
    }

    #[Test]
    public function envio_valida_justificativa_e_documentos(): void
    {
        $inscricao = $this->criarInscricao(['MAC']);

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->post(route('inscricao.isencao.store'), [
                'disciplinas' => ['obrigatoria'],
                'justificativa' => 'curta',
            ])
            ->assertSessionHasErrors(['justificativa', 'documentos']);

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->post(route('inscricao.isencao.store'), [
                'disciplinas' => ['obrigatoria'],
                'justificativa' => 'Justificativa suficientemente longa para o pedido de isenção.',
                'documentos' => [
                    UploadedFile::fake()->create('documento.txt', 10, 'text/plain'),
                ],
            ])
            ->assertSessionHasErrors('documentos.0');

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->post(route('inscricao.isencao.store'), [
                'disciplinas' => ['obrigatoria'],
                'justificativa' => 'Justificativa suficientemente longa para o pedido de isenção.',
                'documentos' => [
                    UploadedFile::fake()->create('grande.pdf', 1025, 'application/pdf'),
                ],
            ])
            ->assertSessionHasErrors('documentos.0');

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->post(route('inscricao.isencao.store'), [
                'disciplinas' => ['obrigatoria'],
                'justificativa' => 'Justificativa suficientemente longa para o pedido de isenção.',
                'documentos' => collect(range(1, 6))
                    ->map(fn (int $i): UploadedFile => UploadedFile::fake()->create("doc{$i}.pdf", 10, 'application/pdf'))
                    ->all(),
            ])
            ->assertSessionHasErrors('documentos');

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->post(route('inscricao.isencao.store'), [
                'disciplinas' => ['opcional_1'],
                'justificativa' => 'Justificativa suficientemente longa para o pedido de isenção.',
                'documentos' => [
                    UploadedFile::fake()->create('comprovante.pdf', 10, 'application/pdf'),
                ],
            ])
            ->assertSessionHasErrors('disciplinas');

        $this->assertNull($inscricao->fresh()->solicitacaoIsencao);
    }

    #[Test]
    public function edicao_pendente_atualiza_disciplinas_e_documentos(): void
    {
        $inscricao = $this->criarInscricao(['MAC', 'MAE']);
        $solicitacao = $this->criarSolicitacao($inscricao, ['obrigatoria']);
        $documentoAntigo = $solicitacao->documentos->first();

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->get(route('inscricao.isencao.edit'))
            ->assertOk()
            ->assertSee('MAC0101')
            ->assertSee($documentoAntigo->nome_original);

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->put(route('inscricao.isencao.update'), [
                'disciplinas' => ['opcional_1'],
                'justificativa' => 'Atualizei a justificativa e os documentos do pedido de isenção.',
                'remover_documentos' => [$documentoAntigo->id],
                'documentos' => [
                    UploadedFile::fake()->create('novo.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('inscricao.isencao.show'));

        $solicitacao->refresh();
        $inscricao->refresh();

        $this->assertSame(['opcional_1'], array_column($solicitacao->disciplinas_snapshot, 'slot'));
        $this->assertSame('Atualizei a justificativa e os documentos do pedido de isenção.', $solicitacao->justificativa);
        $this->assertCount(1, $solicitacao->documentos);
        $this->assertSame('novo.pdf', $solicitacao->documentos->first()->nome_original);
        Storage::disk('local')->assertMissing($documentoAntigo->path);
        $this->assertSame(
            PagamentoDisciplinaStatus::Pendente->value,
            $inscricao->pagamentos_disciplinas['obrigatoria']
        );
        $this->assertSame(
            PagamentoDisciplinaStatus::AguardandoIsencao->value,
            $inscricao->pagamentos_disciplinas['opcional_1']
        );
    }

    #[Test]
    public function cancelamento_restaura_formulario_e_permite_reabrir(): void
    {
        $inscricao = $this->criarInscricao(['MAC']);
        $solicitacao = $this->criarSolicitacao($inscricao, ['obrigatoria']);

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->post(route('inscricao.isencao.cancelar'))
            ->assertRedirect(route('inscricao.isencao.show'));

        $this->assertSame(SolicitacaoIsencaoStatus::Cancelada, $solicitacao->fresh()->status);

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->get(route('inscricao.isencao.show'))
            ->assertOk()
            ->assertSee('Reabrir solicitação');

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->post(route('inscricao.isencao.store'), [
                'disciplinas' => ['obrigatoria'],
                'justificativa' => 'Reabri o pedido com a justificativa atualizada para análise.',
                'documentos' => [
                    UploadedFile::fake()->create('reabertura.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('inscricao.isencao.show'));

        $this->assertSame(SolicitacaoIsencaoStatus::Pendente, $solicitacao->fresh()->status);
        $this->assertSame(2, $solicitacao->fresh()->documentos()->count());
    }

    #[Test]
    public function pagina_de_status_exibe_decisao_da_secretaria(): void
    {
        $inscricao = $this->criarInscricao(['MAC']);
        $solicitacao = $this->criarSolicitacao($inscricao, ['obrigatoria']);

        $solicitacao->update([
            'status' => SolicitacaoIsencaoStatus::Aprovada,
            'decidida_em' => now(),
            'resposta_secretaria' => 'Documentação aceita pela secretaria.',
        ]);

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->get(route('inscricao.isencao.show'))
            ->assertOk()
            ->assertSee('Aprovada')
            ->assertSee('Documentação aceita pela secretaria.')
            ->assertSee('MAC0101');
    }

    #[Test]
    public function documento_nao_pode_ser_baixado_por_outra_inscricao(): void
    {
        $outra = $this->criarInscricao(['MAC'], 'Outro Candidato');
        $solicitacao = $this->criarSolicitacao($outra, ['obrigatoria']);
        $documento = $solicitacao->documentos->first();
        Storage::disk('local')->put($documento->path, '%PDF-1.4 fake');

        $minha = $this->criarInscricao(['MAE'], 'Meu Candidato');

        $this->withSession(['inscricao_id' => $minha->id])
            ->get(route('inscricao.isencao.documento', $documento))
            ->assertForbidden();

        $this->withSession(['inscricao_id' => $outra->id])
            ->get(route('inscricao.isencao.documento', $documento))
            ->assertOk();
    }

    #[Test]
    public function pagamento_bloqueado_nao_gera_pix_e_aponta_para_isencao(): void
    {
        $inscricao = $this->criarInscricao(['MAC']);
        $this->criarSolicitacao($inscricao, ['obrigatoria']);

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->get(route('inscricao.pagamento'))
            ->assertOk()
            ->assertSee('Solicitação de isenção em análise')
            ->assertDontSee('Gerar cobrança Pix');

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->post(route('inscricao.pagamento.gerar'))
            ->assertRedirect(route('inscricao.pagamento'))
            ->assertSessionHasErrors('pix');

        $this->assertNull($inscricao->fresh()->pix_idfpix);
    }

    /**
     * @param  list<string>  $departamentos
     */
    private function criarInscricao(array $departamentos = ['MAC'], string $nome = 'Candidato Isenção'): Inscricao
    {
        $disciplinas = [];

        foreach ($departamentos as $indice => $departamento) {
            $disciplinas[] = DisciplinaOfertada::factory()->create([
                'periodo_id' => $this->periodo->id,
                'departamento' => $departamento,
                'codigo' => ($indice + 1) * 101,
            ]);
        }

        $pagamentos = [];

        foreach (array_slice(Inscricao::SLOTS_DISCIPLINA, 0, count($disciplinas)) as $indice => $slot) {
            if (in_array($departamentos[$indice], Inscricao::DEPARTAMENTOS_COM_PAGAMENTO, true)) {
                $pagamentos[$slot] = PagamentoDisciplinaStatus::Pendente->value;
            }
        }

        return Inscricao::factory()->create([
            'periodo_id' => $this->periodo->id,
            'nome_completo' => $nome,
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
            'pagamentos_disciplinas' => $pagamentos === [] ? null : $pagamentos,
        ]);
    }

    /**
     * @param  list<string>  $slots
     */
    private function criarSolicitacao(Inscricao $inscricao, array $slots): SolicitacaoIsencao
    {
        $solicitacao = SolicitacaoIsencao::factory()
            ->comDocumentos(1)
            ->comSnapshot($this->snapshotDaInscricao($inscricao, $slots))
            ->create(['inscricao_id' => $inscricao->id]);

        $pagamentos = $inscricao->pagamentos_disciplinas ?? [];

        foreach ($slots as $slot) {
            if (array_key_exists($slot, $pagamentos)) {
                $pagamentos[$slot] = PagamentoDisciplinaStatus::AguardandoIsencao->value;
            }
        }

        $inscricao->update(['pagamentos_disciplinas' => $pagamentos]);

        return $solicitacao->fresh(['documentos', 'inscricao']);
    }

    /**
     * @param  list<string>  $slots
     * @return list<array<string, mixed>>
     */
    private function snapshotDaInscricao(Inscricao $inscricao, array $slots): array
    {
        $snapshot = [];

        foreach ($slots as $slot) {
            $disciplina = $inscricao->disciplinaNoSlot($slot);

            if ($disciplina === null) {
                continue;
            }

            $snapshot[] = [
                'slot' => $slot,
                'disciplina_id' => $disciplina->id,
                'codigo' => $disciplina->codigo_completo,
                'nome' => $disciplina->nome,
                'departamento' => $disciplina->departamento,
                'valor' => 107,
            ];
        }

        return $snapshot;
    }
}
