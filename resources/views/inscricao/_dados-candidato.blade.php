@props([
    'inscricao',
    'downloadRoute' => 'inscricoes.download',
    'ocultarDocumentosIdentidade' => false,
])

@php
    $pdfsIdentidadeOcultos = [
        'pdf_cpf',
        'pdf_rg_rne_rnm',
        'pdf_passaporte',
        'pdf_visto_estudante_mercosul',
    ];
@endphp

@php
    $rotulos = [
        'tipo' => 'Tipo de cadastro (etapa 2)',
        'unidade' => 'Unidade USP',
        'pos_graduacao_externo' => 'Pós-graduação externa à USP',
        'nome_programa_externo' => 'Nome do programa externo',
        'formado_graduacao_usp' => 'Formado na graduação USP',
        'curso_usp_anterior' => 'Curso na USP anteriormente',
        'data_nascimento' => 'Data de nascimento',
        'genero' => 'Gênero',
        'nome_mae' => 'Nome da mãe',
        'cpf' => 'CPF',
        'rg_rne_rnm' => 'RG / RNE / RNM',
        'visto_estudante_mercosul' => 'Visto Estudante ou Mercosul (texto)',
        'orgao_expedidor' => 'Órgão expedidor',
        'estado_expedicao' => 'Estado de expedição',
        'data_expedicao' => 'Data de expedição',
        'pais_nascimento' => 'País de nascimento',
        'estado_nascimento' => 'Estado de nascimento',
        'municipio_provincia' => 'Município / Província',
        'nacionalidade' => 'Nacionalidade',
        'endereco_completo' => 'Endereço completo',
        'cep' => 'CEP',
        'telefone' => 'Telefone / Celular',
        'estrangeiro' => 'Estrangeiro',
    ];
    $pdfRotulos = [
        'pdf_comprovante_matricula' => 'Comprovante de matrícula',
        'pdf_historico_escolar' => 'Histórico escolar',
        'pdf_diploma_graduacao' => 'Diploma de graduação',
        'pdf_historico_graduacao' => 'Histórico da graduação',
        'pdf_rg_rne_rnm' => 'RG / RNM / RNE (PDF)',
        'pdf_cpf' => 'CPF (PDF)',
        'pdf_passaporte' => 'Passaporte',
        'pdf_visto_estudante_mercosul' => 'Visto Estudante ou Mercosul (PDF)',
    ];
@endphp

@php
    $disciplinasSelecionadas = [
        [
            'rotulo' => 'Disciplina obrigatória',
            'slot' => 'obrigatoria',
            'disciplina' => $inscricao->disciplinaObrigatoria,
            'justificativa' => $inscricao->justificativa_disciplina_obrigatoria,
        ],
        [
            'rotulo' => 'Opcional 1',
            'slot' => 'opcional_1',
            'disciplina' => $inscricao->disciplinaOpcional1,
            'justificativa' => $inscricao->justificativa_disciplina_opcional_1,
        ],
        [
            'rotulo' => 'Opcional 2',
            'slot' => 'opcional_2',
            'disciplina' => $inscricao->disciplinaOpcional2,
            'justificativa' => $inscricao->justificativa_disciplina_opcional_2,
        ],
    ];
    $pagamentos = is_array($inscricao->pagamentos_disciplinas) ? $inscricao->pagamentos_disciplinas : [];
@endphp

