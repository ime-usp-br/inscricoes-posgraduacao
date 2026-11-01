<?php

namespace App\Http\Controllers;

use App\Http\Requests\DecidirSolicitacaoIsencaoRequest;
use App\Models\Inscricao;
use App\Services\SolicitacaoIsencaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class SolicitacaoIsencaoAdminController extends Controller
{
    public function decidir(
        DecidirSolicitacaoIsencaoRequest $request,
        Inscricao $inscricao,
        SolicitacaoIsencaoService $service,
    ): RedirectResponse {
        $solicitacao = $inscricao->solicitacaoIsencao;

        if ($solicitacao === null) {
            return redirect()
                ->route('inscricoes.show', $inscricao)
                ->withErrors(['isencao' => 'Não há solicitação de isenção para esta inscrição.']);
        }

        $aprovada = $request->string('decisao')->toString() === 'aprovar';

        try {
            $service->decidir(
                $solicitacao,
                Auth::user(),
                $aprovada,
                $request->input('resposta_secretaria'),
                $request->input('motivo_reprovacao'),
            );
        } catch (\Throwable $exception) {
            return redirect()
                ->route('inscricoes.show', $inscricao)
                ->withErrors(['isencao' => $exception->getMessage()]);
        }

        $mensagem = $aprovada
            ? 'Solicitação de isenção aprovada.'
            : 'Solicitação de isenção reprovada.';

        return redirect()
            ->route('inscricoes.show', $inscricao)
            ->with('success', $mensagem);
    }

    public function estender(
        \Illuminate\Http\Request $request,
        Inscricao $inscricao,
        SolicitacaoIsencaoService $service,
    ): RedirectResponse {
        $this->validate($request, [
            'prazo_pagamento' => ['required', 'date'],
        ]);

        $solicitacao = $inscricao->solicitacaoIsencao;

        if ($solicitacao === null) {
            return redirect()
                ->route('inscricoes.show', $inscricao)
                ->withErrors(['isencao' => 'Não há solicitação de isenção para esta inscrição.']);
        }

        try {
            $service->estenderPrazo($solicitacao, \Illuminate\Support\Carbon::parse($request->input('prazo_pagamento')));
        } catch (\Throwable $exception) {
            return redirect()
                ->route('inscricoes.show', $inscricao)
                ->withErrors(['isencao' => $exception->getMessage()]);
        }

        return redirect()
            ->route('inscricoes.show', $inscricao)
            ->with('success', 'Prazo de pagamento da isenção estendido.');
    }
}
