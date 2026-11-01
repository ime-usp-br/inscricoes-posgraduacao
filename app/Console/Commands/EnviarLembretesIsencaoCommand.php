<?php

namespace App\Console\Commands;

use App\Models\SolicitacaoIsencao;
use App\Notifications\SolicitacaoIsencaoDecidida;
use App\Services\SolicitacaoIsencaoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class EnviarLembretesIsencaoCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'isencao:enviar-lembretes';

    /**
     * @var string
     */
    protected $description = 'Envia lembretes de pagamento para solicitações de isenção com prazo próximo.';

    public function __construct(
        private readonly SolicitacaoIsencaoService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $agora = now();
        $dias = (int) config('isencao.lembrete_dias_antes', 2);

        $solicitacoes = SolicitacaoIsencao::query()
            ->with(['inscricao.periodo', 'decididaPor'])
            ->whereNull('lembrete_enviado_em')
            ->whereNotNull('lembrete_agendado_para')
            ->where('lembrete_agendado_para', '<=', $agora)
            ->get();

        $enviados = 0;

        foreach ($solicitacoes as $solicitacao) {
            $inscricao = $solicitacao->inscricao;

            if ($inscricao === null) {
                continue;
            }

            if (! in_array($solicitacao->status->value, ['aprovada', 'reprovada'], true)) {
                continue;
            }

            if ($inscricao->possuiPixGerado()) {
                continue;
            }

            if ($inscricao->prazoPagamentoExpirado($agora)) {
                continue;
            }

            $prazo = $inscricao->prazoPagamento();

            if ($prazo !== null && $prazo->greaterThan($agora->copy()->addDays($dias + 1))) {
                $this->service->agendarLembreteInterno($solicitacao, $prazo);

                continue;
            }

            $solicitacao->loadMissing('inscricao');

            Notification::send($solicitacao, new SolicitacaoIsencaoDecidida($solicitacao));

            $solicitacao->lembrete_enviado_em = now();
            $solicitacao->save();

            $enviados++;
        }

        $this->info("Lembretes enviados: {$enviados}");

        return self::SUCCESS;
    }
}
