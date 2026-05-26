<?php

namespace Tests\Feature;

use App\Enums\AprovacaoSecretariaDisciplina;
use App\Enums\InscricaoStatus;
use App\Models\DisciplinaOfertada;
use App\Models\Inscricao;
use App\Models\Periodo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InscricaoJustificativasDisciplinasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Professor', 'guard_name' => 'web']);
    }

    #[Test]
    public function etapa_3_persists_justifications_for_selected_disciplines(): void
    {
        $periodo = Periodo::factory()->create();
        $disciplinas = DisciplinaOfertada::factory()->count(3)->create([
            'periodo_id' => $periodo->id,
        ]);

        $inscricao = Inscricao::factory()->create([
            'periodo_id' => $periodo->id,
            'etapa_concluida' => 2,
        ]);

        $response = $this->withSession(['inscricao_id' => $inscricao->id])
            ->post(route('inscricao.etapa3'), [
                'disciplina_obrigatoria_id' => $disciplinas[0]->id,
                'justificativa_disciplina_obrigatoria' => 'Necessária para a linha de pesquisa principal.',
                'disciplina_opcional_1_id' => $disciplinas[1]->id,
                'justificativa_disciplina_opcional_1' => 'Complementa a base teórica.',
                'disciplina_opcional_2_id' => $disciplinas[2]->id,
                'justificativa_disciplina_opcional_2' => 'Aprofunda a aplicação prática.',
            ]);

        $response->assertRedirect(route('home'));

        $inscricao->refresh();

        $this->assertSame($disciplinas[0]->id, $inscricao->disciplina_obrigatoria_id);
        $this->assertSame('Necessária para a linha de pesquisa principal.', $inscricao->justificativa_disciplina_obrigatoria);
        $this->assertSame('Complementa a base teórica.', $inscricao->justificativa_disciplina_opcional_1);
        $this->assertSame('Aprofunda a aplicação prática.', $inscricao->justificativa_disciplina_opcional_2);
        $this->assertSame(3, $inscricao->etapa_concluida);
        $this->assertSame(InscricaoStatus::Inscrito, $inscricao->status);
    }

    #[Test]
    public function etapa_3_requires_justification_for_selected_optional_discipline(): void
    {
        $periodo = Periodo::factory()->create();
        $disciplinas = DisciplinaOfertada::factory()->count(2)->create([
            'periodo_id' => $periodo->id,
        ]);

        $inscricao = Inscricao::factory()->create([
            'periodo_id' => $periodo->id,
            'etapa_concluida' => 2,
        ]);

        $this->from(route('home'))
            ->withSession(['inscricao_id' => $inscricao->id])
            ->post(route('inscricao.etapa3'), [
                'disciplina_obrigatoria_id' => $disciplinas[0]->id,
                'justificativa_disciplina_obrigatoria' => 'Justificativa válida.',
                'disciplina_opcional_1_id' => $disciplinas[1]->id,
                'justificativa_disciplina_opcional_1' => '',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('justificativa_disciplina_opcional_1');
    }

    #[Test]
    public function admin_and_professor_views_show_discipline_justifications(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $professor = User::factory()->create();
        $professor->assignRole('Professor');

        $inscricao = Inscricao::factory()
            ->concluida()
            ->comTresDisciplinas()
            ->create([
                'aprovacao_obrigatoria_secretaria' => AprovacaoSecretariaDisciplina::Aprovado,
                'status' => InscricaoStatus::AprovadoSecretaria,
                'justificativa_disciplina_obrigatoria' => 'Quero cursar esta disciplina para fortalecer a base obrigatória.',
                'justificativa_disciplina_opcional_1' => 'A opcional 1 se conecta ao meu projeto.',
                'justificativa_disciplina_opcional_2' => 'A opcional 2 amplia meu repertório metodológico.',
            ]);

        $this->actingAs($admin)
            ->get(route('inscricoes.show', $inscricao))
            ->assertOk()
            ->assertSee('Justificativa:')
            ->assertSee('Quero cursar esta disciplina para fortalecer a base obrigatória.')
            ->assertSee('A opcional 1 se conecta ao meu projeto.')
            ->assertSee('A opcional 2 amplia meu repertório metodológico.');

        $this->actingAs($professor)
            ->get(route('professor.inscricoes.show', $inscricao))
            ->assertOk()
            ->assertSee('Justificativa:')
            ->assertSee('Quero cursar esta disciplina para fortalecer a base obrigatória.')
            ->assertSee('A opcional 1 se conecta ao meu projeto.')
            ->assertSee('A opcional 2 amplia meu repertório metodológico.');
    }
}