<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100 space-y-4">
        <h3 class="text-lg font-semibold border-b border-gray-200 dark:border-gray-600 pb-2">Etapa 1</h3>
        <dl class="grid grid-cols-1 gap-3 text-sm">
            <div><dt class="font-medium text-gray-600 dark:text-gray-400">Nome completo</dt><dd>{{ $inscricao->nome_completo }}</dd></div>
            <div><dt class="font-medium text-gray-600 dark:text-gray-400">E-mail</dt><dd>{{ $inscricao->email }}</dd></div>
            <div><dt class="font-medium text-gray-600 dark:text-gray-400">Aluno USP</dt><dd>{{ $inscricao->aluno_usp ? 'Sim' : 'Não' }}</dd></div>
            @if ($inscricao->aluno_usp && $inscricao->numero_usp)
                <div><dt class="font-medium text-gray-600 dark:text-gray-400">Número USP</dt><dd>{{ $inscricao->numero_usp }}</dd></div>
            @endif
        </dl>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100 space-y-4">
        <h3 class="text-lg font-semibold border-b border-gray-200 dark:border-gray-600 pb-2">Etapa 2</h3>
        @php $dados = $inscricao->dados_etapa_2; @endphp
        @if (empty($dados) || ! is_array($dados))
            <p class="text-sm text-gray-600 dark:text-gray-400">Nenhum dado complementar registrado.</p>
        @else
            <dl class="grid grid-cols-1 gap-3 text-sm">
                @foreach ($dados as $chave => $valor)
                    @if (str_starts_with($chave, 'pdf_'))
                        @continue
                    @endif
                    <div>
                        <dt class="font-medium text-gray-600 dark:text-gray-400">{{ $rotulos[$chave] ?? str_replace('_', ' ', $chave) }}</dt>
                        <dd>
                            @if ($chave === 'tipo')
                                @if ($valor === 'usp')
                                    Aluno USP
                                @elseif ($valor === 'nao_usp')
                                    Não aluno USP
                                @else
                                    {{ is_scalar($valor) ? $valor : json_encode($valor) }}
                                @endif
                            @elseif ($chave === 'estrangeiro')
                                {{ $valor ? 'Sim' : 'Não' }}
                            @elseif (in_array($chave, ['pos_graduacao_externo', 'formado_graduacao_usp', 'curso_usp_anterior'], true))
                                {{ $valor === 'sim' ? 'Sim' : ($valor === 'nao' ? 'Não' : $valor) }}
                            @else
                                {{ is_scalar($valor) ? $valor : json_encode($valor) }}
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
            @php
                $pdfsVisiveis = collect($dados)->filter(function ($valor, $chave) use ($ocultarDocumentosIdentidade, $pdfsIdentidadeOcultos) {
                    if (! str_starts_with($chave, 'pdf_') || ! is_string($valor) || $valor === '') {
                        return false;
                    }
                    if ($ocultarDocumentosIdentidade && in_array($chave, $pdfsIdentidadeOcultos, true)) {
                        return false;
                    }

                    return true;
                });
            @endphp
            @if ($pdfsVisiveis->isNotEmpty())
                <div class="pt-4 border-t border-gray-200 dark:border-gray-600 space-y-2">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Documentos (PDF)</p>
                    <ul class="list-disc pl-5 space-y-1 text-sm">
                        @foreach ($pdfsVisiveis as $chave => $valor)
                            <li>
                                <a href="{{ route($downloadRoute, [$inscricao, $chave]) }}" class="text-blue-700 dark:text-blue-400 hover:underline">
                                    {{ $pdfRotulos[$chave] ?? $chave }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif
    </div>
</div>

<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 lg:p-8 text-gray-900 dark:text-gray-100 space-y-4">
        <h3 class="text-lg font-semibold border-b border-gray-200 dark:border-gray-600 pb-2">Período e disciplinas</h3>
        <dl class="grid grid-cols-1 gap-3 text-sm">
            <div>
                <dt class="font-medium text-gray-600 dark:text-gray-400">Secretaria</dt>
                <dd class="mt-1">
                    <x-inscricao-resumo-etapa-badge :resumo="$inscricao->resumoAprovacaoSecretaria()" etapa="secretaria" />
                </dd>
            </div>
            <div>
                <dt class="font-medium text-gray-600 dark:text-gray-400">Professor</dt>
                <dd class="mt-1">
                    <x-inscricao-resumo-etapa-badge :resumo="$inscricao->resumoAprovacaoProfessor()" etapa="professor" />
                </dd>
            </div>
            <div>
                <dt class="font-medium text-gray-600 dark:text-gray-400">Período</dt>
                <dd>{{ $inscricao->periodo?->ano }}/{{ $inscricao->periodo?->semestre }}</dd>
            </div>
            @foreach ($disciplinasSelecionadas as $item)
                @if ($item['disciplina'])
                    <div>
                        <dt class="font-medium text-gray-600 dark:text-gray-400">{{ $item['rotulo'] }}</dt>
                        <dd class="space-y-2">
                            <div>{{ $item['disciplina']->codigo_completo }} — {{ $item['disciplina']->nome }}</div>
                            <div>
                                <span class="font-medium text-gray-600 dark:text-gray-400">Justificativa:</span>
                                <p class="mt-1 whitespace-pre-line">{{ $item['justificativa'] ?: '—' }}</p>
                            </div>
                            @if (isset($pagamentos[$item['slot']]))
                                <div>
                                    <span class="font-medium text-gray-600 dark:text-gray-400">Pagamento:</span>
                                    {{ ($pagamentos[$item['slot']] ?? '') === 'pago' ? 'Pago' : 'Não pago' }}
                                </div>
                            @endif
                        </dd>
                    </div>
                @endif
            @endforeach
            @if ($inscricao->pix_valor !== null)
                <div>
                    <dt class="font-medium text-gray-600 dark:text-gray-400">Pagamento Pix</dt>
                    <dd class="space-y-1">
                        <div>Valor: R$ {{ number_format((float) $inscricao->pix_valor, 2, ',', '.') }}
                            @if ($inscricao->pix_quantidade_disciplinas)
                                ({{ $inscricao->pix_quantidade_disciplinas }} disciplina(s))
                            @endif
                        </div>
                        <div>Status: {{ $inscricao->pix_status ?? '—' }}</div>
                        @if ($inscricao->pix_pago_em)
                            <div>Pago em: {{ $inscricao->pix_pago_em->format('d/m/Y H:i') }}</div>
                        @endif
                    </dd>
                </div>
            @endif
            <div>
                <dt class="font-medium text-gray-600 dark:text-gray-400">Concluída em</dt>
                <dd>{{ optional($inscricao->concluido_em)->format('d/m/Y H:i') ?? '—' }}</dd>
            </div>
        </dl>
    </div>
</div>
