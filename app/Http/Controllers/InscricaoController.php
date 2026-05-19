<?php

namespace App\Http\Controllers;

use App\Enums\InscricaoStatus;
use App\Http\Requests\InscricaoEtapa1Request;
use App\Http\Requests\InscricaoEtapa2Request;
use App\Http\Requests\InscricaoEtapa3Request;
use App\Models\DisciplinaOfertada;
use App\Models\Inscricao;
use App\Models\Periodo;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class InscricaoController extends Controller
{
    public function home(): View
    {
        $periodoAtivo = Periodo::ativoParaInscricoes();

        if ($periodoAtivo === null) {
            return view('inscricao.fora-periodo');
        }

        $inscricao = $this->inscricaoAtualNaSessao($periodoAtivo->id);

        if ($inscricao !== null && $inscricao->etapa_concluida >= 3) {
            return view('inscricao.concluida', [
                'periodo' => $periodoAtivo,
                'inscricao' => $inscricao,
            ]);
        }

        $disciplinas = DisciplinaOfertada::query()
            ->where('periodo_id', $periodoAtivo->id)
            ->orderBy('departamento')
            ->orderBy('codigo')
            ->get();

        $passo = 1;
        if ($inscricao !== null) {
            if ($inscricao->etapa_concluida === 1) {
                $passo = 2;
            } elseif ($inscricao->etapa_concluida === 2) {
                $passo = 3;
            }
        }

        return view('inscricao.home', [
            'periodo' => $periodoAtivo,
            'inscricao' => $inscricao,
            'disciplinas' => $disciplinas,
            'passo' => $passo,
        ]);
    }

    public function storeEtapa1(InscricaoEtapa1Request $request): RedirectResponse
    {
        $periodoAtivo = Periodo::ativoParaInscricoes();
        if ($periodoAtivo === null) {
            return redirect()->route('home')->withErrors(['periodo' => 'Não há período de inscrições ativo.']);
        }

        $validated = $request->validated();
        $alunoUsp = $validated['aluno_usp'] === 'sim';

        $existente = $this->inscricaoAtualNaSessao($periodoAtivo->id);

        if ($existente !== null && $existente->etapa_concluida >= 2) {
            return redirect()->route('home')->withErrors(['fluxo' => 'Para alterar a primeira etapa, reinicie a inscrição.']);
        }

        if ($existente !== null && $existente->etapa_concluida === 1) {
            Storage::disk('local')->deleteDirectory('inscricoes/'.$existente->id);
            $existente->update([
                'nome_completo' => $validated['nome_completo'],
                'email' => $validated['email'],
                'aluno_usp' => $alunoUsp,
                'numero_usp' => $alunoUsp ? ($validated['numero_usp'] ?? null) : null,
                'dados_etapa_2' => null,
                'disciplina_obrigatoria_id' => null,
                'disciplina_opcional_1_id' => null,
                'disciplina_opcional_2_id' => null,
                'concluido_em' => null,
            ]);
        } else {
            $inscricao = Inscricao::query()->create([
                'periodo_id' => $periodoAtivo->id,
                'nome_completo' => $validated['nome_completo'],
                'email' => $validated['email'],
                'aluno_usp' => $alunoUsp,
                'numero_usp' => $alunoUsp ? ($validated['numero_usp'] ?? null) : null,
                'dados_etapa_2' => null,
                'etapa_concluida' => 1,
            ]);
            session(['inscricao_id' => $inscricao->id]);
        }

        return redirect()->route('home')->with('success', 'Dados da etapa 1 salvos. Continue para a etapa 2.');
    }

    public function storeEtapa2(InscricaoEtapa2Request $request): RedirectResponse
    {
        $periodoAtivo = Periodo::ativoParaInscricoes();
        if ($periodoAtivo === null) {
            return redirect()->route('home')->withErrors(['periodo' => 'Não há período de inscrições ativo.']);
        }

        $inscricao = $this->inscricaoAtualNaSessao($periodoAtivo->id);
        if ($inscricao === null || $inscricao->etapa_concluida !== 1) {
            return redirect()->route('home')->withErrors(['sessao' => 'Sessão inválida ou etapa incorreta. Reinicie a inscrição.']);
        }

        $validated = $request->validated();

        if ($inscricao->aluno_usp) {
            $dados = [
                'tipo' => 'usp',
                'unidade' => $validated['unidade'],
                'pdf_comprovante_matricula' => $this->storePdf($inscricao, $this->singleUploadedFile($request, 'pdf_comprovante_matricula'), 'comprovante_matricula'),
                'pdf_historico_escolar' => $this->storePdf($inscricao, $this->singleUploadedFile($request, 'pdf_historico_escolar'), 'historico_escolar'),
            ];
        } else {
            $estrangeiro = ($validated['estrangeiro'] ?? 'nao') === 'sim';
            $dados = [
                'tipo' => 'nao_usp',
                'pos_graduacao_externo' => $validated['pos_graduacao_externo'],
                'nome_programa_externo' => $validated['nome_programa_externo'] ?? null,
                'curso_usp_anterior' => $validated['curso_usp_anterior'],
                'data_nascimento' => $validated['data_nascimento'],
                'genero' => $validated['genero'],
                'nome_mae' => $validated['nome_mae'],
                'cpf' => $validated['cpf'],
                'rg_rne_rnm' => $validated['rg_rne_rnm'],
                'visto_estudante_mercosul' => $validated['visto_estudante_mercosul'] ?? null,
                'orgao_expedidor' => $validated['orgao_expedidor'],
                'estado_expedicao' => $validated['estado_expedicao'],
                'data_expedicao' => $validated['data_expedicao'],
                'pais_nascimento' => $validated['pais_nascimento'],
                'estado_nascimento' => $validated['estado_nascimento'],
                'municipio_provincia' => $validated['municipio_provincia'],
                'nacionalidade' => $validated['nacionalidade'],
                'endereco_completo' => $validated['endereco_completo'],
                'cep' => $validated['cep'],
                'telefone' => $validated['telefone'],
                'estrangeiro' => $estrangeiro,
                'pdf_diploma_graduacao' => $this->storePdf($inscricao, $this->singleUploadedFile($request, 'pdf_diploma_graduacao'), 'diploma_graduacao'),
                'pdf_historico_graduacao' => $this->storePdf($inscricao, $this->singleUploadedFile($request, 'pdf_historico_graduacao'), 'historico_graduacao'),
                'pdf_rg_rne_rnm' => $this->storePdf($inscricao, $this->singleUploadedFile($request, 'pdf_rg_rne_rnm'), 'rg_rne_rnm'),
                'pdf_cpf' => $this->storePdf($inscricao, $this->singleUploadedFile($request, 'pdf_cpf'), 'cpf'),
            ];
            if ($estrangeiro) {
                $dados['pdf_passaporte'] = $this->storePdf($inscricao, $this->singleUploadedFile($request, 'pdf_passaporte'), 'passaporte');
                $dados['pdf_visto_estudante_mercosul'] = $this->storePdf($inscricao, $this->singleUploadedFile($request, 'pdf_visto_estudante_mercosul'), 'visto_estudante_mercosul');
            }
        }

        $inscricao->update([
            'dados_etapa_2' => $dados,
            'etapa_concluida' => 2,
        ]);

        return redirect()->route('home')->with('success', 'Dados da etapa 2 salvos. Selecione as disciplinas na etapa 3.');
    }

    public function storeEtapa3(InscricaoEtapa3Request $request): RedirectResponse
    {
        $periodoAtivo = Periodo::ativoParaInscricoes();
        if ($periodoAtivo === null) {
            return redirect()->route('home')->withErrors(['periodo' => 'Não há período de inscrições ativo.']);
        }

        $inscricao = $this->inscricaoAtualNaSessao($periodoAtivo->id);
        if ($inscricao === null || $inscricao->etapa_concluida !== 2) {
            return redirect()->route('home')->withErrors(['sessao' => 'Sessão inválida ou etapa incorreta.']);
        }

        $validated = $request->validated();
        $op1 = $request->filled('disciplina_opcional_1_id') ? $request->integer('disciplina_opcional_1_id') : null;
        $op2 = $request->filled('disciplina_opcional_2_id') ? $request->integer('disciplina_opcional_2_id') : null;

        $inscricao->update([
            'disciplina_obrigatoria_id' => $request->integer('disciplina_obrigatoria_id'),
            'disciplina_opcional_1_id' => $op1,
            'disciplina_opcional_2_id' => $op2,
            'etapa_concluida' => 3,
            'concluido_em' => now(),
            'status' => InscricaoStatus::Inscrito,
        ]);

        return redirect()->route('home')->with('success', 'Inscrição enviada com sucesso.');
    }

    public function reiniciar(): RedirectResponse
    {
        $id = $this->inscricaoIdFromSession();
        if ($id !== null) {
            $inscricao = Inscricao::query()->whereKey($id)->first();
            if ($inscricao !== null) {
                $inscricao->delete();
            }
            session()->forget('inscricao_id');
        }

        return redirect()->route('home');
    }

    private function inscricaoIdFromSession(): ?int
    {
        $raw = session('inscricao_id');
        if (is_int($raw)) {
            return $raw;
        }
        if (is_string($raw) && ctype_digit($raw)) {
            return (int) $raw;
        }

        return null;
    }

    private function inscricaoAtualNaSessao(int $periodoAtivoId): ?Inscricao
    {
        $id = $this->inscricaoIdFromSession();
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

    private function singleUploadedFile(FormRequest $request, string $key): ?UploadedFile
    {
        $file = $request->file($key);

        return $file instanceof UploadedFile ? $file : null;
    }

    private function storePdf(Inscricao $inscricao, ?UploadedFile $file, string $prefix): string
    {
        if ($file === null || ! $file->isValid()) {
            return '';
        }

        $nome = $prefix.'_'.uniqid('', true).'.pdf';
        $path = $file->storeAs('inscricoes/'.$inscricao->id, $nome, 'local');

        return is_string($path) ? $path : '';
    }
}
