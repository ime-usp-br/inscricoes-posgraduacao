<?php

namespace App\Http\Controllers;

use App\Models\Inscricao;
use App\Services\InscricaoPagamentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WspixWebhookController extends Controller
{
    public function __invoke(Request $request, InscricaoPagamentoService $pagamentoService): JsonResponse
    {
        $tokenEsperado = config('wspix.webhook_token');
        $tokenRecebido = $request->header('X-Token');

        if (! is_string($tokenEsperado) || $tokenEsperado === '') {
            Log::warning('Webhook WSPIX recebido sem WSPIX_WEBHOOK_TOKEN configurado.');

            return response()->json(['message' => 'Webhook não configurado.'], 503);
        }

        if (! is_string($tokenRecebido) || ! hash_equals($tokenEsperado, $tokenRecebido)) {
            return response()->json(['message' => 'Token inválido.'], 401);
        }

        $dados = $request->all();
        $idfpix = isset($dados['idfpix']) ? (string) $dados['idfpix'] : '';

        if ($idfpix === '') {
            return response()->json(['message' => 'idfpix ausente.'], 422);
        }

        if (! $pagamentoService->pixEstaConcluido($dados)) {
            return response()->json(['message' => 'Pagamento ainda não concluído.'], 200);
        }

        $inscricao = Inscricao::query()->where('pix_idfpix', $idfpix)->first();

        if ($inscricao === null) {
            Log::warning('Webhook WSPIX: inscrição não encontrada.', ['idfpix' => $idfpix]);

            return response()->json(['message' => 'Inscrição não encontrada.'], 404);
        }

        $pagamentoService->confirmarPagamento($inscricao, $dados);

        return response()->json(['message' => 'Pagamento registrado.']);
    }
}
