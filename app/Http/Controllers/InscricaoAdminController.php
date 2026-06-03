<?php

namespace App\Http\Controllers;

use App\Enums\AprovacaoSecretariaDisciplina;
use App\Enums\ResumoAprovacaoInscricao;
use App\Http\Requests\AprovarDisciplinaSecretariaRequest;
use App\Http\Requests\AprovarInscricoesEmBlocoSecretariaRequest;
use App\Models\DisciplinaOfertada;
use App\Models\Inscricao;
use App\Models\Periodo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InscricaoAdminController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $periodoId = $request->integer('periodo_id');
        $disciplinaId = $request->integer('disciplina_id');
        $filtroAprovacao = $request->string('aprovacao')->toString();
        $filtroPagamento = match ($request->string('pagamento')->toString()) {
            'pendente' => 'nao_pago',
            'pago', 'nao_aplicavel' => 'demais',
            default => $request->string('pagamento')->toString(),
        };

        $query = Inscricao::query()
            ->with(['periodo', 'disciplinaObrigatoria', 'disciplinaOpcional1', 'disciplinaOpcional2'])
            ->where('etapa_concluida', '>=', 3)
            ->orderByDesc('concluido_em')
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where('nome_completo', 'like', '%'.$search.'%');
        }

        if ($periodoId > 0) {
            $query->where('periodo_id', $periodoId);
        }

        if ($disciplinaId > 0) {
            $query->where(function (Builder $q) use ($disciplinaId): void {
                $q->where('disciplina_obrigatoria_id', $disciplinaId)
                    ->orWhere('disciplina_opcional_1_id', $disciplinaId)
                    ->orWhere('disciplina_opcional_2_id', $disciplinaId);
            });
        }

        match ($filtroAprovacao) {
            'secretaria_pendente' => $query->filtrarResumoSecretaria(ResumoAprovacaoInscricao::Pendente),
            'secretaria_aprovada' => $query->filtrarResumoSecretaria(ResumoAprovacaoInscricao::Aprovada),
            'secretaria_reprovada' => $query->filtrarResumoSecretaria(ResumoAprovacaoInscricao::Reprovada),
            'professor_pendente' => $query->filtrarResumoProfessor(ResumoAprovacaoInscricao::Pendente),
            'professor_aprovada' => $query->filtrarResumoProfessor(ResumoAprovacaoInscricao::Aprovada),
            'professor_reprovada' => $query->filtrarResumoProfessor(ResumoAprovacaoInscricao::Reprovada),
            default => null,
        };

        match ($filtroPagamento) {
            'nao_pago' => $query->comPagamentoPendente(),
            'demais' => $query->comPagamentoPagoOuNaoAplicavel(),
            default => null,
        };

        $inscricoes = $query->paginate(20)->withQueryString();
        Inscricao::atribuirFlagDuplicataPorEmail($inscricoes);

        $periodos = Periodo::query()
            ->orderByDesc('ano')
            ->orderByDesc('semestre')
            ->get();

        $disciplinas = DisciplinaOfertada::query()
            ->with('periodo')
            ->orderByDesc('periodo_id')
            ->orderBy('departamento')
            ->orderBy('codigo')
            ->get();

        return view('inscricao.admin.index', compact(
            'inscricoes',
            'periodos',
            'disciplinas',
            'search',
            'periodoId',
            'disciplinaId',
            'filtroAprovacao',
            'filtroPagamento',
        ));
    }

    public function show(Inscricao $inscricao): View
    {
        $inscricao->load([
            'periodo',
            'disciplinaObrigatoria',
            'disciplinaOpcional1',
            'disciplinaOpcional2',
        ]);
        Inscricao::atribuirFlagDuplicataPorEmail([$inscricao]);

        return view('inscricao.admin.show', compact('inscricao'));
    }

    public function download(Inscricao $inscricao, string $campo): StreamedResponse
    {
        if (! str_starts_with($campo, 'pdf_')) {
            abort(404);
        }

        /** @var array<string, mixed>|null $dados */
        $dados = $inscricao->dados_etapa_2;
        if (! is_array($dados) || ! isset($dados[$campo]) || ! is_string($dados[$campo])) {
            abort(404);
        }

        $path = $dados[$campo];
        $prefix = 'inscricoes/'.$inscricao->id.'/';
        if (! str_starts_with($path, $prefix)) {
            abort(403);
        }

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->download($path);
    }

    public function aprovarEmBlocoSecretaria(AprovarInscricoesEmBlocoSecretariaRequest $request): RedirectResponse
    {
        $ids = $request->validated('inscricoes');

        $inscricoes = Inscricao::query()
            ->whereIn('id', $ids)
            ->where('etapa_concluida', '>=', 3)
            ->get();

        $inscricoesAtualizadas = 0;
        $disciplinasAprovadas = 0;
        $semAlteracao = 0;

        foreach ($inscricoes as $inscricao) {
            $aprovadas = $inscricao->aprovarTodasDisciplinasPelaSecretaria();

            if ($aprovadas > 0) {
                $inscricoesAtualizadas++;
                $disciplinasAprovadas += $aprovadas;
            } else {
                $semAlteracao++;
            }
        }

        $redirect = redirect()->route('inscricoes.index', $request->only([
            'q',
            'periodo_id',
            'disciplina_id',
            'aprovacao',
            'pagamento',
            'page',
        ]));

        if ($inscricoesAtualizadas === 0) {
            return $redirect->withErrors([
                'aprovacao' => 'Nenhuma disciplina pendente foi aprovada nas inscrições selecionadas.',
            ]);
        }

        $mensagem = $inscricoesAtualizadas === 1
            ? '1 inscrição aprovada pela secretaria'
            : "{$inscricoesAtualizadas} inscrições aprovadas pela secretaria";

        $mensagem .= " ({$disciplinasAprovadas} disciplina(s) no total).";

        if ($semAlteracao > 0) {
            $mensagem .= " {$semAlteracao} inscrição(ões) selecionada(s) já estavam totalmente aprovadas.";
        }

        $idsNaoEncontrados = count($ids) - $inscricoes->count();
        if ($idsNaoEncontrados > 0) {
            $mensagem .= " {$idsNaoEncontrados} seleção(ões) ignorada(s) (inscrição inexistente ou não concluída).";
        }

        return $redirect->with('success', $mensagem);
    }

    public function aprovarDisciplinaSecretaria(
        AprovarDisciplinaSecretariaRequest $request,
        Inscricao $inscricao,
    ): RedirectResponse {
        if ($inscricao->etapa_concluida < 3) {
            return redirect()
                ->route('inscricoes.show', $inscricao)
                ->withErrors(['aprovacao' => 'A inscrição ainda não foi concluída pelo candidato.']);
        }

        $slot = $request->string('disciplina')->toString();

        $disciplinaId = match ($slot) {
            'obrigatoria' => $inscricao->disciplina_obrigatoria_id,
            'opcional_1' => $inscricao->disciplina_opcional_1_id,
            'opcional_2' => $inscricao->disciplina_opcional_2_id,
            default => null,
        };

        if ($disciplinaId === null) {
            return redirect()
                ->route('inscricoes.show', $inscricao)
                ->withErrors(['aprovacao' => 'Esta disciplina não está vinculada à inscrição.']);
        }

        if ($inscricao->aprovacaoSecretariaParaSlot($slot) === AprovacaoSecretariaDisciplina::Aprovado) {
            return redirect()
                ->route('inscricoes.show', $inscricao)
                ->with('info', 'Esta disciplina já está aprovada pela secretaria.');
        }

        $inscricao->marcarDisciplinaAprovadaPelaSecretaria($slot);

        $inscricao->load([
            'disciplinaObrigatoria',
            'disciplinaOpcional1',
            'disciplinaOpcional2',
        ]);

        $codigo = match ($slot) {
            'obrigatoria' => $inscricao->disciplinaObrigatoria?->codigo_completo,
            'opcional_1' => $inscricao->disciplinaOpcional1?->codigo_completo,
            'opcional_2' => $inscricao->disciplinaOpcional2?->codigo_completo,
            default => null,
        };

        return redirect()
            ->route('inscricoes.show', $inscricao)
            ->with('success', "Disciplina {$codigo} aprovada pela secretaria.");
    }

    public function reprovarDisciplinaSecretaria(
        AprovarDisciplinaSecretariaRequest $request,
        Inscricao $inscricao,
    ): RedirectResponse {
        if ($inscricao->etapa_concluida < 3) {
            return redirect()
                ->route('inscricoes.show', $inscricao)
                ->withErrors(['aprovacao' => 'A inscrição ainda não foi concluída pelo candidato.']);
        }

        $slot = $request->string('disciplina')->toString();

        $disciplinaId = match ($slot) {
            'obrigatoria' => $inscricao->disciplina_obrigatoria_id,
            'opcional_1' => $inscricao->disciplina_opcional_1_id,
            'opcional_2' => $inscricao->disciplina_opcional_2_id,
            default => null,
        };

        if ($disciplinaId === null) {
            return redirect()
                ->route('inscricoes.show', $inscricao)
                ->withErrors(['aprovacao' => 'Esta disciplina não está vinculada à inscrição.']);
        }

        if ($inscricao->aprovacaoSecretariaParaSlot($slot) === AprovacaoSecretariaDisciplina::Reprovado) {
            return redirect()
                ->route('inscricoes.show', $inscricao)
                ->with('info', 'Esta disciplina já está reprovada pela secretaria.');
        }

        $inscricao->marcarDisciplinaReprovadaPelaSecretaria($slot);

        $inscricao->load([
            'disciplinaObrigatoria',
            'disciplinaOpcional1',
            'disciplinaOpcional2',
        ]);

        $codigo = match ($slot) {
            'obrigatoria' => $inscricao->disciplinaObrigatoria?->codigo_completo,
            'opcional_1' => $inscricao->disciplinaOpcional1?->codigo_completo,
            'opcional_2' => $inscricao->disciplinaOpcional2?->codigo_completo,
            default => null,
        };

        return redirect()
            ->route('inscricoes.show', $inscricao)
            ->with('success', "Disciplina {$codigo} reprovada pela secretaria.");
    }
}
