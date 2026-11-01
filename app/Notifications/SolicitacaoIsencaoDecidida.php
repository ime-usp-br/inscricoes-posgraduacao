<?php

namespace App\Notifications;

use App\Models\Inscricao;
use App\Models\SolicitacaoIsencao;
use App\Notifications\Concerns\WithEmailLogging;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitacaoIsencaoDecidida extends Notification implements ShouldQueue
{
    use Queueable, WithEmailLogging;

    public function __construct(
        private readonly SolicitacaoIsencao $solicitacao,
    ) {
        $this->initEmailLogging();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMailMessage($notifiable): MailMessage
    {
        return $this->buildMail($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->buildMail($notifiable);
    }

    private function buildMail(object $notifiable): MailMessage
    {
        $inscricao = $this->solicitacao->inscricao ?? Inscricao::find($this->solicitacao->inscricao_id);
        $aprovada = $this->solicitacao->status->value === 'aprovada';

        $url = route('inscricao.isencao.show');

        $message = (new MailMessage)
            ->subject($aprovada ? 'Solicitação de isenção aprovada' : 'Solicitação de isenção reprovada')
            ->greeting('Olá, '.($inscricao?->nome_completo ?? $notifiable->name ?? 'candidato').',');

        if ($aprovada) {
            $message->line('Sua solicitação de isenção foi aprovada pela secretaria.')
                ->line('As disciplinas solicitadas estão isentas da taxa de inscrição.');
        } else {
            $message->line('Sua solicitação de isenção foi reprovada pela secretaria.');
            if ($this->solicitacao->motivo_reprovacao) {
                $message->line('Motivo: '.$this->solicitacao->motivo_reprovacao);
            }
        }

        if ($this->solicitacao->resposta_secretaria) {
            $message->line('Resposta da secretaria: '.$this->solicitacao->resposta_secretaria);
        }

        if (! $aprovada) {
            $message->line('Caso o prazo esteja aberto, você pode seguir com o pagamento da taxa pelo link abaixo.');
        }

        return $message
            ->action('Ver solicitação', $url)
            ->line('Em caso de dúvidas, entre em contato com a secretaria.');
    }
}
