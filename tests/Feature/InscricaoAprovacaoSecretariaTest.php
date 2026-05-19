<?php

namespace Tests\Feature;

use App\Enums\AprovacaoSecretariaDisciplina;
use App\Enums\InscricaoStatus;
use App\Models\Inscricao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InscricaoAprovacaoSecretariaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    }

    #[Test]
    public function guest_cannot_access_inscricoes_list(): void
    {
        $this->get(route('inscricoes.index'))->assertRedirect(route('login.local'));
    }

    #[Test]
    public function non_admin_cannot_access_inscricoes_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('inscricoes.index'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_approve_discipline_and_updates_status_when_all_approved(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $inscricao = Inscricao::factory()
            ->concluida()
            ->comTresDisciplinas()
            ->create();

        $inscricao->load(['disciplinaObrigatoria', 'disciplinaOpcional1', 'disciplinaOpcional2']);

        $this->assertSame(InscricaoStatus::Inscrito, $inscricao->status);

        $this->actingAs($admin)
            ->post(route('inscricoes.aprovar-secretaria', $inscricao), [
                'disciplina' => 'obrigatoria',
            ])
            ->assertRedirect(route('inscricoes.show', $inscricao));

        $inscricao->refresh();
        $this->assertSame(AprovacaoSecretariaDisciplina::Aprovado, $inscricao->aprovacao_obrigatoria_secretaria);
        $this->assertSame(InscricaoStatus::AprovadoSecretaria, $inscricao->status);

        $this->actingAs($admin)
            ->post(route('inscricoes.aprovar-secretaria', $inscricao), ['disciplina' => 'opcional_1']);

        $this->actingAs($admin)
            ->post(route('inscricoes.aprovar-secretaria', $inscricao), ['disciplina' => 'opcional_2']);

        $inscricao->refresh();
        $this->assertSame(InscricaoStatus::AprovadoSecretaria, $inscricao->status);
    }

    #[Test]
    public function inscricoes_index_shows_status_column(): void
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
            ->get(route('inscricoes.index'))
            ->assertOk()
            ->assertSee('Aprovada pela Secretaria')
            ->assertSee($inscricao->nome_completo);
    }

    #[Test]
    public function rejecting_one_discipline_does_not_change_general_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $inscricao = Inscricao::factory()
            ->concluida()
            ->comTresDisciplinas()
            ->create();

        $this->actingAs($admin)
            ->post(route('inscricoes.reprovar-secretaria', $inscricao), [
                'disciplina' => 'obrigatoria',
            ])
            ->assertRedirect(route('inscricoes.show', $inscricao));

        $inscricao->refresh();
        $this->assertSame(AprovacaoSecretariaDisciplina::Reprovado, $inscricao->aprovacao_obrigatoria_secretaria);
        $this->assertSame(InscricaoStatus::Inscrito, $inscricao->status);
    }

    #[Test]
    public function admin_can_toggle_between_approved_and_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $inscricao = Inscricao::factory()
            ->concluida()
            ->comTresDisciplinas()
            ->create(['aprovacao_obrigatoria_secretaria' => AprovacaoSecretariaDisciplina::Aprovado]);

        $this->actingAs($admin)
            ->post(route('inscricoes.reprovar-secretaria', $inscricao), [
                'disciplina' => 'obrigatoria',
            ])
            ->assertRedirect(route('inscricoes.show', $inscricao));

        $inscricao->refresh();
        $this->assertSame(AprovacaoSecretariaDisciplina::Reprovado, $inscricao->aprovacao_obrigatoria_secretaria);

        $this->actingAs($admin)
            ->post(route('inscricoes.aprovar-secretaria', $inscricao), [
                'disciplina' => 'obrigatoria',
            ])
            ->assertRedirect(route('inscricoes.show', $inscricao));

        $inscricao->refresh();
        $this->assertSame(AprovacaoSecretariaDisciplina::Aprovado, $inscricao->aprovacao_obrigatoria_secretaria);
    }

    #[Test]
    public function mixed_approval_and_rejection_is_approved_when_at_least_one_approved(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $inscricao = Inscricao::factory()
            ->concluida()
            ->comTresDisciplinas()
            ->create();

        $this->actingAs($admin)
            ->post(route('inscricoes.reprovar-secretaria', $inscricao), ['disciplina' => 'obrigatoria']);

        $this->actingAs($admin)
            ->post(route('inscricoes.aprovar-secretaria', $inscricao), ['disciplina' => 'opcional_1']);

        $this->actingAs($admin)
            ->post(route('inscricoes.aprovar-secretaria', $inscricao), ['disciplina' => 'opcional_2']);

        $inscricao->refresh();
        $this->assertSame(InscricaoStatus::AprovadoSecretaria, $inscricao->status);
    }

    #[Test]
    public function all_rejected_disciplines_marks_as_reprovada_secretaria(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $inscricao = Inscricao::factory()
            ->concluida()
            ->comTresDisciplinas()
            ->create();

        $this->actingAs($admin)
            ->post(route('inscricoes.reprovar-secretaria', $inscricao), ['disciplina' => 'obrigatoria']);

        $this->actingAs($admin)
            ->post(route('inscricoes.reprovar-secretaria', $inscricao), ['disciplina' => 'opcional_1']);

        $this->actingAs($admin)
            ->post(route('inscricoes.reprovar-secretaria', $inscricao), ['disciplina' => 'opcional_2']);

        $inscricao->refresh();
        $this->assertSame(InscricaoStatus::ReprovadoSecretaria, $inscricao->status);
    }

    #[Test]
    public function can_filter_inscricoes_by_secretaria_approval(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $aprovada = Inscricao::factory()->concluida()->comTresDisciplinas()->create([
            'aprovacao_obrigatoria_secretaria' => AprovacaoSecretariaDisciplina::Aprovado,
        ]);

        Inscricao::factory()->concluida()->comTresDisciplinas()->create();

        $this->actingAs($admin)
            ->get(route('inscricoes.index', ['aprovacao' => 'secretaria_aprovada']))
            ->assertOk()
            ->assertSee($aprovada->nome_completo);
    }

    #[Test]
    public function show_page_displays_approve_button_with_discipline_code(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $inscricao = Inscricao::factory()
            ->concluida()
            ->comTresDisciplinas()
            ->create();

        $codigo = $inscricao->disciplinaObrigatoria->codigo_completo;

        $this->actingAs($admin)
            ->get(route('inscricoes.show', $inscricao))
            ->assertOk()
            ->assertSee('Aprovação pela Secretaria (1ª etapa)')
            ->assertSee("Aprovar {$codigo}", false)
            ->assertSee("Reprovar {$codigo}", false);
    }
}
