<?php

namespace App\Http\Controllers;

use App\Enums\AprovacaoProfessorDisciplina;
use App\Enums\AprovacaoSecretariaDisciplina;
use App\Enums\ResumoAprovacaoInscricao;
use App\Http\Controllers\Concerns\DownloadsInscricaoPdf;
use App\Http\Requests\AprovarDisciplinaSecretariaRequest;
use App\Http\Requests\AprovarInscricoesEmBlocoSecretariaRequest;
use App\Http\Requests\ReprovarInscricaoSecretariaRequest;
use App\Models\DisciplinaOfertada;
use App\Models\Inscricao;
use App\Models\Periodo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InscricaoAdminController extends Controller
{
    use DownloadsInscricaoPdf;

    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $disciplinaId = $request->integer('disciplina_id');
        $filtroAprovacao = $request->string('aprovacao')->toString();
        $filtroPagamento = match ($request->string('pagamento')->toString()) {
            'pendente' => 'nao_pago',
            'pago', 'nao_aplicavel' => 'demais',
            default => $request->string('pagamento')->toString(),
        };

        $periodoAtivo = Periodo::ativoParaInscricoes();
        $periodoId = $request->integer('periodo_id');
        if (! $request->has('periodo_id') || $periodoId <= 0) {
            $periodoId = $periodoAtivo->id ?? 0;
        }

        $query = Inscricao::query()
            ->with(['periodo', 'disciplinaObrigatoria', 'disciplinaOpcional1', 'disciplinaOpcional2', 'solicitacaoIsencao'])
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
            'solicitacaoIsencao.documentos',
            'solicitacaoIsencao.decididaPor',
        ]);
        Inscricao::atribuirFlagDuplicataPorEmail([$inscricao]);

        return view('inscricao.admin.show', compact('inscricao'));
    }

    public function relatorio(): StreamedResponse
    {
        $inscricoes = Inscricao::query()
            ->with([
                'periodo',
                'disciplinaObrigatoria',
                'disciplinaOpcional1',
                'disciplinaOpcional2',
                'solicitacaoIsencao',
            ])
            ->where('etapa_concluida', '>=', 3)
            ->orderBy('nome_completo')
            ->orderBy('id')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Aprovados');

        $headers = [
            // Mais relevantes para colocar no sistema USP (primeiro)
            'ID inscrição',
            'Período',
            'Nome completo',
            'E-mail',
            'Aluno USP',
            'Nº USP',
            // Documentos / dados pessoais chave
            'CPF',
            'RG / RNE / RNM',
            'Passaporte',
            'Telefone / Celular',
            // Disciplinas aprovadas em ambas as etapas (explícito e claro)
            'Disciplina obrigatória aprovada (secretaria+professor)',
            'Opcional 1 aprovada (secretaria+professor)',
            'Opcional 2 aprovada (secretaria+professor)',
            'Disciplinas aprovadas (resumido)',
            // Situações / resumo
            'Pagamento (resumo)',
            'Tem solicitação de isenção?',
            'Status solicitação de isenção',
            // Demais informações
            'Data de nascimento',
            'Nacionalidade',
            'País de nascimento',
            'Estado de nascimento',
            'Município / Província',
            'Concluída em',
            'Gênero',
            'Nome da mãe',
            'Endereço completo',
            'CEP',
            'Unidade USP',
            'Tipo de cadastro (etapa 2)',
            'Pós-graduação externa à USP',
            'Nome do programa externo',
            'Formado na graduação USP',
            'Curso na USP anteriormente',
            'Estrangeiro',
            'Órgão expedidor',
            'Estado de expedição',
            'Data de expedição',
            'Visto Estudante ou Mercosul (texto)',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($inscricoes as $inscricao) {
            $aprovadasPorAmbos = [];
            foreach (Inscricao::SLOTS_DISCIPLINA as $slot) {
                if (
                    $inscricao->aprovacaoSecretariaParaSlot($slot) === AprovacaoSecretariaDisciplina::Aprovado
                    && $inscricao->aprovacaoProfessorParaSlot($slot) === AprovacaoProfessorDisciplina::Aprovado
                ) {
                    $disciplina = match ($slot) {
                        'obrigatoria' => $inscricao->disciplinaObrigatoria,
                        'opcional_1' => $inscricao->disciplinaOpcional1,
                        'opcional_2' => $inscricao->disciplinaOpcional2,
                        default => null,
                    };

                    if ($disciplina instanceof DisciplinaOfertada) {
                        $aprovadasPorAmbos[] = [
                            'slot' => $slot,
                            'disciplina' => $disciplina,
                        ];
                    }
                }
            }

            if ($aprovadasPorAmbos === []) {
                continue;
            }

            $dados = is_array($inscricao->dados_etapa_2) ? $inscricao->dados_etapa_2 : [];

            $getDados = function (string $chave) use ($dados): string {
                if (! array_key_exists($chave, $dados)) {
                    return '';
                }

                $valor = $dados[$chave];

                if (is_bool($valor)) {
                    return $valor ? 'Sim' : 'Não';
                }

                if ($valor === null) {
                    return '';
                }

                if (is_array($valor)) {
                    return json_encode($valor, JSON_UNESCAPED_UNICODE);
                }

                if (is_scalar($valor)) {
                    $str = trim((string) $valor);
                    if ($str === 'sim') {
                        return 'Sim';
                    }
                    if ($str === 'nao') {
                        return 'Não';
                    }

                    return $str;
                }

                return '';
            };

            $periodoLabel = '';
            if ($inscricao->relationLoaded('periodo') && $inscricao->periodo) {
                $periodoLabel = $inscricao->periodo->ano.'/'.$inscricao->periodo->semestre;
            } elseif ($inscricao->periodo_id && $inscricao->periodo) {
                $periodoLabel = $inscricao->periodo->ano.'/'.$inscricao->periodo->semestre;
            }

            $temIsencao = $inscricao->relationLoaded('solicitacaoIsencao') ? $inscricao->solicitacaoIsencao !== null : $inscricao->solicitacaoIsencao()->exists();
            $statusIsencao = '';
            if ($temIsencao) {
                $solicitacao = $inscricao->relationLoaded('solicitacaoIsencao') ? $inscricao->solicitacaoIsencao : $inscricao->solicitacaoIsencao;
                if ($solicitacao && method_exists($solicitacao->status, 'label')) {
                    $statusIsencao = $solicitacao->status->label();
                } elseif ($solicitacao && isset($solicitacao->status)) {
                    $statusIsencao = is_object($solicitacao->status) ? (string) $solicitacao->status->value : (string) $solicitacao->status;
                }
            }

            $mapAprovadasPorSlot = [
                'obrigatoria' => null,
                'opcional_1' => null,
                'opcional_2' => null,
            ];
            foreach ($aprovadasPorAmbos as $item) {
                if (isset($mapAprovadasPorSlot[$item['slot']])) {
                    $mapAprovadasPorSlot[$item['slot']] = $item['disciplina'];
                }
            }

            $formatDisciplinaAprovada = function ($disciplina, string $slot): string {
                if (! $disciplina instanceof DisciplinaOfertada) {
                    return '';
                }

                $slotLabel = match ($slot) {
                    'obrigatoria' => 'obrigatória',
                    'opcional_1' => 'opcional 1',
                    'opcional_2' => 'opcional 2',
                    default => $slot,
                };

                return $disciplina->codigo_completo.' '.$disciplina->nome.' ('.$slotLabel.')';
            };

            $resumido = [];
            foreach ($aprovadasPorAmbos as $item) {
                /** @var DisciplinaOfertada $disciplina */
                $disciplina = $item['disciplina'];
                $slotLabel = match ($item['slot']) {
                    'obrigatoria' => 'obrigatória',
                    'opcional_1' => 'opcional 1',
                    'opcional_2' => 'opcional 2',
                    default => $item['slot'],
                };
                $resumido[] = $disciplina->codigo_completo.' '.$disciplina->nome.' ('.$slotLabel.')';
            }

            $resumoPagamento = $inscricao->resumoPagamento();
            $temIsencao = $inscricao->relationLoaded('solicitacaoIsencao') ? $inscricao->solicitacaoIsencao !== null : $inscricao->solicitacaoIsencao()->exists();
            $statusIsencao = '';
            if ($temIsencao) {
                $solicitacao = $inscricao->relationLoaded('solicitacaoIsencao') ? $inscricao->solicitacaoIsencao : $inscricao->solicitacaoIsencao;
                if ($solicitacao && isset($solicitacao->status)) {
                    if (is_object($solicitacao->status) && method_exists($solicitacao->status, 'label')) {
                        $statusIsencao = $solicitacao->status->label();
                    } elseif (is_object($solicitacao->status)) {
                        $statusIsencao = (string) ($solicitacao->status->value ?? $solicitacao->status);
                    } else {
                        $statusIsencao = (string) $solicitacao->status;
                    }
                }
            }

            $linha = [
                $inscricao->id,
                $periodoLabel,
                $inscricao->nome_completo ?? '',
                $inscricao->email ?? '',
                $inscricao->aluno_usp ? 'Sim' : 'Não',
                $inscricao->numero_usp ?? '',
                $getDados('cpf'),
                $getDados('rg_rne_rnm'),
                $getDados('passaporte'),
                $getDados('telefone'),
                $formatDisciplinaAprovada($mapAprovadasPorSlot['obrigatoria'], 'obrigatoria'),
                $formatDisciplinaAprovada($mapAprovadasPorSlot['opcional_1'], 'opcional_1'),
                $formatDisciplinaAprovada($mapAprovadasPorSlot['opcional_2'], 'opcional_2'),
                implode('; ', $resumido),
                method_exists($resumoPagamento, 'label') ? $resumoPagamento->label() : (is_object($resumoPagamento) ? ($resumoPagamento->value ?? (string) $resumoPagamento) : (string) $resumoPagamento),
                $temIsencao ? 'Sim' : 'Não',
                $statusIsencao,
                $getDados('data_nascimento'),
                $getDados('nacionalidade'),
                $getDados('pais_nascimento'),
                $getDados('estado_nascimento'),
                $getDados('municipio_provincia'),
                optional($inscricao->concluido_em)->format('d/m/Y H:i') ?? '',
                $getDados('genero'),
                $getDados('nome_mae'),
                $getDados('endereco_completo'),
                $getDados('cep'),
                $getDados('unidade'),
                $getDados('tipo'),
                $getDados('pos_graduacao_externo'),
                $getDados('nome_programa_externo'),
                $getDados('formado_graduacao_usp'),
                $getDados('curso_usp_anterior'),
                $getDados('estrangeiro'),
                $getDados('orgao_expedidor'),
                $getDados('estado_expedicao'),
                $getDados('data_expedicao'),
                $getDados('visto_estudante_mercosul');

            $sheet->fromArray($linha, null, 'A'.$row);
            $row++;
        }

        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
            $sheet->getColumnDimension($column)->setWidth($sheet->getColumnDimension($column)->getWidth() + 2);
        }
        $sheet->getStyle('A1:'.$lastColumn.'1')->getFont()->setBold(true);
        $sheet->getStyle('A1:'.$lastColumn.'1')->getAlignment()->setWrapText(true);
        $sheet->getDefaultRowDimension()->setRowHeight(-1);

        $filename = 'relatorio-aprovados-'.now()->format('Y-m-d-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function download(Inscricao $inscricao, string $campo): StreamedResponse
    {
        return $this->streamInscricaoPdf($inscricao, $campo);
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
        $bloqueadasPorPagamento = 0;

        foreach ($inscricoes as $inscricao) {
            if ($inscricao->temPagamentoEmAberto()) {
                $bloqueadasPorPagamento++;

                continue;
            }

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
            $mensagemErro = $bloqueadasPorPagamento > 0
                ? 'Nenhuma inscrição foi aprovada: há pagamento ou isenção pendente.'
                : 'Nenhuma disciplina pendente foi aprovada nas inscrições selecionadas.';

            return $redirect->withErrors([
                'aprovacao' => $mensagemErro,
            ]);
        }

        $mensagem = $inscricoesAtualizadas === 1
            ? '1 inscrição aprovada pela secretaria'
            : "{$inscricoesAtualizadas} inscrições aprovadas pela secretaria";

        $mensagem .= " ({$disciplinasAprovadas} disciplina(s) no total).";

        if ($semAlteracao > 0) {
            $mensagem .= " {$semAlteracao} inscrição(ões) selecionada(s) já estavam totalmente aprovadas.";
        }

        if ($bloqueadasPorPagamento > 0) {
            $mensagem .= " {$bloqueadasPorPagamento} inscrição(ões) ignorada(s) por pagamento ou isenção pendente.";
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

        if ($inscricao->temPagamentoEmAberto()) {
            return redirect()
                ->route('inscricoes.show', $inscricao)
                ->withErrors(['aprovacao' => 'Não é possível aprovar a inscrição enquanto houver pagamento ou isenção pendente.']);
        }

        $slot = $request->string('disciplina')->toString();

        if ($slot === '') {
            if ($inscricao->resumoAprovacaoSecretaria() === ResumoAprovacaoInscricao::Aprovada) {
                return redirect()
                    ->route('inscricoes.show', $inscricao)
                    ->with('info', 'Esta inscrição já está aprovada pela secretaria.');
            }

            $inscricao->aprovarPelaSecretaria();

            return redirect()
                ->route('inscricoes.show', $inscricao)
                ->with('success', 'Inscrição aprovada pela secretaria.');
        }

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
        ReprovarInscricaoSecretariaRequest $request,
        Inscricao $inscricao,
    ): RedirectResponse {
        if ($inscricao->etapa_concluida < 3) {
            return redirect()
                ->route('inscricoes.show', $inscricao)
                ->withErrors(['aprovacao' => 'A inscrição ainda não foi concluída pelo candidato.']);
        }

        $slot = $request->string('disciplina')->toString();
        $justificativa = $request->string('justificativa')->toString();

        if ($slot === '') {
            $jaReprovada = $inscricao->resumoAprovacaoSecretaria() === ResumoAprovacaoInscricao::Reprovada;

            $inscricao->reprovarPelaSecretaria($justificativa);

            return redirect()
                ->route('inscricoes.show', $inscricao)
                ->with('success', $jaReprovada
                    ? 'Reprovação atualizada pela secretaria.'
                    : 'Inscrição reprovada pela secretaria.');
        }

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

        $inscricao->marcarDisciplinaReprovadaPelaSecretaria($slot, $justificativa);

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
