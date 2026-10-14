<?php

namespace Tests\Feature;

use App\Enums\PagamentoDisciplinaStatus;
use App\Models\DisciplinaOfertada;
use App\Models\Inscricao;
use App\Models\Periodo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InscricaoPagamentoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Secretario', 'guard_name' => 'web']);

        $this->fakeWspixApi();
    }

    #[Test]
    public function etapa_3_redireciona_para_pagamento_unico_quando_ha_mac(): void
    {
        $periodo = Periodo::factory()->create();
        $mac = DisciplinaOfertada::factory()->create([
            'periodo_id' => $periodo->id,
            'departamento' => 'MAC',
        ]);

        $inscricao = $this->inscricaoNaoUspNaoFormado($periodo->id);

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->post(route('inscricao.etapa3'), [
                'disciplina_obrigatoria_id' => $mac->id,
                'justificativa_disciplina_obrigatoria' => 'Justificativa obrigatória.',
            ])
            ->assertRedirect(route('inscricao.pagamento'));

        $inscricao->refresh();
        $this->assertSame(
            PagamentoDisciplinaStatus::Pendente->value,
            $inscricao->pagamentos_disciplinas['obrigatoria'] ?? null
        );

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->get(route('inscricao.pagamento'))
            ->assertOk()
            ->assertSee('R$ 107,00');

        $inscricao->refresh();
        $this->assertSame('PIX-TEST-001', $inscricao->pix_idfpix);
        $this->assertSame(107.0, (float) $inscricao->pix_valor);
        $this->assertSame(1, $inscricao->pix_quantidade_disciplinas);
    }

    #[Test]
    public function pagamento_unico_cobra_107_por_disciplina_mac_ou_mae(): void
    {
        $periodo = Periodo::factory()->create();
        $mac = DisciplinaOfertada::factory()->create([
            'periodo_id' => $periodo->id,
            'departamento' => 'MAC',
            'codigo' => 1001,
        ]);
        $mae = DisciplinaOfertada::factory()->create([
            'periodo_id' => $periodo->id,
            'departamento' => 'MAE',
            'codigo' => 50,
        ]);

        $inscricao = $this->inscricaoNaoUspNaoFormado($periodo->id);
        $inscricao->update([
            'disciplina_obrigatoria_id' => $mac->id,
            'justificativa_disciplina_obrigatoria' => 'Justificativa 1.',
            'disciplina_opcional_1_id' => $mae->id,
            'justificativa_disciplina_opcional_1' => 'Justificativa 2.',
            'etapa_concluida' => 3,
            'concluido_em' => now(),
            'pagamentos_disciplinas' => [
                'obrigatoria' => PagamentoDisciplinaStatus::Pendente->value,
                'opcional_1' => PagamentoDisciplinaStatus::Pendente->value,
            ],
        ]);

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->get(route('inscricao.pagamento'))
            ->assertOk()
            ->assertSee('R$ 214,00')
            ->assertSee('MAC1001')
            ->assertSee('MAE0050')
            ->assertSee('Total a pagar (pagamento único)');

        $inscricao->refresh();
        $this->assertSame(214.0, (float) $inscricao->pix_valor);
        $this->assertSame(2, $inscricao->pix_quantidade_disciplinas);
    }

    #[Test]
    public function verificar_pagamento_marca_disciplinas_como_pagas(): void
    {
        $periodo = Periodo::factory()->create();
        $mac = DisciplinaOfertada::factory()->create([
            'periodo_id' => $periodo->id,
            'departamento' => 'MAC',
        ]);

        $inscricao = $this->inscricaoNaoUspNaoFormado($periodo->id);
        $inscricao->update([
            'disciplina_obrigatoria_id' => $mac->id,
            'justificativa_disciplina_obrigatoria' => 'Justificativa.',
            'etapa_concluida' => 3,
            'concluido_em' => now(),
            'pagamentos_disciplinas' => ['obrigatoria' => PagamentoDisciplinaStatus::Pendente->value],
            'pix_idfpix' => 'PIX-TEST-001',
            'pix_status' => 'Ativo',
            'pix_valor' => 107,
            'pix_quantidade_disciplinas' => 1,
        ]);

        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, '/pix/gerar')) {
                return Http::response([
                    'idfpix' => 'PIX-TEST-001',
                    'qrCode' => '000201',
                    'qrCodeImgBase64' => base64_encode('qr'),
                ]);
            }

            return Http::response([
                'idfpix' => 'PIX-TEST-001',
                'status' => 'Concluído',
                'dataPag' => now()->format('d/m/Y H:i:s'),
                'qrCode' => '000201',
            ]);
        });

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->post(route('inscricao.pagamento.verificar'))
            ->assertRedirect(route('home'));

        $inscricao->refresh();
        $this->assertSame(PagamentoDisciplinaStatus::Pago->value, $inscricao->pagamentos_disciplinas['obrigatoria']);
        $this->assertSame('Concluído', $inscricao->pix_status);
        $this->assertNotNull($inscricao->pix_pago_em);
    }

    #[Test]
    public function webhook_wspix_confirma_pagamento(): void
    {
        $inscricao = Inscricao::factory()->create([
            'pix_idfpix' => 'PIX-WEBHOOK-1',
            'pix_status' => 'Ativo',
            'pix_valor' => 214,
            'pix_quantidade_disciplinas' => 2,
            'pagamentos_disciplinas' => [
                'obrigatoria' => PagamentoDisciplinaStatus::Pendente->value,
                'opcional_1' => PagamentoDisciplinaStatus::Pendente->value,
            ],
        ]);

        $this->postJson(route('webhooks.wspix'), [
            'idfpix' => 'PIX-WEBHOOK-1',
            'status' => 'Pago',
            'valor' => 214,
            'dataPag' => now()->format('d/m/Y H:i:s'),
        ], [
            'X-Token' => 'test-webhook-token',
        ])->assertOk();

        $inscricao->refresh();
        $this->assertSame(PagamentoDisciplinaStatus::Pago->value, $inscricao->pagamentos_disciplinas['obrigatoria']);
        $this->assertSame(PagamentoDisciplinaStatus::Pago->value, $inscricao->pagamentos_disciplinas['opcional_1']);
    }

    #[Test]
    public function webhook_rejeita_token_invalido(): void
    {
        $this->postJson(route('webhooks.wspix'), [
            'idfpix' => 'PIX-1',
            'status' => 'Pago',
        ], [
            'X-Token' => 'token-errado',
        ])->assertUnauthorized();
    }

    #[Test]
    public function candidato_nao_formado_sem_mac_ou_mae_nao_gera_pix(): void
    {
        $periodo = Periodo::factory()->create();
        $mat = DisciplinaOfertada::factory()->create([
            'periodo_id' => $periodo->id,
            'departamento' => 'MAT',
        ]);

        $inscricao = $this->inscricaoNaoUspNaoFormado($periodo->id);

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->post(route('inscricao.etapa3'), [
                'disciplina_obrigatoria_id' => $mat->id,
                'justificativa_disciplina_obrigatoria' => 'Justificativa obrigatória.',
            ])
            ->assertRedirect(route('home'));

        $inscricao->refresh();
        $this->assertNull($inscricao->pagamentos_disciplinas);
        $this->assertNull($inscricao->pix_idfpix);
    }

    #[Test]
    public function home_redireciona_para_pagamento_enquanto_houver_pendencia(): void
    {
        $periodo = Periodo::factory()->create();
        $mac = DisciplinaOfertada::factory()->create([
            'periodo_id' => $periodo->id,
            'departamento' => 'MAC',
        ]);

        $inscricao = $this->inscricaoNaoUspNaoFormado($periodo->id);
        $inscricao->update([
            'disciplina_obrigatoria_id' => $mac->id,
            'justificativa_disciplina_obrigatoria' => 'Justificativa.',
            'etapa_concluida' => 3,
            'concluido_em' => now(),
            'pagamentos_disciplinas' => ['obrigatoria' => PagamentoDisciplinaStatus::Pendente->value],
            'pix_idfpix' => 'PIX-TEST-001',
            'pix_valor' => 107,
            'pix_quantidade_disciplinas' => 1,
        ]);

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->get(route('home'))
            ->assertRedirect(route('inscricao.pagamento'));
    }

    #[Test]
    public function gerar_pix_sanitiza_campos_textuais_para_charset_compativel_do_wspix(): void
    {
        $periodo = Periodo::factory()->create();
        $mac = DisciplinaOfertada::factory()->create([
            'periodo_id' => $periodo->id,
            'departamento' => 'MAC',
        ]);

        $inscricao = $this->inscricaoNaoUspNaoFormado($periodo->id);
        $inscricao->update([
            'nome_completo' => 'João da Silva — Pós',
            'disciplina_obrigatoria_id' => $mac->id,
            'justificativa_disciplina_obrigatoria' => 'Justificativa.',
            'etapa_concluida' => 3,
            'concluido_em' => now(),
            'pagamentos_disciplinas' => ['obrigatoria' => PagamentoDisciplinaStatus::Pendente->value],
            'pix_idfpix' => null,
            'pix_valor' => null,
            'pix_quantidade_disciplinas' => null,
        ]);

        Http::fake([
            '*' => Http::response([
                'idfpix' => 'PIX-TEST-001',
                'status' => 'Ativo',
                'qrCode' => '000201',
                'qrCodeImgBase64' => base64_encode('qr'),
            ]),
        ]);

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->get(route('inscricao.pagamento'))
            ->assertOk();

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), '/pix/gerar')) {
                return true;
            }

            $data = $request->data();
            $nome = (string) ($data['nomePesOrg'] ?? '');
            $cobranca = (string) ($data['infoCobranca'] ?? '');

            return ! preg_match('/[^\x20-\x7E]/', $nome)
                && ! preg_match('/[^\x20-\x7E]/', $cobranca);
        });
    }

    #[Test]
    public function inscricoes_index_exibe_coluna_e_filtra_por_status_de_pagamento(): void
    {
        $secretario = User::factory()->create();
        $secretario->assignRole('Secretario');

        $periodo = Periodo::factory()->create();
        $mac = DisciplinaOfertada::factory()->create([
            'periodo_id' => $periodo->id,
            'departamento' => 'MAC',
        ]);

        $pendente = $this->inscricaoNaoUspNaoFormado($periodo->id);
        $pendente->update([
            'nome_completo' => 'Candidato Pagamento Pendente',
            'disciplina_obrigatoria_id' => $mac->id,
            'justificativa_disciplina_obrigatoria' => 'Justificativa.',
            'etapa_concluida' => 3,
            'concluido_em' => now(),
            'pagamentos_disciplinas' => ['obrigatoria' => PagamentoDisciplinaStatus::Pendente->value],
            'pix_idfpix' => 'PIX-LISTA-1',
        ]);

        $pago = $this->inscricaoNaoUspNaoFormado($periodo->id);
        $pago->update([
            'nome_completo' => 'Candidato Pagamento Pago',
            'disciplina_obrigatoria_id' => $mac->id,
            'justificativa_disciplina_obrigatoria' => 'Justificativa.',
            'etapa_concluida' => 3,
            'concluido_em' => now(),
            'pagamentos_disciplinas' => ['obrigatoria' => PagamentoDisciplinaStatus::Pago->value],
            'pix_pago_em' => now(),
        ]);

        $isento = Inscricao::factory()->concluida()->create([
            'periodo_id' => $periodo->id,
            'nome_completo' => 'Candidato Sem Pagamento',
            'aluno_usp' => true,
            'pagamentos_disciplinas' => null,
        ]);

        $this->actingAs($secretario)
            ->get(route('inscricoes.index'))
            ->assertOk()
            ->assertSee('Pagamento')
            ->assertSee('Não pago')
            ->assertSee('Pago')
            ->assertSee('Não aplicável')
            ->assertDontSee('Pix gerado');

        $this->actingAs($secretario)
            ->get(route('inscricoes.index', ['pagamento' => 'nao_pago']))
            ->assertOk()
            ->assertSee($pendente->nome_completo)
            ->assertDontSee($pago->nome_completo)
            ->assertDontSee($isento->nome_completo);

        $this->actingAs($secretario)
            ->get(route('inscricoes.index', ['pagamento' => 'demais']))
            ->assertOk()
            ->assertSee($pago->nome_completo)
            ->assertSee($isento->nome_completo)
            ->assertDontSee($pendente->nome_completo);
    }

    private function inscricaoNaoUspNaoFormado(int $periodoId): Inscricao
    {
        return Inscricao::factory()->create([
            'periodo_id' => $periodoId,
            'aluno_usp' => false,
            'etapa_concluida' => 2,
            'dados_etapa_2' => [
                'tipo' => 'nao_usp',
                'formado_graduacao_usp' => 'nao',
                'cpf' => '52998224725',
            ],
        ]);
    }

    private function fakeWspixApi(): void
    {
        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, '/pix/gerar')) {
                return Http::response([
                    'idfpix' => 'PIX-TEST-001',
                    'qrCode' => '00020126580014BR.GOV.BCB.PIX',
                    'qrCodeImgBase64' => base64_encode('fake-qr-image'),
                ]);
            }

            return Http::response([
                'idfpix' => 'PIX-TEST-001',
                'status' => 'Ativo',
                'qrCode' => '00020126580014BR.GOV.BCB.PIX',
                'qrCodeImgBase64' => base64_encode('fake-qr-image'),
            ]);
        });
    }
}
