<?php

namespace Tests\Feature;

use App\Enums\AprovacaoProfessorDisciplina;
use App\Enums\AprovacaoSecretariaDisciplina;
use App\Models\DisciplinaOfertada;
use App\Models\Inscricao;
use App\Models\Periodo;
use App\Models\User;
use App\Services\ReplicadoDisciplinaOfertadaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Professor só enxerga e avalia disciplinas pelas quais é responsável
 * (e-mail do usuário == professor_email da disciplina).
 */
class InscricaoProfessorEscopoTest extends TestCase
{
    use RefreshDatabase;

    private Periodo $periodo;

    private User $professorA;

    private User $professorB;

    private DisciplinaOfertada $disciplinaA;

    private DisciplinaOfertada $disciplinaB;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Professor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Secretario', 'guard_name' => 'web']);

        $this->periodo = Periodo::factory()->create();

        $this->professorA = $this->criarProfessor('prof.a@ime.usp.br');
        $this->professorB = $this->criarProfessor('prof.b@ime.usp.br');

        $this->disciplinaA = $this->criarDisciplina('0101', 'Disciplina Alfa', $this->professorA->email);
        $this->disciplinaB = $this->criarDisciplina('0202', 'Disciplina Beta', $this->professorB->email);
    }

    #[Test]
    public function lista_mostra_apenas_inscricoes_com_disciplina_do_professor(): void
    {
        $daA = $this->criarInscricao('Candidato Alfa', obrigatoria: $this->disciplinaA);
        $daB = $this->criarInscricao('Candidato Beta', obrigatoria: $this->disciplinaB);

        $this->actingAs($this->professorA)
            ->get(route('professor.inscricoes.index'))
            ->assertOk()
            ->assertSee($daA->nome_completo)
            ->assertDontSee($daB->nome_completo);
    }

    #[Test]
    public function lista_ignora_disciplina_propria_sem_aprovacao_da_secretaria(): void
    {
        $semAprovacao = $this->criarInscricao('Candidato Sem Aprovacao', obrigatoria: $this->disciplinaA, aprovarSecretaria: false);

        $this->actingAs($this->professorA)
            ->get(route('professor.inscricoes.index'))
            ->assertOk()
            ->assertDontSee($semAprovacao->nome_completo);
    }

    #[Test]
    public function lista_ignora_disciplina_propria_reprovada_pela_secretaria(): void
    {
        $inscricao = $this->criarInscricao(
            'Candidato Reprovado',
            obrigatoria: $this->disciplinaA,
            opcional1: $this->disciplinaB,
        );
        $inscricao->update(['aprovacao_obrigatoria_secretaria' => AprovacaoSecretariaDisciplina::Reprovado]);

        $this->actingAs($this->professorA)
            ->get(route('professor.inscricoes.index'))
            ->assertOk()
            ->assertDontSee('Candidato Reprovado');

        $this->actingAs($this->professorB)
            ->get(route('professor.inscricoes.index'))
            ->assertOk()
            ->assertSee('Candidato Reprovado');
    }

    #[Test]
    public function inscricao_com_disciplinas_de_dois_professores_aparece_para_ambos(): void
    {
        $inscricao = $this->criarInscricao(
            'Candidato Misto',
            obrigatoria: $this->disciplinaA,
            opcional1: $this->disciplinaB,
        );

        foreach ([$this->professorA, $this->professorB] as $professor) {
            $this->actingAs($professor)
                ->get(route('professor.inscricoes.index'))
                ->assertOk()
                ->assertSee($inscricao->nome_completo);
        }
    }

    #[Test]
    public function coluna_de_disciplinas_da_lista_mostra_so_as_do_professor(): void
    {
        $this->criarInscricao(
            'Candidato Misto',
            obrigatoria: $this->disciplinaA,
            opcional1: $this->disciplinaB,
        );

        $this->actingAs($this->professorA)
            ->get(route('professor.inscricoes.index'))
            ->assertOk()
            ->assertSee($this->disciplinaA->codigo_completo)
            ->assertDontSee($this->disciplinaB->codigo_completo);
    }

    #[Test]
    public function filtro_de_disciplina_lista_apenas_disciplinas_do_professor(): void
    {
        $this->actingAs($this->professorA)
            ->get(route('professor.inscricoes.index'))
            ->assertOk()
            ->assertSee('Disciplina Alfa')
            ->assertDontSee('Disciplina Beta');
    }

    #[Test]
    public function filtro_por_disciplina_de_outro_professor_e_ignorado(): void
    {
        $daA = $this->criarInscricao('Candidato Alfa', obrigatoria: $this->disciplinaA);

        $this->actingAs($this->professorA)
            ->get(route('professor.inscricoes.index', ['disciplina_id' => $this->disciplinaB->id]))
            ->assertOk()
            ->assertSee($daA->nome_completo);
    }

    #[Test]
    public function email_do_professor_e_comparado_sem_diferenciar_maiusculas_nem_espacos(): void
    {
        $this->disciplinaA->update(['professor_email' => '  PROF.A@IME.USP.BR ']);
        $inscricao = $this->criarInscricao('Candidato Alfa', obrigatoria: $this->disciplinaA);

        $this->actingAs($this->professorA)
            ->get(route('professor.inscricoes.index'))
            ->assertOk()
            ->assertSee($inscricao->nome_completo);

        $this->actingAs($this->professorA)
            ->get(route('professor.inscricoes.show', $inscricao))
            ->assertOk();
    }

    #[Test]
    public function show_exibe_acoes_apenas_para_as_disciplinas_do_professor(): void
    {
        $inscricao = $this->criarInscricao(
            'Candidato Misto',
            obrigatoria: $this->disciplinaA,
            opcional1: $this->disciplinaB,
        );

        $this->actingAs($this->professorA)
            ->get(route('professor.inscricoes.show', $inscricao))
            ->assertOk()
            ->assertSee('Aprovar '.$this->disciplinaA->codigo_completo)
            ->assertSee('Reprovar '.$this->disciplinaA->codigo_completo)
            ->assertDontSee('Aprovar '.$this->disciplinaB->codigo_completo)
            ->assertDontSee('Reprovar '.$this->disciplinaB->codigo_completo);
    }

    #[Test]
    public function show_retorna_404_quando_nenhuma_disciplina_e_do_professor(): void
    {
        $inscricao = $this->criarInscricao('Candidato Beta', obrigatoria: $this->disciplinaB);

        $this->actingAs($this->professorA)
            ->get(route('professor.inscricoes.show', $inscricao))
            ->assertNotFound();
    }

    #[Test]
    public function professor_aprova_e_reprova_a_propria_disciplina(): void
    {
        $inscricao = $this->criarInscricao(
            'Candidato Misto',
            obrigatoria: $this->disciplinaA,
            opcional1: $this->disciplinaB,
        );

        $this->actingAs($this->professorA)
            ->post(route('professor.inscricoes.aprovar', $inscricao), ['disciplina' => 'obrigatoria'])
            ->assertRedirect(route('professor.inscricoes.show', $inscricao));

        $inscricao->refresh();
        $this->assertSame(AprovacaoProfessorDisciplina::Aprovado, $inscricao->aprovacao_obrigatoria_professor);
        $this->assertNull($inscricao->aprovacao_opcional_1_professor);

        $this->actingAs($this->professorA)
            ->post(route('professor.inscricoes.reprovar', $inscricao), ['disciplina' => 'obrigatoria'])
            ->assertRedirect(route('professor.inscricoes.show', $inscricao));

        $inscricao->refresh();
        $this->assertSame(AprovacaoProfessorDisciplina::Reprovado, $inscricao->aprovacao_obrigatoria_professor);
    }

    #[Test]
    public function professor_nao_pode_aprovar_nem_reprovar_disciplina_de_outro_professor(): void
    {
        $inscricao = $this->criarInscricao(
            'Candidato Misto',
            obrigatoria: $this->disciplinaA,
            opcional1: $this->disciplinaB,
        );

        $this->actingAs($this->professorA)
            ->post(route('professor.inscricoes.aprovar', $inscricao), ['disciplina' => 'opcional_1'])
            ->assertForbidden();

        $this->actingAs($this->professorA)
            ->post(route('professor.inscricoes.reprovar', $inscricao), ['disciplina' => 'opcional_1'])
            ->assertForbidden();

        $this->assertNull($inscricao->fresh()->aprovacao_opcional_1_professor);
    }

    #[Test]
    public function professor_nao_pode_avaliar_inscricao_sem_nenhuma_disciplina_propria(): void
    {
        $inscricao = $this->criarInscricao('Candidato Beta', obrigatoria: $this->disciplinaB);

        $this->actingAs($this->professorA)
            ->post(route('professor.inscricoes.aprovar', $inscricao), ['disciplina' => 'obrigatoria'])
            ->assertForbidden();

        $this->assertNull($inscricao->fresh()->aprovacao_obrigatoria_professor);
    }

    #[Test]
    public function professor_nao_baixa_documentos_de_inscricao_sem_disciplina_propria(): void
    {
        Storage::fake('local');

        $inscricao = $this->criarInscricao('Candidato Beta', obrigatoria: $this->disciplinaB);
        $path = 'inscricoes/'.$inscricao->id.'/historico_escolar.pdf';
        $inscricao->update(['dados_etapa_2' => ['pdf_historico_escolar' => $path]]);
        Storage::disk('local')->put($path, 'conteudo-historico');

        $this->actingAs($this->professorA)
            ->get(route('professor.inscricoes.download', [$inscricao, 'pdf_historico_escolar']))
            ->assertNotFound();

        $this->actingAs($this->professorB)
            ->get(route('professor.inscricoes.download', [$inscricao, 'pdf_historico_escolar']))
            ->assertOk();
    }

    #[Test]
    public function disciplina_sem_professor_email_nao_da_acesso_a_professor(): void
    {
        $this->disciplinaA->update(['professor_email' => null]);
        $inscricao = $this->criarInscricao('Candidato Alfa', obrigatoria: $this->disciplinaA);

        $this->actingAs($this->professorA)
            ->get(route('professor.inscricoes.index'))
            ->assertOk()
            ->assertDontSee($inscricao->nome_completo);

        $this->actingAs($this->professorA)
            ->get(route('professor.inscricoes.show', $inscricao))
            ->assertNotFound();
    }

    #[Test]
    public function admin_continua_vendo_e_avaliando_todas_as_disciplinas(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $inscricao = $this->criarInscricao(
            'Candidato Misto',
            obrigatoria: $this->disciplinaA,
            opcional1: $this->disciplinaB,
        );

        $this->actingAs($admin)
            ->get(route('professor.inscricoes.index'))
            ->assertOk()
            ->assertSee($inscricao->nome_completo);

        $this->actingAs($admin)
            ->post(route('professor.inscricoes.aprovar', $inscricao), ['disciplina' => 'obrigatoria'])
            ->assertRedirect(route('professor.inscricoes.show', $inscricao));

        $this->actingAs($admin)
            ->post(route('professor.inscricoes.aprovar', $inscricao), ['disciplina' => 'opcional_1'])
            ->assertRedirect(route('professor.inscricoes.show', $inscricao));

        $inscricao->refresh();
        $this->assertSame(AprovacaoProfessorDisciplina::Aprovado, $inscricao->aprovacao_obrigatoria_professor);
        $this->assertSame(AprovacaoProfessorDisciplina::Aprovado, $inscricao->aprovacao_opcional_1_professor);
    }

    #[Test]
    public function secretaria_continua_vendo_a_situacao_de_todas_as_disciplinas(): void
    {
        $secretario = User::factory()->create();
        $secretario->assignRole('Secretario');

        $inscricao = $this->criarInscricao(
            'Candidato Misto',
            obrigatoria: $this->disciplinaA,
            opcional1: $this->disciplinaB,
        );

        $this->actingAs($secretario)
            ->get(route('inscricoes.show', $inscricao))
            ->assertOk()
            ->assertSee('Aprovação pelo Professor (2ª etapa)')
            ->assertSee($this->disciplinaA->codigo_completo)
            ->assertSee($this->disciplinaB->codigo_completo);
    }

    #[Test]
    public function reimportar_disciplinas_do_replicado_preserva_professor_cadastrado(): void
    {
        $servico = Mockery::mock(ReplicadoDisciplinaOfertadaService::class)->makePartial();
        $servico->shouldReceive('listarParaPeriodo')->once()->andReturn([
            ['sgldis' => 'MAT0101', 'departamento' => 'MAT', 'codigo' => '0101', 'nome' => 'Nome Atualizado'],
            ['sgldis' => 'MAT0303', 'departamento' => 'MAT', 'codigo' => '0303', 'nome' => 'Disciplina Nova'],
        ]);

        $importadas = $servico->importarParaPeriodo($this->periodo, '2026-01-01');

        $this->assertSame(2, $importadas);

        $this->disciplinaA->refresh();
        $this->assertSame('Nome Atualizado', $this->disciplinaA->nome);
        $this->assertSame($this->professorA->email, $this->disciplinaA->professor_email);

        $this->assertDatabaseHas('disciplinas_ofertadas', [
            'periodo_id' => $this->periodo->id,
            'departamento' => 'MAT',
            'codigo' => '0303',
            'professor_email' => null,
        ]);
    }

    private function criarProfessor(string $email): User
    {
        $professor = User::factory()->create(['email' => $email]);
        $professor->assignRole('Professor');

        return $professor;
    }

    private function criarDisciplina(string $codigo, string $nome, ?string $professorEmail): DisciplinaOfertada
    {
        return DisciplinaOfertada::factory()->create([
            'periodo_id' => $this->periodo->id,
            'departamento' => 'MAT',
            'codigo' => $codigo,
            'nome' => $nome,
            'professor_nome' => null,
            'professor_email' => $professorEmail,
        ]);
    }

    private function criarInscricao(
        string $nome,
        DisciplinaOfertada $obrigatoria,
        ?DisciplinaOfertada $opcional1 = null,
        bool $aprovarSecretaria = true,
    ): Inscricao {
        $aprovado = $aprovarSecretaria ? AprovacaoSecretariaDisciplina::Aprovado : null;

        return Inscricao::factory()->concluida()->create([
            'periodo_id' => $this->periodo->id,
            'nome_completo' => $nome,
            'disciplina_obrigatoria_id' => $obrigatoria->id,
            'justificativa_disciplina_obrigatoria' => 'Justificativa obrigatória.',
            'aprovacao_obrigatoria_secretaria' => $aprovado,
            'disciplina_opcional_1_id' => $opcional1?->id,
            'justificativa_disciplina_opcional_1' => $opcional1 !== null ? 'Justificativa opcional 1.' : null,
            'aprovacao_opcional_1_secretaria' => $opcional1 !== null ? $aprovado : null,
        ]);
    }
}
