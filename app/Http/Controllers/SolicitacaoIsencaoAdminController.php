<?php

namespace App\Http\Controllers;

use App\Http\Requests\DecidirSolicitacaoIsencaoRequest;
use App\Models\Inscricao;
use App\Services\SolicitacaoIsencaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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

        $usuario = Auth::user();

        if ($usuario === null) {
            abort(403);
        }

        $decisao = $request->string('decisao')->toString();
        $aprovada = $decisao === 'aprovar';

        try {
            $service->decidir(
                $solicitacao,
                $usuario,
                $aprovada,
                is_string($request->input('resposta_secretaria')) ? $request->input('resposta_secretaria') : null,
                is_string($request->input('motivo_reprovacao')) ? $request->input('motivo_reprovacao') : null,
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
        Request $request,
        Inscricao $inscricao,
        SolicitacaoIsencaoService $service,
    ): RedirectResponse {
        $validator = Validator::make($request->all(), [
            'prazo_pagamento' => ['required', 'date'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('inscricoes.show', $inscricao)
                ->withErrors($validator);
        }

        $solicitacao = $inscricao->solicitacaoIsencao;

        if ($solicitacao === null) {
            return redirect()
                ->route('inscricoes.show', $inscricao)
                ->withErrors(['isencao' => 'Não há solicitação de isenção para esta inscrição.']);
        }

        $prazo = $request->input('prazo_pagamento');

        try {
            $service->estenderPrazo($solicitacao, Carbon::parse(is_string($prazo) ? $prazo : now()->toDateTimeString()));
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
