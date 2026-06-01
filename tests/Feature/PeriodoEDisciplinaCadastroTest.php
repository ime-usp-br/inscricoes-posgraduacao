<?php

namespace Tests\Feature;

use App\Models\DisciplinaOfertada;
use App\Models\Periodo;
use App\Models\User;
use App\Services\ReplicadoDisciplinaOfertadaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PeriodoEDisciplinaCadastroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Secretario', 'guard_name' => 'web']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function secretario_can_create_periodo_without_manual_status_and_it_is_computed_automatically(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 26, 12, 0, 0, Periodo::BRASILIA_TIMEZONE));

        $secretario = User::factory()->create();
        $secretario->assignRole('Secretario');

        $this->mockReplicadoDisciplinas([
            [
                'sgldis' => 'MAT0101',
                'departamento' => 'MAT',
                'codigo' => '0101',
                'nome' => 'Álgebra Linear',
            ],
        ]);

        $this->actingAs($secretario)
            ->post(route('periodo.salvar'), [
                'ano' => 2026,
                'semestre' => 1,
                'data_inicio_inscricao' => '2026-05-20',
                'data_fim_inscricao' => '2026-05-26',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $periodo = Periodo::query()->firstOrFail();

        $this->assertSame('aberto', $periodo->status);
        $this->assertSame('2026-05-20 00:00:00', $periodo->getRawOriginal('data_inicio_inscricao'));
        $this->assertSame('2026-05-26 23:59:59', $periodo->getRawOriginal('data_fim_inscricao'));
        $this->assertTrue($periodo->is(Periodo::ativoParaInscricoes()));
        $this->assertDatabaseHas('disciplinas_ofertadas', [
            'periodo_id' => $periodo->id,
            'departamento' => 'MAT',
            'codigo' => '0101',
            'nome' => 'Álgebra Linear',
        ]);
    }

    #[Test]
    public function confirmar_exibe_disciplinas_encontradas_no_replicado(): void
    {
        $secretario = User::factory()->create();
        $secretario->assignRole('Secretario');

        $this->mockReplicadoDisciplinas([
            [
                'sgldis' => 'MAC0201',
                'departamento' => 'MAC',
                'codigo' => '0201',
                'nome' => 'Análise Real I',
            ],
            [
                'sgldis' => 'MAE0050',
                'departamento' => 'MAE',
                'codigo' => '0050',
                'nome' => 'Equações Diferenciais',
            ],
        ]);

        $this->actingAs($secretario)
            ->post(route('periodo.confirmar'), [
                'ano' => 2026,
                'semestre' => 1,
                'data_inicio_inscricao' => '2026-06-01',
                'data_fim_inscricao' => '2026-06-30',
            ])
            ->assertOk()
            ->assertSee('MAC0201')
            ->assertSee('MAE0050')
            ->assertSee('2 disciplina(s) serão cadastradas');
    }

    #[Test]
    public function salvar_importa_todas_disciplinas_retornadas_pelo_replicado(): void
    {
        $secretario = User::factory()->create();
        $secretario->assignRole('Secretario');

        $this->mockReplicadoDisciplinas([
            [
                'sgldis' => 'MAC1001',
                'departamento' => 'MAC',
                'codigo' => '1001',
                'nome' => 'Disciplina MAC',
            ],
            [
                'sgldis' => 'MAE0050',
                'departamento' => 'MAE',
                'codigo' => '0050',
                'nome' => 'Disciplina MAE',
            ],
        ]);

        $this->actingAs($secretario)
            ->post(route('periodo.salvar'), [
                'ano' => 2026,
                'semestre' => 2,
                'data_inicio_inscricao' => '2026-08-01',
                'data_fim_inscricao' => '2026-08-31',
            ])
            ->assertRedirect();

        $periodo = Periodo::query()->where('semestre', 2)->firstOrFail();

        $this->assertSame(2, $periodo->disciplinasOfertadas()->count());
    }

    /**
     * @param  list<array{sgldis: string, departamento: string, codigo: string, nome: string}>  $disciplinas
     */
    private function mockReplicadoDisciplinas(array $disciplinas): void
    {
        $mock = $this->mock(ReplicadoDisciplinaOfertadaService::class);

        $mock->shouldReceive('listarParaPeriodo')
            ->andReturn($disciplinas);

        $mock->shouldReceive('importarParaPeriodo')
            ->andReturnUsing(function (Periodo $periodo) use ($disciplinas): int {
                foreach ($disciplinas as $disciplina) {
                    DisciplinaOfertada::query()->updateOrCreate(
                        [
                            'periodo_id' => $periodo->id,
                            'departamento' => $disciplina['departamento'],
                            'codigo' => $disciplina['codigo'],
                        ],
                        [
                            'nome' => $disciplina['nome'],
                            'professor_nome' => null,
                            'professor_email' => null,
                        ],
                    );
                }

                return count($disciplinas);
            });
    }

    #[Test]
    public function periodo_status_changes_automatically_before_during_and_after_the_window(): void
    {
        $periodo = Periodo::factory()->create([
            'data_inicio_inscricao' => '2026-06-01',
            'data_fim_inscricao' => '2026-06-10',
        ]);

        Carbon::setTestNow(Carbon::create(2026, 5, 31, 23, 59, 0, Periodo::BRASILIA_TIMEZONE));
        $this->assertSame('fechado', $periodo->fresh()->status);

        Carbon::setTestNow(Carbon::create(2026, 6, 5, 12, 0, 0, Periodo::BRASILIA_TIMEZONE));
        $this->assertSame('aberto', $periodo->fresh()->status);

        Carbon::setTestNow(Carbon::create(2026, 6, 11, 0, 0, 0, Periodo::BRASILIA_TIMEZONE));
        $this->assertSame('fechado', $periodo->fresh()->status);
    }

    #[Test]
    public function secretario_can_create_disciplina_with_optional_professor_fields_and_mpm_department(): void
    {
        $secretario = User::factory()->create();
        $secretario->assignRole('Secretario');

        $periodo = Periodo::factory()->create();

        $this->actingAs($secretario)
            ->post(route('disciplina-ofertada.store'), [
                'periodo_id' => $periodo->id,
                'departamento' => 'MPM',
                'codigo' => '1234',
                'nome' => 'Modelagem Matemática',
                'professor_nome' => '',
                'professor_email' => '',
            ])
            ->assertRedirect(route('disciplina-ofertada.index'));

        $this->assertDatabaseHas('disciplinas_ofertadas', [
            'periodo_id' => $periodo->id,
            'departamento' => 'MPM',
            'codigo' => '1234',
            'nome' => 'Modelagem Matemática',
            'professor_nome' => null,
            'professor_email' => null,
        ]);
    }

    #[Test]
    public function disciplina_form_shows_mpm_and_hides_codigo_completo_preview(): void
    {
        $secretario = User::factory()->create();
        $secretario->assignRole('Secretario');

        $disciplina = DisciplinaOfertada::factory()->create();

        $this->actingAs($secretario)
            ->get(route('disciplina-ofertada.create'))
            ->assertOk()
            ->assertSee('MPM')
            ->assertDontSee('Código completo');

        $this->actingAs($secretario)
            ->get(route('disciplina-ofertada.edit', $disciplina))
            ->assertOk()
            ->assertSee('MPM')
            ->assertDontSee('Código completo');
    }
}
