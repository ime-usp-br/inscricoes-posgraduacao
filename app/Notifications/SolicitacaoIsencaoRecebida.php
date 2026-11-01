<?php

namespace App\Notifications;

use App\Models\Inscricao;
use App\Models\SolicitacaoIsencao;
use App\Notifications\Concerns\WithEmailLogging;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitacaoIsencaoRecebida extends Notification implements ShouldQueue
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

        $url = route('inscricoes.show', $inscricao);

        return (new MailMessage)
            ->subject('Nova solicitação de isenção para análise')
            ->greeting('Olá, '.($notifiable->name ?? 'secretaria').',')
            ->line('Foi recebida uma nova solicitação de isenção.')
            ->line('Candidato: '.($inscricao?->nome_completo ?? '—'))
            ->line('E-mail: '.($inscricao?->email ?? '—'))
            ->action('Analisar solicitação', $url)
            ->line('Acesse a inscrição para avaliar e decidir sobre a isenção.');
    }
}
