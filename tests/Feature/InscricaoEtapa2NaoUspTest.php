<?php

namespace Tests\Feature;

use App\Models\Inscricao;
use App\Models\Periodo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InscricaoEtapa2NaoUspTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function etapa_2_nao_usp_persists_formado_graduacao_usp(): void
    {
        $periodo = Periodo::factory()->create();
        $inscricao = Inscricao::factory()->create([
            'periodo_id' => $periodo->id,
            'aluno_usp' => false,
            'etapa_concluida' => 1,
        ]);

        $pdf = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $this->withSession(['inscricao_id' => $inscricao->id])
            ->post(route('inscricao.etapa2'), $this->payloadEtapa2NaoUspMinimo($pdf, [
                'formado_graduacao_usp' => 'sim',
            ]))
            ->assertRedirect(route('home'));

        $inscricao->refresh();

        $this->assertSame('sim', $inscricao->dados_etapa_2['formado_graduacao_usp'] ?? null);
        $this->assertSame(2, $inscricao->etapa_concluida);
    }

    #[Test]
    public function etapa_2_nao_usp_rejects_pdf_larger_than_one_megabyte(): void
    {
        $periodo = Periodo::factory()->create();
        $inscricao = Inscricao::factory()->create([
            'periodo_id' => $periodo->id,
            'aluno_usp' => false,
            'etapa_concluida' => 1,
        ]);

        $pdfPequeno = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
        $pdfGrande = UploadedFile::fake()->create('grande.pdf', 1025, 'application/pdf');

        $payload = $this->payloadEtapa2NaoUspMinimo($pdfPequeno);
        $payload['pdf_diploma_graduacao'] = $pdfGrande;

        $this->from(route('home'))
            ->withSession(['inscricao_id' => $inscricao->id])
            ->post(route('inscricao.etapa2'), $payload)
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('pdf_diploma_graduacao');
    }

    #[Test]
    public function etapa_2_nao_usp_requires_formado_graduacao_usp(): void
    {
        $periodo = Periodo::factory()->create();
        $inscricao = Inscricao::factory()->create([
            'periodo_id' => $periodo->id,
            'aluno_usp' => false,
            'etapa_concluida' => 1,
        ]);

        $pdf = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $payload = $this->payloadEtapa2NaoUspMinimo($pdf);
        unset($payload['formado_graduacao_usp']);

        $this->from(route('home'))
            ->withSession(['inscricao_id' => $inscricao->id])
            ->post(route('inscricao.etapa2'), $payload)
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('formado_graduacao_usp');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payloadEtapa2NaoUspMinimo(UploadedFile $pdf, array $overrides = []): array
    {
        return array_merge([
            'pos_graduacao_externo' => 'nao',
            'nome_programa_externo' => null,
            'formado_graduacao_usp' => 'nao',
            'curso_usp_anterior' => 'nao',
            'data_nascimento' => '1990-01-15',
            'genero' => 'Feminino',
            'nome_mae' => 'Maria da Silva Santos',
            'cpf' => '12345678901',
            'rg_rne_rnm' => '123456789',
            'orgao_expedidor' => 'SSP',
            'estado_expedicao' => 'SP',
            'data_expedicao' => '2010-05-20',
            'pais_nascimento' => 'Brasil',
            'estado_nascimento' => 'SP',
            'municipio_provincia' => 'São Paulo',
            'nacionalidade' => 'Brasileira',
            'endereco_completo' => 'Rua Exemplo, 100',
            'cep' => '01310100',
            'telefone' => '11999999999',
            'estrangeiro' => 'nao',
            'pdf_diploma_graduacao' => $pdf,
            'pdf_historico_graduacao' => $pdf,
            'pdf_rg_rne_rnm' => $pdf,
            'pdf_cpf' => $pdf,
        ], $overrides);
    }
}
