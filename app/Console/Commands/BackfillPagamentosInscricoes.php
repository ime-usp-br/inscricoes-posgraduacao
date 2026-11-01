<?php

namespace App\Console\Commands;

use App\Models\Inscricao;
use Illuminate\Console\Command;

class BackfillPagamentosInscricoes extends Command
{
    protected $signature = 'pagamentos:backfill
                            {--dry-run : Apenas informa o que seria alterado}
                            {--chunk=100 : Quantidade de inscrições processadas por lote}';

    protected $description = 'Sincroniza os status de pagamento por disciplina das inscrições existentes';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        $total = 0;
        $atualizadas = 0;
        $inalteradas = 0;

        Inscricao::query()
            ->with(['disciplinaObrigatoria', 'disciplinaOpcional1', 'disciplinaOpcional2'])
            ->orderBy('id')
            ->chunkById($chunk, function ($inscricoes) use ($dryRun, &$total, &$atualizadas, &$inalteradas): void {
                foreach ($inscricoes as $inscricao) {
                    $total++;

                    $inscricao->sincronizarPagamentosDisciplinas();

                    if (! $inscricao->isDirty('pagamentos_disciplinas')) {
                        $inalteradas++;

                        continue;
                    }

                    $atualizadas++;

                    if (! $dryRun) {
                        $inscricao->save();
                    }
                }
            });

        $modo = $dryRun ? ' (simulação)' : '';

        $this->info(sprintf(
            'Pagamentos sincronizados%s: %d inscrição(ões) processada(s), %d atualizada(s), %d inalterada(s).',
            $modo,
            $total,
            $atualizadas,
            $inalteradas
        ));

        return self::SUCCESS;
    }
}
