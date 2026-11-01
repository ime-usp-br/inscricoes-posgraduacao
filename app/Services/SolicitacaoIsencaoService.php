<?php

namespace App\Services;

use App\Enums\PagamentoDisciplinaStatus;
use App\Enums\SolicitacaoIsencaoStatus;
use App\Models\Inscricao;
use App\Models\SolicitacaoIsencao;
use App\Models\User;
use App\Notifications\SolicitacaoIsencaoDecidida;
use App\Notifications\SolicitacaoIsencaoEnviada;
use App\Notifications\SolicitacaoIsencaoRecebida;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use RuntimeException;

class SolicitacaoIsencaoService
{
    public function valorPorDisciplina(): float
    {
        $valor = config('wspix.valor_por_disciplina', 107);

        return round(is_numeric($valor) ? (float) $valor : 107.0, 2);
    }

    /**
     * Cria a solicitação ou reabre uma solicitação cancelada da mesma inscrição.
     *
     * @param  list<string>  $slots
     */
    public function criar(Inscricao $inscricao, array $slots, string $justificativa): SolicitacaoIsencao
    {
        $inscricao->refresh();
        $this->carregarRelacoes($inscricao);

        if (! $inscricao->podeSolicitarIsencao()) {
            throw new RuntimeException('Esta inscrição não pode solicitar isenção neste momento.');
        }

        $justificativa = $this->validarJustificativa($justificativa);

        return DB::transaction(function () use ($inscricao, $slots, $justificativa): SolicitacaoIsencao {
            $inscricao->sincronizarPagamentosDisciplinas();

            $selecionadas = $this->selecionarDisciplinas($inscricao, $slots);
            $snapshot = $this->montarSnapshot($selecionadas);
            $solicitacao = $inscricao->solicitacaoIsencao;

            $dados = [
                'status' => SolicitacaoIsencaoStatus::Pendente,
                'justificativa' => $justificativa,
                'disciplinas_snapshot' => $snapshot,
                'resposta_secretaria' => null,
                'motivo_reprovacao' => null,
                'decidida_por' => null,
                'decidida_em' => null,
                'prazo_pagamento_em' => null,
                'prazo_pagamento_estendido_em' => null,
                'lembrete_agendado_para' => null,
                'lembrete_enviado_em' => null,
                'cancelada_em' => null,
                'motivo_cancelamento' => null,
            ];

            if ($solicitacao === null) {
                $solicitacao = new SolicitacaoIsencao($dados);
                $solicitacao->inscricao()->associate($inscricao);
                $solicitacao->save();
            } else {
                if ($solicitacao->status !== SolicitacaoIsencaoStatus::Cancelada) {
                    throw new RuntimeException('Já existe uma solicitação de isenção para esta inscrição.');
                }

                $solicitacao->fill($dados)->save();
            }

            $this->moverSlotsParaAguardandoIsencao($inscricao, $this->slotsDosItens($snapshot));

            // Enquanto a solicitação estiver pendente, nenhum Pix deve continuar válido.
            $inscricao->limparPix();
            $inscricao->save();
            $inscricao->setRelation('solicitacaoIsencao', $solicitacao);

            try {
                $adminsSecretarios = User::query()->role(['Admin', 'Secretario'])->get();
                Notification::send($solicitacao, new SolicitacaoIsencaoEnviada($solicitacao));
                Notification::send($adminsSecretarios, new SolicitacaoIsencaoRecebida($solicitacao));
            } catch (\Throwable) {
                // best-effort
            }

            return $this->fresca($solicitacao, ['documentos', 'inscricao']);
        });
    }

