<?php

namespace App\Http\Controllers;

use App\Enums\AprovacaoProfessorDisciplina;
use App\Enums\AprovacaoSecretariaDisciplina;
use App\Http\Controllers\Concerns\DownloadsInscricaoPdf;
use App\Http\Requests\AprovarDisciplinaProfessorRequest;
use App\Models\DisciplinaOfertada;
use App\Models\Inscricao;
use App\Models\Periodo;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InscricaoProfessorController extends Controller
{
    use DownloadsInscricaoPdf;

    public function index(Request $request): View
    {
        $usuario = $this->usuario();
        $search = trim($request->string('q')->toString());
        $disciplinaId = $request->integer('disciplina_id');

        $periodoAtivo = Periodo::ativoParaInscricoes();

        $disciplinas = DisciplinaOfertada::query()
            ->with('periodo')
            ->when(! $usuario->isAdmin(), fn (Builder $q) => $q->responsavelPor($usuario))
            ->orderByDesc('periodo_id')
            ->orderBy('departamento')
            ->orderBy('codigo')
            ->get();

        // Ignora filtro por disciplina que o usuário não pode avaliar.
        if ($disciplinaId > 0 && ! $disciplinas->contains('id', $disciplinaId)) {
            $disciplinaId = 0;
        }

        $query = Inscricao::query()
            ->with(['periodo', 'disciplinaObrigatoria', 'disciplinaOpcional1', 'disciplinaOpcional2'])
            ->where('etapa_concluida', '>=', 3)
            ->avaliavelPeloProfessor($usuario)
            ->orderByDesc('concluido_em')
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where('nome_completo', 'like', '%'.$search.'%');
        }

        if ($periodoAtivo) {
            $query->where('periodo_id', $periodoAtivo->id);
        }

        if ($disciplinaId > 0) {
            $query->where(function (Builder $q) use ($disciplinaId): void {
                $q->where('disciplina_obrigatoria_id', $disciplinaId)
                    ->orWhere('disciplina_opcional_1_id', $disciplinaId)
                    ->orWhere('disciplina_opcional_2_id', $disciplinaId);
            });
        }

        $inscricoes = $query->paginate(20)->withQueryString();
        Inscricao::atribuirFlagDuplicataPorEmail($inscricoes);

        return view('inscricao.professor.index', compact(
            'inscricoes',
            'disciplinas',
            'search',
            'disciplinaId',
        ));
    }

    public function show(Inscricao $inscricao): View
    {
        if ($inscricao->disciplinasParaAprovacaoProfessorDe($this->usuario()) === []) {
            abort(404);
        }

        $inscricao->load([
            'periodo',
            'disciplinaObrigatoria',
            'disciplinaOpcional1',
            'disciplinaOpcional2',
        ]);
        Inscricao::atribuirFlagDuplicataPorEmail([$inscricao]);

        return view('inscricao.professor.show', compact('inscricao'));
    }

    public function download(Inscricao $inscricao, string $campo): StreamedResponse
    {
        if ($inscricao->disciplinasParaAprovacaoProfessorDe($this->usuario()) === []) {
            abort(404);
        }

        if (in_array($campo, $this->pdfsIdentidadeOcultosAoProfessor, true)) {
            abort(403);
        }

        return $this->streamInscricaoPdf($inscricao, $campo);
    }

    public function aprovarDisciplinaProfessor(
        AprovarDisciplinaProfessorRequest $request,
        Inscricao $inscricao,
    ): RedirectResponse {
        return $this->processarAprovacaoProfessor($request, $inscricao, true);
    }

    public function reprovarDisciplinaProfessor(
        AprovarDisciplinaProfessorRequest $request,
        Inscricao $inscricao,
    ): RedirectResponse {
        return $this->processarAprovacaoProfessor($request, $inscricao, false);
    }

    private function processarAprovacaoProfessor(
        AprovarDisciplinaProfessorRequest $request,
        Inscricao $inscricao,
        bool $aprovar,
    ): RedirectResponse {
        $slot = $request->string('disciplina')->toString();

        Gate::authorize('avaliarDisciplinaComoProfessor', [$inscricao, $slot]);

        if ($inscricao->etapa_concluida < 3) {
            return redirect()
                ->route('professor.inscricoes.show', $inscricao)
                ->withErrors(['aprovacao' => 'A inscrição ainda não foi concluída pelo candidato.']);
        }

        if ($inscricao->aprovacaoSecretariaParaSlot($slot) !== AprovacaoSecretariaDisciplina::Aprovado) {
            return redirect()
                ->route('professor.inscricoes.show', $inscricao)
                ->withErrors(['aprovacao' => 'Esta disciplina ainda não está disponível para avaliação nesta etapa.']);
        }

        $aprovacaoAtual = $inscricao->aprovacaoProfessorParaSlot($slot);

        if ($aprovar && $aprovacaoAtual === AprovacaoProfessorDisciplina::Aprovado) {
            return redirect()
                ->route('professor.inscricoes.show', $inscricao)
                ->with('info', 'Esta disciplina já está aprovada pelo professor.');
        }

        if (! $aprovar && $aprovacaoAtual === AprovacaoProfessorDisciplina::Reprovado) {
            return redirect()
                ->route('professor.inscricoes.show', $inscricao)
                ->with('info', 'Esta disciplina já está reprovada pelo professor.');
        }

        if ($aprovar) {
            $inscricao->marcarDisciplinaAprovadaPeloProfessor($slot);
            $mensagem = 'aprovada';
        } else {
            $inscricao->marcarDisciplinaReprovadaPeloProfessor($slot);
            $mensagem = 'reprovada';
        }

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
            ->route('professor.inscricoes.show', $inscricao)
            ->with('success', "Disciplina {$codigo} {$mensagem} pelo professor.");
    }

    private function usuario(): User
    {
        $usuario = Auth::user();

        abort_unless($usuario instanceof User, 403);

        return $usuario;
    }
}
