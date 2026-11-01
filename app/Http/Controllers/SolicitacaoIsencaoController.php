<?php

namespace App\Http\Controllers;

use App\Enums\SolicitacaoIsencaoStatus;
use App\Http\Requests\SolicitacaoIsencaoRequest;
use App\Models\Inscricao;
use App\Models\SolicitacaoIsencao;
use App\Models\SolicitacaoIsencaoDocumento;
use App\Services\SolicitacaoIsencaoDocumentoService;
use App\Services\SolicitacaoIsencaoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SolicitacaoIsencaoController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $this->garantirFluxoHabilitado();

        $inscricao = $this->inscricaoDaSessao();

        if ($inscricao === null || $inscricao->etapa_concluida < 3) {
            return redirect()
                ->route('home')
                ->withErrors(['sessao' => 'Sessão inválida ou inscrição incompleta.']);
        }

        $solicitacao = $inscricao->solicitacaoIsencao;

        if ($solicitacao !== null && $solicitacao->status !== SolicitacaoIsencaoStatus::Cancelada) {
            return view('inscricao.isencao.status', [
                'inscricao' => $inscricao,
                'periodo' => $inscricao->periodo,
                'solicitacao' => $solicitacao,
            ]);
        }

        if (! $inscricao->podeSolicitarIsencao()) {
            return redirect()
                ->route('inscricao.pagamento')
                ->withErrors(['isencao' => 'Esta inscrição não está elegível para solicitar isenção neste momento.']);
        }

        return view('inscricao.isencao.form', [
            'inscricao' => $inscricao,
            'periodo' => $inscricao->periodo,
            'solicitacao' => $solicitacao,
            'disciplinas' => $inscricao->disciplinasElegiveisParaIsencao(),
        ]);
    }

    public function edit(): View|RedirectResponse
    {
        $this->garantirFluxoHabilitado();

        $inscricao = $this->inscricaoDaSessao();
        $solicitacao = $this->solicitacaoPendenteDaSessao($inscricao);

        if ($inscricao === null || $solicitacao === null) {
            return redirect()
                ->route('inscricao.isencao.show')
                ->withErrors(['sessao' => 'Somente solicitações pendentes podem ser editadas.']);
        }

        return view('inscricao.isencao.form', [
            'inscricao' => $inscricao,
            'periodo' => $inscricao->periodo,
            'solicitacao' => $solicitacao,
            'disciplinas' => $this->disciplinasParaFormulario($inscricao, $solicitacao),
        ]);
    }

    public function store(
        SolicitacaoIsencaoRequest $request,
        SolicitacaoIsencaoService $service,
        SolicitacaoIsencaoDocumentoService $documentos,
    ): RedirectResponse {
        $this->garantirFluxoHabilitado();

        $inscricao = $this->inscricaoDaSessao();

        if ($inscricao === null) {
            return redirect()
                ->route('home')
                ->withErrors(['sessao' => 'Sessão inválida ou inscrição incompleta.']);
        }

        $solicitacao = $service->criar(
            $inscricao,
            $request->slotsSelecionados(),
            $request->string('justificativa')->toString()
        );

        $documentos->anexar($solicitacao, $request->documentosEnviados());

        return redirect()
            ->route('inscricao.isencao.show')
            ->with('success', 'Solicitação de isenção enviada. A secretaria fará a análise.');
    }

    public function update(
        SolicitacaoIsencaoRequest $request,
        SolicitacaoIsencaoService $service,
        SolicitacaoIsencaoDocumentoService $documentos,
    ): RedirectResponse {
        $this->garantirFluxoHabilitado();

        $inscricao = $this->inscricaoDaSessao();
        $solicitacao = $this->solicitacaoPendenteDaSessao($inscricao);

        if ($inscricao === null || $solicitacao === null) {
            return redirect()
                ->route('home')
                ->withErrors(['sessao' => 'Solicitação pendente não encontrada para edição.']);
        }

        $service->atualizar(
            $solicitacao,
            $request->slotsSelecionados(),
            $request->string('justificativa')->toString()
        );

        $solicitacao->refresh();

        $documentos->remover($solicitacao, $request->documentosParaRemover());
        $documentos->anexar($solicitacao, $request->documentosEnviados());

        return redirect()
            ->route('inscricao.isencao.show')
            ->with('success', 'Solicitação de isenção atualizada.');
    }

    public function cancelar(SolicitacaoIsencaoService $service): RedirectResponse
    {
        $this->garantirFluxoHabilitado();

        $inscricao = $this->inscricaoDaSessao();
        $solicitacao = $this->solicitacaoPendenteDaSessao($inscricao);

        if ($solicitacao === null) {
            return redirect()
                ->route('home')
                ->withErrors(['sessao' => 'Solicitação pendente não encontrada.']);
        }

        $service->cancelar($solicitacao);

        return redirect()
            ->route('inscricao.isencao.show')
            ->with('success', 'Solicitação de isenção cancelada. Você pode reabrir enquanto o período de inscrições estiver aberto.');
    }

    public function documento(SolicitacaoIsencaoDocumento $documento): StreamedResponse
    {
        $this->garantirFluxoHabilitado();

        $inscricao = $this->inscricaoDaSessao();
        $documento->loadMissing('solicitacaoIsencao');

        if (
            $inscricao === null
            || $documento->solicitacaoIsencao?->inscricao_id !== $inscricao->id
        ) {
            abort(403);
        }

        $disco = config('isencao.documentos.disco', 'local');
        $disco = is_string($disco) ? $disco : 'local';

        if (! Storage::disk($disco)->exists($documento->path)) {
            abort(404);
        }

        return Storage::disk($disco)->download($documento->path, $documento->nome_original);
    }

    /**
     * Inclui as disciplinas atualmente pleiteadas mesmo que já estejam aguardando isenção.
     *
     * @return list<array{slot: string, disciplina: \App\Models\DisciplinaOfertada, status: \App\Enums\PagamentoDisciplinaStatus}>
     */
    private function disciplinasParaFormulario(Inscricao $inscricao, SolicitacaoIsencao $solicitacao): array
    {
        $porSlot = [];

        foreach ($inscricao->disciplinasElegiveisParaIsencao() as $item) {
            $porSlot[$item['slot']] = $item;
        }

        $snapshot = $solicitacao->disciplinas_snapshot;

        foreach ($snapshot as $item) {
            if (! is_array($item)) {
                continue;
            }

            $slot = $item['slot'] ?? null;

            if (! is_string($slot) || isset($porSlot[$slot])) {
                continue;
            }

            $disciplina = $inscricao->disciplinaNoSlot($slot);

            if ($disciplina === null) {
                continue;
            }

            $porSlot[$slot] = [
                'slot' => $slot,
                'disciplina' => $disciplina,
                'status' => $inscricao->statusPagamentoNoSlot($slot)
                    ?? \App\Enums\PagamentoDisciplinaStatus::Pendente,
            ];
        }

        $ordenadas = [];

        foreach (Inscricao::SLOTS_DISCIPLINA as $slot) {
            if (isset($porSlot[$slot])) {
                $ordenadas[] = $porSlot[$slot];
            }
        }

        return $ordenadas;
    }

    private function solicitacaoPendenteDaSessao(?Inscricao $inscricao): ?SolicitacaoIsencao
    {
        $solicitacao = $inscricao?->solicitacaoIsencao;

        if ($solicitacao?->status !== SolicitacaoIsencaoStatus::Pendente) {
            return null;
        }

        return $solicitacao;
    }

    private function inscricaoDaSessao(): ?Inscricao
    {
        $raw = session('inscricao_id');

        $id = match (true) {
            is_int($raw) => $raw,
            is_string($raw) && ctype_digit($raw) => (int) $raw,
            default => null,
        };

        if ($id === null) {
            return null;
        }

        return Inscricao::query()
            ->with([
                'periodo',
                'disciplinaObrigatoria',
                'disciplinaOpcional1',
                'disciplinaOpcional2',
                'solicitacaoIsencao.documentos',
            ])
            ->whereKey($id)
            ->first();
    }

    private function garantirFluxoHabilitado(): void
    {
        abort_unless((bool) config('isencao.habilitado', false), 404);
    }
}
