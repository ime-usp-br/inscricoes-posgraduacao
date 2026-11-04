<?php

namespace Tests\Feature;

use App\Enums\AprovacaoSecretariaDisciplina;
use App\Models\Inscricao;
use App\Models\User;
use App\Notifications\AprovacaoProfessorPendenteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnviarLembretesAprovacaoProfessorCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function comando_envia_lembrete_e_registra_pendencia_do_professor(): void
    {
        Notification::fake();

        $professor = User::factory()->create();
        $inscricao = Inscricao::factory()
            ->concluida()
            ->comTresDisciplinas()
            ->create([
                'aprovacao_obrigatoria_secretaria' => AprovacaoSecretariaDisciplina::Aprovado,
            ]);

        $disciplina = $inscricao->disciplinaObrigatoria;
        $disciplina->update(['professor_email' => $professor->email]);

        $this->artisan('professor:enviar-lembretes-aprovacao', ['--force' => true])
            ->assertExitCode(0);

        Notification::assertSentTo(
            $professor,
            AprovacaoProfessorPendenteNotification::class,
            function (AprovacaoProfessorPendenteNotification $notification) use ($professor, $inscricao): bool {
                $message = $notification->toMail($professor);

                $this->assertSame('1 inscrição pendente para sua aprovação', $message->subject);
                $this->assertStringContainsString(
                    $inscricao->nome_completo,
                    implode("\n", $message->introLines)
                );
                $this->assertNotNull($notification->emailLogUuid);

                return true;
            }
        );

        $this->assertDatabaseHas('professor_pendency_reminders', [
            'professor_email' => $professor->email,
            'user_id' => $professor->id,
            'inscricao_id' => $inscricao->id,
            'disciplina_ofertada_id' => $disciplina->id,
            'slot' => 'obrigatoria',
        ]);
    }
}
