<?php

namespace App\Notifications;

use App\Models\DisciplinaOfertada;
use App\Models\Inscricao;
use App\Notifications\Concerns\WithEmailLogging;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

class AprovacaoProfessorPendenteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @use WithEmailLogging<\App\Models\User|\Illuminate\Notifications\AnonymousNotifiable> */
    use WithEmailLogging;

    /**
     * @param  array<int, array{inscricao: Inscricao, disciplina: DisciplinaOfertada, slot: string}>  $pendencias
     */
    public function __construct(
        private readonly array $pendencias,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @param  mixed  $notifiable
     */
    protected function toMailMessage($notifiable): MailMessage
    {
        $count = count($this->pendencias);
        $nome = is_object($notifiable) && property_exists($notifiable, 'name') && is_string($notifiable->name)
            ? $notifiable->name
            : null;

        $mail = (new MailMessage)
            ->subject($count === 1 ? '1 inscrição pendente para sua aprovação' : "{$count} inscrições pendentes para sua aprovação")
            ->greeting('Olá'.($nome ? ', '.$nome : '').',');

        $mail->line('As disciplinas abaixo foram aprovadas pela Secretaria e aguardam sua avaliação como professor(a):');
        $mail->line('**Pendências:**');

        foreach ($this->pendencias as $item) {
            $inscricao = $item['inscricao'];
            $disciplina = $item['disciplina'];

            $codigo = $disciplina->codigo_completo;
            $nomeCandidato = $inscricao->nome_completo ?? '-';
            $inscricaoId = $inscricao->id ?? '-';

            $mail->line("- {$codigo} — {$disciplina->nome} | Candidato(a): {$nomeCandidato} | Inscrição: #{$inscricaoId}");
        }

        try {
            $mail->action('Avaliar pendências', route('professor.inscricoes.index'));
        } catch (Throwable) {
            // Não quebrar o envio caso a rota ainda não esteja mapeada
        }

        $mail->line('Por favor, avalie o quanto antes para não atrasar o andamento do processo.');

        return $mail;
    }
}
