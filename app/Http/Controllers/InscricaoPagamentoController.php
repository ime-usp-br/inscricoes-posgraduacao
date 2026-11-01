<?php

namespace App\Http\Controllers;

use App\Models\Inscricao;
use App\Models\Periodo;
use App\Services\InscricaoPagamentoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class InscricaoPagamentoController extends Controller
{
    public function show(InscricaoPagamentoService $pagamentoService): View|RedirectResponse
    {
        $periodoAtivo = Periodo::ativoParaInscricoes();
        if ($periodoAtivo === null) {
            return redirect()->route('home')->withErrors(['periodo' => 'Não há período de inscrições ativo.']);
        }

        $inscricao = $this->inscricaoAtualNaSessao($periodoAtivo->id);
        if ($inscricao === null || $inscricao->etapa_concluida < 3) {
            return redirect()->route('home')->withErrors(['sessao' => 'Sessão inválida ou inscrição incompleta.']);
        }

        if (! $inscricao->temPagamentoPendente() && ! $inscricao->temIsencaoPendente()) {
            return redirect()->route('home')->with('success', 'Pagamento já confirmado.');
        }

        $disciplinasPendentes = $inscricao->disciplinasComPagamentoPendente();
        $valorTotal = $pagamentoService->valorTotal($inscricao);
        $quantidade = $pagamentoService->quantidadeDisciplinasComPagamento($inscricao);
        $valorUnitario = $pagamentoService->valorPorDisciplina();

        $pixDados = null;
        $erroPix = null;

        if ($inscricao->possuiPixGerado()) {
            try {
                $pixDados = $pagamentoService->pixAtual($inscricao);
                $inscricao->refresh();

                if (! $inscricao->temPagamentoPendente()) {
                    return redirect()->route('home')->with('success', 'Pagamento confirmado com sucesso.');
                }
            } catch (Throwable $exception) {
                $erroPix = $exception->getMessage();
            }
        }

        return view('inscricao.pagamento', [
            'periodo' => $periodoAtivo,
            'inscricao' => $inscricao,
            'disciplinasPendentes' => $disciplinasPendentes,
            'valorUnitario' => $valorUnitario,
            'valorTotal' => $valorTotal,
            'quantidade' => $quantidade,
            'pixDados' => $pixDados,
            'erroPix' => $erroPix,
            'podeSolicitarIsencao' => $inscricao->podeSolicitarIsencao(),
            'temIsencaoPendente' => $inscricao->temIsencaoPendente(),
            'solicitacaoIsencao' => $inscricao->solicitacaoIsencao,
        ]);
    }

    /**
     * Acesso ao pagamento via link assinado (válido por 30 dias após o prazo).
     */
    public function showAssinada(Inscricao $inscricao, InscricaoPagamentoService $pagamentoService): View|RedirectResponse
    {
        $periodo = $inscricao->periodo;

        if ($periodo === null || $inscricao->etapa_concluida < 3) {
            abort(403);
        }

        if (! $inscricao->temPagamentoPendente()) {
            return redirect()->route('home')->with('success', 'Pagamento já confirmado.');
        }

        if ($inscricao->temIsencaoPendente()) {
            return redirect()->route('inscricao.isencao.show')->withErrors(['isencao' => 'Solicitação de isenção em análise.']);
        }

        $disciplinasPendentes = $inscricao->disciplinasComPagamentoPendente();
        $valorTotal = $pagamentoService->valorTotal($inscricao);
        $quantidade = $pagamentoService->quantidadeDisciplinasComPagamento($inscricao);
        $valorUnitario = $pagamentoService->valorPorDisciplina();

        $pixDados = null;
        $erroPix = null;

        if ($inscricao->possuiPixGerado()) {
            try {
                $pixDados = $pagamentoService->pixAtual($inscricao);
                $inscricao->refresh();

                if (! $inscricao->temPagamentoPendente()) {
                    return redirect()->route('home')->with('success', 'Pagamento confirmado com sucesso.');
                }
            } catch (Throwable $exception) {
                $erroPix = $exception->getMessage();
            }
        }

        return view('inscricao.pagamento', [
            'periodo' => $periodo,
            'inscricao' => $inscricao,
            'disciplinasPendentes' => $disciplinasPendentes,
            'valorUnitario' => $valorUnitario,
            'valorTotal' => $valorTotal,
            'quantidade' => $quantidade,
            'pixDados' => $pixDados,
            'erroPix' => $erroPix,
            'podeSolicitarIsencao' => $inscricao->podeSolicitarIsencao(),
            'temIsencaoPendente' => $inscricao->temIsencaoPendente(),
            'solicitacaoIsencao' => $inscricao->solicitacaoIsencao,
        ]);
    }

    public function gerar(InscricaoPagamentoService $pagamentoService): RedirectResponse
    {
        $periodoAtivo = Periodo::ativoParaInscricoes();
        if ($periodoAtivo === null) {
            return redirect()->route('home')->withErrors(['periodo' => 'Não há período de inscrições ativo.']);
        }

        $inscricao = $this->inscricaoAtualNaSessao($periodoAtivo->id);
        if ($inscricao === null || $inscricao->etapa_concluida < 3) {
            return redirect()->route('home')->withErrors(['sessao' => 'Sessão inválida ou inscrição incompleta.']);
        }

        try {
            $pagamentoService->gerarPix($inscricao);
        } catch (Throwable $exception) {
            return redirect()
                ->route('inscricao.pagamento')
                ->withErrors(['pix' => $exception->getMessage()]);
        }

        return redirect()
            ->route('inscricao.pagamento')
            ->with('success', 'Cobrança Pix gerada com sucesso.');
    }

    public function verificar(Request $request, InscricaoPagamentoService $pagamentoService): RedirectResponse
    {
        $periodoAtivo = Periodo::ativoParaInscricoes();
        if ($periodoAtivo === null) {
            return redirect()->route('home')->withErrors(['periodo' => 'Não há período de inscrições ativo.']);
        }

        $inscricao = $this->inscricaoAtualNaSessao($periodoAtivo->id);
        if ($inscricao === null || ! $inscricao->temPagamentoPendente()) {
            return redirect()->route('home');
        }

        try {
            $pagamentoService->verificarPagamento($inscricao);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('inscricao.pagamento')
                ->withErrors(['pix' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            return redirect()
                ->route('inscricao.pagamento')
                ->withErrors(['pix' => 'Não foi possível verificar o pagamento. Tente novamente.']);
        }

        $inscricao->refresh();

        if (! $inscricao->temPagamentoPendente()) {
            return redirect()->route('home')->with('success', 'Pagamento confirmado com sucesso.');
        }

        return redirect()
            ->route('inscricao.pagamento')
            ->with('info', 'Pagamento ainda não identificado. Se já pagou, aguarde alguns instantes e tente novamente.');
    }

    /** Redireciona rota legada com slot para a página única de pagamento. */
    public function redirectLegado(string $slot): RedirectResponse
    {
        return redirect()->route('inscricao.pagamento');
    }

    private function inscricaoAtualNaSessao(int $periodoAtivoId): ?Inscricao
    {
        $raw = session('inscricao_id');
        $id = is_int($raw) ? $raw : (is_string($raw) && ctype_digit($raw) ? (int) $raw : null);
        if ($id === null) {
            return null;
        }

        $inscricao = Inscricao::query()
            ->with(['periodo', 'disciplinaObrigatoria', 'disciplinaOpcional1', 'disciplinaOpcional2'])
            ->whereKey($id)
            ->first();

        if ($inscricao === null || (int) $inscricao->periodo_id !== $periodoAtivoId) {
            session()->forget('inscricao_id');

            return null;
        }

        return $inscricao;
    }
}