    /**
     * Edita uma solicitação ainda pendente, incluindo as disciplinas pleiteadas.
     *
     * @param  list<string>  $slots
     */
    public function atualizar(SolicitacaoIsencao $solicitacao, array $slots, string $justificativa): SolicitacaoIsencao
    {
        $solicitacao->refresh();
        $solicitacao->loadMissing('inscricao');
        $inscricao = $solicitacao->inscricao;

        if ($inscricao === null) {
            throw new RuntimeException('Solicitação sem inscrição vinculada.');
        }

        if ($solicitacao->status !== SolicitacaoIsencaoStatus::Pendente) {
            throw new RuntimeException('Somente solicitações pendentes podem ser editadas.');
        }

        $justificativa = $this->validarJustificativa($justificativa);
        $this->carregarRelacoes($inscricao);

        return DB::transaction(function () use ($solicitacao, $inscricao, $slots, $justificativa): SolicitacaoIsencao {
            $inscricao->sincronizarPagamentosDisciplinas();

            $slotsAnteriores = $this->slotsDoSnapshot($solicitacao);
            $this->restaurarSlotsParaPendente($inscricao, $slotsAnteriores);

            $selecionadas = $this->selecionarDisciplinas($inscricao, $slots, permitirPendente: true);
            $snapshot = $this->montarSnapshot($selecionadas);

            $solicitacao->fill([
                'justificativa' => $justificativa,
                'disciplinas_snapshot' => $snapshot,
            ])->save();

            $this->moverSlotsParaAguardandoIsencao($inscricao, $this->slotsDosItens($snapshot));
            $inscricao->save();
            $inscricao->setRelation('solicitacaoIsencao', $solicitacao);

            return $this->fresca($solicitacao, ['documentos', 'inscricao']);
        });
    }

    public function decidir(
        SolicitacaoIsencao $solicitacao,
        User $usuario,
        bool $aprovada,
        ?string $resposta = null,
        ?string $motivoReprovacao = null,
    ): SolicitacaoIsencao {
        $solicitacao->refresh();
        $solicitacao->loadMissing('inscricao');
        $inscricao = $solicitacao->inscricao;

        if ($inscricao === null) {
            throw new RuntimeException('Solicitação sem inscrição vinculada.');
        }

        $this->carregarRelacoes($inscricao);

        if ($solicitacao->status === SolicitacaoIsencaoStatus::Cancelada) {
            throw new RuntimeException('Solicitação cancelada não pode ser julgada.');
        }

        if (! $this->podeRevisar($solicitacao)) {
            throw new RuntimeException('A decisão não pode mais ser revista após a geração de Pix ou aprovação da inscrição.');
        }

        $motivoReprovacao = $this->textoOuNulo($motivoReprovacao);

        if (! $aprovada && $motivoReprovacao === null) {
            throw new InvalidArgumentException('Informe o motivo da reprovação da isenção.');
        }

        return DB::transaction(function () use (
            $solicitacao,
            $inscricao,
            $usuario,
            $aprovada,
            $resposta,
            $motivoReprovacao
        ): SolicitacaoIsencao {
            $decididaEm = now();
            $prazoDecisao = $decididaEm->copy()
                ->addDays($this->configInt('isencao.prazo_pagamento_dias', 5))
                ->endOfDay();

            $slots = $this->slotsDoSnapshot($solicitacao);

            if ($aprovada) {
                $this->marcarSlotsComoIsento($inscricao, $slots);
            } else {
                $this->restaurarSlotsParaPendente($inscricao, $slots);
            }

            $solicitacao->fill([
                'status' => $aprovada
                    ? SolicitacaoIsencaoStatus::Aprovada
                    : SolicitacaoIsencaoStatus::Reprovada,
                'resposta_secretaria' => $this->textoOuNulo($resposta),
                'motivo_reprovacao' => $aprovada ? null : $motivoReprovacao,
                'decidida_por' => $usuario->id,
                'decidida_em' => $decididaEm,
                'prazo_pagamento_em' => $prazoDecisao,
                'prazo_pagamento_estendido_em' => null,
                'cancelada_em' => null,
                'motivo_cancelamento' => null,
            ]);

            $prazoFinal = $inscricao->periodo?->data_fim_inscricao?->copy();

            if ($prazoFinal === null || $prazoDecisao->greaterThan($prazoFinal)) {
                $prazoFinal = $prazoDecisao->copy();
            }

            $this->agendarLembrete($solicitacao, $prazoFinal->endOfDay());
            $solicitacao->save();

            $inscricao->save();
            $inscricao->setRelation('solicitacaoIsencao', $solicitacao);

            $solicitacao->loadMissing('inscricao');
            try {
                Notification::send($solicitacao, new SolicitacaoIsencaoDecidida($solicitacao));
            } catch (\Throwable) {
                // best-effort
            }

            return $this->fresca($solicitacao, ['documentos', 'inscricao', 'decididaPor']);
        });
    }

