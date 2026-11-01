<?php

namespace App\Notifications;

use App\Models\Inscricao;
use App\Models\SolicitacaoIsencao;
use App\Notifications\Concerns\WithEmailLogging;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitacaoIsencaoEnviada extends Notification implements ShouldQueue
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

        $url = route('inscricao.isencao.show');

        return (new MailMessage)
            ->subject('Solicitação de isenção recebida')
            ->greeting('Olá, '.($inscricao?->nome_completo ?? $notifiable->name ?? 'candidato').',')
            ->line('Recebemos sua solicitação de isenção da taxa de inscrição.')
            ->line('A secretaria analisará o pedido e você será notificado sobre a decisão.')
            ->action('Acompanhar solicitação', $url)
            ->line('Caso já tenha dúvida, acompanhe o status pelo link acima.');
    }
}
