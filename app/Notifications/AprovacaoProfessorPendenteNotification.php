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
    use Queueable, WithEmailLogging;

    /**
     * @param array<int, array{inscricao: Inscricao, disciplina: DisciplinaOfertada, slot: string}> $pendencias
     */
    public function __construct(
        private readonly array $pendencias,
    ) {
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = count($this->pendencias);

        $mail = (new MailMessage)
            ->subject($count === 1 ? '1 inscrição pendente para sua aprovação' : "{$count} inscrições pendentes para sua aprovação")
            ->greeting('Olá'.($notifiable->name ? ', '.$notifiable->name : ',').',');

        $mail->line('As disciplinas abaixo foram aprovadas pela Secretaria e aguardam sua avaliação como professor(a):');
        $mail->line('**Pendências:**');

        foreach ($this->pendencias as $item) {
            $inscricao = $item['inscricao'];
            $disciplina = $item['disciplina'];

            if ($disciplina instanceof DisciplinaOfertada) {
                $codigo = $disciplina->codigo_completo;
            } else {
                $codigo = '-';
            }

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