    public function podeRevisar(SolicitacaoIsencao $solicitacao): bool
    {
        if ($solicitacao->status === SolicitacaoIsencaoStatus::Cancelada) {
            return false;
        }

        if ($solicitacao->status === SolicitacaoIsencaoStatus::Pendente) {
            return true;
        }

        $inscricao = $solicitacao->inscricao;

        if ($inscricao === null) {
            return false;
        }

        return ! $inscricao->possuiPixGerado() && ! $inscricao->foiAprovada();
    }

    public function cancelar(SolicitacaoIsencao $solicitacao, ?string $motivo = null): void
    {
        $solicitacao->refresh();
        $solicitacao->loadMissing('inscricao');
        $inscricao = $solicitacao->inscricao;

        if ($solicitacao->status !== SolicitacaoIsencaoStatus::Pendente) {
            throw new RuntimeException('Somente solicitações pendentes podem ser canceladas.');
        }

        DB::transaction(function () use ($solicitacao, $inscricao, $motivo): void {
            if ($inscricao !== null) {
                $this->restaurarSlotsParaPendente(
                    $inscricao,
                    $this->slotsDoSnapshot($solicitacao)
                );
                $inscricao->save();
                $inscricao->setRelation('solicitacaoIsencao', $solicitacao);
            }

            $solicitacao->fill([
                'status' => SolicitacaoIsencaoStatus::Cancelada,
                'cancelada_em' => now(),
                'motivo_cancelamento' => $this->textoOuNulo($motivo) ?? 'Cancelada pelo candidato.',
                'lembrete_agendado_para' => null,
                'lembrete_enviado_em' => null,
            ])->save();
        });
    }

    /**
     * Pagamento confirmado vence a isenção: a solicitação é encerrada sem reembolso automático.
     */
    public function cancelarPorPagamento(SolicitacaoIsencao $solicitacao, ?Carbon $pagoEm = null): void
    {
        if (! in_array(
            $solicitacao->status,
            [SolicitacaoIsencaoStatus::Pendente, SolicitacaoIsencaoStatus::Aprovada],
            true
        )) {
            return;
        }

        $solicitacao->fill([
            'status' => SolicitacaoIsencaoStatus::Cancelada,
            'cancelada_em' => $pagoEm ?? now(),
            'motivo_cancelamento' => 'Pagamento confirmado via Pix.',
            'lembrete_agendado_para' => null,
            'lembrete_enviado_em' => null,
        ])->save();

        $solicitacao->loadMissing('inscricao');
        $solicitacao->inscricao?->setRelation('solicitacaoIsencao', $solicitacao);
    }

    public function estenderPrazo(SolicitacaoIsencao $solicitacao, CarbonInterface $novoPrazo): SolicitacaoIsencao
    {
        $solicitacao->refresh();
        $solicitacao->loadMissing('inscricao');

        if (! in_array(
            $solicitacao->status,
            [SolicitacaoIsencaoStatus::Aprovada, SolicitacaoIsencaoStatus::Reprovada],
            true
        )) {
            throw new RuntimeException('Somente solicitações decididas podem ter o prazo estendido.');
        }

        $novoPrazo = Carbon::instance($novoPrazo)->endOfDay();
        $prazoAtual = $solicitacao->inscricao?->prazoPagamento();

        if ($prazoAtual !== null && $novoPrazo->lessThanOrEqualTo($prazoAtual)) {
            throw new InvalidArgumentException('O novo prazo deve ser posterior ao prazo atual.');
        }

        $solicitacao->prazo_pagamento_estendido_em = $novoPrazo;
        $this->agendarLembrete($solicitacao, $novoPrazo);
        $solicitacao->save();

        return $this->fresca($solicitacao, ['documentos', 'inscricao', 'decididaPor']);
    }

