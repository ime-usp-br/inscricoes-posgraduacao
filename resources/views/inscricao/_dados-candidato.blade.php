@props([
    'inscricao',
    'downloadRoute' => 'inscricoes.download',
])

@php
    $rotulos = [
        'tipo' => 'Tipo de cadastro (etapa 2)',
        'unidade' => 'Unidade USP',
        'pos_graduacao_externo' => 'Pós-graduação externa à USP',
        'nome_programa_externo' => 'Nome do programa externo',
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
                            @elseif (in_array($chave, ['pos_graduacao_externo', 'curso_usp_anterior'], true))
                                {{ $valor === 'sim' ? 'Sim' : ($valor === 'nao' ? 'Não' : $valor) }}
                            @else
                                {{ is_scalar($valor) ? $valor : json_encode($valor) }}
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
            <div class="pt-4 border-t border-gray-200 dark:border-gray-600 space-y-2">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Documentos (PDF)</p>
                <ul class="list-disc pl-5 space-y-1 text-sm">
                    @foreach ($dados as $chave => $valor)
                        @if (! str_starts_with($chave, 'pdf_') || ! is_string($valor) || $valor === '')
                            @continue
                        @endif
                        <li>
                            <a href="{{ route($downloadRoute, [$inscricao, $chave]) }}" class="text-blue-700 dark:text-blue-400 hover:underline">
                                {{ $pdfRotulos[$chave] ?? $chave }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
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
            @if ($inscricao->disciplinaObrigatoria)
                <div>
                    <dt class="font-medium text-gray-600 dark:text-gray-400">Disciplina obrigatória</dt>
                    <dd>{{ $inscricao->disciplinaObrigatoria->codigo_completo }} — {{ $inscricao->disciplinaObrigatoria->nome }}</dd>
                </div>
            @endif
            @if ($inscricao->disciplinaOpcional1)
                <div>
                    <dt class="font-medium text-gray-600 dark:text-gray-400">Opcional 1</dt>
                    <dd>{{ $inscricao->disciplinaOpcional1->codigo_completo }} — {{ $inscricao->disciplinaOpcional1->nome }}</dd>
                </div>
            @endif
            @if ($inscricao->disciplinaOpcional2)
                <div>
                    <dt class="font-medium text-gray-600 dark:text-gray-400">Opcional 2</dt>
                    <dd>{{ $inscricao->disciplinaOpcional2->codigo_completo }} — {{ $inscricao->disciplinaOpcional2->nome }}</dd>
                </div>
            @endif
            <div>
                <dt class="font-medium text-gray-600 dark:text-gray-400">Concluída em</dt>
                <dd>{{ optional($inscricao->concluido_em)->format('d/m/Y H:i') ?? '—' }}</dd>
            </div>
        </dl>
    </div>
</div>
