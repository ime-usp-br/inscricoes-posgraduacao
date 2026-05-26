<?php

namespace Tests\Feature;

use App\Models\DisciplinaOfertada;
use App\Models\Periodo;
use App\Models\User;
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

        $this->actingAs($secretario)
            ->post(route('periodo.salvar'), [
                'ano' => 2026,
                'semestre' => 1,
                'data_inicio_inscricao' => '2026-05-20',
                'data_fim_inscricao' => '2026-05-26',
            ])
            ->assertRedirect();

        $periodo = Periodo::query()->firstOrFail();

        $this->assertSame('aberto', $periodo->status);
        $this->assertSame('2026-05-20 00:00:00', $periodo->getRawOriginal('data_inicio_inscricao'));
        $this->assertSame('2026-05-26 23:59:59', $periodo->getRawOriginal('data_fim_inscricao'));
        $this->assertTrue($periodo->is(Periodo::ativoParaInscricoes()));
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