    private function carregarRelacoes(Inscricao $inscricao): void
    {
        $inscricao->loadMissing([
            'periodo',
            'disciplinaObrigatoria',
            'disciplinaOpcional1',
            'disciplinaOpcional2',
            'solicitacaoIsencao',
        ]);

        $inscricao->load([
            'disciplinaObrigatoria',
            'disciplinaOpcional1',
            'disciplinaOpcional2',
        ]);
    }

    private function validarJustificativa(string $justificativa): string
    {
        $texto = trim($justificativa);
        $minimo = $this->configInt('isencao.justificativa.min', 20);
        $maximo = $this->configInt('isencao.justificativa.max', 2000);
        $tamanho = mb_strlen($texto);

        if ($tamanho < $minimo) {
            throw new InvalidArgumentException("A justificativa deve ter ao menos {$minimo} caracteres.");
        }

        if ($tamanho > $maximo) {
            throw new InvalidArgumentException("A justificativa deve ter no máximo {$maximo} caracteres.");
        }

        return $texto;
    }

    /**
     * @param  list<string>  $slots
     * @return list<array{slot: string, disciplina: \App\Models\DisciplinaOfertada, status: PagamentoDisciplinaStatus}>
     */
    private function selecionarDisciplinas(
        Inscricao $inscricao,
        array $slots,
        bool $permitirPendente = false,
    ): array {
        $slots = array_values(array_unique(array_map('strval', $slots)));

        if ($slots === []) {
            throw new InvalidArgumentException('Selecione ao menos uma disciplina para solicitar isenção.');
        }

        $invalidos = array_diff($slots, Inscricao::SLOTS_DISCIPLINA);

        if ($invalidos !== []) {
            throw new InvalidArgumentException('Disciplina inválida na solicitação de isenção.');
        }

        $elegiveis = [];

        foreach ($inscricao->disciplinasElegiveisParaIsencao() as $item) {
            $elegiveis[$item['slot']] = $item;
        }

        $selecionadas = [];

        foreach (Inscricao::SLOTS_DISCIPLINA as $slot) {
            if (! in_array($slot, $slots, true) || ! isset($elegiveis[$slot])) {
                continue;
            }

            $item = $elegiveis[$slot];
            $status = $inscricao->statusPagamentoNoSlot($slot);

            if (
                ! $permitirPendente
                && $status !== null
                && $status !== PagamentoDisciplinaStatus::Pendente
            ) {
                continue;
            }

            $selecionadas[] = $item;
        }

        if ($selecionadas === []) {
            throw new InvalidArgumentException('Nenhuma das disciplinas selecionadas é elegível para isenção.');
        }

        return $selecionadas;
    }

    /**
     * @param  list<array{slot: string, disciplina: \App\Models\DisciplinaOfertada, status: PagamentoDisciplinaStatus}>  $disciplinas
     * @return list<array{slot: string, disciplina_id: int, codigo: string, nome: string, departamento: string, valor: float}>
     */
    private function montarSnapshot(array $disciplinas): array
    {
        $valor = $this->valorPorDisciplina();

        return array_map(
            fn (array $item): array => [
                'slot' => $item['slot'],
                'disciplina_id' => $item['disciplina']->id,
                'codigo' => $item['disciplina']->codigo_completo,
                'nome' => $item['disciplina']->nome,
                'departamento' => $item['disciplina']->departamento,
                'valor' => $valor,
            ],
            $disciplinas
        );
    }

