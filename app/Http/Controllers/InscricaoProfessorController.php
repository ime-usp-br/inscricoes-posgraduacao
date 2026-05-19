<?php

namespace App\Http\Controllers;

use App\Enums\AprovacaoProfessorDisciplina;
use App\Enums\AprovacaoSecretariaDisciplina;
use App\Http\Requests\AprovarDisciplinaProfessorRequest;
use App\Models\DisciplinaOfertada;
use App\Models\Inscricao;
use App\Models\Periodo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InscricaoProfessorController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $periodoId = $request->integer('periodo_id');
        $disciplinaId = $request->integer('disciplina_id');

        $query = Inscricao::query()
            ->with(['periodo', 'disciplinaObrigatoria', 'disciplinaOpcional1', 'disciplinaOpcional2'])
            ->where('etapa_concluida', '>=', 3)
            ->comDisciplinaAprovadaPelaSecretaria()
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

        $inscricoes = $query->paginate(20)->withQueryString();

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

        return view('inscricao.professor.index', compact(
            'inscricoes',
            'periodos',
            'disciplinas',
            'search',
            'periodoId',
            'disciplinaId',
        ));
    }

    public function show(Inscricao $inscricao): View
    {
        if (! $inscricao->disciplinasParaAprovacaoProfessor()) {
            abort(404);
        }

        $inscricao->load([
            'periodo',
            'disciplinaObrigatoria',
            'disciplinaOpcional1',
            'disciplinaOpcional2',
        ]);

        return view('inscricao.professor.show', compact('inscricao'));
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
        if ($inscricao->etapa_concluida < 3) {
            return redirect()
                ->route('professor.inscricoes.show', $inscricao)
                ->withErrors(['aprovacao' => 'A inscrição ainda não foi concluída pelo candidato.']);
        }

        $slot = $request->string('disciplina')->toString();

        if ($inscricao->aprovacaoSecretariaParaSlot($slot) !== AprovacaoSecretariaDisciplina::Aprovado) {
            return redirect()
                ->route('professor.inscricoes.show', $inscricao)
                ->withErrors(['aprovacao' => 'Esta disciplina ainda não foi aprovada pela secretaria.']);
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
}
