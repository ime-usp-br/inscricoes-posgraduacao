<?php

namespace Tests\Feature;

use App\Enums\AprovacaoProfessorDisciplina;
use App\Enums\AprovacaoSecretariaDisciplina;
use App\Models\Inscricao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InscricaoRelatorioSecretariaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Secretario', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Professor', 'guard_name' => 'web']);
    }

    #[Test]
    public function secretaria_page_shows_gerar_relatorio_button(): void
    {
        $secretario = User::factory()->create();
        $secretario->assignRole('Secretario');

        $this->actingAs($secretario)
            ->get(route('secretaria'))
            ->assertOk()
            ->assertSee('Gerar relatório')
            ->assertSee(route('inscricoes.relatorio'), false);
    }

    #[Test]
    public function guest_cannot_download_relatorio(): void
    {
        $this->get(route('inscricoes.relatorio'))->assertRedirect(route('login.local'));
    }

    #[Test]
    public function non_secretario_cannot_download_relatorio(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('inscricoes.relatorio'))
            ->assertForbidden();
    }

    #[Test]
    public function relatorio_includes_only_disciplines_approved_by_secretaria_and_professor(): void
    {
        $secretario = User::factory()->create();
        $secretario->assignRole('Secretario');

        $aprovada = Inscricao::factory()
            ->concluida()
            ->comTresDisciplinas()
            ->create([
                'nome_completo' => 'Candidato Aprovado Completo',
                'aprovacao_obrigatoria_secretaria' => AprovacaoSecretariaDisciplina::Aprovado,
                'aprovacao_opcional_1_secretaria' => AprovacaoSecretariaDisciplina::Aprovado,
                'aprovacao_opcional_2_secretaria' => AprovacaoSecretariaDisciplina::Aprovado,
                'aprovacao_obrigatoria_professor' => AprovacaoProfessorDisciplina::Aprovado,
                'aprovacao_opcional_1_professor' => AprovacaoProfessorDisciplina::Aprovado,
                'aprovacao_opcional_2_professor' => AprovacaoProfessorDisciplina::Reprovado,
            ]);

        Inscricao::factory()
            ->concluida()
            ->comTresDisciplinas()
            ->create([
                'nome_completo' => 'Candidato So Secretaria',
                'aprovacao_obrigatoria_secretaria' => AprovacaoSecretariaDisciplina::Aprovado,
                'aprovacao_obrigatoria_professor' => null,
            ]);

        $aprovada->load(['disciplinaObrigatoria', 'disciplinaOpcional1', 'disciplinaOpcional2']);

        $response = $this->actingAs($secretario)
            ->get(route('inscricoes.relatorio'));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        $this->assertStringContainsString(
            '.xlsx',
            (string) $response->headers->get('content-disposition')
        );

        $tempPath = tempnam(sys_get_temp_dir(), 'relatorio_').'.xlsx';
        file_put_contents($tempPath, $response->streamedContent());

        try {
            $sheet = IOFactory::load($tempPath)->getActiveSheet();

            $this->assertSame('Nome completo', $sheet->getCell('A1')->getValue());
            $this->assertSame('Disciplina 1', $sheet->getCell('B1')->getValue());
            $this->assertSame('Disciplina 2', $sheet->getCell('C1')->getValue());
            $this->assertSame('Disciplina 3', $sheet->getCell('D1')->getValue());

            $this->assertSame('Candidato Aprovado Completo', $sheet->getCell('A2')->getValue());
            $this->assertSame(
                $aprovada->disciplinaObrigatoria->codigo_completo.' — '.$aprovada->disciplinaObrigatoria->nome,
                $sheet->getCell('B2')->getValue()
            );
            $this->assertSame(
                $aprovada->disciplinaOpcional1->codigo_completo.' — '.$aprovada->disciplinaOpcional1->nome,
                $sheet->getCell('C2')->getValue()
            );
            $this->assertSame('', (string) $sheet->getCell('D2')->getValue());
            $this->assertNull($sheet->getCell('A3')->getValue());
        } finally {
            @unlink($tempPath);
        }
    }
}
