<?php

namespace Tests\Feature;

use App\Enums\AprovacaoProfessorDisciplina;
use App\Enums\AprovacaoSecretariaDisciplina;
use App\Enums\InscricaoStatus;
use App\Models\Inscricao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InscricaoAprovacaoProfessorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    }

    #[Test]
    public function professor_list_only_shows_inscriptions_with_secretaria_approved_discipline(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $comAprovacao = Inscricao::factory()
            ->concluida()
            ->comTresDisciplinas()
            ->create(['aprovacao_obrigatoria_secretaria' => AprovacaoSecretariaDisciplina::Aprovado]);

        $semAprovacao = Inscricao::factory()
            ->concluida()
            ->comTresDisciplinas()
            ->create();

        $this->actingAs($admin)
            ->get(route('professor.inscricoes.index'))
            ->assertOk()
            ->assertSee($comAprovacao->nome_completo)
            ->assertDontSee($semAprovacao->nome_completo);
    }

    #[Test]
    public function cannot_open_professor_show_without_secretaria_approved_discipline(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $inscricao = Inscricao::factory()
            ->concluida()
            ->comTresDisciplinas()
            ->create();

        $this->actingAs($admin)
            ->get(route('professor.inscricoes.show', $inscricao))
            ->assertNotFound();
    }

    #[Test]
    public function admin_can_approve_discipline_by_professor_and_updates_status_when_all_approved(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $inscricao = Inscricao::factory()
            ->concluida()
            ->comTresDisciplinas()
            ->create([
                'aprovacao_obrigatoria_secretaria' => AprovacaoSecretariaDisciplina::Aprovado,
                'aprovacao_opcional_1_secretaria' => AprovacaoSecretariaDisciplina::Aprovado,
                'aprovacao_opcional_2_secretaria' => AprovacaoSecretariaDisciplina::Aprovado,
                'status' => InscricaoStatus::AprovadoSecretaria,
            ]);

        $this->actingAs($admin)
            ->post(route('professor.inscricoes.aprovar', $inscricao), ['disciplina' => 'obrigatoria']);

        $this->actingAs($admin)
            ->post(route('professor.inscricoes.aprovar', $inscricao), ['disciplina' => 'opcional_1']);

        $this->actingAs($admin)
            ->post(route('professor.inscricoes.aprovar', $inscricao), ['disciplina' => 'opcional_2']);

        $inscricao->refresh();
        $this->assertSame(InscricaoStatus::AprovadoProfessor, $inscricao->status);
        $this->assertSame(AprovacaoProfessorDisciplina::Aprovado, $inscricao->aprovacao_obrigatoria_professor);
    }

    #[Test]
    public function professor_can_toggle_between_approved_and_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $inscricao = Inscricao::factory()
            ->concluida()
            ->comTresDisciplinas()
            ->create([
                'aprovacao_obrigatoria_secretaria' => AprovacaoSecretariaDisciplina::Aprovado,
                'aprovacao_obrigatoria_professor' => AprovacaoProfessorDisciplina::Aprovado,
            ]);

        $this->actingAs($admin)
            ->post(route('professor.inscricoes.reprovar', $inscricao), ['disciplina' => 'obrigatoria'])
            ->assertRedirect(route('professor.inscricoes.show', $inscricao));

        $inscricao->refresh();
        $this->assertSame(AprovacaoProfessorDisciplina::Reprovado, $inscricao->aprovacao_obrigatoria_professor);

        $this->actingAs($admin)
            ->post(route('professor.inscricoes.aprovar', $inscricao), ['disciplina' => 'obrigatoria']);

        $inscricao->refresh();
        $this->assertSame(AprovacaoProfessorDisciplina::Aprovado, $inscricao->aprovacao_obrigatoria_professor);
    }

    #[Test]
    public function secretaria_show_displays_professor_approval_section_read_only(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $inscricao = Inscricao::factory()
            ->concluida()
            ->comTresDisciplinas()
            ->create([
                'aprovacao_obrigatoria_secretaria' => AprovacaoSecretariaDisciplina::Aprovado,
                'aprovacao_obrigatoria_professor' => AprovacaoProfessorDisciplina::Aprovado,
            ]);

        $this->actingAs($admin)
            ->get(route('inscricoes.show', $inscricao))
            ->assertOk()
            ->assertSee('Aprovação pelo Professor (2ª etapa)')
            ->assertSee('Aprovada pelo Professor')
            ->assertDontSee(route('professor.inscricoes.aprovar', $inscricao));
    }

    #[Test]
    public function secretaria_hub_does_not_show_professor_card(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin)
            ->get(route('secretaria'))
            ->assertOk()
            ->assertDontSee('Aprovação do Professor');
    }

    #[Test]
    public function one_professor_approval_sets_aprovado_professor_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $inscricao = Inscricao::factory()
            ->concluida()
            ->comTresDisciplinas()
            ->create([
                'aprovacao_obrigatoria_secretaria' => AprovacaoSecretariaDisciplina::Aprovado,
                'status' => InscricaoStatus::AprovadoSecretaria,
            ]);

        $this->actingAs($admin)
            ->post(route('professor.inscricoes.aprovar', $inscricao), ['disciplina' => 'obrigatoria']);

        $inscricao->refresh();
        $this->assertSame(InscricaoStatus::AprovadoProfessor, $inscricao->status);
    }

    #[Test]
    public function cannot_approve_by_professor_without_secretaria_approval(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $inscricao = Inscricao::factory()
            ->concluida()
            ->comTresDisciplinas()
            ->create([
                'aprovacao_obrigatoria_secretaria' => AprovacaoSecretariaDisciplina::Aprovado,
                'aprovacao_opcional_1_secretaria' => AprovacaoSecretariaDisciplina::Reprovado,
            ]);

        $this->actingAs($admin)
            ->post(route('professor.inscricoes.aprovar', $inscricao), ['disciplina' => 'opcional_1'])
            ->assertRedirect(route('professor.inscricoes.show', $inscricao))
            ->assertSessionHasErrors('aprovacao');
    }
}