    /**
     * @param  list<string>  $slots
     */
    private function moverSlotsParaAguardandoIsencao(Inscricao $inscricao, array $slots): void
    {
        $pagamentos = is_array($inscricao->pagamentos_disciplinas)
            ? $inscricao->pagamentos_disciplinas
            : [];

        foreach ($slots as $slot) {
            if (! array_key_exists($slot, $pagamentos)) {
                continue;
            }

            if ($pagamentos[$slot] === PagamentoDisciplinaStatus::Pago->value) {
                continue;
            }

            $pagamentos[$slot] = PagamentoDisciplinaStatus::AguardandoIsencao->value;
        }

        $inscricao->pagamentos_disciplinas = $pagamentos;
    }

    /**
     * @param  list<string>  $slots
     */
    private function marcarSlotsComoIsento(Inscricao $inscricao, array $slots): void
    {
        $pagamentos = is_array($inscricao->pagamentos_disciplinas)
            ? $inscricao->pagamentos_disciplinas
            : [];

        foreach ($slots as $slot) {
            if (! array_key_exists($slot, $pagamentos)) {
                continue;
            }

            if ($pagamentos[$slot] === PagamentoDisciplinaStatus::Pago->value) {
                continue;
            }

            $pagamentos[$slot] = PagamentoDisciplinaStatus::Isento->value;
        }

        $inscricao->pagamentos_disciplinas = $pagamentos;
    }

    /**
     * @param  list<string>  $slots
     */
    private function restaurarSlotsParaPendente(Inscricao $inscricao, array $slots): void
    {
        $pagamentos = is_array($inscricao->pagamentos_disciplinas)
            ? $inscricao->pagamentos_disciplinas
            : [];

        foreach ($slots as $slot) {
            if (! array_key_exists($slot, $pagamentos)) {
                continue;
            }

            if ($pagamentos[$slot] === PagamentoDisciplinaStatus::AguardandoIsencao->value) {
                $pagamentos[$slot] = PagamentoDisciplinaStatus::Pendente->value;
            }
        }

        $inscricao->pagamentos_disciplinas = $pagamentos;
    }

    public function agendarLembreteInterno(SolicitacaoIsencao $solicitacao, CarbonInterface $prazo): void
    {
        $this->agendarLembrete($solicitacao, $prazo);
        $solicitacao->lembrete_enviado_em = null;
        $solicitacao->save();
    }

    private function agendarLembrete(SolicitacaoIsencao $solicitacao, CarbonInterface $prazo): void
    {
        $dias = $this->configInt('isencao.lembrete_dias_antes', 2);

        if ($dias <= 0) {
            $solicitacao->lembrete_agendado_para = null;
            $solicitacao->lembrete_enviado_em = null;

            return;
        }

        $lembrete = Carbon::instance($prazo)->subDays($dias);
        $solicitacao->lembrete_agendado_para = $lembrete->isPast() ? now() : $lembrete;
        $solicitacao->lembrete_enviado_em = null;
    }

    private function textoOuNulo(?string $texto): ?string
    {
        $texto = trim((string) $texto);

        return $texto === '' ? null : $texto;
    }

    /**
     * @param  list<string>  $relacoes
     */
    private function fresca(SolicitacaoIsencao $solicitacao, array $relacoes = []): SolicitacaoIsencao
    {
        $fresca = $solicitacao->fresh($relacoes);

        if ($fresca === null) {
            throw new RuntimeException('Não foi possível recarregar a solicitação de isenção.');
        }

        return $fresca;
    }

    /**
     * @return list<string>
     */
    private function slotsDoSnapshot(SolicitacaoIsencao $solicitacao): array
    {
        $snapshot = $solicitacao->getAttribute('disciplinas_snapshot');

        return is_array($snapshot) ? $this->slotsDosItens($snapshot) : [];
    }

    /**
     * @param  array<mixed>  $itens
     * @return list<string>
     */
    private function slotsDosItens(array $itens): array
    {
        $slots = [];

        foreach ($itens as $item) {
            if (is_array($item) && isset($item['slot']) && is_string($item['slot'])) {
                $slots[] = $item['slot'];
            }
        }

        return $slots;
    }

    private function configInt(string $chave, int $padrao): int
    {
        $valor = config($chave, $padrao);

        return is_numeric($valor) ? (int) $valor : $padrao;
    }
}
